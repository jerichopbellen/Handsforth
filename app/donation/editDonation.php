<?php
ob_start();
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

$donation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$donation_id) {
    $_SESSION['error'] = 'Invalid donation ID';
    header('Location: index.php');
    exit();
}

$donors = $pdo->query("SELECT donor_id, name, email FROM donors ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT * FROM donations WHERE donation_id = ?');
$stmt->execute([$donation_id]);
$donation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$donation) {
    $_SESSION['error'] = 'Donation not found';
    header('Location: index.php');
    exit();
}

function normalize_goods_items_for_update(array $items) {
    $normalized = [];

    foreach ($items as $item) {
        $item_id = isset($item['item_id']) ? (int)$item['item_id'] : 0;
        $category = trim((string)($item['category'] ?? ''));
        $description = trim((string)($item['description'] ?? ''));
        $condition = trim((string)($item['condition'] ?? ''));
        $quantity_raw = $item['quantity'] ?? '';
        $unit = trim((string)($item['unit'] ?? ''));
        $estimated_raw = $item['estimated_value'] ?? '';

        $is_empty_row = ($category === '')
            && ($description === '')
            && ($condition === '')
            && ($unit === '')
            && ($quantity_raw === '' || $quantity_raw === null)
            && ($estimated_raw === '' || $estimated_raw === null);

        if ($is_empty_row) {
            continue;
        }

        $quantity = (int)$quantity_raw;
        if ($description === '' || $quantity <= 0 || $condition === '') {
            throw new Exception('Each goods item must include item name, quantity, and condition.');
        }

        $estimated_value = null;
        if ($estimated_raw !== '' && $estimated_raw !== null) {
            $estimated_value = (float)$estimated_raw;
        }

        $normalized[] = [
            'item_id' => $item_id,
            'category' => $category !== '' ? $category : null,
            'description' => $description,
            'item_condition' => $condition,
            'quantity' => $quantity,
            'unit' => $unit !== '' ? $unit : null,
            'estimated_value' => $estimated_value,
        ];
    }

    return $normalized;
}

$items_stmt = $pdo->prepare('SELECT item_id, category, description, item_condition, quantity, unit, estimated_value FROM donation_items WHERE donation_id = ? ORDER BY item_id ASC');
$items_stmt->execute([$donation_id]);
$existing_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

$distributed_qty_by_item = [];
$distribution_count_stmt = $pdo->prepare('SELECT COUNT(*) FROM distributions WHERE donation_id = ?');
$distribution_count_stmt->execute([$donation_id]);
$existing_distribution_count = (int)$distribution_count_stmt->fetchColumn();

$dist_notes_stmt = $pdo->prepare('SELECT notes FROM distributions WHERE donation_id = ? AND notes IS NOT NULL');
$dist_notes_stmt->execute([$donation_id]);
$distribution_note_rows = $dist_notes_stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($distribution_note_rows as $dist_note_row) {
    $notes = json_decode((string)($dist_note_row['notes'] ?? ''), true);
    if (!is_array($notes) || ($notes['type'] ?? '') !== 'goods_distribution') {
        continue;
    }

    $items = $notes['items'] ?? [];
    if (!is_array($items)) {
        continue;
    }

    foreach ($items as $distributed_item) {
        $item_id = isset($distributed_item['item_id']) ? (int)$distributed_item['item_id'] : 0;
        $quantity = isset($distributed_item['quantity']) ? (int)$distributed_item['quantity'] : 0;
        if ($item_id <= 0 || $quantity <= 0) {
            continue;
        }

        $distributed_qty_by_item[$item_id] = ($distributed_qty_by_item[$item_id] ?? 0) + $quantity;
    }
}

$monetary_stmt = $pdo->prepare('SELECT id, amount, payment_method, check_number, designation, recurring FROM monetary_details WHERE donation_id = ? LIMIT 1');
$monetary_stmt->execute([$donation_id]);
$existing_monetary = $monetary_stmt->fetch(PDO::FETCH_ASSOC);

if (!$existing_monetary) {
    $existing_monetary = [
        'id' => null,
        'amount' => $donation['amount'] ?? null,
        'payment_method' => '',
        'check_number' => '',
        'designation' => '',
        'recurring' => 0,
    ];
}

if (isset($_POST['submit'])) {
    $donor_id = isset($_POST['donor_id']) ? (int)$_POST['donor_id'] : (int)($donation['donor_id'] ?? 0);
    $new_donor_name = trim($_POST['new_donor_name'] ?? '');
    $new_donor_email = trim($_POST['new_donor_email'] ?? '');
    $new_donor_phone = trim($_POST['new_donor_phone'] ?? '');

    if ($donor_id <= 0 && $new_donor_name === '') {
        $_SESSION['error'] = 'Donor selection or creation required';
        header('Location: editDonation.php?id=' . $donation_id);
        exit();
    }

    $donation_type = $_POST['donation_type'] ?? $donation['donation_type'];
    if (!in_array($donation_type, ['goods', 'funds'], true)) {
        $donation_type = $donation['donation_type'];
    }

    if ($existing_distribution_count > 0 && $donation_type !== $donation['donation_type']) {
        $_SESSION['error'] = 'Cannot change donation type after distributions have been recorded.';
        header('Location: editDonation.php?id=' . $donation_id);
        exit();
    }

    $date_received = trim($_POST['date_received'] ?? '');
    if ($date_received === '') {
        $date_received = $donation['date_received'];
    }

    try {
        $pdo->beginTransaction();

        if ($donor_id <= 0) {
            $insert_donor_stmt = $pdo->prepare('INSERT INTO donors (name, email, phone) VALUES (?, ?, ?)');
            $insert_donor_stmt->execute([$new_donor_name, $new_donor_email ?: null, $new_donor_phone ?: null]);
            $donor_id = (int)$pdo->lastInsertId();
        }

        $description_goods = trim($_POST['description_goods'] ?? '');
        $description_funds = trim($_POST['description_funds'] ?? '');
        $description = $donation_type === 'goods' ? $description_goods : $description_funds;
        if ($description === '') {
            $description = (string)($donation['description'] ?? '');
        }

        $donation_amount = null;

        if ($donation_type === 'goods') {
            $submitted_items = normalize_goods_items_for_update($_POST['items'] ?? []);
            if (empty($submitted_items)) {
                if (!empty($existing_items)) {
                    foreach ($existing_items as $item) {
                        $submitted_items[] = [
                            'item_id' => (int)$item['item_id'],
                            'category' => $item['category'] ?: null,
                            'description' => $item['description'],
                            'item_condition' => $item['item_condition'],
                            'quantity' => (int)$item['quantity'],
                            'unit' => $item['unit'] ?: null,
                            'estimated_value' => $item['estimated_value'] !== null ? (float)$item['estimated_value'] : null,
                        ];
                    }
                } else {
                    throw new Exception('At least one goods item is required.');
                }
            }

            $pdo->prepare('DELETE FROM monetary_details WHERE donation_id = ?')->execute([$donation_id]);

            $existing_item_ids = array_map('intval', array_column($existing_items, 'item_id'));
            $retained_item_ids = [];

            $item_update_stmt = $pdo->prepare(
                'UPDATE donation_items SET category = ?, description = ?, item_condition = ?, quantity = ?, unit = ?, estimated_value = ? WHERE donation_id = ? AND item_id = ?'
            );
            $item_insert_stmt = $pdo->prepare(
                'INSERT INTO donation_items (donation_id, category, description, item_condition, quantity, unit, estimated_value) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );

            foreach ($submitted_items as $item) {
                $item_id = (int)($item['item_id'] ?? 0);

                if ($item_id > 0) {
                    $already_distributed_qty = (int)($distributed_qty_by_item[$item_id] ?? 0);
                    if ($item['quantity'] < $already_distributed_qty) {
                        throw new Exception('Quantity for "' . $item['description'] . '" cannot be less than already distributed quantity (' . $already_distributed_qty . ').');
                    }
                }

                if ($item_id > 0 && in_array($item_id, $existing_item_ids, true)) {
                    $item_update_stmt->execute([
                        $item['category'],
                        $item['description'],
                        $item['item_condition'],
                        $item['quantity'],
                        $item['unit'],
                        $item['estimated_value'],
                        $donation_id,
                        $item_id,
                    ]);
                    $retained_item_ids[] = $item_id;
                    continue;
                }

                $item_insert_stmt->execute([
                    $donation_id,
                    $item['category'],
                    $item['description'],
                    $item['item_condition'],
                    $item['quantity'],
                    $item['unit'],
                    $item['estimated_value'],
                ]);
            }

            $removed_item_ids = array_diff($existing_item_ids, $retained_item_ids);
            if (!empty($removed_item_ids)) {
                $item_delete_stmt = $pdo->prepare('DELETE FROM donation_items WHERE donation_id = ? AND item_id = ?');
                foreach ($removed_item_ids as $removed_item_id) {
                    $already_distributed_qty = (int)($distributed_qty_by_item[$removed_item_id] ?? 0);
                    if ($already_distributed_qty > 0) {
                        throw new Exception('Cannot remove an item that already has distributed quantity recorded.');
                    }
                    $item_delete_stmt->execute([$donation_id, $removed_item_id]);
                }
            }
        } else {
            $amount_raw = trim((string)($_POST['amount'] ?? ''));
            $payment_method_raw = trim((string)($_POST['payment_method'] ?? ''));
            $reference_raw = trim((string)($_POST['check_number'] ?? ''));
            $designation_raw = trim((string)($_POST['designation'] ?? ''));

            $amount = $amount_raw !== ''
                ? (float)$amount_raw
                : (float)($existing_monetary['amount'] ?? $donation['amount'] ?? 0);

            $payment_method = $payment_method_raw !== ''
                ? $payment_method_raw
                : (string)($existing_monetary['payment_method'] ?? '');

            $reference_number = $reference_raw !== ''
                ? $reference_raw
                : (string)($existing_monetary['check_number'] ?? '');

            $designation = $designation_raw !== ''
                ? $designation_raw
                : (string)($existing_monetary['designation'] ?? '');

            $recurring = isset($_POST['recurring']) ? 1 : 0;

            if ($amount <= 0 || $payment_method === '') {
                throw new Exception('Amount and payment method are required for funds donations.');
            }

            $funds_distribution_stmt = $pdo->prepare('SELECT COALESCE(SUM(distributed_amount), 0) FROM distributions WHERE donation_id = ?');
            $funds_distribution_stmt->execute([$donation_id]);
            $already_distributed_funds = (float)$funds_distribution_stmt->fetchColumn();
            if ($amount + 0.00001 < $already_distributed_funds) {
                throw new Exception('Amount cannot be less than already distributed funds ($' . number_format($already_distributed_funds, 2) . ').');
            }

            $donation_amount = $amount;

            $pdo->prepare('DELETE FROM donation_items WHERE donation_id = ?')->execute([$donation_id]);

            $pdo->prepare('DELETE FROM monetary_details WHERE donation_id = ?')->execute([$donation_id]);
            $insert_monetary_stmt = $pdo->prepare(
                'INSERT INTO monetary_details (donation_id, amount, payment_method, check_number, designation, recurring) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $insert_monetary_stmt->execute([
                $donation_id,
                $amount,
                $payment_method,
                $reference_number !== '' ? $reference_number : null,
                $designation !== '' ? $designation : null,
                $recurring,
            ]);
        }

        $update_donation_stmt = $pdo->prepare(
            'UPDATE donations SET donor_id = ?, donation_type = ?, amount = ?, description = ?, date_received = ? WHERE donation_id = ?'
        );
        $update_donation_stmt->execute([
            $donor_id ?: null,
            $donation_type,
            $donation_amount,
            $description !== '' ? $description : null,
            $date_received,
            $donation_id,
        ]);

        $pdo->commit();
        $_SESSION['success'] = 'Donation updated successfully';
        header('Location: index.php');
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $_SESSION['error'] = 'Failed to update donation: ' . $e->getMessage();
        header('Location: editDonation.php?id=' . $donation_id);
        exit();
    }
}
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-white">
                    <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Donation</h4>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $donation_id); ?>" method="POST">
                        <div class="mb-3">
                            <label for="donor_id" class="form-label">Donor <span class="text-danger">*</span></label>
                            <select class="form-select" id="donor_id" name="donor_id">
                                <option value="">Select Existing Donor</option>
                                <?php foreach ($donors as $donor): ?>
                                    <option value="<?php echo $donor['donor_id']; ?>" <?php echo ((int)$donation['donor_id'] === (int)$donor['donor_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($donor['name']) . ' (' . htmlspecialchars($donor['email']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Or Create New Donor</label>
                            <input type="text" class="form-control mb-2" name="new_donor_name" placeholder="Name">
                            <input type="email" class="form-control mb-2" name="new_donor_email" placeholder="Email">
                            <input type="text" class="form-control mb-2" name="new_donor_phone" placeholder="Phone">
                        </div>

                        <div class="mb-3">
                            <label for="donation_type" class="form-label">Donation Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="donation_type" name="donation_type" required>
                                <option value="funds" <?php echo $donation['donation_type'] === 'funds' ? 'selected' : ''; ?>>Monetary</option>
                                <option value="goods" <?php echo $donation['donation_type'] === 'goods' ? 'selected' : ''; ?>>In-Kind (Items)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="date_received" class="form-label">Donation Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_received" name="date_received" required value="<?php echo htmlspecialchars($donation['date_received']); ?>">
                        </div>

                        <div id="goods_fields" style="display:none;">
                            <h5>In-Kind Goods Items</h5>
                            <div id="items_container">
                                <?php foreach ($existing_items as $idx => $item): ?>
                                    <div class="row mb-2 align-items-end goods-item-row">
                                        <input type="hidden" name="items[<?php echo $idx; ?>][item_id]" value="<?php echo (int)$item['item_id']; ?>">
                                        <div class="col-md-2">
                                            <label class="form-label">Category</label>
                                            <select name="items[<?php echo $idx; ?>][category]" class="form-select">
                                                <option value="">Category</option>
                                                <option value="food" <?php echo $item['category'] === 'food' ? 'selected' : ''; ?>>Food/Perishables</option>
                                                <option value="clothing" <?php echo $item['category'] === 'clothing' ? 'selected' : ''; ?>>Clothing</option>
                                                <option value="electronics" <?php echo $item['category'] === 'electronics' ? 'selected' : ''; ?>>Electronics</option>
                                                <option value="furniture" <?php echo $item['category'] === 'furniture' ? 'selected' : ''; ?>>Furniture</option>
                                                <option value="medical" <?php echo $item['category'] === 'medical' ? 'selected' : ''; ?>>Medical Supplies</option>
                                                <option value="hygiene" <?php echo $item['category'] === 'hygiene' ? 'selected' : ''; ?>>Hygiene Products</option>
                                                <option value="toys" <?php echo $item['category'] === 'toys' ? 'selected' : ''; ?>>Toys/Educational</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Item Name</label>
                                            <input type="text" name="items[<?php echo $idx; ?>][description]" class="form-control" value="<?php echo htmlspecialchars($item['description']); ?>" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Condition</label>
                                            <select name="items[<?php echo $idx; ?>][condition]" class="form-select" required>
                                                <option value="">Select</option>
                                                <option value="new" <?php echo $item['item_condition'] === 'new' ? 'selected' : ''; ?>>New</option>
                                                <option value="like_new" <?php echo $item['item_condition'] === 'like_new' ? 'selected' : ''; ?>>Like New</option>
                                                <option value="gently_used" <?php echo $item['item_condition'] === 'gently_used' ? 'selected' : ''; ?>>Gently Used</option>
                                            </select>
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label">Qty</label>
                                            <input type="number" name="items[<?php echo $idx; ?>][quantity]" class="form-control" min="1" value="<?php echo (int)$item['quantity']; ?>" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Unit</label>
                                            <input type="text" name="items[<?php echo $idx; ?>][unit]" class="form-control" value="<?php echo htmlspecialchars($item['unit'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label">Value</label>
                                            <input type="number" name="items[<?php echo $idx; ?>][estimated_value]" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($item['estimated_value'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.goods-item-row').remove()">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-outline-secondary mb-3" onclick="addItemRow()">Add Another Item</button>

                            <div class="mb-3 mt-3">
                                <label for="description_goods" class="form-label">Description</label>
                                <textarea class="form-control" id="description_goods" name="description_goods" rows="2" placeholder="Describe the goods donation purpose or notes"><?php echo htmlspecialchars($donation['description'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div id="monetary_fields" style="display:none;">
                            <h5>Monetary Donation Details</h5>
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount ($)</label>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" value="<?php echo htmlspecialchars($existing_monetary['amount'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_method" name="payment_method">
                                    <option value="">Select Method</option>
                                    <option value="cash" <?php echo ($existing_monetary['payment_method'] ?? '') === 'cash' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="check" <?php echo ($existing_monetary['payment_method'] ?? '') === 'check' ? 'selected' : ''; ?>>Check</option>
                                    <option value="credit_card" <?php echo ($existing_monetary['payment_method'] ?? '') === 'credit_card' ? 'selected' : ''; ?>>Credit Card</option>
                                    <option value="bank_transfer" <?php echo ($existing_monetary['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                    <option value="paypal" <?php echo ($existing_monetary['payment_method'] ?? '') === 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                                    <option value="venmo" <?php echo ($existing_monetary['payment_method'] ?? '') === 'venmo' ? 'selected' : ''; ?>>Venmo</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="check_number" class="form-label">Reference Number / Check Number</label>
                                <input type="text" class="form-control" id="check_number" name="check_number" value="<?php echo htmlspecialchars($existing_monetary['check_number'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="designation" class="form-label">Fund Designation</label>
                                <input type="text" class="form-control" id="designation" name="designation" value="<?php echo htmlspecialchars($existing_monetary['designation'] ?? ''); ?>" placeholder="e.g. Building Fund, General Operations">
                            </div>
                            <div class="mb-3">
                                <label for="description_funds" class="form-label">Description</label>
                                <textarea class="form-control" id="description_funds" name="description_funds" rows="2" placeholder="Describe the donation purpose or notes"><?php echo htmlspecialchars($donation['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="recurring" name="recurring" <?php echo !empty($existing_monetary['recurring']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="recurring">Recurring Monthly Gift</label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="submit" class="btn btn-warning"><i class="bi bi-check-circle me-1"></i>Update Donation</button>
                            <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleFields() {
    const type = document.getElementById('donation_type').value;
    const goodsSection = document.getElementById('goods_fields');
    const monetarySection = document.getElementById('monetary_fields');

    goodsSection.style.display = type === 'goods' ? 'block' : 'none';
    monetarySection.style.display = type === 'funds' ? 'block' : 'none';

    goodsSection.querySelectorAll('input, select, textarea').forEach((field) => {
        field.disabled = type !== 'goods';
    });
    monetarySection.querySelectorAll('input, select, textarea').forEach((field) => {
        field.disabled = type !== 'funds';
    });

    if (type === 'goods') {
        const itemRows = document.querySelectorAll('#items_container .goods-item-row');
        if (itemRows.length === 0) {
            addItemRow();
        }
    }
}

let itemIdx = <?php echo count($existing_items); ?>;

function addItemRow() {
    const container = document.getElementById('items_container');
    const idx = itemIdx++;

    const row = document.createElement('div');
    row.className = 'row mb-2 align-items-end goods-item-row';
    row.innerHTML = `
        <div class="col-md-2">
            <label class="form-label">Category</label>
            <select name="items[${idx}][category]" class="form-select">
                <option value="">Category</option>
                <option value="food">Food/Perishables</option>
                <option value="clothing">Clothing</option>
                <option value="electronics">Electronics</option>
                <option value="furniture">Furniture</option>
                <option value="medical">Medical Supplies</option>
                <option value="hygiene">Hygiene Products</option>
                <option value="toys">Toys/Educational</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Item Name</label>
            <input type="text" name="items[${idx}][description]" class="form-control" placeholder="Item name" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Condition</label>
            <select name="items[${idx}][condition]" class="form-select" required>
                <option value="">Select</option>
                <option value="new">New</option>
                <option value="like_new">Like New</option>
                <option value="gently_used">Gently Used</option>
            </select>
        </div>
        <div class="col-md-1">
            <label class="form-label">Qty</label>
            <input type="number" name="items[${idx}][quantity]" class="form-control" min="1" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Unit</label>
            <input type="text" name="items[${idx}][unit]" class="form-control" placeholder="Unit">
        </div>
        <div class="col-md-1">
            <label class="form-label">Value</label>
            <input type="number" name="items[${idx}][estimated_value]" class="form-control" step="0.01" min="0">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.goods-item-row').remove()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    `;

    container.appendChild(row);
}

document.getElementById('donation_type').addEventListener('change', toggleFields);
toggleFields();
</script>

<?php include("../../includes/footer.php"); ?>

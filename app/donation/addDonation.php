<?php
ob_start();
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

$staff_id = (int)$_SESSION['user_id'];
$donors = $pdo->query("SELECT donor_id, name, email FROM donors ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

function generate_txn_number() {
    return 'TXN-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));
}

function normalize_goods_items(array $items) {
    $normalized = [];

    foreach ($items as $item) {
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

if (isset($_POST['submit'])) {
    $donation_type = $_POST['donation_type'] ?? '';
    $date_received = $_POST['date_received'] ?? '';
    $donor_id = isset($_POST['donor_id']) ? (int)$_POST['donor_id'] : 0;
    $new_donor_name = trim($_POST['new_donor_name'] ?? '');
    $new_donor_email = trim($_POST['new_donor_email'] ?? '');
    $new_donor_phone = trim($_POST['new_donor_phone'] ?? '');

    if (!in_array($donation_type, ['funds', 'goods'], true) || empty($date_received)) {
        $_SESSION['error'] = 'Donation type and date are required';
        header('Location: addDonation.php');
        exit();
    }

    if ($donor_id <= 0 && $new_donor_name === '') {
        $_SESSION['error'] = 'Donor selection or creation required';
        header('Location: addDonation.php');
        exit();
    }

    $description_funds = trim($_POST['description_funds'] ?? '');
    $description_goods = trim($_POST['description_goods'] ?? '');

    $amount = null;
    $payment_method = '';
    $reference_number = '';
    $designation = '';
    $recurring = 0;
    $goods_items = [];

    try {
        if ($donation_type === 'funds') {
            $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
            $payment_method = trim($_POST['payment_method'] ?? '');
            $reference_number = trim($_POST['check_number'] ?? '');
            $designation = trim($_POST['designation'] ?? '');
            $recurring = isset($_POST['recurring']) ? 1 : 0;

            if ($amount <= 0 || $payment_method === '') {
                throw new Exception('Amount and payment method are required for funds donations.');
            }
        } else {
            $goods_items = normalize_goods_items($_POST['items'] ?? []);
            if (empty($goods_items)) {
                throw new Exception('At least one goods item is required.');
            }
        }

        $pdo->beginTransaction();

        if ($donor_id <= 0) {
            $stmt = $pdo->prepare('INSERT INTO donors (name, email, phone) VALUES (?, ?, ?)');
            $stmt->execute([$new_donor_name, $new_donor_email ?: null, $new_donor_phone ?: null]);
            $donor_id = (int)$pdo->lastInsertId();
        }

        $txn_number = generate_txn_number();
        $header_amount = $donation_type === 'funds' ? $amount : null;
        $header_description = $donation_type === 'funds' ? $description_funds : $description_goods;
        $header_description = $header_description !== '' ? $header_description : null;

        $stmt = $pdo->prepare(
            'INSERT INTO donations (donor_id, staff_id, txn_number, donation_type, amount, description, date_received) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$donor_id ?: null, $staff_id, $txn_number, $donation_type, $header_amount, $header_description, $date_received]);
        $donation_id = (int)$pdo->lastInsertId();

        $receipt_path = '';
        if (!empty($_FILES['receipt']['name'])) {
            $target_dir = '../../uploads/';
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $target_file = $target_dir . uniqid('receipt_', true) . '_' . basename($_FILES['receipt']['name']);
            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $target_file)) {
                $receipt_path = $target_file;
            }
        }

        if ($receipt_path !== '') {
            $stmt = $pdo->prepare('UPDATE donations SET receipt_file = ? WHERE donation_id = ?');
            $stmt->execute([$receipt_path, $donation_id]);
        }

        if ($donation_type === 'funds') {
            $stmt = $pdo->prepare(
                'INSERT INTO monetary_details (donation_id, amount, payment_method, check_number, designation, recurring) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $donation_id,
                $amount,
                $payment_method,
                $reference_number !== '' ? $reference_number : null,
                $designation !== '' ? $designation : null,
                $recurring,
            ]);
        } else {
            $item_stmt = $pdo->prepare(
                'INSERT INTO donation_items (donation_id, category, description, item_condition, quantity, unit, estimated_value) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );

            foreach ($goods_items as $item) {
                $item_stmt->execute([
                    $donation_id,
                    $item['category'],
                    $item['description'],
                    $item['item_condition'],
                    $item['quantity'],
                    $item['unit'],
                    $item['estimated_value'],
                ]);
            }
        }

        $pdo->commit();
        $_SESSION['success'] = 'Donation added successfully';
        header('Location: index.php');
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $_SESSION['error'] = 'Failed to add donation: ' . $e->getMessage();
        header('Location: addDonation.php');
        exit();
    }
}
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Add New Donation</h4>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="donor_id" class="form-label">Donor <span class="text-danger">*</span></label>
                            <select class="form-select" id="donor_id" name="donor_id">
                                <option value="">Select Existing Donor</option>
                                <?php foreach ($donors as $donor): ?>
                                    <option value="<?php echo $donor['donor_id']; ?>">
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
                            <select class="form-select" id="donation_type" name="donation_type" required onchange="toggleFields()">
                                <option value="">Select Type</option>
                                <option value="funds">Monetary</option>
                                <option value="goods">In-Kind (Items)</option>
                            </select>
                        </div>

                        <div id="monetary_fields" style="display:none;">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount ($)</label>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0">
                            </div>
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_method" name="payment_method">
                                    <option value="">Select Method</option>
                                    <option value="cash">Cash</option>
                                    <option value="check">Check</option>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="paypal">PayPal</option>
                                    <option value="venmo">Venmo</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="check_number" class="form-label">Reference Number / Check Number</label>
                                <input type="text" class="form-control" id="check_number" name="check_number">
                            </div>
                            <div class="mb-3">
                                <label for="designation" class="form-label">Fund Designation</label>
                                <input type="text" class="form-control" id="designation" name="designation" placeholder="e.g. Building Fund, General Operations, Emergency Relief">
                            </div>
                            <div class="mb-3">
                                <label for="description_funds" class="form-label">Description</label>
                                <textarea class="form-control" id="description_funds" name="description_funds" rows="2" placeholder="Describe the donation purpose or notes"></textarea>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="recurring" name="recurring">
                                <label class="form-check-label" for="recurring">Recurring Monthly Gift</label>
                            </div>
                        </div>

                        <div id="inkind_fields" style="display:none;">
                            <label class="form-label">Items Donated</label>
                            <div id="item_rows"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addItemRow()">Add Another Item</button>
                            <div class="mb-3 mt-3">
                                <label for="description_goods" class="form-label">Description</label>
                                <textarea class="form-control" id="description_goods" name="description_goods" rows="2" placeholder="Describe the goods donation purpose or notes"></textarea>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="receipt" class="form-label">Upload Receipt (optional)</label>
                            <input type="file" class="form-control" id="receipt" name="receipt" accept=".pdf,.jpg,.jpeg,.png">
                        </div>

                        <div class="mb-3">
                            <label for="date_received" class="form-label">Date Received <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_received" name="date_received" required value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i>Save Donation</button>
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
    const monetary = document.getElementById('monetary_fields');
    const goods = document.getElementById('inkind_fields');

    monetary.style.display = type === 'funds' ? 'block' : 'none';
    goods.style.display = type === 'goods' ? 'block' : 'none';

    monetary.querySelectorAll('input, select, textarea').forEach((field) => {
        field.disabled = type !== 'funds';
    });
    goods.querySelectorAll('input, select, textarea').forEach((field) => {
        field.disabled = type !== 'goods';
    });

    if (type === 'goods') {
        const itemRows = document.getElementById('item_rows');
        if (itemRows.children.length === 0) {
            addItemRow();
        }
    }
}

let itemIdx = 0;
function addItemRow() {
    const container = document.getElementById('item_rows');
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

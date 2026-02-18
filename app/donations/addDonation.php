<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

$staff_id = $_SESSION['user_id'];

// Donor lookup
$donors = $pdo->query("SELECT donor_id, name, email FROM donors ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

function generate_txn_number() {
    return 'TXN-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));
}

if (isset($_POST['submit'])) {
    $donation_type = $_POST['donation_type'] ?? '';
    $date_received = $_POST['date_received'] ?? '';
    $donor_id = $_POST['donor_id'] ?? '';
    $new_donor_name = trim($_POST['new_donor_name'] ?? '');
    $new_donor_email = trim($_POST['new_donor_email'] ?? '');
    $new_donor_phone = trim($_POST['new_donor_phone'] ?? '');
    $txn_number = generate_txn_number();

    if (empty($donation_type) || empty($date_received)) {
        $_SESSION['error'] = 'Donation type and date are required';
        header("Location: addDonation.php");
        exit();
    }

    if (!$donor_id && !$new_donor_name) {
        $_SESSION['error'] = 'Donor selection or creation required';
        header("Location: addDonation.php");
        exit();
    }

    try {
        $pdo->beginTransaction();
        // Donor creation
        if (!$donor_id) {
            $stmt = $pdo->prepare("INSERT INTO donors (name, email, phone) VALUES (?, ?, ?)");
            $stmt->execute([$new_donor_name, $new_donor_email, $new_donor_phone]);
            $donor_id = $pdo->lastInsertId();
        }

        // Header
        $donation_type_db = $donation_type === 'funds' ? 'monetary' : 'in-kind';
        $total_value = 0;
        $stmt = $pdo->prepare("INSERT INTO donations (donor_id, staff_id, txn_number, donation_type, total_value, date_received) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$donor_id, $staff_id, $txn_number, $donation_type_db, $total_value, $date_received]);
        $donation_id = $pdo->lastInsertId();

        if ($donation_type === 'goods') {
            // In-kind items
            $items = $_POST['items'] ?? [];
            $item_total = 0;
            foreach ($items as $item) {
                $stmt = $pdo->prepare("INSERT INTO donation_items (donation_id, category, item_condition, quantity, unit, estimated_value, value_source, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $photo = '';
                if (!empty($_FILES['item_photos']['name'][$item['idx']])) {
                    $tmp = $_FILES['item_photos']['tmp_name'][$item['idx']];
                    $name = basename($_FILES['item_photos']['name'][$item['idx']]);
                    $target = '../../assets/uploads/' . uniqid() . '_' . $name;
                    if (move_uploaded_file($tmp, $target)) {
                        $photo = $target;
                    }
                }
                $stmt->execute([
                    $donation_id,
                    $item['category'],
                    $item['condition'],
                    $item['quantity'],
                    $item['unit'],
                    $item['estimated_value'],
                    $item['value_source'],
                    $photo
                ]);
                $item_total += floatval($item['estimated_value']);
            }
            // Update total value
            $pdo->prepare("UPDATE donations SET total_value = ? WHERE donation_id = ?")->execute([$item_total, $donation_id]);
        } else {
            // Monetary
            $amount = floatval($_POST['amount'] ?? 0);
            $payment_method = $_POST['payment_method'] ?? '';
            $check_number = $_POST['check_number'] ?? null;
            $designation = $_POST['designation'] ?? '';
            if ($amount <= 0 || !$payment_method) {
                throw new Exception('Amount and payment method required');
            }
            $stmt = $pdo->prepare("INSERT INTO monetary_details (donation_id, amount, payment_method, check_number, designation) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$donation_id, $amount, $payment_method, $check_number, $designation]);
            $pdo->prepare("UPDATE donations SET total_value = ? WHERE donation_id = ?")->execute([$amount, $donation_id]);
        }
        $pdo->commit();
        $_SESSION['success'] = 'Donation added successfully';
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Failed to add donation: ' . $e->getMessage();
        header("Location: addDonation.php");
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
                                    <option value="<?php echo $donor['donor_id']; ?>" <?php echo (($_POST['donor_id'] ?? '') == $donor['donor_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($donor['name']) . ' (' . htmlspecialchars($donor['email']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Or Create New Donor</label>
                            <input type="text" class="form-control mb-2" name="new_donor_name" placeholder="Name" value="<?php echo htmlspecialchars($_POST['new_donor_name'] ?? ''); ?>">
                            <input type="email" class="form-control mb-2" name="new_donor_email" placeholder="Email" value="<?php echo htmlspecialchars($_POST['new_donor_email'] ?? ''); ?>">
                            <input type="text" class="form-control mb-2" name="new_donor_phone" placeholder="Phone" value="<?php echo htmlspecialchars($_POST['new_donor_phone'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="donation_type" class="form-label">Donation Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="donation_type" name="donation_type" required onchange="toggleFields()">
                                <option value="">Select Type</option>
                                <option value="funds" <?php echo (($_POST['donation_type'] ?? '') === 'funds') ? 'selected' : ''; ?>>Monetary</option>
                                <option value="goods" <?php echo (($_POST['donation_type'] ?? '') === 'goods') ? 'selected' : ''; ?>>In-Kind (Items)</option>
                            </select>
                        </div>
                        <div id="monetary_fields" style="display:none;">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount ($)</label>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0">
                            </div>
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_method" name="payment_method" onchange="toggleCheckField()">
                                    <option value="">Select Method</option>
                                    <option value="cash">Cash</option>
                                    <option value="check">Check</option>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="paypal">PayPal</option>
                                    <option value="venmo">Venmo</option>
                                </select>
                            </div>
                            <div class="mb-3" id="check_number_field" style="display:none;">
                                <label for="check_number" class="form-label">Check Number</label>
                                <input type="text" class="form-control" id="check_number" name="check_number">
                            </div>
                            <div class="mb-3">
                                <label for="designation" class="form-label">Fund Designation</label>
                                <input type="text" class="form-control" id="designation" name="designation" placeholder="e.g. Building Fund, General Operations, Emergency Relief">
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
                        </div>
                        <div class="mb-3">
                            <label for="date_received" class="form-label">Date Received <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_received" name="date_received" required value="<?php echo htmlspecialchars($_POST['date_received'] ?? date('Y-m-d')); ?>">
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
    document.getElementById('monetary_fields').style.display = type === 'funds' ? 'block' : 'none';
    document.getElementById('inkind_fields').style.display = type === 'goods' ? 'block' : 'none';
}
function toggleCheckField() {
    const method = document.getElementById('payment_method').value;
    document.getElementById('check_number_field').style.display = method === 'check' ? 'block' : 'none';
}
let itemIdx = 0;
function addItemRow() {
    const container = document.getElementById('item_rows');
    const idx = itemIdx++;
    const row = document.createElement('div');
    row.className = 'row mb-2';
    row.innerHTML = `
        <div class="col-md-2">
            <select name="items[${idx}][category]" class="form-select" required>
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
        <div class="col-md-2">
            <input type="text" name="items[${idx}][description]" class="form-control" placeholder="Description" required>
        </div>
        <div class="col-md-2">
            <select name="items[${idx}][condition]" class="form-select" required>
                <option value="new">New</option>
                <option value="like_new">Like New</option>
                <option value="gently_used">Gently Used</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" name="items[${idx}][quantity]" class="form-control" min="1" placeholder="Qty" required>
        </div>
        <div class="col-md-2">
            <input type="text" name="items[${idx}][unit]" class="form-control" placeholder="Unit" required>
        </div>
        <div class="col-md-1">
            <input type="number" name="items[${idx}][estimated_value]" class="form-control" step="0.01" placeholder="Value" required>
        </div>
        <div class="col-md-1">
            <input type="file" name="item_photos[]" class="form-control">
            <input type="hidden" name="items[${idx}][idx]" value="${idx}">
        </div>
    `;
    container.appendChild(row);
}
document.getElementById('donation_type').addEventListener('change', toggleFields);
document.getElementById('payment_method') && document.getElementById('payment_method').addEventListener('change', toggleCheckField);
toggleFields();
</script>

<?php include("../../includes/footer.php"); ?>
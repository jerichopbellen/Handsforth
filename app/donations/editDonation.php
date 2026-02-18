
<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

$donation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$donation_id) {
    $_SESSION['error'] = 'Invalid donation ID';
    header("Location: index.php");
    exit();
}

// Fetch donors for selection
$donors = $pdo->query("SELECT donor_id, name, email FROM donors ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch donation details
$stmt = $pdo->prepare("SELECT * FROM donations WHERE donation_id = ?");
$stmt->execute([$donation_id]);
$donation = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$donation) {
    $_SESSION['error'] = 'Donation not found';
    header("Location: index.php");
    exit();
}

if (isset($_POST['submit'])) {
    // Handle donor
    $donor_id = $_POST['donor_id'] ?? '';
    $new_donor_name = trim($_POST['new_donor_name'] ?? '');
    $new_donor_email = trim($_POST['new_donor_email'] ?? '');
    $new_donor_phone = trim($_POST['new_donor_phone'] ?? '');
    if (!$donor_id && !$new_donor_name) {
        $_SESSION['error'] = 'Donor selection or creation required';
        header("Location: editDonation.php?id=$donation_id");
        exit();
    }
    if (!$donor_id) {
        $stmt = $pdo->prepare("INSERT INTO donors (name, email, phone) VALUES (?, ?, ?)");
        $stmt->execute([$new_donor_name, $new_donor_email, $new_donor_phone]);
        $donor_id = $pdo->lastInsertId();
    }

    $donation_type = $_POST['donation_type'] ?? '';
    $date_received = $_POST['date_received'] ?? date('Y-m-d');
    $anonymous = isset($_POST['anonymous']) ? 1 : 0;

    // Update donation header
    $stmt = $pdo->prepare("UPDATE donations SET donor_id = ?, donation_type = ?, date_received = ?, anonymous = ? WHERE donation_id = ?");
    $stmt->execute([$donor_id, $donation_type, $date_received, $anonymous, $donation_id]);

    if ($donation_type === 'goods') {
        // Handle in-kind items
        $items = $_POST['items'] ?? [];
        $pdo->prepare("DELETE FROM donation_items WHERE donation_id = ?")->execute([$donation_id]);
        $item_total = 0;
        foreach ($items as $item) {
            $stmt = $pdo->prepare("INSERT INTO donation_items (donation_id, category, item_condition, quantity, unit, estimated_value) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $donation_id,
                $item['category'],
                $item['condition'],
                $item['quantity'],
                $item['unit'],
                $item['estimated_value']
            ]);
            $item_total += floatval($item['estimated_value']);
        }
        $pdo->prepare("UPDATE donations SET total_value = ? WHERE donation_id = ?")->execute([$item_total, $donation_id]);
    } else {
        // Handle monetary details
        $amount = floatval($_POST['amount'] ?? 0);
        $payment_method = $_POST['payment_method'] ?? '';
        $check_number = $_POST['check_number'] ?? null;
        $designation = $_POST['designation'] ?? '';
        $recurring = isset($_POST['recurring']) ? 1 : 0;
        if ($amount <= 0 || !$payment_method) {
            $_SESSION['error'] = 'Amount and payment method required';
            header("Location: editDonation.php?id=$donation_id");
            exit();
        }
        $pdo->prepare("DELETE FROM monetary_details WHERE donation_id = ?")->execute([$donation_id]);
        $stmt = $pdo->prepare("INSERT INTO monetary_details (donation_id, amount, payment_method, check_number, designation, recurring) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$donation_id, $amount, $payment_method, $check_number, $designation, $recurring]);
        $pdo->prepare("UPDATE donations SET total_value = ? WHERE donation_id = ?")->execute([$amount, $donation_id]);
    }
    $_SESSION['success'] = 'Donation updated successfully';
    header("Location: index.php");
    exit();
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
                                    <option value="<?php echo $donor['donor_id']; ?>" <?php echo ($donation['donor_id'] == $donor['donor_id']) ? 'selected' : ''; ?>>
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
                                <option value="funds" <?php echo $donation['donation_type'] === 'funds' ? 'selected' : ''; ?>>Monetary</option>
                                <option value="goods" <?php echo $donation['donation_type'] === 'goods' ? 'selected' : ''; ?>>In-Kind (Items)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="date_received" class="form-label">Donation Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_received" name="date_received" required value="<?php echo htmlspecialchars($donation['date_received']); ?>">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="anonymous" name="anonymous" <?php echo ($donation['anonymous'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="anonymous">Anonymous Donation</label>
                        </div>
                        <div id="goods_fields" style="display:none;">
                            <h5>In-Kind Goods Items</h5>
                            <div id="items_container">
                                <!-- Items will be dynamically added here -->
                            </div>
                            <button type="button" class="btn btn-outline-secondary mb-3" onclick="addItemRow()">Add Another Item</button>
                        </div>
                        <div id="monetary_fields" style="display:none;">
                            <h5>Monetary Donation Details</h5>
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
                                <input type="text" class="form-control" id="designation" name="designation" placeholder="e.g. Building Fund, General Operations">
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="recurring" name="recurring">
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
    document.getElementById('goods_fields').style.display = type === 'goods' ? 'block' : 'none';
    document.getElementById('monetary_fields').style.display = type === 'funds' ? 'block' : 'none';
}
function toggleCheckField() {
    const method = document.getElementById('payment_method').value;
    document.getElementById('check_number_field').style.display = method === 'check' ? 'block' : 'none';
}
// Dynamic item rows for goods
function addItemRow() {
    const container = document.getElementById('items_container');
    const idx = container.children.length;
    const row = document.createElement('div');
    row.className = 'border p-2 mb-2';
    row.innerHTML = `
        <div class="mb-2">
            <label>Type of Good</label>
            <select name="items[${idx}][category]" class="form-select">
                <option value="">Select</option>
                <option value="food">Food/Perishables</option>
                <option value="clothing">Clothing</option>
                <option value="electronics">Electronics</option>
                <option value="furniture">Furniture</option>
                <option value="medical">Medical Supplies</option>
                <option value="hygiene">Hygiene Products</option>
                <option value="toys">Toys/Educational</option>
            </select>
        </div>
        <div class="mb-2">
            <label>Description</label>
            <input type="text" name="items[${idx}][description]" class="form-control">
        </div>
        <div class="mb-2">
            <label>Quantity</label>
            <input type="number" name="items[${idx}][quantity]" class="form-control" min="1">
        </div>
        <div class="mb-2">
            <label>Unit of Measure</label>
            <input type="text" name="items[${idx}][unit]" class="form-control" placeholder="e.g. lbs, boxes, units">
        </div>
        <div class="mb-2">
            <label>Condition</label>
            <select name="items[${idx}][condition]" class="form-select">
                <option value="new">New</option>
                <option value="like_new">Like New</option>
                <option value="gently_used">Gently Used</option>
            </select>
        </div>
        <div class="mb-2">
            <label>Estimated Value ($)</label>
            <input type="number" name="items[${idx}][estimated_value]" class="form-control" step="0.01" min="0">
        </div>
    `;
    container.appendChild(row);
}
toggleFields();
toggleCheckField();
</script>

<?php include("../../includes/footer.php"); ?>
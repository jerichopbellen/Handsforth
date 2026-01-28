<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

if (isset($_POST['submit'])) {
    $donor_name = trim($_POST['donor_name'] ?? '');
    $donation_type = trim($_POST['donation_type'] ?? '');
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $description = trim($_POST['description'] ?? '');
    $date_received = trim($_POST['date_received'] ?? '');

    if (empty($donation_type) || empty($date_received)) {
        $_SESSION['error'] = 'Donation type and date are required';
        header("Location: addDonation.php");
        exit();
    }

    if ($donation_type === 'funds') {
        if ($amount <= 0) {
            $_SESSION['error'] = 'Amount must be greater than 0';
            header("Location: addDonation.php");
            exit();
        }
    } else {
        if (empty($description)) {
            $_SESSION['error'] = 'Description is required for goods donations';
            header("Location: addDonation.php");
            exit();
        }
    }

    if (!strtotime($date_received)) {
        $_SESSION['error'] = 'Invalid date format';
        header("Location: addDonation.php");
        exit();
    }

    $sql = "INSERT INTO donations (donor_name, donation_type, amount, description, date_received) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        $_SESSION['error'] = 'Database error: ' . mysqli_error($conn);
        header("Location: addDonation.php");
        exit();
    }

    $amount_insert = ($donation_type === 'funds') ? $amount : NULL;
    
    mysqli_stmt_bind_param($stmt, 'ssdss', $donor_name, $donation_type, $amount_insert, $description, $date_received);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = 'Donation added successfully';
        mysqli_stmt_close($stmt);
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = 'Failed to add donation: ' . mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
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
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                        <div class="mb-3">
                            <label for="donor_name" class="form-label">Donor Name (Optional)</label>
                            <input type="text" class="form-control" id="donor_name" name="donor_name" 
                                   value="<?php echo htmlspecialchars($_POST['donor_name'] ?? ''); ?>" maxlength="200">
                        </div>

                        <div class="mb-3">
                            <label for="donation_type" class="form-label">Donation Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="donation_type" name="donation_type" required onchange="toggleFields()">
                                <option value="">Select Type</option>
                                <option value="funds" <?php echo (($_POST['donation_type'] ?? '') === 'funds') ? 'selected' : ''; ?>>Funds</option>
                                <option value="goods" <?php echo (($_POST['donation_type'] ?? '') === 'goods') ? 'selected' : ''; ?>>Goods</option>
                            </select>
                        </div>

                        <div class="mb-3" id="amount_field" style="display: none;">
                            <label for="amount" class="form-label">Amount ($)</label>
                            <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0"
                                   value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>">
                        </div>

                        <div class="mb-3" id="description_field" style="display: none;">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                      maxlength="500"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="date_received" class="form-label">Date Received <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_received" name="date_received" required
                                   value="<?php echo htmlspecialchars($_POST['date_received'] ?? date('Y-m-d')); ?>">
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
    document.getElementById('amount_field').style.display = type === 'funds' ? 'block' : 'none';
    document.getElementById('description_field').style.display = type === 'goods' ? 'block' : 'none';
}
toggleFields();
</script>

<?php include("../../includes/footer.php"); ?>
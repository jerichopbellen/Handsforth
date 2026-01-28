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

$sql = "SELECT * FROM donations WHERE donation_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $donation_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$donation = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$donation) {
    $_SESSION['error'] = 'Donation not found';
    header("Location: index.php");
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
        header("Location: editDonation.php?id=$donation_id");
        exit();
    }

    if ($donation_type === 'funds') {
        if ($amount <= 0) {
            $_SESSION['error'] = 'Amount must be greater than 0';
            header("Location: editDonation.php?id=$donation_id");
            exit();
        }
    } else {
        if (empty($description)) {
            $_SESSION['error'] = 'Description is required for goods donations';
            header("Location: editDonation.php?id=$donation_id");
            exit();
        }
    }

    if (!strtotime($date_received)) {
        $_SESSION['error'] = 'Invalid date format';
        header("Location: editDonation.php?id=$donation_id");
        exit();
    }

    $update_sql = "UPDATE donations SET donor_name = ?, donation_type = ?, amount = ?, description = ?, date_received = ? WHERE donation_id = ?";
    
    $update_stmt = mysqli_prepare($conn, $update_sql);
    
    if (!$update_stmt) {
        $_SESSION['error'] = 'Database error: ' . mysqli_error($conn);
        header("Location: editDonation.php?id=$donation_id");
        exit();
    }

    $amount_insert = ($donation_type === 'funds') ? $amount : NULL;
    
    mysqli_stmt_bind_param($update_stmt, 'ssdssi', $donor_name, $donation_type, $amount_insert, $description, $date_received, $donation_id);

    if (mysqli_stmt_execute($update_stmt)) {
        $_SESSION['success'] = 'Donation updated successfully';
        mysqli_stmt_close($update_stmt);
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = 'Failed to update donation: ' . mysqli_stmt_error($update_stmt);
        mysqli_stmt_close($update_stmt);
        header("Location: editDonation.php?id=$donation_id");
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
                            <label for="donor_name" class="form-label">Donor Name (Optional)</label>
                            <input type="text" class="form-control" id="donor_name" name="donor_name" 
                                   value="<?php echo htmlspecialchars($donation['donor_name'] ?? ''); ?>" maxlength="200">
                        </div>

                        <div class="mb-3">
                            <label for="donation_type" class="form-label">Donation Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="donation_type" name="donation_type" required onchange="toggleFields()">
                                <option value="">Select Type</option>
                                <option value="funds" <?php echo $donation['donation_type'] === 'funds' ? 'selected' : ''; ?>>Funds</option>
                                <option value="goods" <?php echo $donation['donation_type'] === 'goods' ? 'selected' : ''; ?>>Goods</option>
                            </select>
                        </div>

                        <div class="mb-3" id="amount_field" style="display: none;">
                            <label for="amount" class="form-label">Amount ($)</label>
                            <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0"
                                   value="<?php echo htmlspecialchars($donation['amount'] ?? ''); ?>">
                        </div>

                        <div class="mb-3" id="description_field" style="display: none;">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                      maxlength="500"><?php echo htmlspecialchars($donation['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="date_received" class="form-label">Date Received <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_received" name="date_received" required
                                   value="<?php echo htmlspecialchars($donation['date_received']); ?>">
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
    document.getElementById('amount_field').style.display = type === 'funds' ? 'block' : 'none';
    document.getElementById('description_field').style.display = type === 'goods' ? 'block' : 'none';
}
toggleFields();
</script>

<?php include("../../includes/footer.php"); ?>
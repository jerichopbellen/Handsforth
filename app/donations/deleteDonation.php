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

if (isset($_POST['confirm_delete'])) {
    $delete_sql = "DELETE FROM donations WHERE donation_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    
    if (!$delete_stmt) {
        $_SESSION['error'] = 'Database error: ' . mysqli_error($conn);
        header("Location: index.php");
        exit();
    }

    mysqli_stmt_bind_param($delete_stmt, 'i', $donation_id);

    if (mysqli_stmt_execute($delete_stmt)) {
        $_SESSION['success'] = 'Donation deleted successfully';
        mysqli_stmt_close($delete_stmt);
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = 'Failed to delete donation: ' . mysqli_stmt_error($delete_stmt);
        mysqli_stmt_close($delete_stmt);
        header("Location: index.php");
        exit();
    }
}
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>
    
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Delete Donation</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">Are you sure you want to delete this donation?</p>
                    <div class="bg-light p-3 rounded mb-3">
                        <p><strong>Donor:</strong> <?php echo htmlspecialchars($donation['donor_name'] ?? 'Anonymous'); ?></p>
                        <p><strong>Type:</strong> <?php echo htmlspecialchars($donation['donation_type']); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($donation['date_received']); ?></p>
                    </div>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $donation_id); ?>" method="POST">
                        <div class="d-flex gap-2">
                            <button type="submit" name="confirm_delete" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Delete</button>
                            <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>
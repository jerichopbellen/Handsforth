<?php
ob_start();
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


$stmt = $pdo->prepare("SELECT * FROM donations WHERE donation_id = ?");
$stmt->execute([$donation_id]);
$donation = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$donation) {
    $_SESSION['error'] = 'Donation not found';
    header("Location: index.php");
    exit();
}

// Fetch donor name for display
$donor_name = 'Anonymous';
if (!empty($donation['donor_id'])) {
    $donor_stmt = $pdo->prepare("SELECT name FROM donors WHERE donor_id = ?");
    $donor_stmt->execute([$donation['donor_id']]);
    $donor = $donor_stmt->fetch(PDO::FETCH_ASSOC);
    if ($donor && !empty($donor['name'])) {
        $donor_name = $donor['name'];
    }
}

if (isset($_POST['confirm_delete'])) {
    try {
        $pdo->beginTransaction();
        // Soft delete donation
        $stmt = $pdo->prepare("UPDATE donations SET is_deleted = 1 WHERE donation_id = ?");
        $stmt->execute([$donation_id]);
        $pdo->commit();
        $_SESSION['success'] = 'Donation deleted successfully';
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Failed to delete donation: ' . $e->getMessage();
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
                        <p><strong>Donor:</strong> <?php echo htmlspecialchars($donor_name); ?></p>
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
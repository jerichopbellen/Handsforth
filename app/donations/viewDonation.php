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

// Fetch donation details

$sql = "SELECT d.*, donors.name, donors.email, donors.phone FROM donations d LEFT JOIN donors ON d.donor_id = donors.donor_id WHERE d.donation_id = ?";
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

// Fetch distribution history
$dist_sql = "SELECT rd.*, p.title as project_title FROM resource_distribution rd JOIN projects p ON rd.project_id = p.project_id WHERE rd.donation_id = ? ORDER BY rd.date_distributed DESC";
$dist_stmt = mysqli_prepare($conn, $dist_sql);
mysqli_stmt_bind_param($dist_stmt, 'i', $donation_id);
mysqli_stmt_execute($dist_stmt);
$dist_result = mysqli_stmt_get_result($dist_stmt);
$distributions = mysqli_fetch_all($dist_result, MYSQLI_ASSOC);
mysqli_stmt_close($dist_stmt);
?>
<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-eye me-2"></i>Donation Details</h2>
        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <?php if ($donation['anonymous']): ?>
                <h5 class="card-title">Donor: Anonymous</h5>
            <?php else: ?>
                <h5 class="card-title">Donor Name: <?php echo htmlspecialchars($donation['name']); ?></h5>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($donation['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($donation['phone']); ?></p>
            <?php endif; ?>
            <p><strong>Type:</strong> <?php echo htmlspecialchars($donation['donation_type']); ?></p>
            <p><strong>Amount:</strong> <?php echo $donation['donation_type'] === 'funds' ? '$' . htmlspecialchars($donation['amount']) : 'N/A'; ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($donation['description']); ?></p>
            <p><strong>Date Received:</strong> <?php echo htmlspecialchars($donation['date_received']); ?></p>
        </div>
    </div>
    <h4>Distribution History</h4>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Quantity Distributed</th>
                    <th>Date Distributed</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($distributions)): ?>
                    <tr><td colspan="3" class="text-center text-muted">No distributions found</td></tr>
                <?php else: ?>
                    <?php foreach ($distributions as $dist): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($dist['project_title']); ?></td>
                            <td><?php echo htmlspecialchars($dist['quantity_distributed']); ?></td>
                            <td><?php echo htmlspecialchars($dist['date_distributed']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include("../../includes/footer.php"); ?>

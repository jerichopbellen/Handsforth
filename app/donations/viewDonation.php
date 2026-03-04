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


$stmt = $pdo->prepare("SELECT d.*, donors.name, donors.email, donors.phone FROM donations d LEFT JOIN donors ON d.donor_id = donors.donor_id WHERE d.donation_id = ?");
$stmt->execute([$donation_id]);
$donation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$donation) {
    $_SESSION['error'] = 'Donation not found';
    header("Location: index.php");
    exit();
}

// Fetch monetary amount for 'funds' donations

if ($donation['donation_type'] === 'funds') {
    $stmt = $pdo->prepare("SELECT amount FROM monetary_details WHERE donation_id = ?");
    $stmt->execute([$donation_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $donation['amount'] = $row['amount'] ?? 0;
}

// Fetch distributions

$dist_sql = "SELECT dists.*, p.title AS project_title FROM distributions dists LEFT JOIN projects p ON dists.project_id = p.project_id WHERE dists.donation_id = ? ORDER BY dists.distributed_date DESC";
$dist_stmt = $pdo->prepare($dist_sql);
$dist_stmt->execute([$donation_id]);
$distributions = $dist_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-eye me-2"></i>Donation Details</h2>
        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <?php if (!empty($donation['name'])): ?>
                <h5 class="card-title">Donor Name: <?php echo htmlspecialchars($donation['name']); ?></h5>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($donation['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($donation['phone']); ?></p>
            <?php endif; ?>
            <p><strong>Type:</strong> <?php echo htmlspecialchars($donation['donation_type']); ?></p>
            <p><strong>Amount:</strong> <?php 
                if ($donation['donation_type'] === 'funds') {
                    echo '$' . htmlspecialchars($donation['amount']);
                } else {
                    // For goods, show estimated value if available, else show quantity from donation_items
                    $item_stmt = $pdo->prepare("SELECT SUM(estimated_value) as total_value, SUM(quantity) as total_qty FROM donation_items WHERE donation_id = ?");
                    $item_stmt->execute([$donation_id]);
                    $item_row = $item_stmt->fetch(PDO::FETCH_ASSOC);
                    if (!empty($item_row['total_value'])) {
                        echo '$' . number_format($item_row['total_value'], 2) . ' (estimated value)';
                    } elseif (!empty($item_row['total_qty'])) {
                        echo $item_row['total_qty'] . ' items';
                    } else {
                        echo 'N/A';
                    }
                }
            ?></p>
            <p><strong>Description:</strong> <?php 
                if ($donation['donation_type'] === 'goods') {
                    // For goods, show concatenated item descriptions
                    $desc_stmt = $pdo->prepare("SELECT GROUP_CONCAT(description SEPARATOR '; ') as descriptions FROM donation_items WHERE donation_id = ?");
                    $desc_stmt->execute([$donation_id]);
                    $desc_row = $desc_stmt->fetch(PDO::FETCH_ASSOC);
                    echo htmlspecialchars($desc_row['descriptions'] ?? $donation['description']);
                } else {
                    echo htmlspecialchars($donation['description']);
                }
            ?></p>
            <p><strong>Date Received:</strong> <?php echo htmlspecialchars($donation['date_received']); ?></p>
            <?php if ($donation['donation_type'] === 'funds') {
                $details_stmt = $pdo->prepare("SELECT payment_method, check_number, designation, recurring FROM monetary_details WHERE donation_id = ?");
                $details_stmt->execute([$donation_id]);
                $details = $details_stmt->fetch(PDO::FETCH_ASSOC);
                if ($details) {
            ?>
                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($details['payment_method']); ?></p>
                <?php if (!empty($details['check_number'])): ?><p><strong>Check Number:</strong> <?php echo htmlspecialchars($details['check_number']); ?></p><?php endif; ?>
                <p><strong>Designation:</strong> <?php echo htmlspecialchars($details['designation']); ?></p>
                <p><strong>Recurring:</strong> <?php echo $details['recurring'] ? 'Yes' : 'No'; ?></p>
            <?php }} ?>
            <p><strong>Staff:</strong> <?php 
                if (!empty($donation['staff_id'])) {
                    $staff_stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
                    $staff_stmt->execute([$donation['staff_id']]);
                    $staff = $staff_stmt->fetch(PDO::FETCH_ASSOC);
                    if ($staff) {
                        echo htmlspecialchars(trim($staff['first_name'] . ' ' . $staff['last_name']));
                    } else {
                        echo htmlspecialchars($donation['staff_id']);
                    }
                } else {
                    echo 'N/A';
                }
            ?></p>
            <?php if (!empty($donation['receipt_file'])): ?>
                <p><strong>Receipt:</strong> <a href="<?php echo htmlspecialchars($donation['receipt_file']); ?>" target="_blank">Download</a></p>
            <?php endif; ?>
            <p><strong>Transaction Number:</strong> <?php echo htmlspecialchars($donation['txn_number']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($donation['status']); ?></p>
            <p><strong>Created At:</strong> <?php echo htmlspecialchars($donation['created_at']); ?></p>
            <p><strong>Updated At:</strong> <?php echo htmlspecialchars($donation['updated_at']); ?></p>
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
    <h5>Linked Distributions</h5>
    <ul>
    <?php foreach ($distributions as $dist): ?>
        <li>Beneficiary ID: <?php echo $dist['beneficiary_id']; ?>, Project ID: <?php echo $dist['project_id']; ?>, Amount: $<?php echo $dist['distributed_amount']; ?>, Date: <?php echo $dist['distributed_date']; ?></li>
    <?php endforeach; ?>
    </ul>
</div>
<?php include("../../includes/footer.php"); ?>

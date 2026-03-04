<?php
// Donor Profile Page
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

$donor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$donor_id) {
    echo "Invalid donor ID.";
    exit();
}

// Fetch donor info
$stmt = $pdo->prepare("SELECT * FROM donors WHERE donor_id = ?");
$stmt->execute([$donor_id]);
$donor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$donor) {
    echo "Donor not found.";
    exit();
}

// Fetch transaction history
$tx_stmt = $pdo->prepare("SELECT * FROM donations WHERE donor_id = ? ORDER BY date_received DESC");
$tx_stmt->execute([$donor_id]);
$transactions = $tx_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate engagement score (simple: total donations + frequency)
$total_amount = 0;
foreach ($transactions as $tx) {
    $total_amount += floatval($tx['amount']);
}
$engagement_score = count($transactions) * 10 + $total_amount;

// Communication log
$comm_log = $donor['communication_log'] ?? '';
?>
<div class="container my-5">
    <h2>Donor Profile: <?php echo htmlspecialchars($donor['name']); ?></h2>
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5>Contact Info</h5>
                    <p>Email: <?php echo htmlspecialchars($donor['email']); ?></p>
                    <p>Phone: <?php echo htmlspecialchars($donor['phone']); ?></p>
                    <p>Status: <?php echo htmlspecialchars($donor['engagement_status'] ?? ''); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5>Engagement Score</h5>
                    <h2><?php echo number_format($engagement_score, 2); ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h5>Transaction History</h5>
            <table class="table">
                <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Description</th></tr></thead>
                <tbody>
                <?php foreach ($transactions as $tx): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($tx['date_received']); ?></td>
                        <td><?php echo htmlspecialchars($tx['donation_type']); ?></td>
                        <td><?php echo htmlspecialchars($tx['amount']); ?></td>
                        <td><?php echo htmlspecialchars($tx['description']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h5>Communication Log</h5>
            <pre><?php echo htmlspecialchars($comm_log); ?></pre>
        </div>
    </div>
</div>
<?php include("../../includes/footer.php"); ?>

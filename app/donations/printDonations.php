<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT donation_id, donor_name, donation_type, amount, description, date_received FROM donations WHERE 1=1";
$params = array();
$types = '';

if ($filter_type) {
    $sql .= " AND donation_type = ?";
    $params[] = $filter_type;
    $types .= 's';
}
if ($search) {
    $sql .= " AND (donor_name LIKE ? OR description LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}
if ($filter_date_from) {
    $sql .= " AND date_received >= ?";
    $params[] = $filter_date_from;
    $types .= 's';
}
if ($filter_date_to) {
    $sql .= " AND date_received <= ?";
    $params[] = $filter_date_to;
    $types .= 's';
}
$sql .= " ORDER BY date_received DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$donations = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Printable Donations Report</title>
    <link rel="stylesheet" href="../../assets/print.css">
    <style>
        body { font-family: Arial, sans-serif; }
        .print-header { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background: #eee; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="print-header">
        <h2>Donations Report</h2>
        <p>Printed on <?php echo date('Y-m-d H:i'); ?></p>
        <button class="no-print" onclick="window.print()">Print</button>
        <a class="no-print" href="index.php">Back</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Donor Name</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Description</th>
                <th>Date Received</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($donations as $donation): ?>
                <tr>
                    <td><?php echo $donation['donation_id']; ?></td>
                    <td><?php echo htmlspecialchars($donation['donor_name'] ?? 'Anonymous'); ?></td>
                    <td><?php echo htmlspecialchars($donation['donation_type']); ?></td>
                    <td><?php echo $donation['donation_type'] === 'funds' ? '$' . htmlspecialchars($donation['amount']) : 'N/A'; ?></td>
                    <td><?php echo htmlspecialchars($donation['description']); ?></td>
                    <td><?php echo htmlspecialchars($donation['date_received']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>

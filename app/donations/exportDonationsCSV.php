<?php
// Export donations to CSV
session_start();
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=donations_export_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Donation ID', 'Donor Name', 'Type', 'Amount', 'Description', 'Date Received']);

$sql = "SELECT donation_id, donor_name, donation_type, amount, description, date_received FROM donations ORDER BY date_received DESC";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['donation_id'],
        $row['donor_name'],
        $row['donation_type'],
        $row['amount'],
        $row['description'],
        $row['date_received']
    ]);
}
fclose($output);
exit();

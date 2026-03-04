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
fputcsv($output, ['Donation ID', 'Donor Name', 'Type', 'Amount', 'Description', 'Date Received', 'Payment Method', 'Designation', 'Recurring', 'Staff', 'Receipt', 'Transaction Number', 'Status', 'Created At', 'Updated At']);

// Join donors and users, and get monetary details for funds
$sql = "SELECT d.donation_id, donors.name AS donor_name, d.donation_type, d.amount, d.description, d.date_received, 
        md.payment_method, md.designation, md.recurring, 
        CONCAT(u.first_name, ' ', u.last_name) AS staff_name, d.receipt_file, d.txn_number, d.status, d.created_at, d.updated_at
        FROM donations d
        LEFT JOIN donors ON d.donor_id = donors.donor_id
        LEFT JOIN users u ON d.staff_id = u.user_id
        LEFT JOIN monetary_details md ON d.donation_id = md.donation_id
        ORDER BY d.date_received DESC";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['donation_id'],
        $row['donor_name'],
        $row['donation_type'],
        $row['amount'],
        $row['description'],
        $row['date_received'],
        $row['payment_method'],
        $row['designation'],
        $row['recurring'] ? 'Yes' : 'No',
        $row['staff_name'],
        $row['receipt_file'],
        $row['txn_number'],
        $row['status'],
        $row['created_at'],
        $row['updated_at']
    ]);
}
fclose($output);
exit();

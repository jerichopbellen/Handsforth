<?php
session_start();
include('../../includes/config.php');
include('reportDataUtil.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../public/index.php');
    exit();
}

$filters = [
    'type' => $_GET['type'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'search' => trim($_GET['search'] ?? ''),
];

$rows = fetchDonationReportRows($pdo, $filters);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=donations_report_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Donation ID',
    'Donor Name',
    'Type',
    'Amount (Monetary)',
    'Description / Goods Items',
    'Date Received',
    'Distribution Status',
    'Distribution Progress',
    'Item-Type Progress',
    'Distribution Details',
    'Payment Method',
    'Reference Number',
    'Designation',
    'Recurring',
    'Staff',
    'Receipt File',
    'Transaction Number',
    'Record Status',
    'Created At',
    'Updated At',
]);

foreach ($rows as $row) {
    fputcsv($output, [
        $row['donation_id'],
        $row['donor_name'],
        $row['donation_type'],
        $row['amount_monetary'],
        $row['goods_description'],
        $row['date_received'],
        $row['distribution_status'],
        $row['distribution_progress'],
        $row['item_type_progress'],
        $row['distribution_details'],
        $row['payment_method'],
        $row['reference_number'],
        $row['designation'],
        $row['recurring'],
        $row['staff_name'],
        $row['receipt_file'],
        $row['txn_number'],
        $row['record_status'],
        $row['created_at'],
        $row['updated_at'],
    ]);
}

fclose($output);
exit();

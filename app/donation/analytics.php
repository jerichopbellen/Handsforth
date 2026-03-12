<?php
session_start();
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json; charset=utf-8');

// Total donations per month (last 12 months)
$trend = $pdo->query("
    SELECT DATE_FORMAT(date_received, '%Y-%m') as month, SUM(amount) as total
    FROM donations
    WHERE date_received >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY month
    ORDER BY month
")->fetchAll(PDO::FETCH_ASSOC);

// Donor demographics (by engagement status)
$demographics = $pdo->query("
    SELECT COALESCE(donors.engagement_status, 'unknown') AS engagement_status, COUNT(*) as donor_count
    FROM donors
    JOIN donations ON donors.donor_id = donations.donor_id
    GROUP BY COALESCE(donors.engagement_status, 'unknown')
")->fetchAll(PDO::FETCH_ASSOC);

// Distribution summary (total distributed per project)
$distribution = $pdo->query("
    SELECT projects.title, SUM(distributions.distributed_amount) as total_distributed
    FROM distributions
    JOIN projects ON distributions.project_id = projects.project_id
    GROUP BY projects.title
")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'trend' => $trend,
    'demographics' => $demographics,
    'distribution' => $distribution
]);
?>

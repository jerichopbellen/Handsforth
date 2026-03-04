<?php
include("../../includes/config.php");

// Total donations per month (last 12 months)
$trend = $pdo->query("
    SELECT DATE_FORMAT(date_received, '%Y-%m') as month, SUM(amount) as total
    FROM donations
    WHERE date_received >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY month
    ORDER BY month
")->fetchAll(PDO::FETCH_ASSOC);

// Donor demographics (by city)
$demographics = $pdo->query("
    SELECT donors.city, COUNT(*) as donor_count
    FROM donors
    JOIN donations ON donors.donor_id = donations.donor_id
    GROUP BY donors.city
")->fetchAll(PDO::FETCH_ASSOC);

// Distribution summary (total distributed per project)
$distribution = $pdo->query("
    SELECT projects.name, SUM(distributions.distributed_amount) as total_distributed
    FROM distributions
    JOIN projects ON distributions.project_id = projects.project_id
    GROUP BY projects.name
")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'trend' => $trend,
    'demographics' => $demographics,
    'distribution' => $distribution
]);
?>

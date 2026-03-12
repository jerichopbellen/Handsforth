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
$summary = summarizeDonationReportRows($rows);

$activeFilters = [];
if ($filters['type'] !== '') {
    $activeFilters[] = 'Type: ' . ucfirst($filters['type']);
}
if ($filters['date_from'] !== '') {
    $activeFilters[] = 'From: ' . $filters['date_from'];
}
if ($filters['date_to'] !== '') {
    $activeFilters[] = 'To: ' . $filters['date_to'];
}
if ($filters['search'] !== '') {
    $activeFilters[] = 'Search: ' . $filters['search'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donations Report</title>
    <style>
        @page {
            size: landscape;
            margin: 10mm;
        }
        body {
            font-family: Arial, sans-serif;
            color: #222;
            margin: 0;
            font-size: 12px;
        }
        .print-header {
            margin-bottom: 12px;
        }
        .print-header h2 {
            margin: 0 0 6px 0;
            font-size: 20px;
        }
        .meta {
            margin: 2px 0;
            color: #444;
        }
        .filters {
            margin: 8px 0 10px;
            color: #444;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 12px;
        }
        .summary-card {
            border: 1px solid #bbb;
            border-radius: 4px;
            padding: 8px;
            background: #f7f7f7;
        }
        .summary-card .label {
            font-size: 11px;
            color: #555;
            margin-bottom: 4px;
        }
        .summary-card .value {
            font-size: 14px;
            font-weight: 700;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #666;
            padding: 6px;
            vertical-align: top;
            word-wrap: break-word;
            white-space: normal;
        }
        th {
            background: #ececec;
            text-align: left;
            font-size: 11px;
        }
        .no-print {
            margin: 8px 0;
        }
        .muted {
            color: #666;
            font-size: 11px;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="print-header">
        <h2>Donations Report</h2>
        <div class="meta">Printed on <?php echo date('Y-m-d H:i'); ?></div>
        <div class="meta">Prepared by <?php echo htmlspecialchars($_SESSION['email'] ?? 'Admin'); ?></div>
    </div>

    <div class="filters">
        <strong>Filters:</strong>
        <?php echo !empty($activeFilters) ? htmlspecialchars(implode(' | ', $activeFilters)) : 'None'; ?>
    </div>

    <div class="no-print">
        <button onclick="window.print()">Print</button>
        <a href="index.php">Back</a>
    </div>

    <div class="summary">
        <div class="summary-card">
            <div class="label">Total Donations</div>
            <div class="value"><?php echo (int)$summary['total_donations']; ?></div>
        </div>
        <div class="summary-card">
            <div class="label">Funds Donated</div>
            <div class="value">$<?php echo number_format((float)$summary['total_funds_donated'], 2); ?></div>
        </div>
        <div class="summary-card">
            <div class="label">Funds Distributed</div>
            <div class="value">$<?php echo number_format((float)$summary['total_funds_distributed'], 2); ?></div>
        </div>
        <div class="summary-card">
            <div class="label">Goods Units Donated</div>
            <div class="value"><?php echo number_format((int)$summary['total_goods_units_donated']); ?></div>
        </div>
        <div class="summary-card">
            <div class="label">Goods Units Distributed</div>
            <div class="value"><?php echo number_format((int)$summary['total_goods_units_distributed']); ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:4%;">ID</th>
                <th style="width:10%;">Donor</th>
                <th style="width:6%;">Type</th>
                <th style="width:14%;">Amount / Goods</th>
                <th style="width:7%;">Date</th>
                <th style="width:8%;">Distribution Status</th>
                <th style="width:10%;">Distribution Progress</th>
                <th style="width:14%;">Distribution Details</th>
                <th style="width:11%;">Payment Details</th>
                <th style="width:8%;">Staff</th>
                <th style="width:8%;">Txn #</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="11" style="text-align:center;" class="muted">No donations found for the selected filters.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?php echo (int)$row['donation_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['donor_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['donation_type']); ?></td>
                        <td>
                            <?php if (strtolower($row['donation_type']) === 'funds'): ?>
                                <?php echo htmlspecialchars($row['amount_monetary'] !== '' ? $row['amount_monetary'] : 'N/A'); ?>
                            <?php else: ?>
                                <?php echo htmlspecialchars($row['goods_description']); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['date_received']); ?></td>
                        <td><?php echo htmlspecialchars($row['distribution_status']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($row['distribution_progress']); ?>
                            <?php if ($row['item_type_progress'] !== ''): ?>
                                <div class="muted"><?php echo htmlspecialchars($row['item_type_progress']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['distribution_details'] !== '' ? $row['distribution_details'] : 'N/A'); ?></td>
                        <td>
                            <?php if (strtolower($row['donation_type']) === 'funds'): ?>
                                <?php echo htmlspecialchars($row['payment_method'] !== '' ? $row['payment_method'] : 'N/A'); ?>
                                <?php if ($row['designation'] !== ''): ?>
                                    <div class="muted"><?php echo htmlspecialchars($row['designation']); ?></div>
                                <?php endif; ?>
                                <?php if ($row['reference_number'] !== ''): ?>
                                    <div class="muted">Ref: <?php echo htmlspecialchars($row['reference_number']); ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['staff_name']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($row['txn_number'] !== '' ? $row['txn_number'] : 'N/A'); ?>
                            <?php if ($row['record_status'] !== ''): ?>
                                <div class="muted"><?php echo htmlspecialchars($row['record_status']); ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

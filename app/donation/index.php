<?php
ob_start();
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

$filter_type     = $_GET['type'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to   = $_GET['date_to'] ?? '';
$search           = trim($_GET['search'] ?? '');
$sort             = $_GET['sort'] ?? '';
$sort_columns     = [
    'donor_name' => 'COALESCE(donors.name, \'\')',
    'donation_type' => 'd.donation_type',
    'amount' => 'd.amount',
    'date_received' => 'd.date_received',
];

$report_filters = [];
if ($filter_type !== '') {
    $report_filters['type'] = $filter_type;
}
if ($filter_date_from !== '') {
    $report_filters['date_from'] = $filter_date_from;
}
if ($filter_date_to !== '') {
    $report_filters['date_to'] = $filter_date_to;
}
if ($search !== '') {
    $report_filters['search'] = $search;
}
$report_query = !empty($report_filters) ? ('?' . http_build_query($report_filters)) : '';

$sql = "SELECT d.donation_id, d.donation_type, d.amount, d.description, d.date_received, d.donor_id, donors.name AS donor_name_lookup 
    FROM donations d
    LEFT JOIN donors ON d.donor_id = donors.donor_id
    WHERE 1=1";
$params = [];
$types  = '';

if ($filter_type) {
    $sql .= " AND donation_type = ?";
    $params[] = $filter_type;
    $types .= 's';
}
if ($search) {
    $sql .= " AND (donors.name LIKE ? OR d.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
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

$sql .= isset($sort_columns[$sort]) ? " ORDER BY {$sort_columns[$sort]} DESC" : " ORDER BY d.date_received DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$donations = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$distribution_map = [];
if (!empty($donations)) {
    $donation_ids = array_values(array_unique(array_map('intval', array_column($donations, 'donation_id'))));
    if (!empty($donation_ids)) {
        $placeholders = implode(',', array_fill(0, count($donation_ids), '?'));
        $bind_types = str_repeat('i', count($donation_ids));

        $dist_sql = "SELECT donation_id, distributed_amount, notes FROM distributions WHERE donation_id IN ($placeholders)";
        $dist_stmt = mysqli_prepare($conn, $dist_sql);
        mysqli_stmt_bind_param($dist_stmt, $bind_types, ...$donation_ids);
        mysqli_stmt_execute($dist_stmt);
        $dist_result = mysqli_stmt_get_result($dist_stmt);

        while ($dist_row = mysqli_fetch_assoc($dist_result)) {
            $dist_donation_id = (int)$dist_row['donation_id'];
            if (!isset($distribution_map[$dist_donation_id])) {
                $distribution_map[$dist_donation_id] = [
                    'total_distributed' => 0.0,
                    'goods_items_by_id' => [],
                    'goods_items_by_desc' => [],
                ];
            }

            $distribution_map[$dist_donation_id]['total_distributed'] += (float)($dist_row['distributed_amount'] ?? 0);

            $notes = json_decode((string)($dist_row['notes'] ?? ''), true);
            if (is_array($notes) && ($notes['type'] ?? '') === 'goods_distribution' && !empty($notes['items']) && is_array($notes['items'])) {
                foreach ($notes['items'] as $item) {
                    $item_id = (int)($item['item_id'] ?? 0);
                    $item_desc = trim((string)($item['description'] ?? ''));
                    if ($item_desc === '') {
                        $item_desc = 'Item';
                    }
                    $item_qty = (int)($item['quantity'] ?? 0);
                    if ($item_qty <= 0) {
                        continue;
                    }

                    if ($item_id > 0) {
                        if (!isset($distribution_map[$dist_donation_id]['goods_items_by_id'][$item_id])) {
                            $distribution_map[$dist_donation_id]['goods_items_by_id'][$item_id] = [
                                'description' => $item_desc,
                                'quantity' => 0,
                            ];
                        }
                        $distribution_map[$dist_donation_id]['goods_items_by_id'][$item_id]['quantity'] += $item_qty;
                    }

                    if (!isset($distribution_map[$dist_donation_id]['goods_items_by_desc'][$item_desc])) {
                        $distribution_map[$dist_donation_id]['goods_items_by_desc'][$item_desc] = 0;
                    }
                    $distribution_map[$dist_donation_id]['goods_items_by_desc'][$item_desc] += $item_qty;
                }
            }
        }

        mysqli_stmt_close($dist_stmt);
    }
}

function resolveDonorPhotoPath($photoPath) {
    $photoPath = trim((string)$photoPath);
    if ($photoPath === '') {
        return '';
    }

    $photoPath = str_replace('\\', '/', $photoPath);

    if (
        preg_match('#^(https?:)?//#i', $photoPath) ||
        strpos($photoPath, 'data:') === 0 ||
        strpos($photoPath, '/') === 0
    ) {
        return $photoPath;
    }

    $photoPath = preg_replace('#^(\.\./)+#', '', $photoPath);
    if (strpos($photoPath, 'uploads/') !== 0) {
        $photoPath = 'uploads/' . ltrim($photoPath, '/');
    }

    return '../../' . $photoPath;
}

// enrich with donor details and computed display value
foreach ($donations as $i => $donation) {
    if ($donation['donation_type'] === 'funds') {
        $stmt = mysqli_prepare($conn, "SELECT amount FROM monetary_details WHERE donation_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $donation['donation_id']);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        $amount = (float)($row['amount'] ?? $donation['amount'] ?? 0);
        $donations[$i]['amount'] = $amount;
        $donations[$i]['amount_or_description'] = '$' . number_format($amount, 2);
        mysqli_stmt_close($stmt);
    } else {
        $desc_stmt = mysqli_prepare($conn, "SELECT GROUP_CONCAT(description SEPARATOR '; ') AS descriptions, COALESCE(SUM(quantity), 0) AS total_quantity FROM donation_items WHERE donation_id = ?");
        mysqli_stmt_bind_param($desc_stmt, 'i', $donation['donation_id']);
        mysqli_stmt_execute($desc_stmt);
        $desc_res = mysqli_stmt_get_result($desc_stmt);
        $desc_row = mysqli_fetch_assoc($desc_res);
        mysqli_stmt_close($desc_stmt);

        $goodsDescription = trim((string)($desc_row['descriptions'] ?? ''));
        if ($goodsDescription === '') {
            $goodsDescription = trim((string)($donation['description'] ?? ''));
        }
        $donations[$i]['amount_or_description'] = $goodsDescription !== '' ? $goodsDescription : 'N/A';
        $donations[$i]['goods_total_quantity'] = (int)($desc_row['total_quantity'] ?? 0);
    }
    if (!empty($donation['donor_id'])) {
        $donor_stmt = mysqli_prepare($conn, "SELECT * FROM donors WHERE donor_id = ?");
        mysqli_stmt_bind_param($donor_stmt, 'i', $donation['donor_id']);
        mysqli_stmt_execute($donor_stmt);
        $donor_res = mysqli_stmt_get_result($donor_stmt);
        $donor = mysqli_fetch_assoc($donor_res);
        $donations[$i]['donor_name'] = $donor['name'] ?? 'Anonymous';
        $donations[$i]['donor_photo'] = resolveDonorPhotoPath($donor['photo'] ?? '');
        mysqli_stmt_close($donor_stmt);
    } else {
        $donations[$i]['donor_name'] = 'Anonymous';
        $donations[$i]['donor_photo'] = '';
    }

    $dist_info = $distribution_map[(int)$donation['donation_id']] ?? null;
    $distributed_amount = (float)($dist_info['total_distributed'] ?? 0);

    $donations[$i]['distribution_status_label'] = 'Not Distributed';
    $donations[$i]['distribution_status_class'] = 'bg-secondary';
    $donations[$i]['distribution_detail'] = 'No distributions yet';
    $donations[$i]['distribution_progress_detail'] = '';
    $donations[$i]['distribution_items_detail'] = '';

    if ($donation['donation_type'] === 'funds') {
        $total_amount = (float)($donations[$i]['amount'] ?? 0);
        if ($distributed_amount > 0) {
            $donations[$i]['distribution_status_label'] = ($total_amount > 0 && $distributed_amount + 0.00001 >= $total_amount)
                ? 'Fully Distributed'
                : 'Partially Distributed';
            $donations[$i]['distribution_status_class'] = ($total_amount > 0 && $distributed_amount + 0.00001 >= $total_amount)
                ? 'bg-success'
                : 'bg-warning text-dark';
            $donations[$i]['distribution_detail'] = '$' . number_format($distributed_amount, 2) . ' of $' . number_format($total_amount, 2) . ' distributed';
        }
    } else {
        $total_units = (int)($donations[$i]['goods_total_quantity'] ?? 0);
        $distributed_units = (int)round($distributed_amount);

        $item_types_total = 0;
        $item_types_fully_distributed = 0;
        $dist_items_by_id = $dist_info['goods_items_by_id'] ?? [];

        $goods_item_stmt = mysqli_prepare($conn, "SELECT item_id, quantity FROM donation_items WHERE donation_id = ?");
        mysqli_stmt_bind_param($goods_item_stmt, 'i', $donation['donation_id']);
        mysqli_stmt_execute($goods_item_stmt);
        $goods_item_res = mysqli_stmt_get_result($goods_item_stmt);
        while ($goods_item_row = mysqli_fetch_assoc($goods_item_res)) {
            $item_qty = (int)($goods_item_row['quantity'] ?? 0);
            if ($item_qty <= 0) {
                continue;
            }

            $item_types_total++;
            $item_id = (int)($goods_item_row['item_id'] ?? 0);
            $item_distributed_qty = (int)($dist_items_by_id[$item_id]['quantity'] ?? 0);
            if ($item_distributed_qty >= $item_qty) {
                $item_types_fully_distributed++;
            }
        }
        mysqli_stmt_close($goods_item_stmt);

        if ($distributed_amount > 0) {
            $all_item_types_fully_distributed = $item_types_total > 0 && $item_types_fully_distributed >= $item_types_total;
            $donations[$i]['distribution_status_label'] = $all_item_types_fully_distributed ? 'Fully Distributed' : 'Partially Distributed';
            $donations[$i]['distribution_status_class'] = $all_item_types_fully_distributed ? 'bg-success' : 'bg-warning text-dark';
            $donations[$i]['distribution_detail'] = $distributed_units . ' of ' . $total_units . ' units distributed';

            if ($item_types_total > 0) {
                $donations[$i]['distribution_progress_detail'] = $item_types_fully_distributed . ' of ' . $item_types_total . ' item types fully distributed';
            }

            if (!empty($dist_info['goods_items_by_desc']) && is_array($dist_info['goods_items_by_desc'])) {
                $item_parts = [];
                foreach ($dist_info['goods_items_by_desc'] as $item_desc => $item_qty) {
                    $item_parts[] = $item_desc . ' x' . (int)$item_qty;
                }
                $items_text = implode('; ', $item_parts);
                if (strlen($items_text) > 120) {
                    $items_text = substr($items_text, 0, 117) . '...';
                }
                $donations[$i]['distribution_items_detail'] = $items_text;
            }
        }
    }
}

$total_funds = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM monetary_details"))['total'] ?? 0;
$total_goods = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity) as total FROM donation_items"))['total'] ?? 0;

$can_edit       = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin','donation_manager']);
$can_delete     = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$can_distribute = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin','distribution_manager']);
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>

    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark">
                <i class="bi bi-gift me-2"></i> Donations
            </h2>
            <p class="text-muted">Manage and track all donations</p>
        </div>
        <div>
            <a href="addDonation.php" class="btn fw-semibold" style="background-color:#FFD700; color:#2B547E;">
                <i class="bi bi-plus-circle me-2"></i> Add Donation
            </a>
            <a href="exportDonationsCSV.php<?= htmlspecialchars($report_query); ?>" class="btn btn-outline-secondary fw-semibold">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
            </a>
            <a href="printDonations.php<?= htmlspecialchars($report_query); ?>" class="btn btn-outline-secondary fw-semibold" target="_blank">
                <i class="bi bi-printer me-1"></i> Print Report
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="fw-semibold text-muted">Total Funds Donated</h6>
                    <h3 class="fw-bold text-dark">$<?= number_format($total_funds, 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="fw-semibold text-muted">Total Goods Items</h6>
                    <h3 class="fw-bold text-dark"><?= $total_goods; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold text-muted">Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-0"
                               placeholder="Search donor or description"
                               value="<?= htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold text-muted">Type</label>
                    <select name="type" class="form-select bg-light border-0">
                        <option value="">All Types</option>
                        <option value="funds" <?= $filter_type === 'funds' ? 'selected' : ''; ?>>Funds</option>
                        <option value="goods" <?= $filter_type === 'goods' ? 'selected' : ''; ?>>Goods</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold text-muted">From</label>
                    <input type="date" name="date_from" class="form-control bg-light border-0"
                           value="<?= htmlspecialchars($filter_date_from); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold text-muted">To</label>
                    <input type="date" name="date_to" class="form-control bg-light border-0"
                           value="<?= htmlspecialchars($filter_date_to); ?>">
                </div>
                <div class="col-md-3 d-flex gap-2 align-items-end">
                    <button type="submit" class="btn flex-grow-1 fw-semibold" style="background-color:#2B547E; color:#FFD700;">
                        Filter
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary fw-semibold">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Counter -->
    <div class="mb-3">
        <small class="text-muted">Found <strong><?= count($donations); ?></strong> donation<?= count($donations) !== 1 ? 's' : ''; ?></small>
    </div>

    <!-- Donations Table -->
    <?php if (!empty($donations)): ?>
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="fw-semibold">Donor Image</th>
                            <th><a href="?sort=donor_name" class="text-decoration-none text-dark fw-semibold">Donor Name</a></th>
                            <th><a href="?sort=donation_type" class="text-decoration-none text-dark fw-semibold">Type</a></th>
                            <th><a href="?sort=amount" class="text-decoration-none text-dark fw-semibold">Amount (Monetary) / Description (In Goods Items)</a></th>
                            <th><a href="?sort=date_received" class="text-decoration-none text-dark fw-semibold">Date Received</a></th>
                            <th class="fw-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donations as $donation): ?>
                            <tr>
                                <td>
                                    <?php $initial = strtoupper(substr($donation['donor_name'] ?: 'A', 0, 1)); ?>
                                    <?php if (!empty($donation['donor_photo'])): ?>
                                        <img src="<?= htmlspecialchars($donation['donor_photo']); ?>"
                                             alt="<?= htmlspecialchars($donation['donor_name']); ?>"
                                             class="rounded-circle border"
                                             style="width:44px;height:44px;object-fit:cover;">
                                    <?php else: ?>
                                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center border bg-light text-secondary fw-semibold"
                                              style="width:44px;height:44px;">
                                            <?= htmlspecialchars($initial); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold"><?= htmlspecialchars($donation['donor_name']); ?></td>
                                <td>
                                    <?php if ($donation['donation_type'] === 'funds'): ?>
                                        <span class="badge bg-success">Funds</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Goods</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($donation['amount_or_description'] ?? 'N/A'); ?></div>
                                    <div class="mt-2">
                                        <span class="badge <?= htmlspecialchars($donation['distribution_status_class']); ?>">
                                            <?= htmlspecialchars($donation['distribution_status_label']); ?>
                                        </span>
                                        <div><small class="text-muted"><?= htmlspecialchars($donation['distribution_detail']); ?></small></div>
                                        <?php if (!empty($donation['distribution_progress_detail'])): ?>
                                            <div><small class="text-muted"><?= htmlspecialchars($donation['distribution_progress_detail']); ?></small></div>
                                        <?php endif; ?>
                                        <?php if (!empty($donation['distribution_items_detail'])): ?>
                                            <div><small class="text-muted"><?= htmlspecialchars($donation['distribution_items_detail']); ?></small></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><small class="text-muted"><?= date('M d, Y', strtotime($donation['date_received'])); ?></small></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="viewDonation.php?id=<?= $donation['donation_id']; ?>" 
                                           class="btn btn-sm fw-semibold" style="background-color:#2B547E; color:#FFD700;" title="View">
                                           <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($can_edit): ?>
                                            <a href="editDonation.php?id=<?= $donation['donation_id']; ?>" 
                                               class="btn btn-sm fw-semibold" style="background-color:#0dcaf0; color:#fff;" title="Edit">
                                               <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($can_delete): ?>
                                            <a href="deleteDonation.php?id=<?= $donation['donation_id']; ?>" 
                                               class="btn btn-sm fw-semibold" style="background-color:#dc3545; color:#fff;" 
                                               onclick="return confirm('Delete this donation?');" title="Delete">
                                               <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($can_distribute): ?>
                                            <a href="distributeDonation.php?id=<?= $donation['donation_id']; ?>" 
                                               class="btn btn-sm fw-semibold" style="background-color:#198754; color:#fff;" title="Distribute">
                                               <i class="bi bi-arrow-right-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color:#ccc;"></i>
                <p class="text-muted mt-4 mb-0">No donations found. Try adjusting your filters or add a new one.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include("../../includes/footer.php"); ?>
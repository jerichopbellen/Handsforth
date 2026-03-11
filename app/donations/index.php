<?php
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
$allowedSort      = ['donor_name', 'donation_type', 'amount', 'date_received'];

$sql = "SELECT donation_id, donation_type, amount, description, date_received, donor_id 
        FROM donations WHERE 1=1";
$params = [];
$types  = '';

if ($filter_type) {
    $sql .= " AND donation_type = ?";
    $params[] = $filter_type;
    $types .= 's';
}
if ($search) {
    $sql .= " AND (description LIKE ?)";
    $params[] = "%$search%";
    $types .= 's';
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

$sql .= in_array($sort, $allowedSort) ? " ORDER BY $sort DESC" : " ORDER BY date_received DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$donations = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// enrich with donor names and funds
foreach ($donations as $i => $donation) {
    if ($donation['donation_type'] === 'funds') {
        $stmt = mysqli_prepare($conn, "SELECT amount FROM monetary_details WHERE donation_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $donation['donation_id']);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        $donations[$i]['amount'] = $row['amount'] ?? 0;
        mysqli_stmt_close($stmt);
    }
    if (!empty($donation['donor_id'])) {
        $donor_stmt = mysqli_prepare($conn, "SELECT name FROM donors WHERE donor_id = ?");
        mysqli_stmt_bind_param($donor_stmt, 'i', $donation['donor_id']);
        mysqli_stmt_execute($donor_stmt);
        $donor_res = mysqli_stmt_get_result($donor_stmt);
        $donor = mysqli_fetch_assoc($donor_res);
        $donations[$i]['donor_name'] = $donor['name'] ?? '';
        mysqli_stmt_close($donor_stmt);
    } else {
        $donations[$i]['donor_name'] = '';
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
            <a href="exportDonationsCSV.php" class="btn btn-outline-secondary fw-semibold">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
            </a>
            <a href="printDonations.php" class="btn btn-outline-secondary fw-semibold" target="_blank">
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
                            <th><a href="?sort=donor_name" class="text-decoration-none text-dark fw-semibold">Donor Name</a></th>
                            <th><a href="?sort=donation_type" class="text-decoration-none text-dark fw-semibold">Type</a></th>
                            <th><a href="?sort=amount" class="text-decoration-none text-dark fw-semibold">Amount/Description</a></th>
                            <th><a href="?sort=date_received" class="text-decoration-none text-dark fw-semibold">Date Received</a></th>
                            <th class="fw-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donations as $donation): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($donation['donor_name']); ?></td>
                                <td>
                                    <?php if ($donation['donation_type'] === 'funds'): ?>
                                        <span class="badge bg-success">Funds</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Goods</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($donation['donation_type'] === 'funds') {
                                        echo '$' . htmlspecialchars($donation['amount']);
                                    } else {
                                        echo htmlspecialchars($donation['description'] ?? 'N/A');
                                    }
                                    ?>
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
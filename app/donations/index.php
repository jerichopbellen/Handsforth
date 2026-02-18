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

$total_funds_sql = "SELECT SUM(amount) as total FROM donations WHERE donation_type = 'funds'";
$total_funds_result = mysqli_query($conn, $total_funds_sql);
$total_funds = mysqli_fetch_assoc($total_funds_result)['total'] ?? 0;

$total_goods_sql = "SELECT COUNT(*) as total FROM donations WHERE donation_type = 'goods'";
$total_goods_result = mysqli_query($conn, $total_goods_sql);
$total_goods = mysqli_fetch_assoc($total_goods_result)['total'] ?? 0;
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-gift me-2"></i>Donation Management</h2>
        <div>
            <a href="addDonation.php" class="btn btn-success me-2"><i class="bi bi-plus-circle me-1"></i>Add Donation</a>
            <a href="exportDonationsCSV.php" class="btn btn-outline-primary me-2"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV</a>
            <a href="printDonations.php" class="btn btn-outline-secondary" target="_blank"><i class="bi bi-printer me-1"></i>Print Report</a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Total Funds Donated</h5>
                    <h2>$<?php echo number_format($total_funds, 2); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Total Goods Items</h5>
                    <h2><?php echo $total_goods; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search donor or description" 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="funds" <?php echo $filter_type === 'funds' ? 'selected' : ''; ?>>Funds</option>
                        <option value="goods" <?php echo $filter_type === 'goods' ? 'selected' : ''; ?>>Goods</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Donor Name</th>
                    <th>Type</th>
                    <th>Amount/Description</th>
                    <th>Date Received</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($donations)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No donations found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($donations as $donation): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($donation['donor_name'] ?? 'Anonymous'); ?></td>
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
                            <td><?php echo htmlspecialchars($donation['date_received']); ?></td>
                            <td>
                                <a href="viewDonation.php?id=<?php echo $donation['donation_id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="editDonation.php?id=<?php echo $donation['donation_id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="deleteDonation.php?id=<?php echo $donation['donation_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <a href="distributeDonation.php?id=<?php echo $donation['donation_id']; ?>" class="btn btn-sm btn-primary" title="Distribute">
                                    <i class="bi bi-arrow-right-circle"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>
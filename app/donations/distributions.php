<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

$filter_project = isset($_GET['project']) ? intval($_GET['project']) : '';

$sql = "SELECT rd.distribution_id, rd.date_distributed, rd.quantity_distributed, 
               d.donor_name, d.donation_type, d.amount, d.description,
               p.title as project_title
        FROM resource_distribution rd
        JOIN donations d ON rd.donation_id = d.donation_id
        JOIN projects p ON rd.project_id = p.project_id
        WHERE 1=1";

$params = array();
$types = '';

if ($filter_project) {
    $sql .= " AND rd.project_id = ?";
    $params[] = $filter_project;
    $types .= 'i';
}

$sql .= " ORDER BY rd.date_distributed DESC";

$stmt = mysqli_prepare($conn, $sql);

if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$distributions = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$projects_sql = "SELECT DISTINCT p.project_id, p.title FROM projects p 
                 JOIN resource_distribution rd ON p.project_id = rd.project_id 
                 ORDER BY p.title";
$projects_result = mysqli_query($conn, $projects_sql);
$projects = mysqli_fetch_all($projects_result, MYSQLI_ASSOC);

$summary_sql = "SELECT p.project_id, p.title, 
                       COUNT(rd.distribution_id) as total_distributions,
                       SUM(CASE WHEN d.donation_type = 'funds' THEN rd.quantity_distributed ELSE 0 END) as total_funds,
                       COUNT(CASE WHEN d.donation_type = 'goods' THEN 1 END) as goods_count
                FROM projects p
                LEFT JOIN resource_distribution rd ON p.project_id = rd.project_id
                LEFT JOIN donations d ON rd.donation_id = d.donation_id
                GROUP BY p.project_id, p.title
                ORDER BY p.title";
$summary_result = mysqli_query($conn, $summary_sql);
$summary = mysqli_fetch_all($summary_result, MYSQLI_ASSOC);
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-truck me-2"></i>Resource Distribution Summary</h2>
        <a href="index.php" class="btn btn-primary"><i class="bi bi-gift me-1"></i>View Donations</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <select name="project" class="form-select">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['project_id']; ?>" 
                                    <?php echo $filter_project == $project['project_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($project['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>
    </div>

    <h4 class="mt-4 mb-3">Distribution by Project</h4>
    <div class="row mb-4">
        <?php foreach ($summary as $proj): ?>
            <?php if ($proj['total_distributions'] > 0): ?>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($proj['title']); ?></h5>
                            <p class="mb-2">
                                <strong>Total Distributions:</strong> <?php echo $proj['total_distributions']; ?>
                            </p>
                            <p class="mb-2">
                                <strong>Funds Distributed:</strong> $<?php echo number_format($proj['total_funds'] ?? 0, 2); ?>
                            </p>
                            <p class="mb-0">
                                <strong>Goods Items:</strong> <?php echo $proj['goods_count'] ?? 0; ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <h4 class="mb-3">Distribution Details</h4>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Project</th>
                    <th>Donor</th>
                    <th>Type</th>
                    <th>Quantity Distributed</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($distributions)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No distributions found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($distributions as $dist): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($dist['project_title']); ?></td>
                            <td><?php echo htmlspecialchars($dist['donor_name'] ?? 'Anonymous'); ?></td>
                            <td>
                                <?php if ($dist['donation_type'] === 'funds'): ?>
                                    <span class="badge bg-success">Funds</span>
                                <?php else: ?>
                                    <span class="badge bg-info">Goods</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if ($dist['donation_type'] === 'funds') {
                                    echo '$' . htmlspecialchars(number_format($dist['quantity_distributed'], 2));
                                } else {
                                    echo htmlspecialchars($dist['quantity_distributed']) . ' units';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($dist['date_distributed']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>
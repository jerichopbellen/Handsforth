<?php
ob_start();
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

$filter_project = isset($_GET['project']) ? intval($_GET['project']) : '';


$sql = "SELECT dists.distribution_id, dists.distributed_date, dists.distributed_amount, dists.notes,
               donors.name AS donor_name, dn.donation_type, dn.amount, dn.description,
               p.title as project_title
        FROM distributions dists
        JOIN donations dn ON dists.donation_id = dn.donation_id
        LEFT JOIN donors ON dn.donor_id = donors.donor_id
        JOIN projects p ON dists.project_id = p.project_id
        WHERE 1=1";

$params = [];
if ($filter_project) {
    $sql .= " AND dists.project_id = ?";
    $params[] = $filter_project;
}
$sql .= " ORDER BY dists.distributed_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$distributions = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($distributions as $idx => $dist) {
    $details = 'N/A';
    if (($dist['donation_type'] ?? '') === 'goods') {
        $notes = json_decode((string)($dist['notes'] ?? ''), true);
        if (is_array($notes) && ($notes['type'] ?? '') === 'goods_distribution' && !empty($notes['items']) && is_array($notes['items'])) {
            $parts = [];
            foreach ($notes['items'] as $item) {
                $desc = trim((string)($item['description'] ?? ''));
                $qty = isset($item['quantity']) ? (int)$item['quantity'] : 0;
                if ($qty <= 0) {
                    continue;
                }
                if ($desc === '') {
                    $desc = 'Item';
                }
                $parts[] = $desc . ' x' . $qty;
            }
            if (!empty($parts)) {
                $details = implode('; ', $parts);
            }
        }
    }
    $distributions[$idx]['distribution_details'] = $details;
}


$projects = $pdo->query("SELECT DISTINCT p.project_id, p.title FROM projects p JOIN distributions dists ON p.project_id = dists.project_id ORDER BY p.title")->fetchAll(PDO::FETCH_ASSOC);


$summary_sql = "SELECT p.project_id, p.title, 
              COUNT(dists.distribution_id) as total_distributions,
              SUM(CASE WHEN dn.donation_type = 'funds' THEN dists.distributed_amount ELSE 0 END) as total_funds,
              SUM(CASE WHEN dn.donation_type = 'goods' THEN dists.distributed_amount ELSE 0 END) as goods_units
          FROM projects p
          LEFT JOIN distributions dists ON p.project_id = dists.project_id
          LEFT JOIN donations dn ON dists.donation_id = dn.donation_id
          GROUP BY p.project_id, p.title
          ORDER BY p.title";
$summary = $pdo->query($summary_sql)->fetchAll(PDO::FETCH_ASSOC);
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
                                <strong>Goods Items:</strong> <?php echo number_format($proj['goods_units'] ?? 0, 0); ?>
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
                    <th>Details</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($distributions)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No distributions found</td>
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
                                    echo '$' . htmlspecialchars(number_format($dist['distributed_amount'], 2));
                                } else {
                                    echo htmlspecialchars($dist['distributed_amount']) . ' units';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($dist['distribution_details'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dist['distributed_date']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>
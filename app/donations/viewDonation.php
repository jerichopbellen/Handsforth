<?php
ob_start();
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

$donation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$donation_id) {
    $_SESSION['error'] = 'Invalid donation ID';
    header('Location: index.php');
    exit();
}

$donation_stmt = $pdo->prepare(
    'SELECT d.*, donors.name AS donor_name, donors.email AS donor_email, donors.phone AS donor_phone
     FROM donations d
     LEFT JOIN donors ON d.donor_id = donors.donor_id
     WHERE d.donation_id = ?'
);
$donation_stmt->execute([$donation_id]);
$donation = $donation_stmt->fetch(PDO::FETCH_ASSOC);

if (!$donation) {
    $_SESSION['error'] = 'Donation not found';
    header('Location: index.php');
    exit();
}

$monetary_details = null;
if ($donation['donation_type'] === 'funds') {
    $monetary_stmt = $pdo->prepare('SELECT amount, payment_method, check_number, designation, recurring FROM monetary_details WHERE donation_id = ? LIMIT 1');
    $monetary_stmt->execute([$donation_id]);
    $monetary_details = $monetary_stmt->fetch(PDO::FETCH_ASSOC);
}

$goods_items = [];
if ($donation['donation_type'] === 'goods') {
    $goods_stmt = $pdo->prepare(
        'SELECT category, description, item_condition, quantity, unit, estimated_value
         FROM donation_items
         WHERE donation_id = ?
         ORDER BY item_id ASC'
    );
    $goods_stmt->execute([$donation_id]);
    $goods_items = $goods_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$staff_name = 'N/A';
if (!empty($donation['staff_id'])) {
    $staff_stmt = $pdo->prepare('SELECT first_name, last_name FROM users WHERE user_id = ?');
    $staff_stmt->execute([$donation['staff_id']]);
    $staff = $staff_stmt->fetch(PDO::FETCH_ASSOC);
    if ($staff) {
        $staff_name = trim(($staff['first_name'] ?? '') . ' ' . ($staff['last_name'] ?? ''));
    }
}

$distributions = [];
$dist_stmt = $pdo->prepare(
    'SELECT dists.*, p.title AS project_title
     FROM distributions dists
     LEFT JOIN projects p ON dists.project_id = p.project_id
     WHERE dists.donation_id = ?
     ORDER BY dists.distribution_id DESC'
);
$dist_stmt->execute([$donation_id]);
$distributions = $dist_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($distributions as $idx => $dist) {
    $distributions[$idx]['goods_items_summary'] = '';

    if ($donation['donation_type'] !== 'goods') {
        continue;
    }

    $notes = json_decode((string)($dist['notes'] ?? ''), true);
    if (!is_array($notes) || ($notes['type'] ?? '') !== 'goods_distribution') {
        continue;
    }

    $items = $notes['items'] ?? [];
    if (!is_array($items) || empty($items)) {
        continue;
    }

    $parts = [];
    foreach ($items as $item) {
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
        $distributions[$idx]['goods_items_summary'] = implode('; ', $parts);
    }
}
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-eye me-2"></i>Donation Details</h2>
        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Donor: <?php echo htmlspecialchars($donation['donor_name'] ?? 'Anonymous'); ?></h5>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($donation['donor_email'] ?? 'N/A'); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($donation['donor_phone'] ?? 'N/A'); ?></p>
            <p><strong>Type:</strong> <?php echo htmlspecialchars(ucfirst($donation['donation_type'])); ?></p>
            <p><strong>Date Received:</strong> <?php echo htmlspecialchars($donation['date_received']); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($donation['description'] ?? 'N/A'); ?></p>

            <?php if ($donation['donation_type'] === 'funds'): ?>
                <?php $fund_amount = $monetary_details['amount'] ?? $donation['amount'] ?? 0; ?>
                <p><strong>Amount:</strong> $<?php echo number_format((float)$fund_amount, 2); ?></p>
                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($monetary_details['payment_method'] ?? 'N/A'); ?></p>
                <p><strong>Reference Number:</strong> <?php echo htmlspecialchars($monetary_details['check_number'] ?? 'N/A'); ?></p>
                <p><strong>Designation:</strong> <?php echo htmlspecialchars($monetary_details['designation'] ?? 'N/A'); ?></p>
                <p><strong>Recurring:</strong> <?php echo !empty($monetary_details['recurring']) ? 'Yes' : 'No'; ?></p>
            <?php endif; ?>

            <p><strong>Staff:</strong> <?php echo htmlspecialchars($staff_name); ?></p>
            <?php if (!empty($donation['receipt_file'])): ?>
                <p><strong>Receipt:</strong> <a href="<?php echo htmlspecialchars($donation['receipt_file']); ?>" target="_blank">Download</a></p>
            <?php endif; ?>
            <p><strong>Transaction Number:</strong> <?php echo htmlspecialchars($donation['txn_number'] ?? 'N/A'); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($donation['status'] ?? 'N/A'); ?></p>
            <p><strong>Created At:</strong> <?php echo htmlspecialchars($donation['created_at'] ?? 'N/A'); ?></p>
            <p><strong>Updated At:</strong> <?php echo htmlspecialchars($donation['updated_at'] ?? 'N/A'); ?></p>
        </div>
    </div>

    <?php if ($donation['donation_type'] === 'goods'): ?>
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Goods Items</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Quantity</th>
                                <th>Condition</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th>Estimated Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($goods_items)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No goods items found for this donation.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($goods_items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['description'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($item['quantity'] ?? '0'); ?></td>
                                        <td><?php echo htmlspecialchars($item['item_condition'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($item['unit'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php
                                            if ($item['estimated_value'] !== null && $item['estimated_value'] !== '') {
                                                echo '$' . number_format((float)$item['estimated_value'], 2);
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">Distribution History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Amount Distributed</th>
                            <th><?php echo $donation['donation_type'] === 'goods' ? 'Items Distributed' : 'Details'; ?></th>
                            <th>Date Distributed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($distributions)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No distributions found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($distributions as $dist): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($dist['project_title'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php
                                        if ($dist['distributed_amount'] !== null && $dist['distributed_amount'] !== '') {
                                            if ($donation['donation_type'] === 'funds') {
                                                echo '$' . number_format((float)$dist['distributed_amount'], 2);
                                            } else {
                                                echo number_format((float)$dist['distributed_amount'], 0) . ' units';
                                            }
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($donation['donation_type'] === 'goods') {
                                            echo htmlspecialchars($dist['goods_items_summary'] !== '' ? $dist['goods_items_summary'] : 'N/A');
                                        } else {
                                            echo htmlspecialchars($dist['notes'] ?? 'N/A');
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($dist['distributed_date'] ?? $dist['date_distributed'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>

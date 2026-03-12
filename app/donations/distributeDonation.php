<?php
ob_start();
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

$donation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$donation_id) {
    $_SESSION['error'] = 'Invalid donation ID';
    header('Location: index.php');
    exit();
}

$donation_stmt = $pdo->prepare(
    'SELECT d.*, donors.name AS donor_name
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

$projects = $pdo->query("SELECT project_id, title FROM projects WHERE status IN ('planned', 'ongoing') ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

$goods_items = [];
$item_remaining_map = [];
$remaining_funds = 0.0;

if ($donation['donation_type'] === 'funds') {
    $remaining_stmt = $pdo->prepare('SELECT COALESCE(SUM(distributed_amount), 0) FROM distributions WHERE donation_id = ?');
    $remaining_stmt->execute([$donation_id]);
    $already_distributed = (float)$remaining_stmt->fetchColumn();
    $remaining_funds = max(0.0, (float)($donation['amount'] ?? 0) - $already_distributed);
} else {
    $items_stmt = $pdo->prepare(
        'SELECT item_id, description, quantity, item_condition, category, unit
         FROM donation_items
         WHERE donation_id = ?
         ORDER BY item_id ASC'
    );
    $items_stmt->execute([$donation_id]);
    $goods_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

    $existing_dist_stmt = $pdo->prepare('SELECT notes FROM distributions WHERE donation_id = ? ORDER BY distribution_id ASC');
    $existing_dist_stmt->execute([$donation_id]);
    $existing_distributions = $existing_dist_stmt->fetchAll(PDO::FETCH_ASSOC);

    $distributed_per_item = [];
    foreach ($existing_distributions as $dist) {
        $notes = json_decode((string)($dist['notes'] ?? ''), true);
        if (!is_array($notes) || ($notes['type'] ?? '') !== 'goods_distribution') {
            continue;
        }

        $items = $notes['items'] ?? [];
        if (!is_array($items)) {
            continue;
        }

        foreach ($items as $item) {
            $item_id = isset($item['item_id']) ? (int)$item['item_id'] : 0;
            $qty = isset($item['quantity']) ? (int)$item['quantity'] : 0;
            if ($item_id > 0 && $qty > 0) {
                $distributed_per_item[$item_id] = ($distributed_per_item[$item_id] ?? 0) + $qty;
            }
        }
    }

    foreach ($goods_items as $idx => $item) {
        $item_id = (int)$item['item_id'];
        $donated_qty = (int)($item['quantity'] ?? 0);
        $already_distributed_qty = (int)($distributed_per_item[$item_id] ?? 0);
        $remaining_qty = max(0, $donated_qty - $already_distributed_qty);

        $goods_items[$idx]['already_distributed_qty'] = $already_distributed_qty;
        $goods_items[$idx]['remaining_qty'] = $remaining_qty;
        $item_remaining_map[$item_id] = $remaining_qty;
    }
}

if (isset($_POST['submit'])) {
    $project_id = intval($_POST['project_id'] ?? 0);
    $distribution_date = trim($_POST['date_distributed'] ?? '');

    if (!$project_id) {
        $_SESSION['error'] = 'Project is required';
        header("Location: distributeDonation.php?id=$donation_id");
        exit();
    }

    if (empty($distribution_date) || !strtotime($distribution_date)) {
        $_SESSION['error'] = 'Valid distribution date is required';
        header("Location: distributeDonation.php?id=$donation_id");
        exit();
    }

    try {
        if ($donation['donation_type'] === 'funds') {
            $distribution_value = (float)($_POST['quantity_distributed'] ?? 0);
            if ($distribution_value <= 0) {
                throw new Exception('Amount to distribute must be greater than 0.');
            }

            $remaining_stmt = $pdo->prepare('SELECT COALESCE(SUM(distributed_amount), 0) FROM distributions WHERE donation_id = ?');
            $remaining_stmt->execute([$donation_id]);
            $already_distributed = (float)$remaining_stmt->fetchColumn();
            $remaining = max(0.0, (float)($donation['amount'] ?? 0) - $already_distributed);

            if ($distribution_value > $remaining) {
                throw new Exception('Distribution amount cannot exceed remaining donation amount ($' . number_format($remaining, 2) . ').');
            }

            $insert_stmt = $pdo->prepare(
                'INSERT INTO distributions (project_id, donation_id, distributed_amount, distributed_date, notes) VALUES (?, ?, ?, ?, ?)'
            );
            $insert_stmt->execute([$project_id, $donation_id, $distribution_value, $distribution_date, null]);
        } else {
            if (empty($goods_items)) {
                throw new Exception('No goods items available for this donation.');
            }

            $item_quantities = $_POST['item_quantities'] ?? [];
            if (!is_array($item_quantities)) {
                $item_quantities = [];
            }

            $selected_items = [];
            $total_units = 0;

            foreach ($goods_items as $item) {
                $item_id = (int)$item['item_id'];
                $requested_qty = isset($item_quantities[$item_id]) ? (int)$item_quantities[$item_id] : 0;
                if ($requested_qty < 0) {
                    throw new Exception('Invalid quantity for item: ' . ($item['description'] ?? ('#' . $item_id)));
                }

                if ($requested_qty === 0) {
                    continue;
                }

                $remaining_qty = (int)($item_remaining_map[$item_id] ?? 0);
                if ($requested_qty > $remaining_qty) {
                    throw new Exception('Requested quantity for "' . ($item['description'] ?? ('Item #' . $item_id)) . '" exceeds remaining stock (' . $remaining_qty . ').');
                }

                $selected_items[] = [
                    'item_id' => $item_id,
                    'description' => (string)($item['description'] ?? ''),
                    'quantity' => $requested_qty,
                    'unit' => (string)($item['unit'] ?? ''),
                ];

                $total_units += $requested_qty;
            }

            if ($total_units <= 0) {
                throw new Exception('Select at least one goods item quantity to distribute.');
            }

            $notes_payload = json_encode([
                'type' => 'goods_distribution',
                'items' => $selected_items,
            ], JSON_UNESCAPED_SLASHES);

            $insert_stmt = $pdo->prepare(
                'INSERT INTO distributions (project_id, donation_id, distributed_amount, distributed_date, notes) VALUES (?, ?, ?, ?, ?)'
            );
            $insert_stmt->execute([$project_id, $donation_id, $total_units, $distribution_date, $notes_payload]);
        }

        $_SESSION['success'] = 'Resource distributed successfully';
        header('Location: viewDonation.php?id=' . $donation_id);
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to distribute resource: ' . $e->getMessage();
        header('Location: distributeDonation.php?id=' . $donation_id);
        exit();
    }
}
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>

    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-9">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-arrow-right-circle me-2"></i>Distribute Resource</h4>
                </div>
                <div class="card-body">
                    <div class="bg-light p-3 rounded mb-4">
                        <h6 class="mb-2">Donation Summary</h6>
                        <p class="mb-1"><strong>Donor:</strong> <?php echo htmlspecialchars($donation['donor_name'] ?? 'Anonymous'); ?></p>
                        <p class="mb-1"><strong>Type:</strong> <?php echo htmlspecialchars(ucfirst($donation['donation_type'])); ?></p>
                        <?php if ($donation['donation_type'] === 'funds'): ?>
                            <p class="mb-0"><strong>Remaining Funds:</strong> $<?php echo number_format($remaining_funds, 2); ?></p>
                        <?php else: ?>
                            <p class="mb-0"><strong>Description:</strong> <?php echo htmlspecialchars($donation['description'] ?? 'N/A'); ?></p>
                        <?php endif; ?>
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                            <select class="form-select" id="project_id" name="project_id" required>
                                <option value="">Select Project</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?php echo $project['project_id']; ?>"><?php echo htmlspecialchars($project['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if ($donation['donation_type'] === 'funds'): ?>
                            <div class="mb-3">
                                <label for="quantity_distributed" class="form-label">Amount to Distribute ($) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="quantity_distributed" name="quantity_distributed" min="0.01" step="0.01" required>
                                <small class="text-muted">Maximum: $<?php echo number_format($remaining_funds, 2); ?></small>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <label class="form-label">Select Goods and Quantities</label>
                                <div class="table-responsive border rounded">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Item</th>
                                                <th>Donated</th>
                                                <th>Distributed</th>
                                                <th>Remaining</th>
                                                <th>Distribute Qty</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($goods_items)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-3">No goods items available for this donation.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($goods_items as $item): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="fw-semibold"><?php echo htmlspecialchars($item['description'] ?? 'N/A'); ?></div>
                                                            <small class="text-muted">
                                                                <?php echo htmlspecialchars($item['category'] ?? ''); ?>
                                                                <?php if (!empty($item['item_condition'])): ?>
                                                                    | <?php echo htmlspecialchars($item['item_condition']); ?>
                                                                <?php endif; ?>
                                                            </small>
                                                        </td>
                                                        <td><?php echo (int)($item['quantity'] ?? 0); ?></td>
                                                        <td><?php echo (int)($item['already_distributed_qty'] ?? 0); ?></td>
                                                        <td><?php echo (int)($item['remaining_qty'] ?? 0); ?></td>
                                                        <td style="max-width:120px;">
                                                            <input type="number"
                                                                   class="form-control form-control-sm goods-qty-input"
                                                                   name="item_quantities[<?php echo (int)$item['item_id']; ?>]"
                                                                   min="0"
                                                                   max="<?php echo (int)($item['remaining_qty'] ?? 0); ?>"
                                                                   step="1"
                                                                   value="0"
                                                                   <?php echo ((int)($item['remaining_qty'] ?? 0) <= 0) ? 'disabled' : ''; ?>>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <small class="text-muted d-block mt-2">Total selected units: <span id="selected_units_total">0</span></small>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="date_distributed" class="form-label">Distribution Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_distributed" name="date_distributed" required value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Distribute</button>
                            <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const qtyInputs = document.querySelectorAll('.goods-qty-input');
    const totalEl = document.getElementById('selected_units_total');
    if (!qtyInputs.length || !totalEl) {
        return;
    }

    function updateTotal() {
        let total = 0;
        qtyInputs.forEach((input) => {
            const value = parseInt(input.value || '0', 10);
            const max = parseInt(input.max || '0', 10);
            let safeValue = isNaN(value) ? 0 : value;
            if (safeValue < 0) {
                safeValue = 0;
            }
            if (!isNaN(max) && safeValue > max) {
                safeValue = max;
            }
            input.value = safeValue;
            total += safeValue;
        });
        totalEl.textContent = total;
    }

    qtyInputs.forEach((input) => {
        input.addEventListener('input', updateTotal);
    });

    updateTotal();
})();
</script>

<?php include("../../includes/footer.php"); ?>

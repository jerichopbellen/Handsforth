<?php
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
    header("Location: index.php");
    exit();
}

$sql = "SELECT * FROM donations WHERE donation_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $donation_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$donation = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$donation) {
    $_SESSION['error'] = 'Donation not found';
    header("Location: index.php");
    exit();
}

$projects_sql = "SELECT project_id, title FROM projects WHERE status IN ('planned', 'ongoing') ORDER BY title";
$projects_result = mysqli_query($conn, $projects_sql);
$projects = mysqli_fetch_all($projects_result, MYSQLI_ASSOC);

if (isset($_POST['submit'])) {
    $project_id = intval($_POST['project_id'] ?? 0);
    $quantity_distributed = intval($_POST['quantity_distributed'] ?? 0);
    $date_distributed = trim($_POST['date_distributed'] ?? '');

    if (!$project_id) {
        $_SESSION['error'] = 'Project is required';
        header("Location: distributeDonation.php?id=$donation_id");
        exit();
    }

    if ($quantity_distributed <= 0) {
        $_SESSION['error'] = 'Quantity must be greater than 0';
        header("Location: distributeDonation.php?id=$donation_id");
        exit();
    }

    if (empty($date_distributed) || !strtotime($date_distributed)) {
        $_SESSION['error'] = 'Valid distribution date is required';
        header("Location: distributeDonation.php?id=$donation_id");
        exit();
    }

    if ($donation['donation_type'] === 'funds' && $quantity_distributed > $donation['amount']) {
        $_SESSION['error'] = 'Distribution amount cannot exceed donation amount';
        header("Location: distributeDonation.php?id=$donation_id");
        exit();
    }

    $insert_sql = "INSERT INTO resource_distribution (project_id, donation_id, quantity_distributed, date_distributed) 
                   VALUES (?, ?, ?, ?)";
    
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    
    if (!$insert_stmt) {
        $_SESSION['error'] = 'Database error: ' . mysqli_error($conn);
        header("Location: distributeDonation.php?id=$donation_id");
        exit();
    }

    mysqli_stmt_bind_param($insert_stmt, 'iiis', $project_id, $donation_id, $quantity_distributed, $date_distributed);

    if (mysqli_stmt_execute($insert_stmt)) {
        $_SESSION['success'] = 'Resource distributed successfully';
        mysqli_stmt_close($insert_stmt);
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = 'Failed to distribute resource: ' . mysqli_stmt_error($insert_stmt);
        mysqli_stmt_close($insert_stmt);
        header("Location: distributeDonation.php?id=$donation_id");
        exit();
    }
}
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>
    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-arrow-right-circle me-2"></i>Distribute Resource</h4>
                </div>
                <div class="card-body">
                    <div class="bg-light p-3 rounded mb-4">
                        <h6 class="mb-3">Donation Details</h6>
                        <p class="mb-1"><strong>Donor:</strong> <?php echo htmlspecialchars($donation['donor_name'] ?? 'Anonymous'); ?></p>
                        <p class="mb-1"><strong>Type:</strong> <?php echo htmlspecialchars($donation['donation_type']); ?></p>
                        <?php if ($donation['donation_type'] === 'funds'): ?>
                            <p class="mb-1"><strong>Amount:</strong> $<?php echo htmlspecialchars(number_format($donation['amount'], 2)); ?></p>
                        <?php else: ?>
                            <p class="mb-1"><strong>Description:</strong> <?php echo htmlspecialchars($donation['description']); ?></p>
                        <?php endif; ?>
                        <p class="mb-0"><strong>Date Received:</strong> <?php echo htmlspecialchars($donation['date_received']); ?></p>
                    </div>

                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $donation_id); ?>" method="POST">
                        <div class="mb-3">
                            <label for="project_id" class="form-label">Select Project <span class="text-danger">*</span></label>
                            <select class="form-select" id="project_id" name="project_id" required>
                                <option value="">-- Choose Project --</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?php echo $project['project_id']; ?>"><?php echo htmlspecialchars($project['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="quantity_distributed" class="form-label">
                                <?php echo $donation['donation_type'] === 'funds' ? 'Amount to Distribute ($)' : 'Quantity to Distribute'; ?> 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="quantity_distributed" name="quantity_distributed" 
                                   step="<?php echo $donation['donation_type'] === 'funds' ? '0.01' : '1'; ?>" 
                                   min="0" required>
                            <?php if ($donation['donation_type'] === 'funds'): ?>
                                <small class="text-muted">Max: $<?php echo htmlspecialchars(number_format($donation['amount'], 2)); ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="date_distributed" class="form-label">Distribution Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_distributed" name="date_distributed" required
                                   value="<?php echo date('Y-m-d'); ?>">
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

<?php include("../../includes/footer.php"); ?>
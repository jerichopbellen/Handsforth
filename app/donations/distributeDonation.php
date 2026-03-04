<?php
// Renamed from dsitributeDonation.php for correct spelling.
// The original logic is included below.

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


$stmt = $pdo->prepare("SELECT * FROM donations WHERE donation_id = ?");
$stmt->execute([$donation_id]);
$donation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$donation) {
    $_SESSION['error'] = 'Donation not found';
    header("Location: index.php");
    exit();
}


$projects = $pdo->query("SELECT project_id, title FROM projects WHERE status IN ('planned', 'ongoing') ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['submit'])) {
    $project_id = intval($_POST['project_id'] ?? 0);
    $quantity_distributed = floatval($_POST['quantity_distributed'] ?? 0);
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

    // For funds, ensure not over-distributed
    if ($donation['donation_type'] === 'funds') {
        $stmt = $pdo->prepare("SELECT SUM(quantity_distributed) FROM distributions WHERE donation_id = ?");
        $stmt->execute([$donation_id]);
        $already_distributed = floatval($stmt->fetchColumn());
        $remaining = floatval($donation['amount']) - $already_distributed;
        if ($quantity_distributed > $remaining) {
            $_SESSION['error'] = 'Distribution amount cannot exceed remaining donation amount ($' . number_format($remaining,2) . ')';
            header("Location: distributeDonation.php?id=$donation_id");
            exit();
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO distributions (project_id, donation_id, quantity_distributed, date_distributed) VALUES (?, ?, ?, ?)");
        $stmt->execute([$project_id, $donation_id, $quantity_distributed, $date_distributed]);
        $_SESSION['success'] = 'Resource distributed successfully';
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to distribute resource: ' . $e->getMessage();
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
                    <form method="POST">
                        <div class="mb-3">
                            <label for="project_id" class="form-label">Project</label>
                            <select class="form-select" id="project_id" name="project_id" required>
                                <option value="">Select Project</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?php echo $project['project_id']; ?>"><?php echo htmlspecialchars($project['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="quantity_distributed" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity_distributed" name="quantity_distributed" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="date_distributed" class="form-label">Date Distributed</label>
                            <input type="date" class="form-control" id="date_distributed" name="date_distributed" required>
                        </div>
                        <button type="submit" name="submit" class="btn btn-primary">Distribute</button>
                        <a href="index.php" class="btn btn-secondary ms-2">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include("../../includes/footer.php"); ?>

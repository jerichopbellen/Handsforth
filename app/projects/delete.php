<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$project_id) {
    $_SESSION['error'] = 'Invalid project ID';
    header("Location: index.php");
    exit();
}

$sql = "SELECT * FROM projects WHERE project_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $project_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$project = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$project) {
    $_SESSION['error'] = 'Project not found';
    header("Location: index.php");
    exit();
}

if (isset($_POST['confirm_delete'])) {
    $delete_sql = "DELETE FROM projects WHERE project_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    
    if (!$delete_stmt) {
        $_SESSION['error'] = 'Database error: ' . mysqli_error($conn);
        header("Location: index.php");
        exit();
    }

    mysqli_stmt_bind_param($delete_stmt, 'i', $project_id);
    if (mysqli_stmt_execute($delete_stmt)) {
        $_SESSION['success'] = 'Project deleted successfully';
        mysqli_stmt_close($delete_stmt);
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = 'Failed to delete project: ' . mysqli_stmt_error($delete_stmt);
        mysqli_stmt_close($delete_stmt);
        header("Location: index.php");
        exit();
    }
}
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>
    
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Delete Project</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">Are you sure you want to delete this project?</p>
                    <div class="bg-light p-3 rounded mb-3">
                        <p><strong>Title:</strong> <?php echo htmlspecialchars($project['title'] ?? 'Untitled'); ?></p>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($project['description']); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($project['date']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($project['location']); ?></p>
                    </div>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $project_id); ?>" method="POST">
                        <div class="d-flex gap-2">
                            <button type="submit" name="confirm_delete" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Delete</button>
                            <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>
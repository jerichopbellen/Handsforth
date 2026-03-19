<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

// 2. Get Parameters - We now use the pair to identify the record
$volunteer_id = isset($_GET['volunteer_id']) ? intval($_GET['volunteer_id']) : 0;
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

if ($volunteer_id <= 0 || $project_id <= 0) {
    header("Location: ../projects/index.php?error=Invalid Request");
    exit();
}

$error = "";

// 3. Handle the Actual Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // UPDATED: We delete where BOTH project and volunteer match
    $sql = "UPDATE project_volunteers SET is_deleted = 1 WHERE project_id = ? AND volunteer_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $project_id, $volunteer_id);
    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../projects/view.php?id=" . $project_id . "&msg=Volunteer removed successfully");
        exit();
    } else {
        $error = "Error removing volunteer: " . mysqli_error($conn);
    }
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header text-white" style="background-color:#2B547E;">
                    <h4 class="mb-0" style="color:#FFD700;">
                        <i class="bi bi-trash-fill me-2"></i>Remove Volunteer Assignment
                    </h4>
                </div>
                
                <div class="card-body text-center py-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <i class="bi bi-exclamation-octagon text-danger mb-3" style="font-size: 3rem;"></i>
                    <p class="fs-5">Are you sure you want to remove this volunteer from the project?</p>
                    <p class="text-muted small">This action will remove their project assignment and associated attendance records.</p>

                    <form method="POST" class="mt-4">
                        <input type="hidden" name="confirm_delete" value="1">
                        
                        <div class="d-flex justify-content-center gap-3">
                            <a href="../projects/view.php?id=<?php echo $project_id; ?>" 
                               class="btn fw-semibold px-4" 
                               style="background-color:#FFD700; color:#2B547E;">
                                <i class="bi bi-arrow-left me-1"></i>Cancel
                            </a>

                            <button type="submit" class="btn fw-semibold px-4" 
                                    style="background-color:#2B547E; color:#FFD700;">
                                <i class="bi bi-trash me-1"></i>Confirm Delete
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>
<?php
session_start();
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

// Check if assignment ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php?error=No assignment selected");
    exit();
}

$assignment_id = intval($_GET['id']);

// Delete assignment
$sql = "DELETE FROM project_volunteers WHERE assignment_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $assignment_id);

if (mysqli_stmt_execute($stmt)) {
    header("Location: index.php?success=Assignment deleted successfully");
    exit();
} else {
    header("Location: index.php?error=Failed to delete assignment");
    exit();
}
?>

<div class="container my-5">
    <div class="card shadow-sm border-0">
        <div class="card-header text-white" style="background-color:#2B547E;">
            <h4 class="mb-0" style="color:#FFD700;">
                <i class="bi bi-trash-fill me-2"></i>Delete Volunteer Assignment
            </h4>
        </div>
        <div class="card-body text-center">
            <p class="mb-4">Are you sure you want to delete this assignment?</p>
            <a href="index.php" class="btn fw-semibold" style="background-color:#FFD700; color:#2B547E;">
                <i class="bi bi-arrow-left me-1"></i>Cancel
            </a>
            <a href="delete.php?id=<?php echo $assignment_id; ?>" class="btn fw-semibold" style="background-color:#2B547E; color:#FFD700;">
                <i class="bi bi-trash me-1"></i>Confirm Delete
            </a>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>
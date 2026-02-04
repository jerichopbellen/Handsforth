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

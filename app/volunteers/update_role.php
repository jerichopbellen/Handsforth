<?php
session_start();
include("../../includes/config.php");

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = intval($_POST['project_id']);
    $volunteer_id = intval($_POST['volunteer_id']);
    $role_in_project = $_POST['role_in_project'];

    if ($project_id > 0 && $volunteer_id > 0) {
        $sql = "UPDATE project_volunteers 
                SET role_in_project = ? 
                WHERE project_id = ? AND volunteer_id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sii", $role_in_project, $project_id, $volunteer_id);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: ../projects/view.php?id=$project_id&msg=Role updated successfully");
            exit();
        } else {
            header("Location: ../projects/view.php?id=$project_id&error=Update failed");
            exit();
        }
    }
}

// Redirect back if accessed incorrectly
header("Location: ../projects/index.php");
exit();

<?php

session_start();
include("../../includes/config.php");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $volunteer_id    = $_POST['volunteer_id'];
    $project_id      = $_POST['project_id'];
    $role_in_project = $_POST['role_in_project'];

    // Use ON DUPLICATE KEY UPDATE to prevent multiple assignment rows
    $sql = "INSERT INTO project_volunteers (project_id, volunteer_id, role_in_project, assigned_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE role_in_project = VALUES(role_in_project)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iis", $project_id, $volunteer_id, $role_in_project);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../projects/view.php?id=" . $project_id . "&msg=Volunteer assigned successfully");
        exit();
    } else {
        $error = "Error assigning volunteer: " . mysqli_error($conn);
    }
}
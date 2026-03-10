<?php
session_start();
include("../../includes/config.php");

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

// 2. Handle POST Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = intval($_POST['project_id'] ?? 0);
    $volunteer_id = intval($_POST['volunteer_id'] ?? 0);
    $status = $_POST['status'] ?? 'present';

    if ($project_id <= 0 || $volunteer_id <= 0) {
        header("Location: ../projects/view.php?id=$project_id&error=Invalid IDs");
        exit();
    }

    /* 3. The "Upsert" Logic
       This query tries to INSERT a new record. 
       If a record with the same project_id and volunteer_id already exists, 
       it triggers the UPDATE part instead.
    */
    $sql = "INSERT INTO attendance (project_id, volunteer_id, status, check_in_time) 
            VALUES (?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
            status = VALUES(status)";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'iis', $project_id, $volunteer_id, $status);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../projects/view.php?id=$project_id&msg=Attendance updated");
        exit();
    } else {
        header("Location: ../projects/view.php?id=$project_id&error=Update failed: " . mysqli_error($conn));
        exit();
    }
} else {
    header("Location: ../projects/index.php");
    exit();
}
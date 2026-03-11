<?php
session_start();
include("../../includes/config.php");

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../public/index.php");
    exit();
}

// 2. Handle POST Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = intval($_POST['project_id'] ?? 0);
    $volunteer_id = intval($_POST['volunteer_id'] ?? 0);
    $status = $_POST['status'] ?? 'present';
    
    // Capture manual time inputs
    $check_in = !empty($_POST['check_in_time']) ? $_POST['check_in_time'] : null;
    $check_out = !empty($_POST['check_out_time']) ? $_POST['check_out_time'] : null;

    if ($project_id <= 0 || $volunteer_id <= 0) {
        header("Location: ../projects/view.php?id=$project_id&error=Invalid IDs");
        exit();
    }

    /* 3. Upsert Logic 
       We use VALUES(col_name) to ensure the data from the form is used 
       both during initial insert AND during update.
    */
    $sql = "INSERT INTO attendance (project_id, volunteer_id, status, check_in_time, check_out_time) 
            VALUES (?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            status = VALUES(status),
            check_in_time = VALUES(check_in_time),
            check_out_time = VALUES(check_out_time)";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'iisss', $project_id, $volunteer_id, $status, $check_in, $check_out);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../projects/view.php?id=$project_id&msg=Attendance record updated");
        exit();
    } else {
        header("Location: ../projects/view.php?id=$project_id&error=Update failed: " . mysqli_error($conn));
        exit();
    }
} else {
    header("Location: ../projects/index.php");
    exit();
}
<?php
session_start();
include("../includes/config.php"); 

// 1. Security Check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../app/users/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['project_id'])) {
    $project_id = (int)$_POST['project_id'];
    $volunteer_id = (int)$_SESSION['user_id'];

    // 2. Double Check: Ensure they haven't already applied or aren't already a member
    // This prevents database errors from unique constraint violations
    $check_sql = "SELECT application_id FROM project_applications 
                  WHERE project_id = ? AND volunteer_id = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "ii", $project_id, $volunteer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        // Already applied
        header("Location: dashboard.php?msg=" . urlencode("You have already applied for this project."));
        exit;
    }
    mysqli_stmt_close($stmt);

    // 3. Insert the Application
    $insert_sql = "INSERT INTO project_applications (project_id, volunteer_id, status) VALUES (?, ?, 'pending')";
    $stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($stmt, "ii", $project_id, $volunteer_id);

    if (mysqli_stmt_execute($stmt)) {
        // Success
        $msg = "Application submitted! Wait for admin approval.";
    } else {
        // Database Error
        $msg = "Error submitting application. Please try again.";
    }

    mysqli_stmt_close($stmt);
    
    // 4. Store message in session and redirect
    $_SESSION['success'] = $msg;
    header("Location: index.php");
    exit;

} else {
    // If someone tries to access this file directly without POST
    header("Location: index.php");
    exit;
}
?>
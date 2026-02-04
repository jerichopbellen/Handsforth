<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid attendance ID";
    header("Location: index.php");
    exit();
}

$attendance_id = intval($_GET['id']);

$sql = "DELETE FROM attendance WHERE attendance_id = ?";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    $_SESSION['error'] = "Database error: " . mysqli_error($conn);
    header("Location: index.php");
    exit();
}

mysqli_stmt_bind_param($stmt, 'i', $attendance_id);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success'] = "Attendance record deleted successfully";
    mysqli_stmt_close($stmt);
    header("Location: index.php");
    exit();
} else {
    $_SESSION['error'] = "Failed to delete record: " . mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    header("Location: index.php");
    exit();
}
?>

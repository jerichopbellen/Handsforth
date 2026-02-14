<?php
session_start();

include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No user ID provided.";
    header("Location: listUsers.php");
    exit();
}

$user_id = intval($_GET['id']);

// Fetch user to check existence and handle image cleanup
$sql = "SELECT img_path FROM users WHERE user_id = $user_id";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "User not found.";
    header("Location: listUsers.php");
    exit();
}

$user = mysqli_fetch_assoc($result);

// Remove profile image file if it exists
if (!empty($user['img_path']) && file_exists($user['img_path'])) {
    unlink($user['img_path']);
}

// Delete user record
$sql = "DELETE FROM users WHERE user_id = $user_id";
if (mysqli_query($conn, $sql)) {
    $_SESSION['success'] = "User deleted successfully!";
} else {
    $_SESSION['error'] = "Error deleting user: " . mysqli_error($conn);
}

header("Location: listUsers.php");
exit();
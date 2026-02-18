<?php
session_start();
include("../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'volunteer') {
    header("Location: ../app/users/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['submit'])) {
    $first_name   = trim($_POST['first_name']);
    $last_name    = trim($_POST['last_name']);
    $email        = trim($_POST['email']);
    $phone        = trim($_POST['phone']);
    $skills       = trim($_POST['skills']);
    $availability = trim($_POST['availability']);
    $img_path     = "";

    if (empty($first_name) || empty($last_name) || empty($email)) {
        $_SESSION['error'] = "First name, last name, and email are required.";
        header("Location: editProfile.php");
        exit();
    }

    // Handle image upload
    if (isset($_FILES['img_path']) && $_FILES['img_path']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "../uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName   = time() . "_" . basename($_FILES['img_path']['name']);
        $targetFile = $uploadDir . $fileName;

        $fileType     = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ["jpg", "jpeg", "png", "gif"];

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['img_path']['tmp_name'], $targetFile)) {
                // Save relative path for browser access
                $img_path = "../uploads/" . $fileName;
            }
        }
    }

    // Update USERS table
    if (!empty($img_path)) {
        $update_user_sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, img_path = ? WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $update_user_sql);
        mysqli_stmt_bind_param($stmt, 'sssssi', $first_name, $last_name, $email, $phone, $img_path, $user_id);
    } else {
        $update_user_sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $update_user_sql);
        mysqli_stmt_bind_param($stmt, 'ssssi', $first_name, $last_name, $email, $phone, $user_id);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Update or insert VOLUNTEER_DETAILS
    $check_sql = "SELECT volunteer_id FROM volunteer_details WHERE volunteer_id = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    $exists = mysqli_num_rows($check_result) > 0;
    mysqli_stmt_close($stmt);

    if ($exists) {
        $update_vol_sql = "UPDATE volunteer_details SET skills = ?, availability = ? WHERE volunteer_id = ?";
        $stmt = mysqli_prepare($conn, $update_vol_sql);
        mysqli_stmt_bind_param($stmt, 'ssi', $skills, $availability, $user_id);
    } else {
        $insert_vol_sql = "INSERT INTO volunteer_details (volunteer_id, skills, availability) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_vol_sql);
        mysqli_stmt_bind_param($stmt, 'iss', $user_id, $skills, $availability);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $_SESSION['success'] = "Profile updated successfully.";
    header("Location: index.php");
    exit();
} else {
    header("Location: editProfile.php");
    exit();
}
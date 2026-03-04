<?php
session_start();

include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

// Collect form data safely
$role_id    = intval($_POST['role_id']); // dropdown now uses role_id
$username   = trim($_POST['username']);
$password   = trim($_POST['password']);
$first_name = trim($_POST['first_name']);
$last_name  = trim($_POST['last_name']);
$email      = trim($_POST['email']);
$phone      = trim($_POST['phone']);

// Hash password if provided
$password_hash = !empty($password) ? sha1($password) : null;

// Handle image upload
$img_path = "";
if (isset($_FILES['img_path']) && $_FILES['img_path']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . "/../../uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName   = time() . "_" . basename($_FILES['img_path']['name']);
    $targetFile = $uploadDir . $fileName;

    $fileType     = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $allowedTypes = ["jpg", "jpeg", "png", "gif"];

    if (in_array($fileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES['img_path']['tmp_name'], $targetFile)) {
            $img_path = "../uploads/" . $fileName; // relative path for browser access
        }
    }
}

// Insert into database using prepared statement
$sql = "INSERT INTO users 
            (role_id, username, password_hash, first_name, last_name, email, phone, img_path, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'isssssss',
        $role_id,
        $username,
        $password_hash,
        $first_name,
        $last_name,
        $email,
        $phone,
        $img_path
    );

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "User created successfully!";
        header("Location: listUsers.php");
        exit();
    } else {
        $_SESSION['error'] = "Error creating user: " . mysqli_error($conn);
        header("Location: createUser.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Prepare failed: " . mysqli_error($conn);
    header("Location: createUser.php");
    exit();
}
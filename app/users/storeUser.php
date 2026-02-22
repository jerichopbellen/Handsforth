<?php
session_start();

include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

// Collect form data
$role       = mysqli_real_escape_string($conn, $_POST['role']);
$username   = mysqli_real_escape_string($conn, $_POST['username']);
$password   = sha1(trim($_POST['password']));
$first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
$last_name  = mysqli_real_escape_string($conn, $_POST['last_name']);
$email      = mysqli_real_escape_string($conn, $_POST['email']);
$phone      = mysqli_real_escape_string($conn, $_POST['phone']);

// Handle image upload
$img_path = "";
if (isset($_FILES['img_path']) && $_FILES['img_path']['error'] === UPLOAD_ERR_OK) {
    // Absolute path for saving the file
    $uploadDir = __DIR__ . "/../../uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName   = time() . "_" . basename($_FILES['img_path']['name']);
    $targetFile = $uploadDir . $fileName;

    // Validate file type
    $fileType     = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $allowedTypes = ["jpg", "jpeg", "png", "gif"];

    if (in_array($fileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES['img_path']['tmp_name'], $targetFile)) {
            // Save relative path for browser access
            $img_path = "../uploads/" . $fileName;
        }
    }
}

// Insert into database
$sql = "
    INSERT INTO users 
        (role, username, password_hash, first_name, last_name, email, phone, img_path, created_at, updated_at)
    VALUES 
        ('$role', '$username', '$password', '$first_name', '$last_name', '$email', '$phone', '$img_path', NOW(), NOW())
";

if (mysqli_query($conn, $sql)) {
    $_SESSION['success'] = "User created successfully!";
    header("Location: listUsers.php");
    exit();
} else {
    $_SESSION['error'] = "Error creating user: " . mysqli_error($conn);
    header("Location: createUser.php");
    exit();
}
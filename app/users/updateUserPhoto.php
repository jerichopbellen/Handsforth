<?php
session_start();

include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action']; // "update" or "remove"

    $img_path = "";

    if ($action === "update" && isset($_FILES['img_path']) && $_FILES['img_path']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "../../uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES['img_path']['name']);
        $targetFile = $uploadDir . $fileName;

        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ["jpg", "jpeg", "png", "gif"];

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['img_path']['tmp_name'], $targetFile)) {
                $img_path = "../uploads/" . $fileName;

                // Update DB with new path
                $sql = "UPDATE users SET img_path='$img_path', updated_at=NOW() WHERE user_id=$user_id";
                if (mysqli_query($conn, $sql)) {
                    $_SESSION['success'] = "Profile picture updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating profile picture: " . mysqli_error($conn);
                }
            } else {
                $_SESSION['error'] = "Failed to move uploaded file.";
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Allowed: JPG, JPEG, PNG, GIF.";
        }
    } elseif ($action === "remove") {
        // Remove image path from DB
        $sql = "UPDATE users SET img_path='', updated_at=NOW() WHERE user_id=$user_id";
        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Profile picture removed successfully!";
        } else {
            $_SESSION['error'] = "Error removing profile picture: " . mysqli_error($conn);
        }
    }

    header("Location: editUser.php?id=$user_id");
    exit();
}
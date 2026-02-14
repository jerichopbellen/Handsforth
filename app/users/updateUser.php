<?php
session_start();

include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    // Handle password update only if provided
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "
            UPDATE users 
            SET role='$role', username='$username', password_hash='$password',
                first_name='$first_name', last_name='$last_name', email='$email',
                phone='$phone', updated_at=NOW()
            WHERE user_id=$user_id
        ";
    } else {
        $sql = "
            UPDATE users 
            SET role='$role', username='$username',
                first_name='$first_name', last_name='$last_name', email='$email',
                phone='$phone', updated_at=NOW()
            WHERE user_id=$user_id
        ";
    }

    if (mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "User updated successfully!";
        header("Location: editUser.php?id=$user_id");
        exit();
    } else {
        $_SESSION['error'] = "Error updating user: " . mysqli_error($conn);
        header("Location: editUser.php?id=$user_id");
        exit();
    }
}
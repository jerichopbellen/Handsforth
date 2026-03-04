<?php
session_start();

include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id    = intval($_POST['user_id']);
    $role_id    = intval($_POST['role_id']); // dropdown now uses role_id
    $username   = trim($_POST['username']);
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);

    // Handle password update only if provided
    if (!empty($_POST['password'])) {
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $sql = "UPDATE users 
                   SET role_id=?, username=?, password_hash=?, 
                       first_name=?, last_name=?, email=?, phone=?, updated_at=NOW()
                 WHERE user_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'issssssi',
            $role_id,
            $username,
            $password_hash,
            $first_name,
            $last_name,
            $email,
            $phone,
            $user_id
        );
    } else {
        $sql = "UPDATE users 
                   SET role_id=?, username=?, 
                       first_name=?, last_name=?, email=?, phone=?, updated_at=NOW()
                 WHERE user_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'isssssi',
            $role_id,
            $username,
            $first_name,
            $last_name,
            $email,
            $phone,
            $user_id
        );
    }

    if ($stmt && mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "User updated successfully!";
        header("Location: editUser.php?id=$user_id");
        exit();
    } else {
        $_SESSION['error'] = "Error updating user: " . mysqli_error($conn);
        header("Location: editUser.php?id=$user_id");
        exit();
    }
}
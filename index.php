<?php
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: app/projects/index.php");
    } else {
        header("Location: public/index.php");
    }
    exit();
}

header("Location: landing.php");
exit();
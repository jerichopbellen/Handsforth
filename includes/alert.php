<?php
if (isset($_SESSION['flash'])) {
    echo "<div class='container py-5'><div class='alert alert-warning'>". htmlspecialchars($_SESSION['flash']) . " </div></div>";
    unset($_SESSION['flash']);
}

if (isset($_SESSION['success'])) {
    echo "<div class='container py-2'><div class='alert alert-success'>". htmlspecialchars($_SESSION['success']) . " </div></div>";
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo "<div class='container py-2'><div class='alert alert-danger'>". htmlspecialchars($_SESSION['error']) . " </div></div>";
    unset($_SESSION['error']);
}

?>
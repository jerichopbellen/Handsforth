<?php
include("includes/config.php");
include("includes/header.php");
?>

<section class="vh-100 d-flex align-items-center justify-content-center text-center bg-dark position-relative">
  <!-- Background image -->
  <div class="position-absolute top-0 start-0 w-100 h-100" 
       style="background: url('http://<?= $_SERVER['SERVER_NAME'] ?>/handsforth/assets/welcome-bg.jpg') center/cover no-repeat; opacity: 0.4;">
  </div>

  <!-- Overlay content -->
  <div class="container position-relative text-light">
    <h1 class="fw-bold text-warning mb-3">Welcome to Handsforth</h1>
    <p class="lead mb-4">Community Service Organization Management System</p>
    <a href="http://<?= $_SERVER['SERVER_NAME'] ?>/handsforth/app/users/login.php" 
       class="btn btn-warning btn-lg rounded-pill">
      <i class="bi bi-box-arrow-in-right me-2"></i> Login
    </a>
  </div>
</section>
<?php
include("includes/config.php");
include("includes/header.php");
?>

<section class="vh-100 d-flex align-items-center justify-content-center text-center position-relative">
  <!-- Darkened background image -->
  <div class="position-absolute top-0 start-0 w-100 h-100" 
       style="background: url('http://<?= $_SERVER['SERVER_NAME'] ?>/handsforth/assets/welcome-bg.jpg') center/cover no-repeat;">
    <div style="background-color: rgba(0,0,0,0.6); width:100%; height:100%;"></div>
  </div>

  <!-- Overlay content -->
  <div class="container position-relative">
    <h1 class="fw-bold mb-3 text-white" style="text-shadow: 2px 2px 6px rgba(0,0,0,0.8);">
      Welcome to Handsforth
    </h1>
    <p class="lead mb-4 text-white" style="text-shadow: 1px 1px 4px rgba(0,0,0,0.7);">
      Community Service Organization Management System
    </p>
    <a href="http://<?= $_SERVER['SERVER_NAME'] ?>/handsforth/app/users/login.php" 
       class="btn btn-lg rounded-pill fw-semibold text-dark" 
       style="background-color:#FFD700; box-shadow: 0 4px 8px rgba(0,0,0,0.5);">
      <i class="bi bi-box-arrow-in-right me-2"></i> Login
    </a>
  </div>
</section>
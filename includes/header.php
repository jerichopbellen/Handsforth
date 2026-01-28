<?php
include("config.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Handsforth</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/includes/style/style.css" rel="stylesheet" type="text/css" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top border-bottom border-secondary" style="z-index: 1030;">
  <div class="container-fluid">
    <a class="navbar-brand fw-semibold text-warning" href="http://<?= $_SERVER['SERVER_NAME'] ?>/handsforth/public/index.php">
        Handsforth
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
      aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link active text-light" style="opacity: 0.85;" href="#">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-light" style="opacity: 0.85;" href="#">Contact</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-light" style="opacity: 0.85;" href="#">About</a>
        </li>
        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle text-light" style="opacity: 0.85;" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Admin Panel
            </a>
            <ul class="dropdown-menu dropdown-menu-dark">
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/handsforth/app/attendance/index.php">Attendance</a></li>
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/handsforth/app/beneficiaries/index.php">Beneficiaries</a></li>
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/handsforth/app/donations/index.php">Donations</a></li>
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/handsforth/app/projects/index.php">Projects</a></li>
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/handsforth/app/reports/index.php">Reports</a></li>
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/handsforth/app/users/listUsers.php">Users</a></li>
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/handsforth/app/volunteers/index.php">Volunteers</a></li>
            </ul>
          </li>
        <?php endif; ?>
      </ul>

      <div class="navbar-nav ms-auto d-flex align-items-center gap-3">
        <?php if (!isset($_SESSION['user_id'])): ?>
          <a href="http://<?= $_SERVER['SERVER_NAME'] ?>/handsforth/app/users/login.php" class="nav-item nav-link text-light" style="opacity: 0.85;">Login</a>
        <?php else: ?>
            <span class="nav-item nav-link text-light" style="opacity: 0.85;">
                <i class="bi bi-person-circle me-1"></i>
                <?= htmlspecialchars($_SESSION['email'], ENT_QUOTES, 'UTF-8') ?>
            </span>
            <a href="http://<?= $_SERVER['SERVER_NAME'] ?>/handsforth/app/users/logout.php" class="nav-item nav-link text-light" style="opacity: 0.85;">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
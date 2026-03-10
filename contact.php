<?php
session_start();
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
  <div class="container position-relative text-white">
    <h1 class="fw-bold mb-4" style="text-shadow: 2px 2px 6px rgba(0,0,0,0.8);">
      <i class="bi bi-envelope-fill me-2"></i> Contact Us
    </h1>
    <p class="lead mb-4" style="text-shadow: 1px 1px 4px rgba(0,0,0,0.7);">
      Have questions or need assistance? Reach out to us using the details below.
    </p>

    <ul class="list-unstyled text-start d-inline-block">
      <li class="mb-2">
        <i class="bi bi-geo-alt-fill me-2 text-warning"></i>
        <strong>Address:</strong> 123 Community Center, Metro Manila, Philippines
      </li>
      <li class="mb-2">
        <i class="bi bi-telephone-fill me-2 text-warning"></i>
        <strong>Phone:</strong> +63 912 345 6789
      </li>
      <li class="mb-2">
        <i class="bi bi-envelope-fill me-2 text-warning"></i>
        <strong>Email:</strong> support@communitysystem.org
      </li>
      <li class="mb-2">
        <i class="bi bi-globe me-2 text-warning"></i>
        <strong>Website:</strong> www.communitysystem.org
      </li>
    </ul>
  </div>
</section>

<?php include("includes/footer.php"); ?>
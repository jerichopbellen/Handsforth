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
      <i class="bi bi-info-circle-fill me-2"></i> About Us
    </h1>
    <p class="lead mb-4" style="text-shadow: 1px 1px 4px rgba(0,0,0,0.7);">
      Our system was designed to simplify volunteer management, project tracking, and beneficiary support.
    </p>

    <div class="text-start d-inline-block">
      <h5 class="fw-semibold text-warning">Our Mission</h5>
      <p>To build a reliable platform that fosters collaboration, transparency, and efficiency in community projects.</p>

      <h5 class="fw-semibold text-warning">Our Vision</h5>
      <p>A connected community where technology bridges the gap between volunteers, projects, and beneficiaries — creating lasting impact and sustainable growth.</p>

      <h5 class="fw-semibold text-warning">Core Values</h5>
      <ul>
        <li><strong>Integrity:</strong> Transparency and accountability in all processes.</li>
        <li><strong>Collaboration:</strong> Teamwork and shared responsibility.</li>
        <li><strong>Innovation:</strong> Continuous improvement to meet evolving needs.</li>
      </ul>
    </div>
  </div>
</section>

<?php include("includes/footer.php"); ?>
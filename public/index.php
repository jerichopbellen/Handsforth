<?php
session_start();
include("../includes/header.php");
include("../includes/config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../app/users/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch volunteer profile
$profile_sql = "SELECT u.first_name, u.last_name, u.email, u.phone, u.img_path,
                       v.skills, v.availability
                FROM users u
                LEFT JOIN volunteer_details v ON u.user_id = v.volunteer_id
                WHERE u.user_id = ?";
$stmt = mysqli_prepare($conn, $profile_sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$profile_result = mysqli_stmt_get_result($stmt);
$profile = mysqli_fetch_assoc($profile_result);
mysqli_stmt_close($stmt);

// Upcoming projects
$upcoming_sql = "SELECT p.project_id, p.title, p.description, p.date, p.location, pv.role_in_project
                 FROM project_volunteers pv
                 JOIN projects p ON pv.project_id = p.project_id
                 WHERE pv.volunteer_id = ? AND p.status IN ('planned','ongoing')
                 ORDER BY p.date ASC";
$stmt = mysqli_prepare($conn, $upcoming_sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$upcoming_result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Completed projects
$completed_sql = "SELECT p.project_id, p.title, p.date, p.location, p.status,
                         a.status AS attendance_status, a.check_in_time, a.check_out_time,
                         pv.role_in_project
                  FROM project_volunteers pv
                  JOIN projects p ON pv.project_id = p.project_id
                  LEFT JOIN attendance a ON a.project_id = p.project_id AND a.volunteer_id = pv.volunteer_id
                  WHERE pv.volunteer_id = ? AND p.status IN ('completed','cancelled')
                  ORDER BY p.date DESC";
$stmt = mysqli_prepare($conn, $completed_sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$completed_result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);
?>

<div class="container-fluid my-5 px-5" style="padding-top: 2rem; padding-bottom: 2rem;">
    <?php include("../includes/alert.php"); ?>

    <!-- Welcome Header -->
    <div class="row mb-5">
        <div class="col-12">
            <h1 class="display-5 fw-bold text-dark mb-2">Welcome, <?php echo htmlspecialchars($profile['first_name']); ?>!</h1>
            <p class="text-muted">Track your volunteer journey and upcoming opportunities</p>
        </div>
    </div>

    <div class="row">
        <!-- Profile Card -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 h-100" style="box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);">
                <div class="card-body p-4">
                    <div class="text-center mb-3">
                        <?php 
                            $img_path = $profile['img_path'] ?? 'default-avatar.png';
                            $img_src = !empty($profile['img_path']) ? htmlspecialchars($profile['img_path']) : '../assets/default-avatar.png';
                        ?>
                        <img src="<?php echo $img_src; ?>" alt="Profile Picture" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                    </div>
                    <h5 class="card-title text-center text-dark"><?php echo htmlspecialchars($profile['first_name'] . " " . $profile['last_name']); ?></h5>
                    <hr>
                    <small class="text-muted d-block mb-2"><strong>Email:</strong></small>
                    <p class="text-break"><?php echo htmlspecialchars($profile['email']); ?></p>
                    <small class="text-muted d-block mb-2"><strong>Phone:</strong></small>
                    <p><?php echo htmlspecialchars($profile['phone'] ?? 'N/A'); ?></p>
                    <small class="text-muted d-block mb-2"><strong>Skills:</strong></small>
                    <p><?php echo htmlspecialchars($profile['skills'] ?? 'Not yet added'); ?></p>
                    <a href="editProfile.php" class="btn btn-dark w-100 mt-3">
                        <i class="bi bi-pencil me-2"></i>Edit Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Upcoming Projects -->
            <div class="card border-0 mb-4" style="box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);">
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <h5 class="mb-0 text-black"><i class="bi bi-calendar-check me-2"></i>Upcoming Projects</h5>
                </div>
                <div class="card-body p-4">
                    <?php if (mysqli_num_rows($upcoming_result) > 0): ?>
                        <div class="row g-3">
                            <?php while ($row = mysqli_fetch_assoc($upcoming_result)): ?>
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100 hover-shadow" style="transition: all 0.3s; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);">
                                        <h6 class="text-dark mb-2"><?php echo htmlspecialchars($row['title']); ?></h6>
                                        <p class="mb-2 small text-muted"><?php echo htmlspecialchars($row['description']); ?></p>
                                        <p class="mb-2">
                                            <i class="bi bi-calendar text-muted me-2"></i>
                                            <small><?php echo date('M d, Y', strtotime($row['date'])); ?></small>
                                        </p>
                                        <p class="mb-2">
                                            <i class="bi bi-geo-alt text-muted me-2"></i>
                                            <small><?php echo htmlspecialchars($row['location']); ?></small>
                                        </p>
                                        <span class="badge bg-info text-dark"><?php echo ucfirst($row['role_in_project']); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 2rem; color: #ccc;"></i>
                            <p class="text-muted mt-2">No upcoming projects yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Completed Projects -->
            <div class="card border-0" style="box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);">
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
                    <h5 class="mb-0 text-black"><i class="bi bi-check-circle me-2"></i>Completed Projects</h5>
                </div>
                <div class="card-body p-4">
                    <?php if (mysqli_num_rows($completed_result) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Project</th>
                                        <th>Date</th>
                                        <th>Attendance</th>
                                        <th>Hours</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($completed_result)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                            <td>
                                                <?php if ($row['attendance_status']): ?>
                                                    <span class="badge <?php echo $row['attendance_status'] === 'present' ? 'bg-success' : ($row['attendance_status'] === 'late' ? 'bg-warning text-dark' : 'bg-danger'); ?>">
                                                        <?php echo ucfirst($row['attendance_status']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted small">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><small><?php echo ($row['check_in_time'] && $row['check_out_time']) ? date('H:i', strtotime($row['check_out_time'])) - date('H:i', strtotime($row['check_in_time'])) : '-'; ?></small></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 2rem; color: #ccc;"></i>
                            <p class="text-muted mt-2">No completed projects yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../includes/footer.php"); ?>
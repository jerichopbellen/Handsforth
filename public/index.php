<?php
session_start();
include("../includes/header.php");
include("../includes/config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../app/users/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Fetch volunteer profile
$profile_sql = "SELECT u.first_name, u.last_name, u.email, u.phone, u.img_path,
                       v.skills, v.availability
                FROM users u
                LEFT JOIN volunteer_details v ON u.user_id = v.volunteer_id
                WHERE u.user_id = ?";
$stmt = mysqli_prepare($conn, $profile_sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$profile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// 2. Fetch Available Opportunities (Planned projects user hasn't joined/applied for)
$opp_sql = "SELECT p.* FROM projects p 
            WHERE p.status = 'planned' 
            AND p.project_id NOT IN (SELECT project_id FROM project_volunteers WHERE volunteer_id = ?)
            AND p.project_id NOT IN (SELECT project_id FROM project_applications WHERE volunteer_id = ? AND status = 'pending')
            ORDER BY p.date ASC";
$stmt = mysqli_prepare($conn, $opp_sql);
mysqli_stmt_bind_param($stmt, 'ii', $user_id, $user_id);
mysqli_stmt_execute($stmt);
$opp_result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// 3. Upcoming projects (ONLY those where user is APPROVED/JOINED)
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

// 4. Completed projects
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

<div class="container-fluid my-5 px-5">
    <?php include("../includes/alert.php"); ?>

    <div class="row mb-5">
        <div class="col-12">
            <h1 class="display-5 fw-bold" style="color:#2B547E;">Welcome, <?php echo htmlspecialchars($profile['first_name']); ?>!</h1>
            <p class="text-muted">Track your volunteer journey and discover new opportunities.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 text-center">
                    <?php $img_src = !empty($profile['img_path']) ? htmlspecialchars($profile['img_path']) : '../assets/default-avatar.png'; ?>
                    <img src="<?php echo $img_src; ?>" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover; border: 4px solid #f8f9fa;">
                    <h5 style="color:#2B547E;"><?php echo htmlspecialchars($profile['first_name'] . " " . $profile['last_name']); ?></h5>
                    <hr>
                    <div class="text-start small">
                        <p class="mb-1 text-muted"><strong>Skills:</strong></p>
                        <p><?php echo htmlspecialchars($profile['skills'] ?? 'Not yet added'); ?></p>
                        <p class="mb-1 text-muted"><strong>Availability:</strong></p>
                        <p><?php echo ucfirst($profile['availability'] ?? 'Not set'); ?></p>
                    </div>
                    <a href="editProfile.php" class="btn btn-sm w-100 mt-2 fw-semibold" style="background-color:#2B547E; color:#FFD700;">Edit Profile</a>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            
            <div class="card border-0 mb-4 shadow-sm">
                <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color:#2B547E;">
                    <h5 class="mb-0" style="color:#FFD700;"><i class="bi bi-search me-2"></i>Find Opportunities</h5>
                    <span class="badge bg-light text-dark"><?php echo mysqli_num_rows($opp_result); ?> Available</span>
                </div>
                <div class="card-body p-4">
                    <?php if (mysqli_num_rows($opp_result) > 0): ?>
                        <div class="row g-3">
                            <?php while ($row = mysqli_fetch_assoc($opp_result)): ?>
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100 position-relative">
                                        <h6 class="fw-bold" style="color:#2B547E;"><?php echo htmlspecialchars($row['title']); ?></h6>
                                        <p class="small text-muted mb-2"><?php echo htmlspecialchars(substr($row['description'], 0, 80)) . '...'; ?></p>
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($row['location']); ?></small>
                                            <form action="apply_logic.php" method="POST">
                                                <input type="hidden" name="project_id" value="<?php echo $row['project_id']; ?>">
                                                <button type="submit" class="btn btn-sm fw-bold" style="background-color:#FFD700; color:#2B547E;">Apply Now</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted my-3">No new projects available to join right now.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 mb-4 shadow-sm">
                <div class="card-header text-white" style="background-color:#2B547E;">
                    <h5 class="mb-0" style="color:#FFD700;"><i class="bi bi-calendar-check me-2"></i>My Upcoming Projects</h5>
                </div>
                <div class="card-body p-4">
                    <?php if (mysqli_num_rows($upcoming_result) > 0): ?>
                        <div class="row g-3">
                            <?php while ($row = mysqli_fetch_assoc($upcoming_result)): ?>
                                <div class="col-md-6">
                                    <div class="p-3 border-start border-4 border-warning bg-light rounded shadow-sm">
                                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($row['title']); ?></h6>
                                        <small class="d-block text-muted mb-2"><i class="bi bi-calendar3 me-1"></i><?php echo date('M d, Y', strtotime($row['date'])); ?></small>
                                        <span class="badge bg-secondary"><?php echo ucfirst($row['role_in_project']); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <p class="text-muted">You haven't been added to any upcoming projects yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header text-white" style="background-color:#2B547E;">
                    <h5 class="mb-0" style="color:#FFD700;"><i class="bi bi-check-circle me-2"></i>Project History</h5>
                </div>
                <div class="card-body p-4">
                    <?php if (mysqli_num_rows($completed_result) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light text-muted small">
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
                                            <td class="fw-bold"><?php echo htmlspecialchars($row['title']); ?></td>
                                            <td><small><?php echo date('M d, Y', strtotime($row['date'])); ?></small></td>
                                            <td>
                                                <span class="badge <?php echo $row['attendance_status'] === 'present' ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo ucfirst($row['attendance_status'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                    if($row['check_in_time'] && $row['check_out_time'] && $row['attendance_status'] !== 'absent') {
                                                        echo round((strtotime($row['check_out_time']) - strtotime($row['check_in_time'])) / 3600, 1) . 'h';
                                                    } else { echo '-'; }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">No completed projects in your record.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include("../includes/footer.php"); ?>
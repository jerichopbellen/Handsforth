<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

// 1. Get project ID safely
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Invalid project ID.</div></div>";
    include("../../includes/footer.php");
    exit;
}

// 2. Fetch Project Details
$stmt = $conn->prepare("
    SELECT p.*, u.username 
    FROM projects p 
    LEFT JOIN users u ON p.created_by = u.user_id 
    WHERE p.project_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Project not found.</div></div>";
    include("../../includes/footer.php");
    exit;
}

// 3. Fetch Approved Team & Attendance
$v_stmt = $conn->prepare("
    SELECT pv.volunteer_id, pv.role_in_project, u.first_name, u.last_name, 
           a.status AS att_status, a.check_in_time, a.check_out_time
    FROM project_volunteers pv
    JOIN users u ON pv.volunteer_id = u.user_id
    LEFT JOIN (
        SELECT volunteer_id, project_id, status, check_in_time, check_out_time
        FROM attendance
        WHERE project_id = ?
    ) a ON a.volunteer_id = pv.volunteer_id
    WHERE pv.project_id = ?
");
$v_stmt->bind_param("ii", $id, $id);
$v_stmt->execute();
$volunteers = $v_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$v_stmt->close();

// 4. Fetch Pending Applications (Modified as requested)
$app_stmt = $conn->prepare("
    SELECT pa.application_id, u.user_id, u.first_name, u.last_name, vd.availability, pa.applied_at
    FROM project_applications pa
    JOIN users u ON pa.volunteer_id = u.user_id
    LEFT JOIN volunteer_details vd ON u.user_id = vd.volunteer_id
    WHERE pa.project_id = ? AND pa.status = 'pending'
    ORDER BY pa.applied_at ASC
");
$app_stmt->bind_param("i", $id);
$app_stmt->execute();
$pending_applicants = $app_stmt->get_result();

$statusClass = match(strtolower($project['status'])) {
    'completed' => 'success',
    'ongoing'   => 'warning text-dark',
    'cancelled' => 'danger',
    default     => 'secondary',
};
?>

<style>
    input[type="time"]::-webkit-calendar-picker-indicator { display: none; -webkit-appearance: none; }
    .time-input-field { padding-left: 5px !important; min-width: 95px !important; }
    input:disabled { background-color: #e9ecef !important; cursor: not-allowed; opacity: 0.6; }
    .applicant-card:hover { background-color: #f8f9fa; }
</style>

<div class="container my-5">
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark"><i class="bi bi-kanban"></i> Project Overview</h2>
        <div>
            <a href="index.php" class="btn fw-semibold" style="background-color:#FFD700; color:#2B547E;">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
            <a href="edit.php?id=<?= $id; ?>" class="btn fw-semibold" style="background-color:#2B547E; color:#FFD700;">
                <i class="bi bi-pencil-square me-1"></i> Edit Details
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header text-white" style="background-color:#2B547E;">
                    <h5 class="mb-0" style="color:#FFD700;">Core Information</h5>
                </div>
                <div class="card-body">
                    <h4 class="fw-bold" style="color:#2B547E;"><?= htmlspecialchars($project['title']); ?></h4>
                    <span class="badge bg-<?= $statusClass; ?> mb-3"><?= strtoupper($project['status']); ?></span>
                    <p class="text-muted small mb-1">DESCRIPTION</p>
                    <p><?= nl2br(htmlspecialchars($project['description'])); ?></p>
                    <hr>
                    <p><strong>Location:</strong> <?= htmlspecialchars($project['location']); ?></p>
                    <p><strong>Date:</strong> <?= date("F j, Y", strtotime($project['date'])); ?></p>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header text-white" style="background-color:#2B547E;">
                    <h5 class="mb-0" style="color:#FFD700;">Pending Applications</h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($pending_applicants->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while($app = $pending_applicants->fetch_assoc()): 
                                $availText = $app['availability'] ? ucfirst($app['availability']) : 'Not Set';
                                $icon = match($app['availability']) {
                                    'anytime' => '🟢',
                                    'weekends', 'weekdays' => '🟡',
                                    default => '⚪',
                                };
                            ?>
                                <div class="list-group-item applicant-card p-3">
                                    <div class="mb-2">
                                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></h6>
                                        <small class="text-muted"><?= $icon . ' ' . $availText; ?></small>
                                    </div>
                                    <form action="approve_application.php" method="POST">
                                        <input type="hidden" name="application_id" value="<?= $app['application_id']; ?>">
                                        <input type="hidden" name="project_id" value="<?= $id; ?>">
                                        <input type="hidden" name="volunteer_id" value="<?= $app['user_id']; ?>">
                                        
                                        <div class="input-group input-group-sm">
                                            <select name="role_in_project" class="form-select form-select-sm">
                                                <option value="member">Member</option>
                                                <option value="leader">Leader</option>
                                                <option value="support">Support</option>
                                            </select>
                                            <button type="submit" class="btn fw-semibold" style="background-color:#FFD700; color:#2B547E;">
                                                Approve
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="p-4 text-center">
                            <i class="bi bi-people text-muted" style="font-size: 1.5rem;"></i>
                            <p class="text-muted small mb-0 mt-2">No pending applications for this project.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header d-flex justify-content-between align-items-center text-white" style="background-color:#2B547E;">
                    <h5 class="mb-0" style="color:#FFD700;">Team Attendance</h5>
                    <span class="badge bg-secondary"><?= count($volunteers); ?> Members</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Volunteer</th>
                                    <th>Role</th>
                                    <th style="min-width: 440px;">Attendance Log</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($volunteers as $v): $isAbsent = ($v['att_status'] === 'absent'); ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($v['first_name'] . ' ' . $v['last_name']); ?></strong></td>
                                    <td><span class="badge bg-light text-dark border"><?= ucfirst($v['role_in_project']); ?></span></td>
                                    <td>
                                        <form action="../attendance/update_attendance.php" method="POST" class="d-flex align-items-center gap-2">
                                            <input type="hidden" name="project_id" value="<?= $id; ?>">
                                            <input type="hidden" name="volunteer_id" value="<?= $v['volunteer_id']; ?>">
                                            
                                            <select name="status" class="form-select form-select-sm status-select" style="width: 100px;">
                                                <option value="present" <?= $v['att_status'] == 'present' ? 'selected' : ''; ?>>Present</option>
                                                <option value="absent" <?= $v['att_status'] == 'absent' ? 'selected' : ''; ?>>Absent</option>
                                                <option value="late" <?= $v['att_status'] == 'late' ? 'selected' : ''; ?>>Late</option>
                                            </select>

                                            <div class="input-group input-group-sm" style="width: 145px;">
                                                <span class="input-group-text bg-light"><i class="bi bi-box-arrow-in-right text-success"></i></span>
                                                <input type="time" name="check_in_time" class="form-control time-input-field" 
                                                       value="<?= $v['check_in_time'] ? date("H:i", strtotime($v['check_in_time'])) : ''; ?>" <?= $isAbsent ? 'disabled' : '' ?>>
                                            </div>

                                            <div class="input-group input-group-sm" style="width: 145px;">
                                                <span class="input-group-text bg-light"><i class="bi bi-box-arrow-right text-danger"></i></span>
                                                <input type="time" name="check_out_time" class="form-control time-input-field" 
                                                       value="<?= $v['check_out_time'] ? date("H:i", strtotime($v['check_out_time'])) : ''; ?>" <?= $isAbsent ? 'disabled' : '' ?>>
                                            </div>

                                            <button type="submit" class="btn btn-sm text-white" style="background-color:#2B547E;">Save</button>
                                        </form>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="../volunteers/deleteVolunteer.php?project_id=<?= $id; ?>&volunteer_id=<?= $v['volunteer_id']; ?>" 
                                           class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove volunteer?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', function() {
        const row = this.closest('tr');
        const inputs = row.querySelectorAll('input[type="time"]');
        if (this.value === 'absent') {
            inputs.forEach(i => { i.disabled = true; i.value = ""; });
        } else {
            inputs.forEach(i => i.disabled = false);
        }
    });
});
</script>

<?php include("../../includes/footer.php"); ?>
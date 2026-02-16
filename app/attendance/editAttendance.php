<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

// Get attendance record
$attendance_id = $_GET['id'] ?? null;
if (!$attendance_id) {
    $_SESSION['error'] = "No attendance record specified.";
    header("Location: index.php");
    exit();
}

$sql = "SELECT * FROM attendance WHERE attendance_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $attendance_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$attendance = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$attendance) {
    $_SESSION['error'] = "Attendance record not found.";
    header("Location: index.php");
    exit();
}

// Fetch volunteers from users table
$volunteers = [];
$vsql = "SELECT user_id, CONCAT(first_name, ' ', last_name) AS name 
         FROM users WHERE role = 'volunteer' ORDER BY first_name ASC, last_name ASC";
$vresult = mysqli_query($conn, $vsql);
if ($vresult) {
    while ($row = mysqli_fetch_assoc($vresult)) {
        $volunteers[] = $row;
    }
}

// Fetch projects
$projects = [];
$psql = "SELECT project_id, title FROM projects ORDER BY title ASC";
$presult = mysqli_query($conn, $psql);
if ($presult) {
    while ($row = mysqli_fetch_assoc($presult)) {
        $projects[] = $row;
    }
}

// Handle form submission
if (isset($_POST['submit'])) {
    $volunteer_id  = trim($_POST['volunteer_id'] ?? '');
    $project_id    = trim($_POST['project_id'] ?? '');
    $status        = trim($_POST['status'] ?? '');
    $check_in_time = trim($_POST['check_in_time'] ?? '');
    $check_out_time= trim($_POST['check_out_time'] ?? '');

    if (empty($volunteer_id) || empty($project_id) || empty($status) || empty($check_in_time)) {
        $_SESSION['error'] = "Volunteer, Project, Status, and Check-in time are required.";
        header("Location: editAttendance.php?id=" . $attendance_id);
        exit();
    }

    $sql = "UPDATE attendance 
            SET volunteer_id = ?, project_id = ?, status = ?, check_in_time = ?, check_out_time = ?
            WHERE attendance_id = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        $_SESSION['error'] = "Database error: " . mysqli_error($conn);
        header("Location: editAttendance.php?id=" . $attendance_id);
        exit();
    }

    mysqli_stmt_bind_param($stmt, 'iisssi', $volunteer_id, $project_id, $status, $check_in_time, $check_out_time, $attendance_id);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Attendance record updated successfully.";
        mysqli_stmt_close($stmt);
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to update attendance: " . mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        header("Location: editAttendance.php?id=" . $attendance_id);
        exit();
    }
}
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Attendance</h4>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $attendance_id; ?>" method="POST">
                        
                        <!-- Volunteer Dropdown -->
                        <div class="mb-3">
                            <label for="volunteer_id" class="form-label">Volunteer <span class="text-danger">*</span></label>
                            <select class="form-select" id="volunteer_id" name="volunteer_id" required>
                                <option value="">-- Select Volunteer --</option>
                                <?php foreach ($volunteers as $vol): ?>
                                    <option value="<?php echo $vol['user_id']; ?>"
                                        <?php echo ($attendance['volunteer_id'] == $vol['user_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($vol['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Project Dropdown -->
                        <div class="mb-3">
                            <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                            <select class="form-select" id="project_id" name="project_id" required>
                                <option value="">-- Select Project --</option>
                                <?php foreach ($projects as $proj): ?>
                                    <option value="<?php echo $proj['project_id']; ?>"
                                        <?php echo ($attendance['project_id'] == $proj['project_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($proj['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="present" <?php echo ($attendance['status'] == 'present') ? 'selected' : ''; ?>>Present</option>
                                <option value="absent" <?php echo ($attendance['status'] == 'absent') ? 'selected' : ''; ?>>Absent</option>
                                <option value="late" <?php echo ($attendance['status'] == 'late') ? 'selected' : ''; ?>>Late</option>
                            </select>
                        </div>

                        <!-- Check-in -->
                        <div class="mb-3">
                            <label for="check_in_time" class="form-label">Check-in Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="check_in_time" name="check_in_time" 
                                   value="<?php echo htmlspecialchars($attendance['check_in_time']); ?>" required>
                        </div>

                        <!-- Check-out -->
                        <div class="mb-3">
                            <label for="check_out_time" class="form-label">Check-out Time</label>
                            <input type="time" class="form-control" id="check_out_time" name="check_out_time"
                                   value="<?php echo htmlspecialchars($attendance['check_out_time']); ?>">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Update
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>
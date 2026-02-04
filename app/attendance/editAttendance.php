<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid attendance ID";
    header("Location: index.php");
    exit();
}

$attendance_id = intval($_GET['id']);
$sql = "SELECT * FROM attendance WHERE attendance_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $attendance_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$attendance = mysqli_fetch_assoc($result);

if (!$attendance) {
    $_SESSION['error'] = "Attendance record not found";
    header("Location: index.php");
    exit();
}

if (isset($_POST['submit'])) {
    $status = trim($_POST['status'] ?? '');
    $check_out_time = trim($_POST['check_out_time'] ?? '');

    if (empty($status)) {
        $_SESSION['error'] = "Status is required";
        header("Location: editAttendance.php?id=" . $attendance_id);
        exit();
    }

    $sql_update = "UPDATE attendance SET status = ?, check_out_time = ? WHERE attendance_id = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);

    if (!$stmt_update) {
        $_SESSION['error'] = "Database error: " . mysqli_error($conn);
        header("Location: editAttendance.php?id=" . $attendance_id);
        exit();
    }

    $check_out_value = !empty($check_out_time) ? $check_out_time : NULL;

    mysqli_stmt_bind_param($stmt_update, 'ssi', $status, $check_out_value, $attendance_id);

    if (mysqli_stmt_execute($stmt_update)) {
        $_SESSION['success'] = "Attendance updated successfully";
        mysqli_stmt_close($stmt_update);
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to update attendance: " . mysqli_stmt_error($stmt_update);
        mysqli_stmt_close($stmt_update);
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
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Attendance</h4>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $attendance_id; ?>" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Volunteer ID</label>
                            <input type="text" class="form-control" value="<?php echo $attendance['volunteer_id']; ?>" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Project ID</label>
                            <input type="text" class="form-control" value="<?php echo $attendance['project_id']; ?>" disabled>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="present" <?php echo ($attendance['status'] === 'present') ? 'selected' : ''; ?>>Present</option>
                                <option value="absent" <?php echo ($attendance['status'] === 'absent') ? 'selected' : ''; ?>>Absent</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="check_out_time" class="form-label">Check-out Time</label>
                            <input type="datetime-local" class="form-control" id="check_out_time" name="check_out_time"
                                   value="<?php echo $attendance['check_out_time'] ? date('Y-m-d\TH:i', strtotime($attendance['check_out_time'])) : ''; ?>">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="submit" class="btn btn-warning"><i class="bi bi-check-circle me-1"></i>Update</button>
                            <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>

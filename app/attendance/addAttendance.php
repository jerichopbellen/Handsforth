<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

// Handle form submission
if (isset($_POST['submit'])) {
    $volunteer_id = trim($_POST['volunteer_id'] ?? '');
    $project_id   = trim($_POST['project_id'] ?? '');
    $status       = trim($_POST['status'] ?? '');

    if (empty($volunteer_id) || empty($project_id) || empty($status)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: addAttendance.php");
        exit();
    }

    $sql = "INSERT INTO attendance (volunteer_id, project_id, check_in_time, status) 
            VALUES (?, ?, NOW(), ?)";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        $_SESSION['error'] = "Database error: " . mysqli_error($conn);
        header("Location: addAttendance.php");
        exit();
    }

    mysqli_stmt_bind_param($stmt, 'iis', $volunteer_id, $project_id, $status);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Attendance record added successfully";
        mysqli_stmt_close($stmt);
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to add attendance: " . mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        header("Location: addAttendance.php");
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
                    <h4 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Add Attendance</h4>
                </div> 
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                        <div class="mb-3">
                            <label for="volunteer_id" class="form-label">Volunteer ID <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="volunteer_id" name="volunteer_id" required>
                        </div>

                        <div class="mb-3">
                            <label for="project_id" class="form-label">Project ID <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="project_id" name="project_id" required>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                            </select>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="submit" class="btn btn-success">
                                <i class="bi bi-check-circle me-1"></i>Save
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

<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

// Get assignment ID from URL
if (!isset($_GET['id'])) {
    header("Location: index.php?error=No assignment selected");
    exit();
}
$assignment_id = intval($_GET['id']);

// Fetch current assignment details
$sql = "SELECT pv.assignment_id, pv.project_id, pv.volunteer_id, pv.role_in_project,
               CONCAT(u.first_name, ' ', u.last_name) AS volunteer_name,
               p.title AS project_title
        FROM project_volunteers pv
        JOIN users u ON pv.volunteer_id = u.user_id
        JOIN projects p ON pv.project_id = p.project_id
        WHERE pv.assignment_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $assignment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$assignment = mysqli_fetch_assoc($result);

if (!$assignment) {
    header("Location: index.php?error=Assignment not found");
    exit();
}

// Fetch volunteers
$volunteers = mysqli_query($conn, "SELECT user_id, CONCAT(first_name, ' ', last_name) AS name FROM users WHERE role = 'volunteer' ORDER BY first_name ASC");

// Fetch projects
$projects = mysqli_query($conn, "SELECT project_id, title FROM projects ORDER BY title ASC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $volunteer_id = $_POST['volunteer_id'];
    $project_id   = $_POST['project_id'];
    $role_in_project = $_POST['role_in_project'];

    $update_sql = "UPDATE project_volunteers 
                   SET project_id = ?, volunteer_id = ?, role_in_project = ?
                   WHERE assignment_id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "iisi", $project_id, $volunteer_id, $role_in_project, $assignment_id);

    if (mysqli_stmt_execute($update_stmt)) {
        header("Location: index.php?success=Assignment updated successfully");
        exit();
    } else {
        $error = "Error updating assignment: " . mysqli_error($conn);
    }
}
?>

<div class="container my-5">
    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark">
            <h4><i class="bi bi-pencil-square me-2"></i>Edit Volunteer Assignment</h4>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="volunteer_id" class="form-label">Volunteer</label>
                    <select name="volunteer_id" id="volunteer_id" class="form-select" required>
                        <?php while ($v = mysqli_fetch_assoc($volunteers)): ?>
                            <option value="<?php echo $v['user_id']; ?>" 
                                <?php if ($v['user_id'] == $assignment['volunteer_id']) echo "selected"; ?>>
                                <?php echo htmlspecialchars($v['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="project_id" class="form-label">Project</label>
                    <select name="project_id" id="project_id" class="form-select" required>
                        <?php while ($p = mysqli_fetch_assoc($projects)): ?>
                            <option value="<?php echo $p['project_id']; ?>" 
                                <?php if ($p['project_id'] == $assignment['project_id']) echo "selected"; ?>>
                                <?php echo htmlspecialchars($p['title']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="role_in_project" class="form-label">Role in Project</label>
                    <input type="text" name="role_in_project" id="role_in_project" 
                           class="form-control" value="<?php echo htmlspecialchars($assignment['role_in_project']); ?>" required>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle me-1"></i>Update Assignment
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>

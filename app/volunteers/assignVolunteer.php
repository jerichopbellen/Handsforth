<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

// Fetch volunteers (users with role_name = volunteer)
$volunteers_sql = "SELECT u.user_id, CONCAT(u.first_name, ' ', u.last_name) AS name
                   FROM users u
                   INNER JOIN roles r ON u.role_id = r.role_id
                   WHERE r.role_name = 'volunteer'
                   ORDER BY u.first_name ASC";
$volunteers = mysqli_query($conn, $volunteers_sql);

// Fetch projects
$projects = mysqli_query($conn, "SELECT project_id, title FROM projects ORDER BY title ASC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $volunteer_id   = $_POST['volunteer_id'];
    $project_id     = $_POST['project_id'];
    $role_in_project = $_POST['role_in_project'];

    $sql = "INSERT INTO project_volunteers (project_id, volunteer_id, role_in_project, assigned_at)
            VALUES (?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iis", $project_id, $volunteer_id, $role_in_project);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: index.php?success=Volunteer assigned successfully");
        exit();
    } else {
        $error = "Error assigning volunteer: " . mysqli_error($conn);
    }
}
?>

<div class="container my-5">
    <div class="card shadow-sm border-0">
        <div class="card-header text-white" style="background-color:#2B547E;">
            <h4 class="mb-0" style="color:#FFD700;">
                <i class="bi bi-person-plus-fill me-2"></i>Assign Volunteer to Project
            </h4>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="volunteer_id" class="form-label">Volunteer</label>
                    <select name="volunteer_id" id="volunteer_id" class="form-select" required>
                        <option value="">-- Select Volunteer --</option>
                        <?php while ($v = mysqli_fetch_assoc($volunteers)): ?>
                            <option value="<?php echo $v['user_id']; ?>">
                                <?php echo htmlspecialchars($v['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="project_id" class="form-label">Project</label>
                    <select name="project_id" id="project_id" class="form-select" required>
                        <option value="">-- Select Project --</option>
                        <?php while ($p = mysqli_fetch_assoc($projects)): ?>
                            <option value="<?php echo $p['project_id']; ?>">
                                <?php echo htmlspecialchars($p['title']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="role_in_project" class="form-label">Role in Project</label>
                    <input type="text" name="role_in_project" id="role_in_project" class="form-control" placeholder="e.g., Team Leader, Member" required>
                </div>

                <button type="submit" class="btn fw-semibold" style="background-color:#2B547E; color:#FFD700;">
                    <i class="bi bi-check-circle me-1"></i>Assign Volunteer
                </button>
                <a href="index.php" class="btn fw-semibold" style="background-color:#FFD700; color:#2B547E;">
                    Cancel
                </a>
            </form>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>
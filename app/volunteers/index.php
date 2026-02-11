<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

// Fetch volunteers with their project assignments
$sql = "SELECT pv.assignment_id,
               CONCAT(u.first_name, ' ', u.last_name) AS volunteer_name,
               u.email,
               p.title AS project_title,
               pv.role_in_project,
               pv.assigned_at
        FROM project_volunteers pv
        JOIN users u ON pv.volunteer_id = u.user_id
        JOIN projects p ON pv.project_id = p.project_id
        ORDER BY pv.assigned_at DESC";

$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="bi bi-people-fill me-2"></i>Volunteers Assigned to Projects</h4>
            <div>
                <a href="addVolunteer.php" class="btn btn-light btn-sm me-2">
                    <i class="bi bi-person-plus-fill me-1"></i>Add Volunteer
                </a>
                <a href="assignVolunteer.php" class="btn btn-light btn-sm">
                    <i class="bi bi-plus-circle me-1"></i>Assign Volunteer
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Assignment ID</th>
                            <th>Volunteer</th>
                            <th>Email</th>
                            <th>Project</th>
                            <th>Role in Project</th>
                            <th>Assigned At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $row['assignment_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['volunteer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['project_title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['role_in_project']); ?></td>
                                    <td><?php echo $row['assigned_at']; ?></td>
                                    <td>
                                        <a href="editAssignment.php?id=<?php echo $row['assignment_id']; ?>" 
                                           class="btn btn-sm btn-warning"><i class="bi bi-pencil-square"></i> Edit</a>
                                        <a href="deleteAssignment.php?id=<?php echo $row['assignment_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to remove this assignment?');">
                                           <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No volunteer assignments found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>

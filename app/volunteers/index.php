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

    <div class="card shadow-sm border-0">
        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color:#2B547E;">
            <h4 class="mb-0" style="color:#FFD700;">
                <i class="bi bi-people-fill me-2"></i>Volunteers Assigned to Projects
            </h4>
            <a href="assignVolunteer.php" class="btn fw-semibold btn-sm" style="background-color:#FFD700; color:#2B547E;">
                <i class="bi bi-plus-circle me-1"></i>Assign Volunteer
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="fw-semibold">Assignment ID</th>
                            <th class="fw-semibold">Volunteer</th>
                            <th class="fw-semibold">Email</th>
                            <th class="fw-semibold">Project</th>
                            <th class="fw-semibold">Role in Project</th>
                            <th class="fw-semibold">Assigned At</th>
                            <th class="fw-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?= $row['assignment_id']; ?></td>
                                    <td><?= htmlspecialchars($row['volunteer_name']); ?></td>
                                    <td><?= htmlspecialchars($row['email']); ?></td>
                                    <td><?= htmlspecialchars($row['project_title']); ?></td>
                                    <td><?= htmlspecialchars($row['role_in_project']); ?></td>
                                    <td><?= htmlspecialchars($row['assigned_at']); ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="editAssignment.php?id=<?= $row['assignment_id']; ?>"  
                                               class="btn btn-sm fw-semibold" style="background-color:#2B547E; color:#FFD700;">
                                               <i class="bi bi-pencil-square"></i> Edit
                                            </a>
                                            <a href="deleteVolunteer.php?id=<?= $row['assignment_id']; ?>"  
                                               class="btn btn-sm fw-semibold" style="background-color:#FFD700; color:#2B547E;"
                                               onclick="return confirm('Are you sure you want to remove this assignment?');">
                                               <i class="bi bi-trash"></i> Delete
                                            </a>
                                        </div>
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
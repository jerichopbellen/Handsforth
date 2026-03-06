<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

// Build SQL
$sql = "
    SELECT project_id, title, description, date, location, status, username
    FROM projects
    INNER JOIN users ON projects.created_by = users.user_id
    WHERE 1
";

if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (
        title LIKE '%$search%' OR
        description LIKE '%$search%' OR
        location LIKE '%$search%' OR
        username LIKE '%$search%'
    )";
}

if (!empty($status)) {
    $status = mysqli_real_escape_string($conn, $status);
    $sql .= " AND status = '$status'";
}

$sql .= " ORDER BY date DESC";

$result = mysqli_query($conn, $sql);
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color:#2B547E;">
            <h4 class="mb-0" style="color:#FFD700;">
                <i class="bi bi-kanban-fill me-2"></i>Project Management
            </h4>
            <a href="create.php" class="btn fw-semibold" style="background-color:#FFD700; color:#2B547E;">
                <i class="bi bi-plus-circle me-1"></i>Add Project
            </a>
        </div>
        <div class="card-body">
            <!-- Search & Filter -->
            <form method="GET" class="row g-3 mb-3">
                <div class="col-md-5">
                    <input type="text"
                           name="search"
                           class="form-control"
                           placeholder="Search projects..."
                           value="<?= htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Pending" <?= ($status == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="Ongoing" <?= ($status == 'Ongoing') ? 'selected' : ''; ?>>Ongoing</option>
                        <option value="Completed" <?= ($status == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2 justify-content-end">
                    <button type="submit" class="btn fw-semibold" style="background-color:#2B547E; color:#FFD700;">Apply</button>
                    <a href="index.php" class="btn fw-semibold" style="background-color:#FFD700; color:#2B547E;">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Projects Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-hover table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="fw-semibold">ID</th>
                        <th class="fw-semibold">Title</th>
                        <th class="fw-semibold">Date</th>
                        <th class="fw-semibold">Location</th>
                        <th class="fw-semibold">Status</th>
                        <th class="fw-semibold">Created By</th>
                        <th class="fw-semibold" width="180">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= $row['project_id']; ?></td>
                                <td><?= htmlspecialchars($row['title']); ?></td>
                                <td><?= htmlspecialchars($row['date']); ?></td>
                                <td><?= htmlspecialchars($row['location']); ?></td>
                                <td>
                                    <span class="badge 
                                        <?php 
                                            if ($row['status'] === 'Completed') echo 'bg-success';
                                            elseif ($row['status'] === 'Ongoing') echo 'bg-warning text-dark';
                                            elseif ($row['status'] === 'Pending') echo 'bg-secondary';
                                        ?>">
                                        <?= htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['username']); ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="edit.php?id=<?= $row['project_id']; ?>" class="btn btn-sm fw-semibold" style="background-color:#2B547E; color:#FFD700;">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </a>
                                        <a href="view.php?id=<?= $row['project_id']; ?>" class="btn btn-sm fw-semibold" style="background-color:#FFD700; color:#2B547E;">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                        <a href="delete.php?id=<?= $row['project_id']; ?>" class="btn btn-sm fw-semibold" style="background-color:#dc3545; color:#fff;" onclick="return confirm('Delete this project?');">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center p-4">No projects found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>
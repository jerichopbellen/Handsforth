<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

$totalQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM projects");
$total = mysqli_fetch_assoc($totalQuery)['total'];

$pendingQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM projects WHERE status='Pending'");
$pending = mysqli_fetch_assoc($pendingQuery)['total'];

$ongoingQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM projects WHERE status='Ongoing'");
$ongoing = mysqli_fetch_assoc($ongoingQuery)['total'];

$completedQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM projects WHERE status='Completed'");
$completed = mysqli_fetch_assoc($completedQuery)['total'];

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

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

<div class="container mt-4">

    <?php include("../../includes/alert.php"); ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Project Management</h2>
        <a href="create.php" class="btn btn-success px-4">
            + Add Project
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET">
                <div class="row g-3 align-items-center">

                    <div class="col-md-5">
                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="🔍 Search projects..."
                               value="<?= htmlspecialchars($search); ?>">
                    </div>

                    <div class="col-md-4">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Pending" <?= ($status=='Pending')?'selected':''; ?>>Pending</option>
                            <option value="Ongoing" <?= ($status=='Ongoing')?'selected':''; ?>>Ongoing</option>
                            <option value="Completed" <?= ($status=='Completed')?'selected':''; ?>>Completed</option>
                        </select>
                    </div>

                    <div class="col-md-3 text-end">
                        <button type="submit" class="btn btn-primary w-50">
                            Apply
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary w-45">
                            Reset
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover table-bordered mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>

                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $row['project_id']; ?></td>
                            <td><?= htmlspecialchars($row['title']); ?></td>
                            <td><?= $row['date']; ?></td>
                            <td><?= htmlspecialchars($row['location']); ?></td>
                            <td>
                                <span class="badge bg-<?=
                                    ($row['status'] === 'Completed') ? 'success' :
                                    (($row['status'] === 'Ongoing') ? 'warning text-dark' : 'secondary');
                                ?>">
                                    <?= htmlspecialchars($row['status']); ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['username']); ?></td>
                            <td>
                                <a href="edit.php?id=<?= $row['project_id']; ?>"
                                   class="btn btn-sm btn-primary">Edit</a>

                                <a href="delete.php?id=<?= $row['project_id']; ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Delete this project?');">
                                   Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center p-4">
                            No projects found
                        </td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include("../../includes/footer.php"); ?>
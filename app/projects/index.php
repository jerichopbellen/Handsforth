<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

$sql = "
    SELECT project_id, title, description, date, location, status, created_by, username
    FROM projects
    INNER JOIN users ON projects.created_by = users.user_id
    ORDER BY date DESC
";
$result = mysqli_query($conn, $sql);
?>

<div class="container mt-4">
    <?php include("../../includes/alert.php"); ?>
    <h2>Project List</h2>

    <a href="create.php" class="btn btn-success mb-3">+ Add Project</a>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Description</th>
                <th>Date</th>
                <th>Location</th>
                <th>Status</th>
                <th>Created By</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $row['project_id']; ?></td>
                        <td><?= htmlspecialchars($row['title']); ?></td>
                        <td><?= htmlspecialchars($row['description']); ?></td>
                        <td><?= $row['date']; ?></td>
                        <td><?= htmlspecialchars($row['location']); ?></td>
                        <td>
                            <span class="badge bg-<?=
                                ($row['status'] === 'Completed') ? 'success' :
                                (($row['status'] === 'Ongoing') ? 'warning' : 'secondary');
                            ?>">
                                <?= htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['username']); ?></td>
                        <td>
                            <a href="edit.php?id=<?= $row['project_id']; ?>"
                               class="btn btn-sm btn-primary">
                                Edit
                            </a>
                            <a href="delete.php?id=<?= $row['project_id']; ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Are you sure you want to delete this project?');">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">
                        No records found
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include("../../includes/footer.php"); ?>
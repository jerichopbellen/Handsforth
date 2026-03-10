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
$totalProjects = mysqli_num_rows($result);
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>

    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark">
                <i class="bi bi-kanban-fill me-2"></i> Projects
            </h2>
            <p class="text-muted">Manage and track all your projects</p>
        </div>
        <div>
            <a href="create.php" class="btn fw-semibold" style="background-color:#FFD700; color:#2B547E;">
                <i class="bi bi-plus-circle me-2"></i> New Project
            </a>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label fw-semibold text-muted">Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                        <input type="text"
                               name="search"
                               class="form-control bg-light border-0"
                               placeholder="Title, description, location..."
                               value="<?= htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold text-muted">Status</label>
                    <select name="status" class="form-select bg-light border-0">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?= $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Ongoing" <?= $status === 'Ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                        <option value="Completed" <?= $status === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2 align-items-end">
                    <button type="submit" class="btn flex-grow-1 fw-semibold" style="background-color:#2B547E; color:#FFD700;">
                        Search
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary fw-semibold">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Counter -->
    <div class="mb-3">
        <small class="text-muted">Found <strong><?= $totalProjects; ?></strong> project<?= $totalProjects !== 1 ? 's' : ''; ?></small>
    </div>

    <!-- Projects Table -->
    <?php if ($result && $totalProjects > 0): ?>
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="fw-semibold">Title</th>
                            <th class="fw-semibold">Date</th>
                            <th class="fw-semibold">Location</th>
                            <th class="fw-semibold">Status</th>
                            <th class="fw-semibold">Created By</th>
                            <th class="fw-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($row['title']); ?></td>
                                <td><small class="text-muted"><?= date('M d, Y', strtotime($row['date'])); ?></small></td>
                                <td><small><?= htmlspecialchars($row['location']); ?></small></td>
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
                                <td><small class="text-muted"><?= htmlspecialchars($row['username']); ?></small></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="view.php?id=<?= $row['project_id']; ?>" class="btn btn-sm fw-semibold" style="background-color:#2B547E; color:#FFD700;" title="View details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="delete.php?id=<?= $row['project_id']; ?>" class="btn btn-sm fw-semibold" style="background-color:#dc3545; color:#fff;" onclick="return confirm('Delete this project?');" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color:#ccc;"></i>
                <p class="text-muted mt-4 mb-0">No projects found. Try adjusting your search or create a new one.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include("../../includes/footer.php"); ?>
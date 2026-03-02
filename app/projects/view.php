<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

// Get project ID safely
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Invalid project ID.</div></div>";
    include("../../includes/footer.php");
    exit;
}

// Fetch project using prepared statement
$stmt = $conn->prepare("
    SELECT p.*, u.username
    FROM projects p
    INNER JOIN users u ON p.created_by = u.user_id
    WHERE p.project_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();
$stmt->close();

if (!$project) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Project not found.</div></div>";
    include("../../includes/footer.php");
    exit;
}

$statusClass = ($project['status'] === 'Completed') ? 'success' : (($project['status'] === 'Ongoing') ? 'warning text-dark' : 'secondary');
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Project Details</h2>
        <a href="index.php" class="btn btn-secondary">Back</a>
    </div>

    <div class="card shadow-lg border-0">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><?= htmlspecialchars($project['title']); ?></h4>
        </div>
        <div class="card-body">
            <p class="mb-3"><strong>Description:</strong><br>
                <?= nl2br(htmlspecialchars($project['description'])); ?>
            </p>

            <div class="row mb-3">
                <div class="col-md-4">
                    <p><strong>Date:</strong> <?= htmlspecialchars($project['date']); ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Location:</strong> <?= htmlspecialchars($project['location']); ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Status:</strong>
                        <span class="badge bg-<?= $statusClass; ?>"><?= htmlspecialchars($project['status']); ?></span>
                    </p>
                </div>
            </div>

            <p><strong>Created By:</strong> <?= htmlspecialchars($project['username']); ?></p>

            <div class="mt-4 d-flex gap-2">
                <a href="edit.php?id=<?= $project['project_id']; ?>" class="btn btn-primary">Edit</a>
                <a href="delete.php?id=<?= $project['project_id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this project?');">Delete</a>
            </div>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>
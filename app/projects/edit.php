<?php
session_start();

include("../../includes/header.php");
include("../../includes/config.php");

$id = $_GET['id'];

$result = mysqli_query($conn, "SELECT * FROM projects INNER JOIN users ON projects.created_by = users.user_id WHERE project_id=$id");
$project = mysqli_fetch_assoc($result);

if (isset($_POST['update'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $date = $_POST['date'];
    $location = $_POST['location'];
    $status = $_POST['status'];

    mysqli_query($conn, "UPDATE projects SET
        title='$title',
        description='$description',
        date='$date',
        location='$location',
        status='$status'
        WHERE project_id=$id");

    header("Location: index.php");
    exit;
}
?>

<div class="container mt-5">
    <div class="card shadow-lg border-0">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Edit Project</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="title" class="form-label fw-bold">Title</label>
                    <input type="text" name="title" class="form-control form-control-lg" value="<?= htmlspecialchars($project['title']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label fw-bold">Description</label>
                    <textarea name="description" class="form-control form-control-lg" rows="4" required><?= htmlspecialchars($project['description']); ?></textarea>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="date" class="form-label fw-bold">Date</label>
                            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($project['date']); ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="location" class="form-label fw-bold">Location</label>
                            <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($project['location']); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="status" class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="Ongoing" <?= $project['status']=='Ongoing'?'selected':'' ?>>Ongoing</option>
                                <option value="Completed" <?= $project['status']=='Completed'?'selected':'' ?>>Completed</option>
                                <option value="Pending" <?= $project['status']=='Pending'?'selected':'' ?>>Pending</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="created_by" class="form-label fw-bold">Created By</label>
                            <input type="text" name="created_by" class="form-control" value="<?= htmlspecialchars($project['username']); ?>" readonly>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button name="update" class="btn btn-success px-4">Update</button>
                    <a href="index.php" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>S
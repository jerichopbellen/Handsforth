<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

$id = $_GET['id'];

$result = mysqli_query($conn, "SELECT * FROM projects INNER JOIN users ON projects.created_by = users.user_id WHERE project_id=$id");
$project = mysqli_fetch_assoc($result);

if (isset($_POST['update'])) {
    $title       = $_POST['title'];
    $description = $_POST['description'];
    $date        = $_POST['date'];
    $location    = $_POST['location'];
    $status      = $_POST['status'];

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

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm border-0">
                <div class="card-header text-white" style="background-color:#2B547E;">
                    <h4 class="mb-0" style="color:#FFD700;">
                        <i class="bi bi-pencil-square me-2"></i>Edit Project
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST">

                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                            <input type="text" id="title" name="title" class="form-control"
                                   value="<?= htmlspecialchars($project['title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                            <textarea id="description" name="description" class="form-control" rows="4" required><?= htmlspecialchars($project['description']); ?></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date" class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                                    <input type="date" id="date" name="date" class="form-control"
                                           value="<?= htmlspecialchars($project['date']); ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="location" class="form-label fw-semibold">Location <span class="text-danger">*</span></label>
                                    <input type="text" id="location" name="location" class="form-control"
                                           value="<?= htmlspecialchars($project['location']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                    <select id="status" name="status" class="form-select" required>
                                        <option value="Planned" <?= $project['status']=='Planned'?'selected':'' ?>>Planned</option>
                                        <option value="Ongoing" <?= $project['status']=='Ongoing'?'selected':'' ?>>Ongoing</option>
                                        <option value="Completed" <?= $project['status']=='Completed'?'selected':'' ?>>Completed</option>
                                        <option value="Cancelled" <?= $project['status']=='Cancelled'?'selected':'' ?>>Cancelled</option>
                                        
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="created_by" class="form-label fw-semibold">Created By</label>
                                    <input type="text" id="created_by" name="created_by" class="form-control"
                                           value="<?= htmlspecialchars($project['username']); ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button name="update" class="btn fw-semibold" style="background-color:#2B547E; color:#FFD700;">
                                <i class="bi bi-check-circle me-1"></i>Update
                            </button>
                            <a href="view.php?id=<?= $id ?>" class="btn fw-semibold" style="background-color:#FFD700; color:#2B547E;">
                                <i class="bi bi-arrow-left me-1"></i>Cancel
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>
<?php
session_start();

include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$loggedInAdminId   = $_SESSION['user_id'];
$loggedInAdminName = $_SESSION['username'];
$statusSelected    = '';

if (isset($_POST['save'])) {
    $title       = $_POST['title'];
    $description = $_POST['description'];
    $date        = $_POST['date'];
    $location    = $_POST['location'];
    $status      = $_POST['status'];

    $created_by  = $loggedInAdminId;
    $statusSelected = $status;

    $stmt = $conn->prepare(
        "INSERT INTO projects (title, description, date, location, status, created_by)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sssssi", $title, $description, $date, $location, $status, $created_by);
    $stmt->execute();
    $stmt->close();

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
                        <i class="bi bi-plus-circle me-2"></i>Add Project
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST">

                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                            <input type="text" id="title" name="title" class="form-control"
                                   placeholder="Enter project title"
                                   required
                                   value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                            <textarea id="description" name="description" class="form-control" rows="4"
                                      placeholder="Enter project description"
                                      required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date" class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                                    <input type="date" id="date" name="date" class="form-control"
                                           required
                                           value="<?= isset($_POST['date']) ? htmlspecialchars($_POST['date']) : '' ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="location" class="form-label fw-semibold">Location <span class="text-danger">*</span></label>
                                    <input type="text" id="location" name="location" class="form-control"
                                           placeholder="Enter location"
                                           required
                                           value="<?= isset($_POST['location']) ? htmlspecialchars($_POST['location']) : '' ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                    <select id="status" name="status" class="form-select" required>
                                        <option value="" disabled <?= $statusSelected == '' ? 'selected' : '' ?>>Select Status</option>
                                        <option value="Planned" <?= $statusSelected == 'Planned' ? 'selected' : '' ?>>Planned</option>
                                        <option value="Ongoing" <?= $statusSelected == 'Ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                        <option value="Completed" <?= $statusSelected == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="created_by" class="form-label fw-semibold">Created By</label>
                                    <input type="text" id="created_by" name="created_by" class="form-control"
                                           value="<?= htmlspecialchars($loggedInAdminName); ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button name="save" class="btn fw-semibold" style="background-color:#2B547E; color:#FFD700;">
                                <i class="bi bi-check-circle me-1"></i>Save
                            </button>
                            <a href="index.php" class="btn fw-semibold" style="background-color:#FFD700; color:#2B547E;">
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
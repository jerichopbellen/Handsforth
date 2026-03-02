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
$statusSelected = '';

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
    $stmt->bind_param(
        "sssssi",
        $title,
        $description,
        $date,
        $location,
        $status,
        $created_by
    );
    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit;
}
?>

<div class="container mt-5">
    <div class="card shadow-lg border-0">
        <div class="card-header bg-success text-white">
            <h3 class="mb-0">Add Project</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="title" class="form-label fw-bold">Title</label>
                    <input type="text"
                           name="title"
                           class="form-control form-control-lg"
                           placeholder="Enter project title"
                           required
                           value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label fw-bold">Description</label>
                    <textarea name="description"
                              class="form-control form-control-lg"
                              rows="4"
                              placeholder="Enter project description"
                              required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="date" class="form-label fw-bold">Date</label>
                            <input type="date"
                                   name="date"
                                   class="form-control"
                                   required
                                   value="<?= isset($_POST['date']) ? htmlspecialchars($_POST['date']) : '' ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="location" class="form-label fw-bold">Location</label>
                            <input type="text"
                                   name="location"
                                   class="form-control"
                                   placeholder="Enter location"
                                   required
                                   value="<?= isset($_POST['location']) ? htmlspecialchars($_POST['location']) : '' ?>">
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="status" class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="" disabled <?= $statusSelected == '' ? 'selected' : '' ?>> Select Status </option>
                                <option value="Ongoing" <?= $statusSelected == 'Ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                <option value="Completed" <?= $statusSelected == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="Pending" <?= $statusSelected == 'Pending' ? 'selected' : '' ?>>Pending</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="created_by" class="form-label fw-bold">Created By</label>
                            <input type="text"
                                   name="created_by"
                                   class="form-control"
                                   value="<?= htmlspecialchars($loggedInAdminName); ?>"
                                   readonly>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button name="save" class="btn btn-success px-4">Save</button>
                    <a href="index.php" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>
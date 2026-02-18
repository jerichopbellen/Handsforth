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

<div class="container mt-4">
    <h2>Add Project</h2>

    <form method="POST">


        <label for="title">Title</label>
        <input type="text"
               name="title"
               class="form-control mb-2"
               placeholder="Title"
               required
               value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">

        <label for="description">Description</label>
        <textarea name="description"
                  class="form-control mb-2"
                  placeholder="Description"
                  required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
        
        <label for="date">Date</label>
        <input type="date"
               name="date"
               class="form-control mb-2"
               required
               value="<?= isset($_POST['date']) ? htmlspecialchars($_POST['date']) : '' ?>">

        
        <label for="location">Location</label>
        <input type="text"
               name="location"
               class="form-control mb-2"
               placeholder="Location"
               required
               value="<?= isset($_POST['location']) ? htmlspecialchars($_POST['location']) : '' ?>">


        <label for="status">Status</label>
        <select name="status" class="form-control mb-3" required>
            <option value="" disabled <?= $statusSelected == '' ? 'selected' : '' ?>>
                -- Select Status --
            </option>
            <option value="Ongoing" <?= $statusSelected == 'Ongoing' ? 'selected' : '' ?>>
                Ongoing
            </option>
            <option value="Completed" <?= $statusSelected == 'Completed' ? 'selected' : '' ?>>
                Completed
            </option>
            <option value="Pending" <?= $statusSelected == 'Pending' ? 'selected' : '' ?>>
                Pending
            </option>
        </select>

        <div class="mb-3">
            <label for="created_by" class="form-label">Created By</label>
            <input type="text"
                   name="created_by"
                   class="form-control"
                   value="<?= htmlspecialchars($loggedInAdminName); ?>"
                   readonly>
        </div>

        <button name="save" class="btn btn-success">Save</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>

    </form>
</div>

<?php include("../../includes/footer.php"); ?>
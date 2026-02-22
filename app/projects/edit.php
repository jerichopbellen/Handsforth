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
    $created_by = $_POST['created_by'];

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

<div class="container mt-4">
    <h2>Edit Project</h2>

    <form method="POST">
        <label for="title">Title</label>
        <input type="text" name="title" class="form-control mb-2" value="<?= $project['title']; ?>" required>
        <label for="description">Description</label>
        <textarea name="description" class="form-control mb-2" required><?= $project['description']; ?></textarea>
        <label for="date">Date</label>
        <input type="date" name="date" class="form-control mb-2" value="<?= $project['date']; ?>" required>
        <label for="location">Location</label>
        <input type="text" name="location" class="form-control mb-2" value="<?= $project['location']; ?>" required>
        <label for="status">Status</label>
        <select name="status" class="form-control mb-2">
            <option <?= $project['status']=='Ongoing'?'selected':'' ?>>Ongoing</option>
            <option <?= $project['status']=='Completed'?'selected':'' ?>>Completed</option>
            <option <?= $project['status']=='Pending'?'selected':'' ?>>Pending</option>
        </select>
        <label for="created_by">Created By</label>
        <input type="text" name="created_by" class="form-control mb-2" value="<?= $project['username']; ?>" readonly>

        <button name="update" class="btn btn-primary">Update</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include("../../includes/footer.php"); ?>
<?php
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/config.php';

$id = $_GET['id'];

$result = mysqli_query($conn, "SELECT * FROM projects WHERE project_id=$id");
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
        status='$status',
        created_by='$created_by'
        WHERE project_id=$id");

    header("Location: index.php");
    exit;
}
?>

<div class="container mt-4">
    <h2>Edit Project</h2>

    <form method="POST">
        <input type="text" name="title" class="form-control mb-2" value="<?= $project['title']; ?>" required>
        <textarea name="description" class="form-control mb-2" required><?= $project['description']; ?></textarea>
        <input type="date" name="date" class="form-control mb-2" value="<?= $project['date']; ?>" required>
        <input type="text" name="location" class="form-control mb-2" value="<?= $project['location']; ?>" required>

        <select name="status" class="form-control mb-2">
            <option <?= $project['status']=='Ongoing'?'selected':'' ?>>Ongoing</option>
            <option <?= $project['status']=='Completed'?'selected':'' ?>>Completed</option>
            <option <?= $project['status']=='Pending'?'selected':'' ?>>Pending</option>
        </select>

        <input type="text" name="created_by" class="form-control mb-2" value="<?= $project['created_by']; ?>" required>

        <button name="update" class="btn btn-primary">Update</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

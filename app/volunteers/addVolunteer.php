<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $status     = $_POST['status'];

    $sql = "INSERT INTO users (first_name, last_name, email, password, role, status)
            VALUES (?, ?, ?, ?, 'volunteer', ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssss", $first_name, $last_name, $email, $password, $status);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: index.php?success=Volunteer added successfully");
        exit();
    } else {
        $error = "Error adding volunteer: " . mysqli_error($conn);
    }
}
?>

<div class="container my-5">
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
            <h4><i class="bi bi-person-plus-fill me-2"></i>Add Volunteer</h4>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" name="first_name" id="first_name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" name="last_name" id="last_name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle me-1"></i>Add Volunteer
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>

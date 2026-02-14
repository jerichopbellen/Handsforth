<?php
session_start();

include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

$user_id = intval($_GET['id']);
$sql = "SELECT * FROM users WHERE user_id = $user_id";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "User not found.";
    header("Location: listUsers.php");
    exit();
}

$user = mysqli_fetch_assoc($result);
?>

<div class="container mt-4">
    <?php include("../../includes/alert.php"); ?>

    <div class="card shadow-lg">
        <div class="card-header bg-dark text-white">
            <h4 class="mb-0">Edit User Profile</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Profile Picture Column -->
                <div class="col-md-4 text-center border-end">
                    <img id="preview" 
                         src="<?= !empty($user['img_path']) ? htmlspecialchars($user['img_path']) : '../../assets/default-avatar.png'; ?>" 
                         alt="Profile Picture" 
                         class="img-thumbnail mb-3 rounded-circle" 
                         style="max-width:200px; width: 200px; height: 200px; object-fit: cover;">

                    <form action="updateUserPhoto.php" method="POST" enctype="multipart/form-data" class="mb-3">
                        <input type="hidden" name="user_id" value="<?= $user['user_id']; ?>">
                        <div class="mb-3">
                            <input type="file" name="img_path" id="img_path" class="form-control" accept="image/*">
                        </div>
                        <button type="submit" name="action" value="update" class="btn btn-primary w-100 mb-2">Upload New</button>
                        <button type="submit" name="action" value="remove" class="btn btn-danger w-100"
                                onclick="return confirm('Remove this profile picture?');">Remove</button>
                    </form>
                </div>

                <!-- User Details Column -->
                <div class="col-md-8">
                    <form action="updateUser.php" method="POST" class="row g-3">
                        <input type="hidden" name="user_id" value="<?= $user['user_id']; ?>">

                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select name="role" id="role" class="form-select" required>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="volunteer" <?= $user['role'] === 'volunteer' ? 'selected' : ''; ?>>Volunteer</option>
                                <option value="user" <?= $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username" class="form-control" 
                                   value="<?= htmlspecialchars($user['username']); ?>" required>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="password" class="form-label">Password (leave blank to keep current)</label>
                            <input type="password" name="password" id="password" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" name="first_name" id="first_name" class="form-control"
                                   value="<?= htmlspecialchars($user['first_name']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" name="last_name" id="last_name" class="form-control"
                                   value="<?= htmlspecialchars($user['last_name']); ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control"
                                   value="<?= htmlspecialchars($user['email']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" name="phone" id="phone" class="form-control"
                                   value="<?= htmlspecialchars($user['phone']); ?>">
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-success">Update User</button>
                            <a href="listUsers.php" class="btn btn-secondary">Back to List</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const imgInput = document.getElementById('img_path');
const preview = document.getElementById('preview');

imgInput.addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php include("../../includes/footer.php"); ?>
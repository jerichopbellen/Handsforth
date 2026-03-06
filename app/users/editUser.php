<?php
session_start();

include("../../includes/header.php");
include("../../includes/config.php");

// Restrict access to admins only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

$user_id = intval($_GET['id']);

// Fetch user with role_id
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "User not found.";
    header("Location: listUsers.php");
    exit();
}

$user = mysqli_fetch_assoc($result);

// Fetch roles for dropdown
$roles = [];
$role_sql = "SELECT role_id, role_name FROM roles ORDER BY role_name ASC";
$role_result = mysqli_query($conn, $role_sql);
if ($role_result && mysqli_num_rows($role_result) > 0) {
    while ($row = mysqli_fetch_assoc($role_result)) {
        $roles[] = $row;
    }
}
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm border-0">
                <div class="card-header text-white" style="background-color:#2B547E;">
                    <h4 class="mb-0" style="color:#FFD700;">
                        <i class="bi bi-pencil-square me-2"></i>Edit User Profile
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Profile Picture Column -->
                        <div class="col-md-4 text-center border-end">
                            <img id="preview" 
                                 src="<?= !empty($user['img_path']) ? '../' . htmlspecialchars($user['img_path']) : '../../assets/default-avatar.png'; ?>" 
                                 alt="Profile Picture" 
                                 class="img-thumbnail mb-3 rounded-circle" 
                                 style="max-width:200px; width:200px; height:200px; object-fit:cover;">

                            <form action="updateUserPhoto.php" method="POST" enctype="multipart/form-data" class="mb-3">
                                <input type="hidden" name="user_id" value="<?= $user['user_id']; ?>">
                                <div class="mb-3">
                                    <input type="file" name="img_path" id="img_path" class="form-control" accept="image/*">
                                </div>
                                <button type="submit" name="action" value="update" class="btn fw-semibold mb-2" style="background-color:#2B547E; color:#FFD700;">Upload New</button>
                                <button type="submit" name="action" value="remove" class="btn fw-semibold" style="background-color:#FFD700; color:#2B547E;"
                                        onclick="return confirm('Remove this profile picture?');">Remove</button>
                            </form>
                        </div>

                        <!-- User Details Column -->
                        <div class="col-md-8">
                            <form action="updateUser.php" method="POST" class="row g-3">
                                <input type="hidden" name="user_id" value="<?= $user['user_id']; ?>">

                                <div class="col-md-6 mb-3">
                                    <label for="role_id" class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                                    <select name="role_id" id="role_id" class="form-select" required>
                                        <option value="">-- Select Role --</option>
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?= htmlspecialchars($role['role_id']); ?>"
                                                <?= ($user['role_id'] == $role['role_id']) ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($role['role_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                                    <input type="text" name="username" id="username" class="form-control" 
                                           value="<?= htmlspecialchars($user['username']); ?>" required>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="password" class="form-label fw-semibold">Password (leave blank to keep current)</label>
                                    <input type="password" name="password" id="password" class="form-control">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label fw-semibold">First Name</label>
                                    <input type="text" name="first_name" id="first_name" class="form-control"
                                           value="<?= htmlspecialchars($user['first_name']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label fw-semibold">Last Name</label>
                                    <input type="text" name="last_name" id="last_name" class="form-control"
                                           value="<?= htmlspecialchars($user['last_name']); ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label fw-semibold">Email</label>
                                    <input type="email" name="email" id="email" class="form-control"
                                           value="<?= htmlspecialchars($user['email']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label fw-semibold">Phone</label>
                                    <input type="text" name="phone" id="phone" class="form-control"
                                           value="<?= htmlspecialchars($user['phone']); ?>">
                                </div>

                                <div class="d-flex gap-2 mt-4">
                                    <button type="submit" class="btn fw-semibold" style="background-color:#2B547E; color:#FFD700;">
                                        <i class="bi bi-check-circle me-1"></i>Update User
                                    </button>
                                    <a href="listUsers.php" class="btn fw-semibold" style="background-color:#FFD700; color:#2B547E;">
                                        <i class="bi bi-arrow-left me-1"></i>Back to List
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
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
<?php
session_start();

include("../../includes/header.php");
include("../../includes/config.php");

// Restrict access to admins only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}
?>

<div class="container mt-4">
    <?php include("../../includes/alert.php"); ?>

    <div class="card shadow-lg">
        <div class="card-header bg-dark text-white">
            <h4 class="mb-0">Add New User Profile</h4>
        </div>
        <div class="card-body">
            <form action="storeUser.php" method="POST" enctype="multipart/form-data" class="row g-3">
                <!-- Profile Picture Column -->
                <div class="col-md-4 text-center border-end">
                    <img id="preview" 
                         src="../../assets/default-avatar.png" 
                         alt="Profile Preview" 
                         class="img-thumbnail mb-3 rounded-circle" 
                         style="max-width:200px; width:200px; height:200px; object-fit:cover;">
                    <div class="mb-3">
                        <input type="file" name="img_path" id="img_path" class="form-control" accept="image/*">
                    </div>
                </div>

                <!-- User Details Column -->
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select name="role" id="role" class="form-select" required>
                                <option value="admin">Admin</option>
                                <option value="volunteer">Volunteer</option>
                                <option value="user">User</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username" class="form-control" required>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" name="first_name" id="first_name" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" name="last_name" id="last_name" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" name="phone" id="phone" class="form-control">
                        </div>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-success">Save User</button>
                        <a href="listUsers.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
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
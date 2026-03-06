<?php
session_start();

include("../../includes/header.php");
include("../../includes/config.php");

// Restrict access to admins only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

// Join users with roles to get role name
$sql = "
    SELECT u.user_id, r.role_name, u.username, u.first_name, u.last_name,
           u.email, u.phone, u.created_at, u.updated_at
    FROM users u
    INNER JOIN roles r ON u.role_id = r.role_id
    ORDER BY u.created_at DESC
";
$result = mysqli_query($conn, $sql);
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>

    <div class="card shadow-sm border-0">
        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color:#2B547E;">
            <h4 class="mb-0" style="color:#FFD700;">
                <i class="bi bi-people-fill me-2"></i>User List
            </h4>
            <a href="createUser.php" class="btn fw-semibold" style="background-color:#FFD700; color:#2B547E;">
                <i class="bi bi-plus-circle me-1"></i>Add User
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="fw-semibold">ID</th>
                            <th class="fw-semibold">Role</th>
                            <th class="fw-semibold">Username</th>
                            <th class="fw-semibold">First Name</th>
                            <th class="fw-semibold">Last Name</th>
                            <th class="fw-semibold">Email</th>
                            <th class="fw-semibold">Phone</th>
                            <th class="fw-semibold">Created At</th>
                            <th class="fw-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                            <?php while ($user = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['user_id']); ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                                if ($user['role_name'] === 'admin') echo 'bg-primary';
                                                elseif ($user['role_name'] === 'volunteer') echo 'bg-info text-dark';
                                                else echo 'bg-secondary';
                                            ?>">
                                            <?= htmlspecialchars($user['role_name']); ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($user['username']); ?></td>
                                    <td><?= htmlspecialchars($user['first_name']); ?></td>
                                    <td><?= htmlspecialchars($user['last_name']); ?></td>
                                    <td><?= htmlspecialchars($user['email']); ?></td>
                                    <td><?= htmlspecialchars($user['phone']); ?></td>
                                    <td><?= htmlspecialchars($user['created_at']); ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="editUser.php?id=<?= $user['user_id']; ?>"  
                                               class="btn btn-sm fw-semibold" style="background-color:#2B547E; color:#FFD700;">
                                               <i class="bi bi-pencil-square"></i> Edit
                                            </a>
                                            <a href="deleteUser.php?id=<?= $user['user_id']; ?>"  
                                               class="btn btn-sm fw-semibold" style="background-color:#FFD700; color:#2B547E;"
                                               onclick="return confirm('Are you sure you want to delete this user?');">
                                               <i class="bi bi-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>
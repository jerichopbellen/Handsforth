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
$totalUsers = mysqli_num_rows($result);
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>

    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark">
                <i class="bi bi-people-fill me-2"></i> Users
            </h2>
            <p class="text-muted">Manage and track all system users</p>
        </div>
        <div>
            <a href="createUser.php" class="btn fw-semibold" style="background-color:#FFD700; color:#2B547E;">
                <i class="bi bi-plus-circle me-2"></i> Add User
            </a>
        </div>
    </div>

    <!-- Results Counter -->
    <div class="mb-3">
        <small class="text-muted">Found <strong><?= $totalUsers; ?></strong> user<?= $totalUsers !== 1 ? 's' : ''; ?></small>
    </div>

    <!-- Users Table -->
    <?php if ($result && $totalUsers > 0): ?>
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
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
                            <th class="fw-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
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
                                <td><small class="text-muted"><?= date('M d, Y', strtotime($user['created_at'])); ?></small></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="editUser.php?id=<?= $user['user_id']; ?>" 
                                           class="btn btn-sm fw-semibold" style="background-color:#2B547E; color:#FFD700;" title="Edit">
                                           <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="deleteUser.php?id=<?= $user['user_id']; ?>" 
                                           class="btn btn-sm fw-semibold" style="background-color:#dc3545; color:#fff;" 
                                           onclick="return confirm('Delete this user?');" title="Delete">
                                           <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color:#ccc;"></i>
                <p class="text-muted mt-4 mb-0">No users found. Try adding a new one.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include("../../includes/footer.php"); ?>
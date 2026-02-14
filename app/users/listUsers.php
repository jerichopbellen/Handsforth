<?php
session_start();

include("../../includes/header.php");
include("../../includes/config.php");

// Restrict access to admins only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

$sql = "
    SELECT user_id, role, username, first_name, last_name, email, phone, created_at, updated_at
    FROM users
    ORDER BY created_at DESC
";
$result = mysqli_query($conn, $sql);
?>

<div class="container mt-4">
    <?php include("../../includes/alert.php"); ?>
    <h2>User List</h2>

    <a href="createUser.php" class="btn btn-success mb-3">+ Add User</a>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Role</th>
                <th>Username</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while ($user = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['user_id']); ?></td>
                        <td>
                            <span class="badge bg-<?= 
                                ($user['role'] === 'admin') ? 'primary' : 
                                (($user['role'] === 'volunteer') ? 'info' : 'secondary'); 
                            ?>">
                                <?= htmlspecialchars($user['role']); ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($user['username']); ?></td>
                        <td><?= htmlspecialchars($user['first_name']); ?></td>
                        <td><?= htmlspecialchars($user['last_name']); ?></td>
                        <td><?= htmlspecialchars($user['email']); ?></td>
                        <td><?= htmlspecialchars($user['phone']); ?></td>
                        <td><?= htmlspecialchars($user['created_at']); ?></td>
                        <td>
                            <a href="editUser.php?id=<?= $user['user_id']; ?>" 
                               class="btn btn-sm btn-primary">
                                Edit
                            </a>
                            <a href="deleteUser.php?id=<?= $user['user_id']; ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Are you sure you want to delete this user?');">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="11" class="text-center">No users found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include("../../includes/footer.php"); ?>
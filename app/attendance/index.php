<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

// Fetch attendance records
$sql = "SELECT a.attendance_id, 
               CONCAT(u.first_name, ' ', u.last_name) AS volunteer_name, 
               p.title AS project_name, 
               a.check_in_time, a.check_out_time, a.status
        FROM attendance a
        JOIN users u ON a.volunteer_id = u.user_id
        JOIN projects p ON a.project_id = p.project_id
        ORDER BY a.check_in_time DESC";

$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>

    <div class="card shadow-sm"> 
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center"> 
            <h4 class="mb-0"><i class="bi bi-people me-2"></i>Attendance Records</h4> 
            <a href="addAttendance.php" class="btn btn-light btn-sm text-dark"> 
                <i class="bi bi-plus-circle me-1"></i>Add Attendance 
            </a> 
        </div> 
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Volunteer</th>
                            <th>Project</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $row['attendance_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['volunteer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['project_name']); ?></td>
                                    <td><?php echo date('h:i A', strtotime($row['check_in_time'])); ?></td>
                                    <td><?php echo $row['check_out_time'] ? date('h:i A', strtotime($row['check_out_time'])) : '-'; ?></td>
                                    <td>
                                        <?php 
                                            $badgeClass = 'bg-secondary';
                                            if ($row['status'] === 'present') $badgeClass = 'bg-success';
                                            elseif ($row['status'] === 'absent') $badgeClass = 'bg-danger';
                                            elseif ($row['status'] === 'late') $badgeClass = 'bg-warning text-dark';
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="editAttendance.php?id=<?php echo $row['attendance_id']; ?>" 
                                           class="btn btn-sm btn-warning">
                                           <i class="bi bi-pencil-square"></i> Edit
                                        </a>
                                        <a href="deleteAttendance.php?id=<?php echo $row['attendance_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this record?');">
                                           <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No attendance records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>
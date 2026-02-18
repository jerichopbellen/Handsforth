<?php
session_start();
include("../includes/header.php");
include("../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'volunteer') {
    header("Location: ../app/users/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch current profile
$sql = "SELECT u.first_name, u.last_name, u.email, u.phone, u.img_path,
               v.skills, v.availability
        FROM users u
        LEFT JOIN volunteer_details v ON u.user_id = v.volunteer_id
        WHERE u.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$profile = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
?>

<div class="container my-5">
    <?php include("../includes/alert.php"); ?>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow">
                <div class="card-header bg-dark text-white py-3">
                    <h4 class="mb-0"><i class="bi bi-person-circle me-2"></i>My Profile</h4>
                </div>
                <div class="card-body p-4">
                    <form action="updateProfile.php" method="POST" enctype="multipart/form-data">
                        <!-- Profile Image Section -->
                        <div class="mb-4 pb-4 border-bottom">
                            <label class="form-label fw-bold mb-3">Profile Photo</label>
                            <div class="text-center">
                                <div class="position-relative d-inline-block">
                                    <img id="preview"
                                         src="<?php echo !empty($profile['img_path']) ? htmlspecialchars($profile['img_path']) : '../assets/default-avatar.png'; ?>"
                                         alt="Profile Image"
                                         class="rounded-circle border border-3 border-dark"
                                         style="width: 150px; height: 150px; object-fit: cover;">
                                    <label for="img_path" class="position-absolute bottom-0 end-0 btn btn-sm btn-dark rounded-circle" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                                        <i class="bi bi-camera-fill"></i>
                                    </label>
                                </div>
                                <input type="file" class="form-control d-none" name="img_path" id="img_path" accept="image/*">
                                <small class="text-muted d-block mt-2">Click camera icon to change photo</small>
                            </div>
                        </div>

                        <!-- Basic Information Section -->
                        <div class="mb-4">
                            <h5 class="mb-3 fw-bold"><i class="bi bi-info-circle me-2"></i>Personal Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="first_name" name="first_name"
                                           value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="last_name" name="last_name"
                                           value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email"
                                       value="<?php echo htmlspecialchars($profile['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control form-control-lg" id="phone" name="phone"
                                       value="<?php echo htmlspecialchars($profile['phone']); ?>" placeholder="+1 (555) 123-4567">
                            </div>
                        </div>

                        <!-- Volunteer Details Section -->
                        <div class="mb-4">
                            <h5 class="mb-3 fw-bold"><i class="bi bi-briefcase me-2"></i>Volunteer Information</h5>
                            <div class="mb-3">
                                <label for="skills" class="form-label">Your Skills</label>
                                <textarea class="form-control" id="skills" name="skills" rows="3" placeholder="e.g., Teaching, Mentoring, Event Planning..."><?php echo htmlspecialchars($profile['skills']); ?></textarea>
                                <small class="text-muted">Describe your professional skills and expertise</small>
                            </div>
                            <div class="mb-3">
                                <label for="availability" class="form-label">Availability <span class="text-danger">*</span></label>
                                <select class="form-select form-select-lg" id="availability" name="availability" required>
                                    <option value="">-- Select Your Availability --</option>
                                    <option value="weekdays" <?php echo $profile['availability'] === 'weekdays' ? 'selected' : ''; ?>>Weekdays Only</option>
                                    <option value="weekends" <?php echo $profile['availability'] === 'weekends' ? 'selected' : ''; ?>>Weekends Only</option>
                                    <option value="anytime" <?php echo $profile['availability'] === 'anytime' ? 'selected' : ''; ?>>Anytime</option>
                                </select>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end pt-3 border-top">
                            <a href="index.php" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-arrow-left me-2"></i>Cancel
                            </a>
                            <button type="submit" name="submit" class="btn btn-dark btn-lg">
                                <i class="bi bi-check-circle me-2"></i>Save Changes
                            </button>
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

<?php include("../includes/footer.php"); ?>
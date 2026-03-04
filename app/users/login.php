<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (isset($_SESSION['user_id'])) {
    header("Location: ../../public/index.php");
    exit();
}

if (isset($_POST['submit'])) {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);
    $pass  = sha1(trim($_POST['password']));

    $_SESSION['email_input'] = $_POST['email'];

    if (empty($_POST['email']) || empty($_POST['password'])) {
        $_SESSION['flash'] = 'All fields are required';
        header("Location: login.php");
        exit();
    }

    mysqli_begin_transaction($conn);

    try {
        // Join users with roles to get role name
        $sql = "SELECT u.user_id, u.email, r.role_name, u.username
                FROM users u
                INNER JOIN roles r ON u.role_id = r.role_id
                WHERE u.email=? AND u.password_hash=?
                LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, 'ss', $email, $pass);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        mysqli_stmt_bind_result($stmt, $user_id, $email, $role_name, $username);

        if (mysqli_stmt_num_rows($stmt) === 1) {
            mysqli_stmt_fetch($stmt);

            $_SESSION['email']     = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
            $_SESSION['user_id']   = (int)$user_id;
            $_SESSION['role']      = $role_name; // store role name
            $_SESSION['username']  = $username;
            mysqli_stmt_close($stmt);
            mysqli_commit($conn);

            unset($_SESSION['email_input']);

            if ($role_name === 'admin') {
                header("Location: ../../app/projects/index.php");
            } else {
                header("Location: ../../public/index.php");
            }

            exit();
        } else {
            mysqli_stmt_close($stmt);
            mysqli_commit($conn);

            $_SESSION['flash'] = 'Wrong email or password';
            header("Location: login.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: login.php");
        exit();
    }
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <?php include("../../includes/alert.php"); ?>
            <div class="card shadow-lg border-0">
                <div class="card-header text-center text-white" style="background-color:#2B547E;">
                    <h4 class="mb-0">
                        <i class="bi bi-person-circle me-2"></i>Login to Your Account
                    </h4>
                </div>
                <div class="card-body">
                    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST">
                        <div class="mb-3">
                            <label for="form2Example1" class="form-label">Email address</label>
                            <input type="text" id="form2Example1" class="form-control" name="email"
                                   value="<?php if(isset($_SESSION['email_input'])) { echo htmlspecialchars($_SESSION['email_input']); } ?>">
                        </div>

                        <div class="mb-3">
                            <label for="form2Example2" class="form-label">Password</label>
                            <input type="password" id="form2Example2" class="form-control" name="password">
                        </div>
                        <button type="submit" 
                                class="btn w-100 fw-semibold" 
                                name="submit" 
                                style="background-color:#2B547E; color:#FFD700; box-shadow:0 4px 8px rgba(0,0,0,0.4);">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign in
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include("../../includes/footer.php");
?>
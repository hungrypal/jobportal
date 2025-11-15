<?php
require_once 'includes/db_connect.php';
$page_title = 'Login';

$error = '';
$success = '';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, full_name, user_type FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_type'] = $user['user_type'];
                
                $success = 'Login successful! Redirecting...';
                header("refresh:1;url=dashboard.php");
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="form-container">
                <div class="text-center mb-4">
                    <i class="fas fa-sign-in-alt text-primary" style="font-size: 3rem;"></i>
                    <h2 class="fw-bold mt-3">Welcome Back</h2>
                    <p class="text-muted">Sign in to your JobPortal account</p>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <div class="form-group">
                        <label for="username" class="form-label">Username or Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required>
                            <div class="invalid-feedback">Please enter your username or email.</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                            <div class="invalid-feedback">Please enter your password.</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i>Sign In
                    </button>
                </form>

                <div class="text-center">
                    <a href="#" class="text-primary mb-3 d-block">Forgot your password?</a>
                    <hr>
                    <p class="mb-0">Don't have an account? <a href="register.php" class="text-primary fw-bold">Register here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Demo Credentials Info -->
<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title text-center mb-3">
                        <i class="fas fa-info-circle text-info me-2"></i>Demo Credentials
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary">Job Seeker Account:</h6>
                            <p class="mb-1"><strong>Username:</strong> jobseeker1</p>
                            <p class="mb-1"><strong>Password:</strong> password</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-success">Recruiter Account:</h6>
                            <p class="mb-1"><strong>Username:</strong> recruiter1</p>
                            <p class="mb-1"><strong>Password:</strong> password</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        const icon = this.querySelector('i');
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });
    
    // Auto-fill demo credentials
    const demoButtons = document.querySelectorAll('.demo-login');
    demoButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const username = this.getAttribute('data-username');
            const password = this.getAttribute('data-password');
            
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
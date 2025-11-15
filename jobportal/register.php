<?php
require_once 'includes/db_connect.php';
$page_title = 'Register';

$error = '';
$success = '';

// Get user type from URL parameter
$user_type = isset($_GET['type']) ? $_GET['type'] : '';
if (!in_array($user_type, ['jobseeker', 'recruiter'])) {
    $user_type = '';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $user_type = $_POST['user_type'];
    $phone = trim($_POST['phone']);
    $company_name = isset($_POST['company_name']) ? trim($_POST['company_name']) : null;
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($user_type)) {
        $error = 'All required fields must be filled.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($user_type == 'recruiter' && empty($company_name)) {
        $error = 'Company name is required for recruiters.';
    } else {
        try {
            // Check if username or email already exists
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $check_stmt->execute([$username, $email]);
            
            if ($check_stmt->fetch()) {
                $error = 'Username or email already exists.';
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insert_stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, user_type, phone, company_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insert_stmt->execute([$username, $email, $hashed_password, $full_name, $user_type, $phone, $company_name]);
                
                $success = 'Registration successful! You can now login.';
                
                // Auto login after registration
                $user_id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $full_name;
                $_SESSION['user_type'] = $user_type;
                
                // Redirect to dashboard after 2 seconds
                header("refresh:2;url=dashboard.php");
            }
        } catch (PDOException $e) {
            $error = 'Registration failed. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="form-container">
                <div class="text-center mb-4">
                    <i class="fas fa-user-plus text-primary" style="font-size: 3rem;"></i>
                    <h2 class="fw-bold mt-3">Join JobPortal</h2>
                    <p class="text-muted">Create your account and start your journey</p>
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
                    <div class="mt-2">
                        <small>Redirecting to dashboard...</small>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <!-- User Type Selection -->
                    <div class="form-group">
                        <label class="form-label fw-bold">I am a:</label>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="user_type" id="jobseeker" value="jobseeker" <?php echo $user_type == 'jobseeker' ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="jobseeker">
                                        <i class="fas fa-user me-2"></i>Job Seeker
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="user_type" id="recruiter" value="recruiter" <?php echo $user_type == 'recruiter' ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="recruiter">
                                        <i class="fas fa-building me-2"></i>Recruiter
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="invalid-feedback">Please select your account type.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username" class="form-label">Username *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-at"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                    <div class="invalid-feedback">Please enter a username.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                            <div class="invalid-feedback">Please enter your full name.</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                    </div>

                    <!-- Company Name (only for recruiters) -->
                    <div class="form-group" id="company_field" style="display: none;">
                        <label for="company_name" class="form-label">Company Name *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                            <input type="text" class="form-control" id="company_name" name="company_name">
                            <div class="invalid-feedback">Please enter your company name.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password" class="form-label">Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                                    <div class="invalid-feedback">Password must be at least 6 characters long.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <div class="invalid-feedback">Please confirm your password.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" class="text-primary">Terms and Conditions</a>
                            </label>
                            <div class="invalid-feedback">You must agree to the terms and conditions.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                        <i class="fas fa-user-plus me-2"></i>Create Account
                    </button>
                </form>

                <div class="text-center">
                    <p class="mb-0">Already have an account? <a href="login.php" class="text-primary fw-bold">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide company field based on user type
document.addEventListener('DOMContentLoaded', function() {
    const jobseekerRadio = document.getElementById('jobseeker');
    const recruiterRadio = document.getElementById('recruiter');
    const companyField = document.getElementById('company_field');
    const companyInput = document.getElementById('company_name');
    
    function toggleCompanyField() {
        if (recruiterRadio.checked) {
            companyField.style.display = 'block';
            companyInput.required = true;
        } else {
            companyField.style.display = 'none';
            companyInput.required = false;
        }
    }
    
    jobseekerRadio.addEventListener('change', toggleCompanyField);
    recruiterRadio.addEventListener('change', toggleCompanyField);
    
    // Initial check
    toggleCompanyField();
    
    // Password confirmation validation
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePassword() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    password.addEventListener('input', validatePassword);
    confirmPassword.addEventListener('input', validatePassword);
});
</script>

<?php include 'includes/footer.php'; ?>
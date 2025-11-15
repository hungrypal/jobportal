<?php
require_once 'includes/db_connect.php';
$page_title = 'Post Job';

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'recruiter') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$edit_mode = false;
$job_data = null;

// Check if editing existing job
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $job_id = (int)$_GET['edit'];
    
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND recruiter_id = ?");
    $stmt->execute([$job_id, $user_id]);
    $job_data = $stmt->fetch();
    
    if (!$job_data) {
        header('Location: dashboard.php');
        exit;
    }
}

// Get user's company name
$user_stmt = $pdo->prepare("SELECT company_name FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_info = $user_stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements']);
    $salary_min = !empty($_POST['salary_min']) ? (float)$_POST['salary_min'] : null;
    $salary_max = !empty($_POST['salary_max']) ? (float)$_POST['salary_max'] : null;
    $location = trim($_POST['location']);
    $job_type = $_POST['job_type'];
    $company_name = trim($_POST['company_name']);
    $status = isset($_POST['status']) ? $_POST['status'] : 'active';
    
    // Validation
    if (empty($title) || empty($description) || empty($location) || empty($job_type) || empty($company_name)) {
        $error = 'Please fill in all required fields.';
    } elseif ($salary_min && $salary_max && $salary_min > $salary_max) {
        $error = 'Minimum salary cannot be greater than maximum salary.';
    } else {
        try {
            if ($edit_mode) {
                // Update existing job
                $stmt = $pdo->prepare("UPDATE jobs SET title = ?, description = ?, requirements = ?, 
                                      salary_min = ?, salary_max = ?, location = ?, job_type = ?, 
                                      company_name = ?, status = ? WHERE id = ? AND recruiter_id = ?");
                $stmt->execute([$title, $description, $requirements, $salary_min, $salary_max, 
                               $location, $job_type, $company_name, $status, $job_id, $user_id]);
                $success = 'Job updated successfully!';
            } else {
                // Insert new job
                $stmt = $pdo->prepare("INSERT INTO jobs (title, description, requirements, salary_min, 
                                      salary_max, location, job_type, company_name, recruiter_id) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $requirements, $salary_min, $salary_max, 
                               $location, $job_type, $company_name, $user_id]);
                $success = 'Job posted successfully!';
            }
        } catch (PDOException $e) {
            $error = 'Failed to ' . ($edit_mode ? 'update' : 'post') . ' job. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="form-container">
                <div class="text-center mb-4">
                    <i class="fas fa-plus-circle text-primary" style="font-size: 3rem;"></i>
                    <h2 class="fw-bold mt-3"><?php echo $edit_mode ? 'Edit Job' : 'Post a New Job'; ?></h2>
                    <p class="text-muted">Find the perfect candidate for your position</p>
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
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="title" class="form-label">Job Title *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo $edit_mode ? htmlspecialchars($job_data['title']) : ''; ?>" required>
                                <div class="invalid-feedback">Please enter a job title.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="job_type" class="form-label">Job Type *</label>
                                <select class="form-select" id="job_type" name="job_type" required>
                                    <option value="">Select Type</option>
                                    <option value="full-time" <?php echo ($edit_mode && $job_data['job_type'] == 'full-time') ? 'selected' : ''; ?>>Full-time</option>
                                    <option value="part-time" <?php echo ($edit_mode && $job_data['job_type'] == 'part-time') ? 'selected' : ''; ?>>Part-time</option>
                                    <option value="contract" <?php echo ($edit_mode && $job_data['job_type'] == 'contract') ? 'selected' : ''; ?>>Contract</option>
                                    <option value="internship" <?php echo ($edit_mode && $job_data['job_type'] == 'internship') ? 'selected' : ''; ?>>Internship</option>
                                </select>
                                <div class="invalid-feedback">Please select a job type.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="company_name" class="form-label">Company Name *</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" 
                                       value="<?php echo $edit_mode ? htmlspecialchars($job_data['company_name']) : htmlspecialchars($user_info['company_name']); ?>" required>
                                <div class="invalid-feedback">Please enter the company name.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="location" class="form-label">Location *</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?php echo $edit_mode ? htmlspecialchars($job_data['location']) : ''; ?>" 
                                       placeholder="e.g. New York, NY" required>
                                <div class="invalid-feedback">Please enter the job location.</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Job Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="6" 
                                  placeholder="Describe the role, responsibilities, and what makes this position exciting..." required><?php echo $edit_mode ? htmlspecialchars($job_data['description']) : ''; ?></textarea>
                        <div class="invalid-feedback">Please enter a job description.</div>
                    </div>

                    <div class="form-group">
                        <label for="requirements" class="form-label">Requirements & Qualifications</label>
                        <textarea class="form-control" id="requirements" name="requirements" rows="4" 
                                  placeholder="List the required skills, experience, education, etc..."><?php echo $edit_mode ? htmlspecialchars($job_data['requirements']) : ''; ?></textarea>
                        <small class="form-text text-muted">Optional: List specific requirements for this position</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="salary_min" class="form-label">Minimum Salary (Annual)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rs.</span>
                                    <input type="number" class="form-control" id="salary_min" name="salary_min" 
                                           value="<?php echo $edit_mode ? $job_data['salary_min'] : ''; ?>" 
                                           min="0" step="1000" placeholder="50000">
                                </div>
                                <small class="form-text text-muted">Optional: Leave blank to hide salary range</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="salary_max" class="form-label">Maximum Salary (Annual)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rs.</span>
                                    <input type="number" class="form-control" id="salary_max" name="salary_max" 
                                           value="<?php echo $edit_mode ? $job_data['salary_max'] : ''; ?>" 
                                           min="0" step="1000" placeholder="80000">
                                </div>
                                <div id="salaryDisplay" class="small text-muted mt-1"></div>
                            </div>
                        </div>
                    </div>

                    <?php if ($edit_mode): ?>
                    <div class="form-group">
                        <label for="status" class="form-label">Job Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?php echo $job_data['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="closed" <?php echo $job_data['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                        <small class="form-text text-muted">Change status to closed if no longer accepting applications</small>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I confirm that this job posting complies with <a href="#" class="text-primary">JobPortal's Terms of Service</a>
                            </label>
                            <div class="invalid-feedback">You must agree to the terms.</div>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-<?php echo $edit_mode ? 'save' : 'plus'; ?> me-2"></i>
                            <?php echo $edit_mode ? 'Update Job' : 'Post Job'; ?>
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Job Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Job Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="jobPreview"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="document.querySelector('form').submit()">
                    <?php echo $edit_mode ? 'Update Job' : 'Post Job'; ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add preview functionality
    const previewBtn = document.createElement('button');
    previewBtn.type = 'button';
    previewBtn.className = 'btn btn-outline-primary btn-lg';
    previewBtn.innerHTML = '<i class="fas fa-eye me-2"></i>Preview';
    previewBtn.onclick = showPreview;
    
    const submitBtn = document.querySelector('button[type="submit"]');
    submitBtn.parentNode.insertBefore(previewBtn, submitBtn);
    
    function showPreview() {
        const title = document.getElementById('title').value;
        const company = document.getElementById('company_name').value;
        const location = document.getElementById('location').value;
        const jobType = document.getElementById('job_type').value;
        const description = document.getElementById('description').value;
        const requirements = document.getElementById('requirements').value;
        const salaryMin = document.getElementById('salary_min').value;
        const salaryMax = document.getElementById('salary_max').value;
        
        let salaryInfo = '';
        if (salaryMin && salaryMax) {
            salaryInfo = `<span class="salary-badge">$${parseInt(salaryMin).toLocaleString()} - $${parseInt(salaryMax).toLocaleString()}</span>`;
        }
        
        const previewHTML = `
            <div class="job-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h4 class="fw-bold text-primary mb-1">${title || 'Job Title'}</h4>
                        <p class="company-name mb-0">
                            <i class="fas fa-building me-1"></i>${company || 'Company Name'}
                        </p>
                    </div>
                    <span class="badge bg-primary">${jobType || 'Job Type'}</span>
                </div>
                
                <div class="job-details mb-3">
                    <span class="job-detail">
                        <i class="fas fa-map-marker-alt me-1"></i>${location || 'Location'}
                    </span>
                    ${salaryInfo}
                    <span class="job-detail">
                        <i class="fas fa-clock me-1"></i>Posted today
                    </span>
                </div>
                
                <div class="mb-3">
                    <h6 class="fw-bold">Job Description:</h6>
                    <p class="text-muted">${description || 'Job description will appear here...'}</p>
                </div>
                
                ${requirements ? `
                <div class="mb-3">
                    <h6 class="fw-bold">Requirements:</h6>
                    <p class="text-muted">${requirements}</p>
                </div>
                ` : ''}
                
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Posted by ${company || 'Company'}</small>
                    <button class="btn btn-primary btn-sm" disabled>Apply Now</button>
                </div>
            </div>
        `;
        
        document.getElementById('jobPreview').innerHTML = previewHTML;
        new bootstrap.Modal(document.getElementById('previewModal')).show();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
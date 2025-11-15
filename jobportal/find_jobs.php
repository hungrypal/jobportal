<?php
require_once 'includes/db_connect.php';
$page_title = 'Find Jobs';

$error = '';
$success = '';
$selected_job = null;

// Handle job application
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply'])) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    
    if ($_SESSION['user_type'] != 'jobseeker') {
        $error = 'Only job seekers can apply for jobs.';
    } else {
        $job_id = (int)$_POST['job_id'];
        $cover_letter = trim($_POST['cover_letter']);
        $user_id = $_SESSION['user_id'];
        
        try {
            // Check if already applied
            $check_stmt = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND user_id = ?");
            $check_stmt->execute([$job_id, $user_id]);
            
            if ($check_stmt->fetch()) {
                $error = 'You have already applied for this job.';
            } else {
                // Submit application
                $apply_stmt = $pdo->prepare("INSERT INTO applications (job_id, user_id, cover_letter) VALUES (?, ?, ?)");
                $apply_stmt->execute([$job_id, $user_id, $cover_letter]);
                $success = 'Application submitted successfully!';
            }
        } catch (PDOException $e) {
            $error = 'Failed to submit application. Please try again.';
        }
    }
}

// Build search query
$where_conditions = ["j.status = 'active'"];
$params = [];

// Search filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$job_type = isset($_GET['job_type']) ? $_GET['job_type'] : '';
$salary_min = isset($_GET['salary_min']) ? (int)$_GET['salary_min'] : 0;

if (!empty($search)) {
    $where_conditions[] = "(j.title LIKE ? OR j.description LIKE ? OR j.company_name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($location)) {
    $where_conditions[] = "j.location LIKE ?";
    $params[] = "%$location%";
}

if (!empty($job_type)) {
    $where_conditions[] = "j.job_type = ?";
    $params[] = $job_type;
}

if ($salary_min > 0) {
    $where_conditions[] = "j.salary_min >= ?";
    $params[] = $salary_min;
}

$where_clause = implode(' AND ', $where_conditions);

// Get jobs with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

$sql = "SELECT j.*, u.full_name as recruiter_name 
        FROM jobs j 
        JOIN users u ON j.recruiter_id = u.id 
        WHERE $where_clause 
        ORDER BY j.created_at DESC 
        LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Get total count for pagination
$count_sql = "SELECT COUNT(*) FROM jobs j WHERE $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_jobs = $count_stmt->fetchColumn();
$total_pages = ceil($total_jobs / $per_page);

// Get selected job details if job_id is provided
if (isset($_GET['job_id'])) {
    $job_id = (int)$_GET['job_id'];
    $job_stmt = $pdo->prepare("SELECT j.*, u.full_name as recruiter_name, u.email as recruiter_email 
                              FROM jobs j 
                              JOIN users u ON j.recruiter_id = u.id 
                              WHERE j.id = ?");
    $job_stmt->execute([$job_id]);
    $selected_job = $job_stmt->fetch();
    
    // Check if user already applied (if logged in)
    $already_applied = false;
    if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'jobseeker') {
        $applied_stmt = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND user_id = ?");
        $applied_stmt->execute([$job_id, $_SESSION['user_id']]);
        $already_applied = $applied_stmt->fetch() ? true : false;
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-12">
            <div class="filter-card">
                <h4 class="mb-3">
                    <i class="fas fa-filter me-2 text-primary"></i>Find Your Perfect Job
                </h4>
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search Jobs</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="search" name="search"
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="Job title, company, or keywords...">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="location" class="form-label">Location</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                            <input type="text" class="form-control" id="location" name="location"
                                   value="<?php echo htmlspecialchars($location); ?>"
                                   placeholder="City, State">
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="job_type" class="form-label">Job Type</label>
                        <select class="form-select" id="job_type" name="job_type">
                            <option value="">All Types</option>
                            <option value="full-time" <?php echo $job_type == 'full-time' ? 'selected' : ''; ?>>Full-time</option>
                            <option value="part-time" <?php echo $job_type == 'part-time' ? 'selected' : ''; ?>>Part-time</option>
                            <option value="contract" <?php echo $job_type == 'contract' ? 'selected' : ''; ?>>Contract</option>
                            <option value="internship" <?php echo $job_type == 'internship' ? 'selected' : ''; ?>>Internship</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="salary_min" class="form-label">Min Salary</label>
                        <select class="form-select" id="salary_min" name="salary_min">
                            <option value="">Any Salary</option>
                            <option value="30000" <?php echo $salary_min == 30000 ? 'selected' : ''; ?>>Rs.30,000+</option>
                            <option value="50000" <?php echo $salary_min == 50000 ? 'selected' : ''; ?>>Rs.50,000+</option>
                            <option value="70000" <?php echo $salary_min == 70000 ? 'selected' : ''; ?>>Rs.70,000+</option>
                            <option value="100000" <?php echo $salary_min == 100000 ? 'selected' : ''; ?>>Rs.100,000+</option>
                        </select>
                    </div>
                    
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary d-block w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
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

    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <?php echo number_format($total_jobs); ?> Jobs Found
                    <?php if (!empty($search) || !empty($location) || !empty($job_type) || $salary_min > 0): ?>
                    <small class="text-muted">- Filtered Results</small>
                    <?php endif; ?>
                </h5>
                
                <?php if (!empty($search) || !empty($location) || !empty($job_type) || $salary_min > 0): ?>
                <a href="find_jobs.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-times me-1"></i>Clear Filters
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="<?php echo $selected_job ? 'col-lg-8' : 'col-12'; ?>">
            <?php if (empty($jobs)): ?>
            <div class="text-center py-5">
                <i class="fas fa-search text-muted" style="font-size: 4rem;"></i>
                <h4 class="mt-3 text-muted">No Jobs Found</h4>
                <p class="text-muted">Try adjusting your search criteria or check back later for new opportunities.</p>
                <a href="find_jobs.php" class="btn btn-primary mt-3">
                    <i class="fas fa-refresh me-2"></i>View All Jobs
                </a>
            </div>
            <?php else: ?>
            <div class="row">
                <?php foreach ($jobs as $job): ?>
                <div class="col-lg-<?php echo $selected_job ? '12' : '6'; ?> col-xl-<?php echo $selected_job ? '12' : '4'; ?> mb-4">
                    <div class="job-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <h5 class="fw-bold mb-1">
                                    <a href="?job_id=<?php echo $job['id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($job['title']); ?>
                                    </a>
                                </h5>
                                <p class="company-name mb-2">
                                    <i class="fas fa-building me-1"></i>
                                    <?php echo htmlspecialchars($job['company_name']); ?>
                                </p>
                            </div>
                            <?php
                                $type_class = 'bg-primary'; // Default
                                if ($job['job_type'] == 'part-time') $type_class = 'bg-info';
                                if ($job['job_type'] == 'contract') $type_class = 'bg-success';
                                if ($job['job_type'] == 'internship') $type_class = 'bg-secondary';
                            ?>
                            <span class="badge <?php echo $type_class; ?>"><?php echo ucfirst($job['job_type']); ?></span>
                        </div>
                        
                        <p class="text-muted mb-3"><?php echo substr(htmlspecialchars($job['description']), 0, 120) . '...'; ?></p>
                        
                        <div class="job-details mb-3">
                            <span class="job-detail">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <?php echo htmlspecialchars($job['location']); ?>
                            </span>
                            <?php if ($job['salary_min'] && $job['salary_max']): ?>
                            <span class="job-detail">
                                <i class="fas fa-rupee-sign me-1"></i>
                                Rs.<?php echo number_format($job['salary_min']); ?> - Rs.<?php echo number_format($job['salary_max']); ?>
                            </span>
                            <?php endif; ?>
                            <span class="job-detail">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                            </span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Posted by <?php echo htmlspecialchars($job['recruiter_name']); ?>
                            </small>
                            <div>
                                <a href="?job_id=<?php echo $job['id']; ?>" class="btn btn-outline-primary btn-sm me-2">
                                    <i class="fas fa-eye me-1"></i>View
                                </a>
                                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'jobseeker'): ?>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#applyModal<?php echo $job['id']; ?>">
                                    <i class="fas fa-paper-plane me-1"></i>Apply
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'jobseeker'): ?>
                <div class="modal fade" id="applyModal<?php echo $job['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Apply for <?php echo htmlspecialchars($job['title']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                    <div class="mb-3">
                                        <label for="cover_letter<?php echo $job['id']; ?>" class="form-label">Cover Letter</label>
                                        <textarea class="form-control" id="cover_letter<?php echo $job['id']; ?>" name="cover_letter" 
                                                  rows="5" placeholder="Tell the employer why you're perfect for this role..."></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="apply" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-1"></i>Submit Application
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <?php if ($total_pages > 1): ?>
            <nav aria-label="Job listings pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if ($selected_job): ?>
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 2rem;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h4 class="fw-bold text-primary mb-1"><?php echo htmlspecialchars($selected_job['title']); ?></h4>
                            <p class="company-name mb-0">
                                <i class="fas fa-building me-1"></i>
                                <?php echo htmlspecialchars($selected_job['company_name']); ?>
                            </p>
                        </div>
                        <?php
                            $type_class_selected = 'bg-primary'; // Default
                            if ($selected_job['job_type'] == 'part-time') $type_class_selected = 'bg-info';
                            if ($selected_job['job_type'] == 'contract') $type_class_selected = 'bg-success';
                            if ($selected_job['job_type'] == 'internship') $type_class_selected = 'bg-secondary';
                        ?>
                        <span class="badge <?php echo $type_class_selected; ?>"><?php echo ucfirst($selected_job['job_type']); ?></span>
                    </div>

                    <div class="job-details mb-4">
                        <span class="job-detail d-block mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <?php echo htmlspecialchars($selected_job['location']); ?>
                        </span>
                        <?php if ($selected_job['salary_min'] && $selected_job['salary_max']): ?>
                        <span class="job-detail d-block mb-2">
                            <i class="fas fa-dollar-sign me-2"></i>
                            $<?php echo number_format($selected_job['salary_min']); ?> - Rs.<?php echo number_format($selected_job['salary_max']); ?>
                        </span>
                        <?php endif; ?>
                        <span class="job-detail d-block mb-2">
                            <i class="fas fa-clock me-2"></i>
                            Posted <?php echo date('M j, Y', strtotime($selected_job['created_at'])); ?>
                        </span>
                        <span class="job-detail d-block">
                            <i class="fas fa-user me-2"></i>
                            <?php echo htmlspecialchars($selected_job['recruiter_name']); ?>
                        </span>
                    </div>

                    <div class="mb-4">
                        <h6 class="fw-bold">Job Description</h6>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($selected_job['description'])); ?></p>
                    </div>

                    <?php if (!empty($selected_job['requirements'])): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold">Requirements</h6>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($selected_job['requirements'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="login.php" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to Apply
                    </a>
                    <?php elseif ($_SESSION['user_type'] == 'recruiter'): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        You are logged in as a recruiter.
                    </div>
                    <?php elseif ($already_applied): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        You have already applied for this job.
                    </div>
                    <?php else: ?>
                    <button class="btn btn-primary w-100 mb-3" data-bs-toggle="modal" data-bs-target="#applyModalSelected">
                        <i class="fas fa-paper-plane me-2"></i>Apply for this Job
                    </button>
                    <?php endif; ?>

                    <a href="find_jobs.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-arrow-left me-2"></i>Back to All Jobs
                    </a>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'jobseeker' && !$already_applied): ?>
        <div class="modal fade" id="applyModalSelected" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Apply for <?php echo htmlspecialchars($selected_job['title']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="job_id" value="<?php echo $selected_job['id']; ?>">
                            <div class="mb-3">
                                <label for="cover_letter_selected" class="form-label">Cover Letter</label>
                                <textarea class="form-control" id="cover_letter_selected" name="cover_letter" 
                                          rows="5" placeholder="Tell the employer why you're perfect for this role..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="apply" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i>Submit Application
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
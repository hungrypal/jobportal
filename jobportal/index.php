<?php
require_once 'includes/db_connect.php';
$page_title = 'Home';

// Get latest jobs for homepage
$stmt = $pdo->prepare("SELECT j.*, u.full_name as recruiter_name FROM jobs j 
                       JOIN users u ON j.recruiter_id = u.id 
                       WHERE j.status = 'active' 
                       ORDER BY j.created_at DESC LIMIT 6");
$stmt->execute();
$latest_jobs = $stmt->fetchAll();

// Get job statistics
$stats_stmt = $pdo->prepare("SELECT 
    (SELECT COUNT(*) FROM jobs WHERE status = 'active') as total_jobs,
    (SELECT COUNT(*) FROM users WHERE user_type = 'recruiter') as total_recruiters,
    (SELECT COUNT(*) FROM users WHERE user_type = 'jobseeker') as total_jobseekers,
    (SELECT COUNT(*) FROM applications) as total_applications
");
$stats_stmt->execute();
$stats = $stats_stmt->fetch();

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h1 class="display-4 fw-bold">Find Your Dream Job</h1>
                <p class="lead">Connect with top employers and discover opportunities that match your skills and aspirations.</p>
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                    <a href="find_jobs.php" class="btn btn-light btn-lg px-4">
                        <i class="fas fa-search me-2"></i>Browse Jobs
                    </a>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="btn btn-outline-light btn-lg px-4">
                        <i class="fas fa-user-plus me-2"></i>Join Now
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 col-6 mb-4">
                <div class="dashboard-card">
                    <i class="fas fa-briefcase text-primary"></i>
                    <h4><?php echo number_format($stats['total_jobs']); ?></h4>
                    <p class="text-muted mb-0">Active Jobs</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="dashboard-card">
                    <i class="fas fa-building text-success"></i>
                    <h4><?php echo number_format($stats['total_recruiters']); ?></h4>
                    <p class="text-muted mb-0">Companies</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="dashboard-card">
                    <i class="fas fa-users text-info"></i>
                    <h4><?php echo number_format($stats['total_jobseekers']); ?></h4>
                    <p class="text-muted mb-0">Job Seekers</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="dashboard-card">
                    <i class="fas fa-file-alt text-warning"></i>
                    <h4><?php echo number_format($stats['total_applications']); ?></h4>
                    <p class="text-muted mb-0">Applications</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Latest Jobs Section -->
<section class="py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="fw-bold mb-3">Latest Job Opportunities</h2>
                <p class="text-muted">Discover the newest job postings from top companies</p>
            </div>
        </div>
        
        <div class="row">
            <?php if (empty($latest_jobs)): ?>
            <div class="col-12 text-center">
                <div class="py-5">
                    <i class="fas fa-briefcase text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3 text-muted">No jobs available yet</h4>
                    <p class="text-muted">Check back later for new opportunities!</p>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($latest_jobs as $job): ?>
            <div class="col-lg-6 col-xl-4 mb-4">
                <div class="job-card h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($job['title']); ?></h5>
                            <p class="company-name mb-2">
                                <i class="fas fa-building me-1"></i>
                                <?php echo htmlspecialchars($job['company_name']); ?>
                            </p>
                        </div>
                        <span class="badge bg-primary"><?php echo ucfirst($job['job_type']); ?></span>
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
                        <a href="find_jobs.php?job_id=<?php echo $job['id']; ?>" class="btn btn-outline-primary btn-sm">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($latest_jobs)): ?>
        <div class="text-center mt-4">
            <a href="find_jobs.php" class="btn btn-primary btn-lg">
                <i class="fas fa-search me-2"></i>View All Jobs
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="fw-bold mb-3">How It Works</h2>
                <p class="text-muted">Simple steps to find your perfect job or candidate</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="text-center">
                    <div class="bg-primary text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i class="fas fa-user-plus fa-2x"></i>
                    </div>
                    <h4 class="fw-bold">1. Create Account</h4>
                    <p class="text-muted">Sign up as a job seeker or recruiter and complete your profile</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="text-center">
                    <div class="bg-success text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i class="fas fa-search fa-2x"></i>
                    </div>
                    <h4 class="fw-bold">2. Search & Apply</h4>
                    <p class="text-muted">Browse jobs or post openings and connect with the right match</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="text-center">
                    <div class="bg-info text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i class="fas fa-handshake fa-2x"></i>
                    </div>
                    <h4 class="fw-bold">3. Get Hired</h4>
                    <p class="text-muted">Connect with employers and start your new career journey</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="fw-bold mb-3">Ready to Get Started?</h2>
                <p class="lead mb-4">Join thousands of job seekers and recruiters who trust JobPortal</p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                    <a href="register.php?type=jobseeker" class="btn btn-light btn-lg px-4">
                        <i class="fas fa-user me-2"></i>I'm Looking for Jobs
                    </a>
                    <a href="register.php?type=recruiter" class="btn btn-outline-light btn-lg px-4">
                        <i class="fas fa-building me-2"></i>I'm Hiring
                    </a>
                </div>
                <?php else: ?>
                <a href="dashboard.php" class="btn btn-light btn-lg px-4">
                    <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
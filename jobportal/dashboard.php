<?php
require_once 'includes/db_connect.php';
$page_title = 'Dashboard';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$full_name = $_SESSION['full_name'];

if ($user_type == 'jobseeker') {
    // Job Seeker Dashboard Data
    $apps_stmt = $pdo->prepare("SELECT a.*, j.title, j.company_name, j.location 
                                FROM applications a 
                                JOIN jobs j ON a.job_id = j.id 
                                WHERE a.user_id = ? 
                                ORDER BY a.applied_at DESC");
    $apps_stmt->execute([$user_id]);
    $applications = $apps_stmt->fetchAll();

    $stats_stmt = $pdo->prepare("SELECT 
        COUNT(*) as total_applications,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM applications WHERE user_id = ?");
    $stats_stmt->execute([$user_id]);
    $stats = $stats_stmt->fetch();

} else {
    // Recruiter Dashboard Data
    $jobs_stmt = $pdo->prepare("SELECT * FROM jobs WHERE recruiter_id = ? ORDER BY created_at DESC");
    $jobs_stmt->execute([$user_id]);
    $posted_jobs = $jobs_stmt->fetchAll();

    $apps_stmt = $pdo->prepare("SELECT a.*, j.title as job_title, u.full_name, u.email 
                                FROM applications a 
                                JOIN jobs j ON a.job_id = j.id 
                                JOIN users u ON a.user_id = u.id 
                                WHERE j.recruiter_id = ? 
                                ORDER BY a.applied_at DESC");
    $apps_stmt->execute([$user_id]);
    $applications = $apps_stmt->fetchAll();

    $stats_stmt = $pdo->prepare("SELECT 
        (SELECT COUNT(*) FROM jobs WHERE recruiter_id = ?) as total_jobs,
        (SELECT COUNT(*) FROM jobs WHERE recruiter_id = ? AND status = 'active') as active_jobs,
        (SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.recruiter_id = ?) as total_applications,
        (SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.recruiter_id = ? AND a.status = 'pending') as pending_applications");
    $stats_stmt->execute([$user_id, $user_id, $user_id, $user_id]);
    $stats = $stats_stmt->fetch();
}

include 'includes/header.php';
?>

<div class="container my-5">

    <!-- Welcome Section -->
    <div class="row mb-5">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1 class="fw-bold mb-2">Welcome back, <?php echo htmlspecialchars($full_name); ?>! <i class="fas fa-hand-wave text-warning"></i></h1>
                <p class="text-muted">
                    <?php echo ($user_type == 'jobseeker') 
                        ? 'Manage your job applications and discover new opportunities.' 
                        : 'Manage your job postings and review applications.'; ?>
                </p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="<?php echo ($user_type == 'recruiter') ? 'post_job.php' : 'find_jobs.php'; ?>" class="btn btn-primary">
                    <i class="fas <?php echo ($user_type == 'recruiter') ? 'fa-plus' : 'fa-search'; ?> me-2"></i>
                    <?php echo ($user_type == 'recruiter') ? 'Post New Job' : 'Browse Jobs'; ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-5">
        <?php if ($user_type == 'jobseeker'): ?>
            <?php 
            $colors = ['bg-primary', 'bg-warning text-dark', 'bg-success', 'bg-danger'];
            $labels = ['Total Applications', 'Pending', 'Accepted', 'Rejected'];
            $values = [$stats['total_applications'], $stats['pending'], $stats['accepted'], $stats['rejected']];
            $icons = ['fa-file-alt','fa-clock','fa-check-circle','fa-times-circle'];
            ?>
            <?php for($i=0;$i<4;$i++): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm text-center py-4 <?php echo $colors[$i]; ?>">
                    <i class="fas <?php echo $icons[$i]; ?> fa-2x mb-2"></i>
                    <h4 class="mb-1"><?php echo $values[$i]; ?></h4>
                    <p class="mb-0"><?php echo $labels[$i]; ?></p>
                </div>
            </div>
            <?php endfor; ?>
        <?php else: ?>
            <?php 
            $colors = ['bg-primary','bg-success','bg-info','bg-warning text-dark'];
            $labels = ['Total Jobs Posted','Active Jobs','Total Applications','Pending Review'];
            $values = [$stats['total_jobs'],$stats['active_jobs'],$stats['total_applications'],$stats['pending_applications']];
            $icons = ['fa-briefcase','fa-eye','fa-users','fa-hourglass-half'];
            ?>
            <?php for($i=0;$i<4;$i++): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm text-center py-4 <?php echo $colors[$i]; ?>">
                    <i class="fas <?php echo $icons[$i]; ?> fa-2x mb-2"></i>
                    <h4 class="mb-1"><?php echo $values[$i]; ?></h4>
                    <p class="mb-0"><?php echo $labels[$i]; ?></p>
                </div>
            </div>
            <?php endfor; ?>
        <?php endif; ?>
    </div>

    <!-- Main Dashboard Content -->
    <div class="row">
        <?php if ($user_type == 'jobseeker'): ?>
            <!-- Job Seeker Applications Table -->
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="fas fa-file-alt me-2"></i>My Applications</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($applications)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-search text-muted" style="font-size:4rem;"></i>
                                <h4 class="mt-3 text-muted">No Applications Yet</h4>
                                <p class="text-muted">Start exploring job opportunities and submit your applications!</p>
                                <a href="find_jobs.php" class="btn btn-primary mt-3">
                                    <i class="fas fa-search me-2"></i>Browse Jobs
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Job Title</th>
                                            <th>Company</th>
                                            <th>Location</th>
                                            <th>Applied Date</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($applications as $app): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($app['title']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($app['company_name']); ?></td>
                                                <td><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($app['location']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($app['applied_at'])); ?></td>
                                                <td>
                                                    <span class="badge status-<?php echo $app['status']; ?>">
                                                        <?php echo ucfirst($app['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="find_jobs.php?job_id=<?php echo $app['job_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye me-1"></i>View Job
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Recruiter Dashboard: Jobs + Recent Applications -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-briefcase me-2"></i>My Job Posts</h4>
                        <a href="post_job.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Post New Job</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($posted_jobs)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-briefcase text-muted" style="font-size:4rem;"></i>
                                <h4 class="mt-3 text-muted">No Jobs Posted Yet</h4>
                                <p class="text-muted">Start posting jobs to attract top talent!</p>
                                <a href="post_job.php" class="btn btn-primary mt-3"><i class="fas fa-plus me-2"></i>Post Your First Job</a>
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($posted_jobs as $job): ?>
                                    <div class="col-md-6">
                                        <div class="card h-100 border-0 shadow-sm p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($job['title']); ?></h6>
                                                <span class="badge status-<?php echo $job['status']; ?>"><?php echo ucfirst($job['status']); ?></span>
                                            </div>
                                            <p class="text-muted small mb-2"><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($job['location']); ?></p>
                                            <p class="mb-2 text-truncate"><?php echo htmlspecialchars(substr($job['description'],0,100)).'...'; ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">Posted <?php echo date('M j, Y', strtotime($job['created_at'])); ?></small>
                                                <div>
                                                    <a href="find_jobs.php?job_id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                                    <a href="post_job.php?edit=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white"><h5 class="mb-0"><i class="fas fa-users me-2"></i>Recent Applications</h5></div>
                    <div class="card-body">
                        <?php if (empty($applications)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox text-muted" style="font-size:2rem;"></i>
                                <p class="text-muted mt-2 mb-0">No applications yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($applications,0,5) as $app): ?>
                                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($app['full_name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($app['job_title']); ?></small>
                                    </div>
                                    <span class="badge status-<?php echo $app['status']; ?>"><?php echo ucfirst($app['status']); ?></span>
                                </div>
                            <?php endforeach; ?>
                            <?php if(count($applications) > 5): ?>
                                <div class="text-center mt-3">
                                    <a href="#" class="btn btn-sm btn-outline-primary">View All Applications</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

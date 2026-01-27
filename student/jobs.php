<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle Application
if (isset($_POST['apply'])) {
    $job_id = $_POST['job_id'];
    
    // Check if already applied
    $check = $conn->prepare("SELECT id FROM applications WHERE user_id = ? AND job_id = ?");
    $check->execute([$user_id, $job_id]);
    
    if ($check->rowCount() == 0) {
        $stmt = $conn->prepare("INSERT INTO applications (user_id, job_id) VALUES (?, ?)");
        if ($stmt->execute([$user_id, $job_id])) {
            $message = "<div class='alert alert-success' style='background: #d1fae5; color: #065f46; padding: 1rem; margin-bottom: 1.5rem; border-radius: var(--radius-md);'>Applied successfully!</div>";
        }
    } else {
        $message = "<div class='alert alert-danger' style='background: #fee2e2; color: #991b1b; padding: 1rem; margin-bottom: 1.5rem; border-radius: var(--radius-md);'>You have already applied for this job.</div>";
    }
}

// Fetch Jobs
$stmt = $conn->query("SELECT j.*, (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id AND a.user_id = $user_id) as applied FROM jobs j ORDER BY created_at DESC");
$jobs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jobs - Student</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <i class="fas fa-graduation-cap"></i> PlacementPro
            </div>
            <nav>
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>
                <a href="jobs.php" class="nav-link active">
                    <i class="fas fa-briefcase"></i> Jobs
                </a>
                <a href="my_applications.php" class="nav-link">
                    <i class="fas fa-file-alt"></i> My Applications
                </a>
                <a href="profile.php" class="nav-link">
                    <i class="fas fa-user"></i> Profile
                </a>
            </nav>
            <div style="margin-top: auto;">
                <a href="../auth/auth_logic.php?logout=true" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                 <h1 class="page-title">Available Opportunities</h1>
            </header>

            <?= $message ?>

            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <?php if (count($jobs) > 0): ?>
                    <?php foreach ($jobs as $job): ?>
                        <div class="card">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;"><?= htmlspecialchars($job['title']) ?></h2>
                                    <h3 style="font-size: 1rem; color: var(--text-secondary); margin-bottom: 1rem; font-weight: 500;">
                                        <?= htmlspecialchars($job['company']) ?>
                                    </h3>
                                    
                                    <div style="display: flex; gap: 1.5rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                                        <span style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--text-secondary);">
                                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?>
                                        </span>
                                        <span style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--text-secondary);">
                                            <i class="fas fa-money-bill-wave"></i> <?= htmlspecialchars($job['salary']) ?>
                                        </span>
                                        <span style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--text-secondary);">
                                            <i class="far fa-clock"></i> Posted <?= date('M d, Y', strtotime($job['created_at'])) ?>
                                        </span>
                                    </div>

                                    <div style="margin-bottom: 1.5rem;">
                                        <h4 style="font-weight: 600; font-size: 0.9rem; margin-bottom: 0.5rem;">Description</h4>
                                        <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.6;">
                                            <?= nl2br(htmlspecialchars($job['description'])) ?>
                                        </p>
                                    </div>
                                    
                                    <?php if($job['requirements']): ?>
                                    <div style="margin-bottom: 1.5rem;">
                                        <h4 style="font-weight: 600; font-size: 0.9rem; margin-bottom: 0.5rem;">Requirements</h4>
                                        <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.6;">
                                            <?= nl2br(htmlspecialchars($job['requirements'])) ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div style="min-width: 150px; text-align: right;">
                                    <?php if ($job['applied'] > 0): ?>
                                        <button disabled class="btn btn-outline" style="background-color: #f1f5f9; color: var(--text-secondary); cursor: not-allowed; width: 100%;">
                                            <i class="fas fa-check" style="margin-right: 0.5rem;"></i> Applied
                                        </button>
                                    <?php else: ?>
                                        <form method="POST">
                                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                            <button type="submit" name="apply" class="btn btn-primary" style="width: 100%;">
                                                Apply Now
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card" style="text-align: center; padding: 3rem;">
                        <i class="fas fa-search" style="font-size: 3rem; color: var(--border-color); margin-bottom: 1rem;"></i>
                        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">No Jobs Found</h3>
                        <p style="color: var(--text-secondary);">Check back later for new opportunities.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>

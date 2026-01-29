<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch Stats
try {
    // Active Jobs
    $stmt = $conn->query("SELECT COUNT(*) FROM jobs");
    $active_jobs = $stmt->fetchColumn();

    // My Applications
    $stmt = $conn->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $my_applications = $stmt->fetchColumn();

    // Pending Applications
    $stmt = $conn->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    $pending_applications = $stmt->fetchColumn();

    // Recent Jobs
    $stmt = $conn->query("SELECT * FROM jobs ORDER BY created_at DESC LIMIT 5");
    $recent_jobs = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student</title>
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
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>
                <a href="jobs.php" class="nav-link">
                    <i class="fas fa-briefcase"></i> Jobs
                </a>
                <a href="my_applications.php" class="nav-link">
                    <i class="fas fa-file-alt"></i> My Applications
                </a>
                <a href="resume_builder.php" class="nav-link">
                    <i class="fas fa-file-invoice"></i> Resume Builder
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
                <div>
                    <h1 class="page-title">Welcome back, <?= htmlspecialchars($_SESSION['name']); ?>!</h1>
                    <p style="color: var(--text-secondary);">Here's what's happening with your placements</p>
                </div>
                <div class="user-profile">
                   <!-- Could add user avatar here -->
                </div>
            </header>

            <!-- Stats Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <p style="color: var(--text-secondary); font-size: 0.875rem; font-weight: 500;">Active Jobs</p>
                            <h2 style="font-size: 2rem; font-weight: 700; color: var(--text-primary); margin-top: 0.5rem;"><?= $active_jobs ?></h2>
                        </div>
                        <div style="background-color: #e0e7ff; padding: 0.75rem; border-radius: 0.5rem; color: var(--primary-color);">
                            <i class="fas fa-briefcase"></i>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <p style="color: var(--text-secondary); font-size: 0.875rem; font-weight: 500;">My Applications</p>
                            <h2 style="font-size: 2rem; font-weight: 700; color: var(--text-primary); margin-top: 0.5rem;"><?= $my_applications ?></h2>
                        </div>
                        <div style="background-color: #d1fae5; padding: 0.75rem; border-radius: 0.5rem; color: #059669;">
                            <i class="fas fa-file-contract"></i>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <p style="color: var(--text-secondary); font-size: 0.875rem; font-weight: 500;">Pending</p>
                            <h2 style="font-size: 2rem; font-weight: 700; color: var(--text-primary); margin-top: 0.5rem;"><?= $pending_applications ?></h2>
                        </div>
                        <div style="background-color: #fef3c7; padding: 0.75rem; border-radius: 0.5rem; color: #d97706;">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Jobs -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2 style="font-size: 1.25rem; font-weight: 600;">Recent Job Openings</h2>
                    <a href="jobs.php" style="color: var(--primary-color); font-weight: 500; font-size: 0.875rem;">View All</a>
                </div>
                
                <?php if (count($recent_jobs) > 0): ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($recent_jobs as $job): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); transition: background-color 0.2s;">
                                <div>
                                    <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.25rem;"><?= htmlspecialchars($job['title']) ?></h3>
                                    <p style="color: var(--text-secondary); font-size: 0.875rem;">
                                        <i class="far fa-building"></i> <?= htmlspecialchars($job['company']) ?> &bull; 
                                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?>
                                    </p>
                                </div>
                                <a href="jobs.php?id=<?= $job['id'] ?>" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.875rem;">View Details</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-secondary); text-align: center; padding: 2rem;">No jobs available at the moment.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>

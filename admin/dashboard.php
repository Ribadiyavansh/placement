<?php
session_start();
require_once '../config/db.php';

// Auth check removed as per user request


// Fetch Stats
try {
    $total_students = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $total_jobs = $conn->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
    $total_applications = $conn->query("SELECT COUNT(*) FROM applications")->fetchColumn();
    $pending_apps = $conn->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Admin Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <i class="fas fa-user-shield"></i> AdminPanel
            </div>
            <nav>
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-th-large"></i> Overview
                </a>
                <a href="manage_jobs.php" class="nav-link">
                    <i class="fas fa-briefcase"></i> Manage Jobs
                </a>
                <a href="manage_applications.php" class="nav-link">
                    <i class="fas fa-file-contract"></i> Applications
                </a>
            </nav>
            <div style="margin-top: auto;">
                <a href="../index.php" class="nav-link">
                    <i class="fas fa-home"></i> Home
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div>
                    <h1 class="page-title">Admin Dashboard</h1>
                    <p style="color: var(--text-secondary);">System Overview</p>
                </div>
            </header>

            <!-- Stats Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem;">
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <p style="color: var(--text-secondary); font-size: 0.875rem; font-weight: 500;">Total Students</p>
                            <h2 style="font-size: 2rem; font-weight: 700; color: var(--text-primary); margin-top: 0.5rem;"><?= $total_students ?></h2>
                        </div>
                        <div style="background-color: #e0e7ff; padding: 0.75rem; border-radius: 0.5rem; color: var(--primary-color);">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <p style="color: var(--text-secondary); font-size: 0.875rem; font-weight: 500;">Total Jobs</p>
                            <h2 style="font-size: 2rem; font-weight: 700; color: var(--text-primary); margin-top: 0.5rem;"><?= $total_jobs ?></h2>
                        </div>
                        <div style="background-color: #d1fae5; padding: 0.75rem; border-radius: 0.5rem; color: #059669;">
                            <i class="fas fa-briefcase"></i>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <p style="color: var(--text-secondary); font-size: 0.875rem; font-weight: 500;">Total Applications</p>
                            <h2 style="font-size: 2rem; font-weight: 700; color: var(--text-primary); margin-top: 0.5rem;"><?= $total_applications ?></h2>
                        </div>
                        <div style="background-color: #fef3c7; padding: 0.75rem; border-radius: 0.5rem; color: #d97706;">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <p style="color: var(--text-secondary); font-size: 0.875rem; font-weight: 500;">Pending Actions</p>
                            <h2 style="font-size: 2rem; font-weight: 700; color: var(--text-primary); margin-top: 0.5rem;"><?= $pending_apps ?></h2>
                        </div>
                        <div style="background-color: #fee2e2; padding: 0.75rem; border-radius: 0.5rem; color: #991b1b;">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

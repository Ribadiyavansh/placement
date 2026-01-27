<?php
session_start();
require_once '../config/db.php';

// Auth check removed


// Handle Status Updates
if (isset($_POST['update_status'])) {
    $app_id = $_POST['app_id'];
    $status = $_POST['update_status'];
    
    $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->execute([$status, $app_id]);
}

// Fetch all applications
$stmt = $conn->query("
    SELECT a.*, u.name as student_name, u.email as student_email, j.title as job_title, j.company 
    FROM applications a 
    JOIN users u ON a.user_id = u.id 
    JOIN jobs j ON a.job_id = j.id 
    ORDER BY a.applied_at DESC
");
$applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo">
                <i class="fas fa-user-shield"></i> AdminPanel
            </div>
            <nav>
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-th-large"></i> Overview
                </a>
                <a href="manage_jobs.php" class="nav-link">
                    <i class="fas fa-briefcase"></i> Manage Jobs
                </a>
                <a href="manage_applications.php" class="nav-link active">
                    <i class="fas fa-file-contract"></i> Applications
                </a>
            </nav>
            <div style="margin-top: auto;">
                <a href="../index.php" class="nav-link">
                    <i class="fas fa-home"></i> Home
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header class="header">
                 <h1 class="page-title">Manage Applications</h1>
            </header>

            <div class="card" style="padding: 0;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: #f8fafc; border-bottom: 1px solid var(--border-color); text-align: left;">
                            <th style="padding: 1rem;">Candidate</th>
                            <th style="padding: 1rem;">Job Applied For</th>
                            <th style="padding: 1rem;">Applied Date</th>
                            <th style="padding: 1rem;">Current Status</th>
                            <th style="padding: 1rem;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 1rem;">
                                    <div style="font-weight: 600;"><?= htmlspecialchars($app['student_name']) ?></div>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary);"><?= htmlspecialchars($app['student_email']) ?></div>
                                </td>
                                <td style="padding: 1rem;">
                                    <div style="font-weight: 600;"><?= htmlspecialchars($app['job_title']) ?></div>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary);"><?= htmlspecialchars($app['company']) ?></div>
                                </td>
                                <td style="padding: 1rem; color: var(--text-secondary);"><?= date('M d, Y', strtotime($app['applied_at'])) ?></td>
                                <td style="padding: 1rem;">
                                    <?php if ($app['status'] == 'pending'): ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php elseif ($app['status'] == 'accepted'): ?>
                                        <span class="badge badge-success">Accepted</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 1rem;">
                                    <form method="POST" style="display: flex; gap: 0.5rem;">
                                        <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                        <?php if ($app['status'] !== 'accepted'): ?>
                                            <button type="submit" name="update_status" value="accepted" title="Accept" 
                                                class="btn btn-outline" style="padding: 0.25rem 0.5rem; color: var(--success-color); border-color: var(--success-color);">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($app['status'] !== 'rejected'): ?>
                                            <button type="submit" name="update_status" value="rejected" title="Reject" 
                                                class="btn btn-outline" style="padding: 0.25rem 0.5rem; color: var(--danger-color); border-color: var(--danger-color);">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>

<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

    $user_id = $_SESSION['user_id'];

    // Handle Delete Application
    if (isset($_POST['delete_application'])) {
        $app_id = $_POST['app_id'];
        
        // Verify application belongs to student before deleting
        $check_stmt = $conn->prepare("SELECT id FROM applications WHERE id = ? AND user_id = ?");
        $check_stmt->execute([$app_id, $user_id]);
        
        if ($check_stmt->fetch()) {
            $delete_stmt = $conn->prepare("DELETE FROM applications WHERE id = ?");
            $delete_stmt->execute([$app_id]);
            $_SESSION['message'] = "Application deleted successfully.";
            header("Location: my_applications.php");
            exit();
        }
    }

// Get Applications
$stmt = $conn->prepare("
    SELECT a.*, j.title, j.company, j.location, j.salary 
    FROM applications a 
    JOIN jobs j ON a.job_id = j.id 
    WHERE a.user_id = ? 
    ORDER BY a.applied_at DESC
");
$stmt->execute([$user_id]);
$applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - Student</title>
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
                <a href="jobs.php" class="nav-link">
                    <i class="fas fa-briefcase"></i> Jobs
                </a>
                <a href="my_applications.php" class="nav-link active">
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
                 <h1 class="page-title"><i class="fas fa-file-alt"></i> My Applications</h1>
            </header>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 0.75rem; border-radius: 0.375rem; margin-bottom: 1rem;">
                    <?= $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>

            <div class="card" style="padding: 0; overflow: hidden;">
                <?php if (count($applications) > 0): ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: #f8fafc; border-bottom: 1px solid var(--border-color); text-align: left;">
                                <th style="padding: 1rem; font-weight: 600; font-size: 0.875rem; color: var(--text-secondary);">Company</th>
                                <th style="padding: 1rem; font-weight: 600; font-size: 0.875rem; color: var(--text-secondary);">Job Title</th>
                                <th style="padding: 1rem; font-weight: 600; font-size: 0.875rem; color: var(--text-secondary);">Applied On</th>
                                <th style="padding: 1rem; font-weight: 600; font-size: 0.875rem; color: var(--text-secondary);">Status</th>
                                <th style="padding: 1rem; font-weight: 600; font-size: 0.875rem; color: var(--text-secondary);">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 1rem;">
                                        <div style="font-weight: 600;"><?= htmlspecialchars($app['company']) ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-secondary);"><?= htmlspecialchars($app['location']) ?></div>
                                    </td>
                                    <td style="padding: 1rem;"><?= htmlspecialchars($app['title']) ?></td>
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
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this application?');">
                                            <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                            <button type="submit" name="delete_application" value="1" class="btn btn-outline" style="color: var(--danger-color); border-color: var(--danger-color); padding: 0.25rem 0.5rem; cursor: pointer;" title="Delete Application">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="padding: 3rem; text-align: center;">
                        <p style="color: var(--text-secondary);">You haven't applied to any jobs yet.</p>
                        <a href="jobs.php" class="btn btn-primary" style="margin-top: 1rem;">Browse Jobs</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>

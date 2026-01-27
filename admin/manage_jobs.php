<?php
session_start();
require_once '../config/db.php';

// Auth check removed


$message = '';

// Handle Create Job
if (isset($_POST['create_job'])) {
    $title = $_POST['title'];
    $company = $_POST['company'];
    $location = $_POST['location'];
    $salary = $_POST['salary'];
    $description = $_POST['description'];
    $requirements = $_POST['requirements'];
    $posted_by = $_SESSION['user_id'] ?? 1; // Default to admin ID 1 if not logged in

    $stmt = $conn->prepare("INSERT INTO jobs (title, company, location, salary, description, requirements, posted_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$title, $company, $location, $salary, $description, $requirements, $posted_by])) {
        $message = "<div class='alert alert-success' style='background: #d1fae5; color: #065f46; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem;'>Job posted successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger' style='background: #fee2e2; color: #991b1b; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem;'>Failed to post job.</div>";
    }
}

// Handle Delete Job
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM jobs WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_jobs.php");
    exit();
}

// Fetch all jobs
$jobs = $conn->query("SELECT * FROM jobs ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jobs - Admin</title>
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
                <a href="manage_jobs.php" class="nav-link active">
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

        <main class="main-content">
            <header class="header">
                 <h1 class="page-title">Manage Jobs</h1>
                 <button onclick="document.getElementById('postJobForm').classList.toggle('hidden')" class="btn btn-primary">
                    <i class="fas fa-plus" style="margin-right: 0.5rem;"></i> Post New Job
                 </button>
            </header>

            <?= $message ?>

            <div id="postJobForm" class="hidden card" style="margin-bottom: 2rem; display: none;">
                <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">Post a New Job</h2>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Job Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Salary Range</label>
                            <input type="text" name="salary" class="form-control" placeholder="e.g. $50k - $70k">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Requirements</label>
                        <textarea name="requirements" class="form-control" rows="4"></textarea>
                    </div>
                    <button type="submit" name="create_job" class="btn btn-primary">Publish Job</button>
                    <button type="button" onclick="document.getElementById('postJobForm').style.display='none'" class="btn btn-outline">Cancel</button>
                </form>
            </div>

            <!-- Job List -->
            <div class="card" style="padding: 0;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: #f8fafc; border-bottom: 1px solid var(--border-color); text-align: left;">
                            <th style="padding: 1rem;">Job Details</th>
                            <th style="padding: 1rem;">Location</th>
                            <th style="padding: 1rem;">Posted On</th>
                            <th style="padding: 1rem;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 1rem;">
                                    <div style="font-weight: 600;"><?= htmlspecialchars($job['title']) ?></div>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary);"><?= htmlspecialchars($job['company']) ?></div>
                                </td>
                                <td style="padding: 1rem;"><?= htmlspecialchars($job['location']) ?></td>
                                <td style="padding: 1rem; color: var(--text-secondary);"><?= date('M d, Y', strtotime($job['created_at'])) ?></td>
                                <td style="padding: 1rem;">
                                    <a href="?delete=<?= $job['id'] ?>" class="btn btn-outline" style="color: var(--danger-color); border-color: var(--danger-color); padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick="return confirm('Are you sure?')">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Simple toggle for the form
        document.querySelector('.btn-primary[onclick]').onclick = function() {
            var form = document.getElementById('postJobForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>

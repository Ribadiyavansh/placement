<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle Profile Update (User Details)
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        $sql = "UPDATE users SET name = ?, email = ?";
        $params = [$name, $email];

        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql .= ", password = ?";
            $params[] = $hashed;
        }

        $sql .= " WHERE id = ?";
        $params[] = $user_id;

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        $_SESSION['name'] = $name;
        $message = "<div class='alert alert-success' style='background: #d1fae5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;'>Profile updated successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger' style='background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;'>Error updating profile.</div>";
    }
}

// Handle Resume Upload
if (isset($_POST['upload_resume'])) {
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
        $upload_dir = '../assets/uploads/resumes/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
        if ($file_ext != 'pdf') {
            $message = "<div class='alert alert-danger' style='background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;'>Only PDF files are allowed for resumes.</div>";
        } else {
            $new_filename = 'resume_' . $user_id . '_' . time() . '.' . $file_ext;
            if (move_uploaded_file($_FILES['resume']['tmp_name'], $upload_dir . $new_filename)) {
                $resume_path = 'assets/uploads/resumes/' . $new_filename;
                
                try {
                    $stmt = $conn->prepare("UPDATE users SET resume_path = ? WHERE id = ?");
                    $stmt->execute([$resume_path, $user_id]);
                    $message = "<div class='alert alert-success' style='background: #d1fae5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;'>Resume uploaded successfully!</div>";
                } catch (PDOException $e) {
                     $message = "<div class='alert alert-danger' style='background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;'>Error updating database.</div>";
                }
            } else {
                $message = "<div class='alert alert-danger' style='background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;'>Failed to upload resume file.</div>";
            }
        }
    } else {
        $message = "<div class='alert alert-danger' style='background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;'>Please select a file to upload.</div>";
    }
}

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Student</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
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
                <a href="my_applications.php" class="nav-link">
                    <i class="fas fa-file-alt"></i> My Applications
                </a>
                <a href="profile.php" class="nav-link active">
                    <i class="fas fa-user"></i> Profile
                </a>
            </nav>
            <div style="margin-top: auto;">
                <a href="../auth/auth_logic.php?logout=true" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header class="header">
                 <h1 class="page-title">Profile Settings</h1>
            </header>

            <!-- Tab Navigation -->
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('details')">User Details</button>
                <button class="tab-btn" onclick="switchTab('resume')">Resume</button>
            </div>

            <?= $message ?>

            <div class="card" style="max-width: 600px;">

                <!-- User Details Tab -->
                <div id="details" class="tab-content active">
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>

                <!-- Resume Tab -->
                <div id="resume" class="tab-content" style="display: none;">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label">Resume Headline (Optional)</label>
                            <input type="text" name="resume_headline" class="form-control" placeholder="Ex: Final Year CS Student seeking Internship" >
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Upload Resume (PDF only)</label>
                            <input type="file" name="resume" class="form-control" accept=".pdf" required>
                        </div>
                        
                        <?php if (!empty($user['resume_path'])): ?>
                            <div style="margin-bottom: 1.5rem; padding: 1rem; background-color: #f8fafc; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <i class="fas fa-file-pdf" style="color: #ef4444; font-size: 1.5rem;"></i>
                                    <div>
                                        <p style="font-weight: 500; font-size: 0.95rem;">Current Resume</p>
                                        <a href="../<?= htmlspecialchars($user['resume_path']) ?>" target="_blank" style="color: var(--primary-color); font-size: 0.875rem; text-decoration: underline;">View File</a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <button type="submit" name="upload_resume" class="btn btn-primary">Upload Resume</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        function switchTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(el => {
                el.style.display = 'none';
            });
            
            // Remove active style from all buttons
            document.querySelectorAll('.tab-btn').forEach(button => {
                button.classList.remove('active');
            });

            // Show selected tab content
            document.getElementById(tabId).style.display = 'block';
            
            // Add active class to clicked button
            const buttons = document.querySelectorAll('.tab-btn');
            if (tabId === 'details') {
                buttons[0].classList.add('active');
            }
            if (tabId === 'resume') {
                buttons[1].classList.add('active');
            }
        }
        
        // Check if there was a resume upload message to switch to resume tab automatically?
        // Optional: Persist active tab after reload could be nice but not strictly requred. 
        // For now user details is default.
        <?php if (isset($_POST['upload_resume'])): ?>
            switchTab('resume');
        <?php endif; ?>
    </script>
</body>
</html>

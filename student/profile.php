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

// Fetch all resume data for the builder
try {
    // Personal Info
    $stmt = $conn->prepare("SELECT * FROM resume_personal WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $personal = $stmt->fetch() ?: [];

    // Education
    $stmt = $conn->prepare("SELECT * FROM resume_education WHERE user_id = ? ORDER BY start_year DESC");
    $stmt->execute([$user_id]);
    $education = $stmt->fetchAll();

    // Skills
    $stmt = $conn->prepare("SELECT * FROM resume_skills WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $skills = $stmt->fetchAll();

    // Projects
    $stmt = $conn->prepare("SELECT * FROM resume_projects WHERE user_id = ? ORDER BY start_date DESC");
    $stmt->execute([$user_id]);
    $projects = $stmt->fetchAll();

    // Experience
    $stmt = $conn->prepare("SELECT * FROM resume_experience WHERE user_id = ? ORDER BY start_date DESC");
    $stmt->execute([$user_id]);
    $experience = $stmt->fetchAll();

    // Certifications
    $stmt = $conn->prepare("SELECT * FROM resume_certifications WHERE user_id = ? ORDER BY issue_date DESC");
    $stmt->execute([$user_id]);
    $certifications = $stmt->fetchAll();

    // Achievements
    $stmt = $conn->prepare("SELECT * FROM resume_achievements WHERE user_id = ? ORDER BY date DESC");
    $stmt->execute([$user_id]);
    $achievements = $stmt->fetchAll();

    // Eligibility
    $stmt = $conn->prepare("SELECT * FROM placement_eligibility WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $eligibility = $stmt->fetch() ?: [];

    // Calculate Resume Score
    $score = 0;
    if (!empty($personal)) $score += 15;
    if (!empty($education)) $score += 20;
    if (count($skills) >= 5) $score += 15;
    if (count($projects) >= 2) $score += 20;
    if (count($experience) >= 1) $score += 15;
    if (count($certifications) >= 1) $score += 10;
    if (!empty($eligibility)) $score += 5;

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Student</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Builder specific styles */
        .resume-builder {
            width: 100%;
            max-width: 100%;
        }
        .resume-score {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
            text-align: center;
        }
        .score-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-size: 1.8rem;
            font-weight: 700;
        }
        .section-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            overflow-x: auto;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 5px;
            width: 100%;
        }
        .section-tab-btn {
            padding: 0.75rem 1rem;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: var(--text-secondary);
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            white-space: nowrap;
        }
        .section-tab-btn.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        .section-content {
            display: none;
        }
        .section-content.active {
            display: block;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        .dynamic-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            position: relative;
            border: 1px solid var(--border-color);
        }
        .remove-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            cursor: pointer;
        }
        .add-more-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 500;
        }
        .tag {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
        }
        
        /* Layout adjustments */
        .main-container {
            transition: max-width 0.3s ease;
        }
        .builder-active .card {
            max-width: 1200px !important;
            min-height: calc(100vh - 4rem);
            margin: 0 auto !important;
            border-radius: var(--radius-lg);
        }
        .builder-active .main-content {
            padding: 1rem !important;
        }
    </style>
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
                <a href="resume_builder.php" class="nav-link">
                    <i class="fas fa-file-invoice"></i> Resume Builder
                </a>
                <a href="profile.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'profile.php' && ($_GET['tab'] ?? '') != 'builder') ? 'active' : '' ?>">
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
            <?php if (($_GET['tab'] ?? '') !== 'builder'): ?>
            <header class="header">
                 <h1 class="page-title"><i class="fas fa-user"></i> Profile Settings</h1>
            </header>
            <?php endif; ?>

            <?= $message ?>

            <div class="card" style="<?= ($_GET['tab'] ?? '') == 'builder' ? 'max-width: 1200px; width: 100%; margin: 0 auto; border: none;' : 'max-width: 600px;' ?>">
                <?php if (($_GET['tab'] ?? '') == 'builder'): ?>
                    <!-- Resume Builder Tab -->
                    <div id="builder" class="tab-content" style="display: block;">
                <?php else: ?>
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
                        <!-- Forgot Password Modal -->
                        <div id="forgotModal" class="forgot-modal">
                            <div class="forgot-modal-content">
                                <span class="forgot-close" onclick="document.getElementById('forgotModal').style.display='none'" style="position: absolute; right: 1.5rem; top: 1.5rem; font-size: 2rem; cursor: pointer; color: #aaa;">&times;</span>
                                <h2>Change Password</h2>
                                <form method="POST">
                                    <div class="form-group">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="new_password" class="form-control" required minlength="6">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Confirm Password</label>
                                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-primary w-100">Update Password</button>
                                </form>

                            </div>
                        </div>
                    </div>

                <?php endif; ?>

                <?php if (($_GET['tab'] ?? '') == 'builder'): ?>
                    <div class="resume-builder">
                        <div class="resume-score">
                            <div class="score-circle"><?= $score ?>%</div>
                            <h3 style="margin-bottom: 0.5rem;">Resume Completeness</h3>
                            <div style="display: flex; justify-content: center; gap: 1rem; margin-top: 1rem;">
                                <a href="resume_view.php" class="btn" style="background: rgba(255,255,255,0.2); color: white; padding: 0.5rem 1rem;" target="_blank">
                                    <i class="fas fa-eye"></i> Preview
                                </a>
                            </div>
                        </div>

                        <!-- Section Tabs (Internal) -->
                        <div class="section-tabs">
                            <button class="section-tab-btn active" onclick="switchSection('personal')">Personal Info</button>
                            <button class="section-tab-btn" onclick="switchSection('education')">Education</button>
                            <button class="section-tab-btn" onclick="switchSection('skills')">Skills</button>
                            <button class="section-tab-btn" onclick="switchSection('projects')">Projects</button>
                            <button class="section-tab-btn" onclick="switchSection('experience')">Experience</button>
                            <button class="section-tab-btn" onclick="switchSection('certifications')">Certifications</button>
                            <button class="section-tab-btn" onclick="switchSection('achievements')">Achievements</button>
                            <button class="section-tab-btn" onclick="switchSection('eligibility')">Eligibility</button>
                        </div>

                        <!-- Personal Info Section -->
                        <div id="builder-personal" class="section-content active">
                            <form action="resume_actions.php" method="POST">
                                <input type="hidden" name="action" value="save_personal">
                                <input type="hidden" name="redirect_to" value="profile.php?tab=builder&section=personal">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Gender</label>
                                        <select name="gender" class="form-control">
                                            <option value="">Select</option>
                                            <option value="Male" <?= ($personal['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
                                            <option value="Female" <?= ($personal['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Date of Birth</label>
                                        <input type="date" name="dob" class="form-control" value="<?= $personal['dob'] ?? '' ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Phone</label>
                                        <input type="tel" name="phone" class="form-control" value="<?= $personal['phone'] ?? '' ?>" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Professional Summary</label>
                                    <textarea name="summary" class="form-control" required><?= $personal['summary'] ?? '' ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Info</button>
                            </form>
                        </div>

                        <!-- Education Section -->
                        <div id="builder-education" class="section-content">
                            <?php foreach ($education as $edu): ?>
                            <div class="dynamic-item">
                                <form action="resume_actions.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_education">
                                    <input type="hidden" name="id" value="<?= $edu['id'] ?>">
                                    <input type="hidden" name="redirect_to" value="profile.php?tab=builder&section=education">
                                    <button type="submit" class="remove-btn" onclick="return confirm('Delete this?')">×</button>
                                </form>
                                <p><strong><?= htmlspecialchars($edu['degree']) ?></strong></p>
                                <p><?= htmlspecialchars($edu['institution']) ?></p>
                            </div>
                            <?php endforeach; ?>
                            
                            <form action="resume_actions.php" method="POST">
                                <input type="hidden" name="action" value="add_education">
                                <input type="hidden" name="redirect_to" value="profile.php?tab=builder&section=education">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Degree</label>
                                        <input type="text" name="degree" class="form-control" required placeholder="B.Tech">
                                    </div>
                                    <div class="form-group">
                                        <label>Specialization</label>
                                        <input type="text" name="specialization" class="form-control" required placeholder="CS">
                                    </div>
                                    <div class="form-group">
                                        <label>Institution</label>
                                        <input type="text" name="institution" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>University</label>
                                        <input type="text" name="university_board" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Start Year</label>
                                        <input type="number" name="start_year" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>End Year</label>
                                        <input type="number" name="end_year" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>CGPA/Per%</label>
                                        <input type="number" name="percentage_cgpa" step="0.01" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="current_status" class="form-control">
                                            <option value="Pursuing">Pursuing</option>
                                            <option value="Completed">Completed</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Education</button>
                            </form>
                        </div>

                        <!-- Skills Section -->
                        <div id="builder-skills" class="section-content">
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.5rem;">
                                <?php foreach ($skills as $skill): ?>
                                <div class="tag">
                                    <?= htmlspecialchars($skill['skill_name']) ?>
                                    <form action="resume_actions.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_skill">
                                        <input type="hidden" name="id" value="<?= $skill['id'] ?>">
                                        <input type="hidden" name="redirect_to" value="profile.php?tab=builder&section=skills">
                                        <button type="submit" style="background:none; border:none; color:white; cursor:pointer;">×</button>
                                    </form>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <form action="resume_actions.php" method="POST">
                                <input type="hidden" name="action" value="add_skill">
                                <input type="hidden" name="redirect_to" value="profile.php?tab=builder&section=skills">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Skill Name</label>
                                        <input type="text" name="skill_name" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Type</label>
                                        <select name="type" class="form-control">
                                            <option value="Technical">Technical</option>
                                            <option value="Soft">Soft</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Proficiency</label>
                                        <select name="proficiency" class="form-control">
                                            <option value="Beginner">Beginner</option>
                                            <option value="Intermediate">Intermediate</option>
                                            <option value="Advanced">Advanced</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Skill</button>
                            </form>
                        </div>

                        <!-- Projects Section -->
                        <div id="builder-projects" class="section-content">
                            <?php foreach ($projects as $proj): ?>
                            <div class="dynamic-item">
                                <form action="resume_actions.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_project">
                                    <input type="hidden" name="id" value="<?= $proj['id'] ?>">
                                    <input type="hidden" name="redirect_to" value="profile.php?tab=builder&section=projects">
                                    <button type="submit" class="remove-btn">×</button>
                                </form>
                                <p><strong><?= htmlspecialchars($proj['title']) ?></strong></p>
                            </div>
                            <?php endforeach; ?>
                            <form action="resume_actions.php" method="POST">
                                <input type="hidden" name="action" value="add_project">
                                <input type="hidden" name="redirect_to" value="profile.php?tab=builder&section=projects">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" name="title" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Role</label>
                                        <input type="text" name="role" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Tech Stack</label>
                                        <input type="text" name="tech_stack" class="form-control">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" class="form-control" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Project</button>
                            </form>
                        </div>

                        <!-- Experience Section -->
                        <div id="builder-experience" class="section-content">
                            <?php foreach ($experience as $exp): ?>
                            <div class="dynamic-item">
                                <form action="resume_actions.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_experience">
                                    <input type="hidden" name="id" value="<?= $exp['id'] ?>">
                                    <input type="hidden" name="redirect_to" value="profile.php?tab=builder&section=experience">
                                    <button type="submit" class="remove-btn">×</button>
                                </form>
                                <p><strong><?= htmlspecialchars($exp['company_name']) ?></strong></p>
                            </div>
                            <?php endforeach; ?>
                            <form action="resume_actions.php" method="POST">
                                <input type="hidden" name="action" value="add_experience">
                                <input type="hidden" name="redirect_to" value="profile.php?tab=builder&section=experience">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Company</label>
                                        <input type="text" name="company_name" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Role</label>
                                        <input type="text" name="role" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Start Date</label>
                                        <input type="date" name="start_date" class="form-control" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Exp</button>
                            </form>
                        </div>

                        <!-- Certifications Section -->
                        <div id="builder-certifications" class="section-content">
                            <?php foreach ($certifications as $cert): ?>
                            <div class="dynamic-item">
                                <form action="resume_actions.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_certification">
                                    <input type="hidden" name="id" value="<?= $cert['id'] ?>">
                                    <input type="hidden" name="redirect_to" value="profile.php?tab=builder&section=certifications">
                                    <button type="submit" class="remove-btn">×</button>
                                </form>
                                <p><strong><?= htmlspecialchars($cert['certificate_name']) ?></strong></p>
                                <?php if (!empty($cert['certificate_file'])): ?>
                                <a href="../<?= htmlspecialchars($cert['certificate_file']) ?>" target="_blank" style="font-size: 0.8rem; color: var(--primary-color);">View File</a>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                            <form action="resume_actions.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="add_certification">
                                <input type="hidden" name="redirect_to" value="profile.php?tab=builder&section=certifications">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Name</label>
                                        <input type="text" name="certificate_name" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Org</label>
                                        <input type="text" name="issuing_org" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>File (PDF)</label>
                                        <input type="file" name="certificate_file" class="form-control" accept=".pdf">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Cert</button>
                            </form>
                        </div>

                        <!-- Achievements Section -->
                        <div id="builder-achievements" class="section-content">
                            <?php foreach ($achievements as $ach): ?>
                            <div class="dynamic-item">
                                <form action="resume_actions.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_achievement">
                                    <input type="hidden" name="id" value="<?= $ach['id'] ?>">
                                    <input type="hidden" name="redirect_to" value="profile.php?tab=builder&section=achievements">
                                    <button type="submit" class="remove-btn">×</button>
                                </form>
                                <p><strong><?= htmlspecialchars($ach['title']) ?></strong></p>
                            </div>
                            <?php endforeach; ?>
                            <form action="resume_actions.php" method="POST">
                                <input type="hidden" name="action" value="add_achievement">
                                <input type="hidden" name="redirect_to" value="profile.php?tab=builder&section=achievements">
                                <div class="form-group">
                                    <label>Title</label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" class="form-control" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Achievement</button>
                            </form>
                        </div>

                        <!-- Eligibility Section -->
                        <div id="builder-eligibility" class="section-content">
                            <form action="resume_actions.php" method="POST">
                                <input type="hidden" name="action" value="save_eligibility">
                                <input type="hidden" name="redirect_to" value="profile.php?tab=builder&section=eligibility">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Relocate?</label>
                                        <select name="willing_relocate" class="form-control">
                                            <option value="1" <?= ($eligibility['willing_relocate'] ?? 1) == 1 ? 'selected' : '' ?>>Yes</option>
                                            <option value="0" <?= ($eligibility['willing_relocate'] ?? 1) == 0 ? 'selected' : '' ?>>No</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Expected CTC</label>
                                        <input type="number" name="expected_ctc" step="0.1" class="form-control" value="<?= $eligibility['expected_ctc'] ?? '' ?>">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Eligibility</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
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
            const targetContent = document.getElementById(tabId);
            if (targetContent) {
                targetContent.style.display = 'block';
            }
            
            // Add active class to clicked button
            const buttons = document.querySelectorAll('.tab-btn');
            if (buttons.length > 0) {
                if (tabId === 'details') buttons[0].classList.add('active');
            }

            // Adjust layout width for builder
            if (tabId === 'builder') {
                document.body.classList.add('builder-active');
            } else {
                document.body.classList.remove('builder-active');
            }
        }

        function switchSection(sectionId) {
            document.querySelectorAll('.section-content').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelectorAll('.section-tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.getElementById('builder-' + sectionId).classList.add('active');
            const btns = document.querySelectorAll('.section-tab-btn');
            const sectionMap = {'personal':0, 'education':1, 'skills':2, 'projects':3, 'experience':4, 'certifications':5, 'achievements':6, 'eligibility':7};
            if (sectionMap[sectionId] !== undefined) {
                btns[sectionMap[sectionId]].classList.add('active');
            }
        }
        
        // Handle URL parameters for automatic tab/section switching
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            const section = urlParams.get('section');
            
            if (tab) switchTab(tab);
            if (section) switchSection(section);

            // Forgot Password Modal
            const forgotBtn = document.getElementById('forgot-password-btn');
            const modal = document.getElementById('forgotModal');
            const closeBtn = document.querySelector('.forgot-close');
            const forgotForm = document.getElementById('forgotForm');

            if (forgotBtn && modal) {
                forgotBtn.addEventListener('click', () => {
                    modal.style.display = 'block';
                });
            }

            if (closeBtn && modal) {
                closeBtn.addEventListener('click', () => {
                    modal.style.display = 'none';
                });
            }

            if (modal) {
                window.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        modal.style.display = 'none';
                    }
                });
            }

            if (forgotForm) {
                forgotForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    alert('A password reset link has been sent to your email!');
                    modal.style.display = 'none';
                });
            }
        });


        <?php if (isset($_POST['upload_resume'])): ?>
            switchTab('resume');
        <?php endif; ?>
    </script>
</body>
</html>

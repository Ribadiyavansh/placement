<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch existing resume data
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

    // Languages
    $stmt = $conn->prepare("SELECT * FROM resume_languages WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $languages = $stmt->fetchAll();

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
    <title>Resume Builder - Student</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .resume-builder {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
        }
        .resume-score {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
            text-align: center;
        }
        .score-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2.5rem;
            font-weight: 700;
        }
        .section-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            overflow-x: auto;
            border-bottom: 2px solid var(--border-color);
            width: 100%;
            padding-bottom: 5px;
        }
        .tab-btn {
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: var(--text-secondary);
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            white-space: nowrap;
        }
        .tab-btn.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-family: inherit;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .dynamic-list {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .dynamic-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            position: relative;
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
        .tag-input-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            min-height: 50px;
        }
        .tag {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .tag .remove-tag {
            cursor: pointer;
            font-weight: bold;
        }

        /* Responsive adjustments for resume builder */
        @media (max-width: 768px) {
            .resume-score {
                padding: 1.5rem 1rem;
            }
            .score-circle {
                width: 100px;
                height: 100px;
                font-size: 2rem;
            }
            .card,
            .dynamic-list {
                padding: 1.25rem;
            }
            .form-group {
                margin-bottom: 1.25rem;
            }
        }

        @media (max-width: 480px) {
            .resume-builder {
                margin: 0;
                padding: 0 0.25rem;
            }
            .section-tabs {
                padding-bottom: 0.25rem;
            }
            .tab-btn {
                padding: 0.5rem 0.875rem;
                font-size: 0.8rem;
            }
        }

    </style>
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
                <a href="my_applications.php" class="nav-link">
                    <i class="fas fa-file-alt"></i> My Applications
                </a>
                <a href="resume_builder.php" class="nav-link active">
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
                    <h1 class="page-title"><i class="fas fa-file-invoice"></i> Resume Builder</h1>
                    <p style="color: var(--text-secondary);">Build your professional resume step by step</p>
                </div>
                <div>
                    <a href="resume_view.php" class="btn btn-primary" target="_blank">
                        <i class="fas fa-eye"></i> Preview Resume
                    </a>
                </div>
            </header>

            <div class="resume-builder">
                <!-- Resume Score -->
                <div class="resume-score">
                    <div class="score-circle"><?= $score ?>%</div>
                    <h2>Resume Completeness Score</h2>
                    <p>Complete all sections to reach 100%</p>
                </div>

                <!-- Section Tabs -->
                <div class="section-tabs">
                    <button class="tab-btn active" data-tab="personal">
                        <i class="fas fa-user"></i> Personal Info
                    </button>
                    <button class="tab-btn" data-tab="education">
                        <i class="fas fa-graduation-cap"></i> Education
                    </button>
                    <button class="tab-btn" data-tab="skills">
                        <i class="fas fa-code"></i> Skills
                    </button>
                    <button class="tab-btn" data-tab="projects">
                        <i class="fas fa-project-diagram"></i> Projects
                    </button>
                    <button class="tab-btn" data-tab="experience">
                        <i class="fas fa-briefcase"></i> Experience
                    </button>
                    <button class="tab-btn" data-tab="certifications">
                        <i class="fas fa-certificate"></i> Certifications
                    </button>
                    <button class="tab-btn" data-tab="achievements">
                        <i class="fas fa-trophy"></i> Achievements
                    </button>
                    <button class="tab-btn" data-tab="eligibility">
                        <i class="fas fa-check-circle"></i> Eligibility
                    </button>
                </div>

                <!-- Tab Contents -->
                
                <!-- Personal Information -->
                <div class="tab-content active" id="personal">
                    <div class="card">
                        <h2 style="margin-bottom: 1.5rem;">Personal Information</h2>
                        <form action="resume_actions.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="save_personal">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Full Name *</label>
                                    <input type="text" name="full_name" value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" required readonly>
                                </div>
                                <div class="form-group">
                                    <label>Gender</label>
                                    <select name="gender">
                                        <option value="">Select</option>
                                        <option value="Male" <?= ($personal['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= ($personal['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
                                        <option value="Other" <?= ($personal['gender'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Date of Birth *</label>
                                    <input type="date" name="dob" value="<?= $personal['dob'] ?? '' ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Phone Number *</label>
                                    <input type="tel" name="phone" value="<?= $personal['phone'] ?? '' ?>" pattern="[0-9]{10}" required>
                                </div>
                                <div class="form-group">
                                    <label>Email *</label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" required readonly>
                                </div>
                                <div class="form-group">
                                    <label>Alternate Email</label>
                                    <input type="email" name="alternate_email" value="<?= $personal['alternate_email'] ?? '' ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Address *</label>
                                <textarea name="address" required><?= $personal['address'] ?? '' ?></textarea>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label>LinkedIn URL</label>
                                    <input type="url" name="linkedin_url" value="<?= $personal['linkedin_url'] ?? '' ?>">
                                </div>
                                <div class="form-group">
                                    <label>GitHub URL</label>
                                    <input type="url" name="github_url" value="<?= $personal['github_url'] ?? '' ?>">
                                </div>
                                <div class="form-group">
                                    <label>Portfolio URL</label>
                                    <input type="url" name="portfolio_url" value="<?= $personal['portfolio_url'] ?? '' ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Professional Summary (300 words) *</label>
                                <textarea name="summary" maxlength="2000" required><?= $personal['summary'] ?? '' ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Personal Info
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Education -->
                <div class="tab-content" id="education">
                    <div class="card">
                        <h2 style="margin-bottom: 1.5rem;">Education Details</h2>
                        
                        <?php foreach ($education as $edu): ?>
                        <div class="dynamic-item">
                            <form action="resume_actions.php" method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_education">
                                <input type="hidden" name="id" value="<?= $edu['id'] ?>">
                                <button type="submit" class="remove-btn" onclick="return confirm('Delete this entry?')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                            
                            <p><strong><?= htmlspecialchars($edu['degree']) ?> - <?= htmlspecialchars($edu['specialization']) ?></strong></p>
                            <p><?= htmlspecialchars($edu['institution']) ?> (<?= $edu['start_year'] ?> - <?= $edu['end_year'] ?>)</p>
                            <p>CGPA/Percentage: <?= $edu['percentage_cgpa'] ?>%</p>
                        </div>
                        <?php endforeach; ?>

                        <div class="dynamic-list">
                            <h3>Add New Education</h3>
                            <form action="resume_actions.php" method="POST">
                                <input type="hidden" name="action" value="add_education">
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Degree *</label>
                                        <input type="text" name="degree" placeholder="B.Tech, BBA, MBA" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Specialization *</label>
                                        <input type="text" name="specialization" placeholder="Computer Science" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Institution *</label>
                                        <input type="text" name="institution" required>
                                    </div>
                                    <div class="form-group">
                                        <label>University/Board *</label>
                                        <input type="text" name="university_board" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Start Year *</label>
                                        <input type="number" name="start_year" min="1990" max="2030" required>
                                    </div>
                                    <div class="form-group">
                                        <label>End Year *</label>
                                        <input type="number" name="end_year" min="1990" max="2030" required>
                                    </div>
                                    <div class="form-group">
                                        <label>CGPA/Percentage *</label>
                                        <input type="number" name="percentage_cgpa" step="0.01" min="0" max="100" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Backlogs</label>
                                        <input type="number" name="backlogs" value="0" min="0">
                                    </div>
                                    <div class="form-group">
                                        <label>Status *</label>
                                        <select name="current_status" required>
                                            <option value="Pursuing">Pursuing</option>
                                            <option value="Completed">Completed</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <button type="submit" class="add-more-btn">
                                    <i class="fas fa-plus"></i> Add Education
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Skills -->
                <div class="tab-content" id="skills">
                    <div class="card">
                        <h2 style="margin-bottom: 1.5rem;">Skills</h2>
                        
                        <h3>Current Skills</h3>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 2rem;">
                            <?php foreach ($skills as $skill): ?>
                            <div class="tag">
                                <?= htmlspecialchars($skill['skill_name']) ?> (<?= $skill['proficiency'] ?>)
                                <form action="resume_actions.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_skill">
                                    <input type="hidden" name="id" value="<?= $skill['id'] ?>">
                                    <button type="submit" style="background: none; border: none; color: white; cursor: pointer;">×</button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="dynamic-list">
                            <h3>Add New Skill</h3>
                            <form action="resume_actions.php" method="POST">
                                <input type="hidden" name="action" value="add_skill">
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Skill Name *</label>
                                        <input type="text" name="skill_name" placeholder="e.g., Python, Leadership" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Type *</label>
                                        <select name="type" required>
                                            <option value="Technical">Technical</option>
                                            <option value="Soft">Soft Skill</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Proficiency *</label>
                                        <select name="proficiency" required>
                                            <option value="Beginner">Beginner</option>
                                            <option value="Intermediate">Intermediate</option>
                                            <option value="Advanced">Advanced</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <button type="submit" class="add-more-btn">
                                    <i class="fas fa-plus"></i> Add Skill
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Projects -->
                <div class="tab-content" id="projects">
                    <div class="card">
                        <h2 style="margin-bottom: 1.5rem;">Projects</h2>
                        
                        <?php foreach ($projects as $project): ?>
                        <div class="dynamic-item">
                            <form action="resume_actions.php" method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_project">
                                <input type="hidden" name="id" value="<?= $project['id'] ?>">
                                <button type="submit" class="remove-btn" onclick="return confirm('Delete this project?')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                            
                            <p><strong><?= htmlspecialchars($project['title']) ?></strong></p>
                            <p>Role: <?= htmlspecialchars($project['role']) ?></p>
                            <p>Tech: <?= htmlspecialchars($project['tech_stack']) ?></p>
                            <?php if ($project['project_link']): ?>
                            <p><a href="<?= htmlspecialchars($project['project_link']) ?>" target="_blank">View Project</a></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>

                        <div class="dynamic-list">
                            <h3>Add New Project</h3>
                            <form action="resume_actions.php" method="POST">
                                <input type="hidden" name="action" value="add_project">
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Project Title *</label>
                                        <input type="text" name="title" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Your Role *</label>
                                        <input type="text" name="role" placeholder="Developer, Team Lead" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Tech Stack</label>
                                        <input type="text" name="tech_stack" placeholder="React, Node.js, MongoDB">
                                    </div>
                                    <div class="form-group">
                                        <label>Start Date</label>
                                        <input type="date" name="start_date">
                                    </div>
                                    <div class="form-group">
                                        <label>End Date</label>
                                        <input type="date" name="end_date">
                                    </div>
                                    <div class="form-group">
                                        <label>Project Link</label>
                                        <input type="url" name="project_link" placeholder="GitHub/Live URL">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Description *</label>
                                    <textarea name="description" required></textarea>
                                </div>
                                
                                <button type="submit" class="add-more-btn">
                                    <i class="fas fa-plus"></i> Add Project
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Experience -->
                <div class="tab-content" id="experience">
                    <div class="card">
                        <h2 style="margin-bottom: 1.5rem;">Work Experience</h2>
                        
                        <?php foreach ($experience as $exp): ?>
                        <div class="dynamic-item">
                            <form action="resume_actions.php" method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_experience">
                                <input type="hidden" name="id" value="<?= $exp['id'] ?>">
                                <button type="submit" class="remove-btn" onclick="return confirm('Delete this entry?')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                            
                            <p><strong><?= htmlspecialchars($exp['role']) ?></strong> at <?= htmlspecialchars($exp['company_name']) ?></p>
                            <p><?= $exp['employment_type'] ?> | <?= date('M Y', strtotime($exp['start_date'])) ?> - <?= $exp['end_date'] ? date('M Y', strtotime($exp['end_date'])) : 'Present' ?></p>
                        </div>
                        <?php endforeach; ?>

                        <div class="dynamic-list">
                            <h3>Add New Experience</h3>
                            <form action="resume_actions.php" method="POST">
                                <input type="hidden" name="action" value="add_experience">
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Company Name *</label>
                                        <input type="text" name="company_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Role *</label>
                                        <input type="text" name="role" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Employment Type *</label>
                                        <select name="employment_type" required>
                                            <option value="Internship">Internship</option>
                                            <option value="Full-time">Full-time</option>
                                            <option value="Part-time">Part-time</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Start Date *</label>
                                        <input type="date" name="start_date" required>
                                    </div>
                                    <div class="form-group">
                                        <label>End Date</label>
                                        <input type="date" name="end_date">
                                        <small>Leave blank if currently working</small>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" placeholder="Key responsibilities and achievements"></textarea>
                                </div>
                                
                                <button type="submit" class="add-more-btn">
                                    <i class="fas fa-plus"></i> Add Experience
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Certifications -->
                <div class="tab-content" id="certifications">
                    <div class="card">
                        <h2 style="margin-bottom: 1.5rem;">Certifications</h2>
                        
                        <?php foreach ($certifications as $cert): ?>
                        <div class="dynamic-item">
                            <form action="resume_actions.php" method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_certification">
                                <input type="hidden" name="id" value="<?= $cert['id'] ?>">
                                <button type="submit" class="remove-btn" onclick="return confirm('Delete this certification?')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                            
                            <p><strong><?= htmlspecialchars($cert['certificate_name']) ?></strong></p>
                            <p><?= htmlspecialchars($cert['issuing_org']) ?> | <?= date('M Y', strtotime($cert['issue_date'])) ?></p>
                            <?php if (!empty($cert['certificate_file'])): ?>
                            <p><a href="../<?= htmlspecialchars($cert['certificate_file']) ?>" target="_blank" style="color: var(--primary-color); text-decoration: underline;"><i class="fas fa-file-pdf"></i> View Certificate</a></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>

                        <div class="dynamic-list">
                            <h3>Add New Certification</h3>
                            <form action="resume_actions.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="add_certification">
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Certificate Name *</label>
                                        <input type="text" name="certificate_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Issuing Organization *</label>
                                        <input type="text" name="issuing_org" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Issue Date</label>
                                        <input type="date" name="issue_date">
                                    </div>
                                    <div class="form-group">
                                        <label>Credential ID</label>
                                        <input type="text" name="credential_id">
                                    </div>
                                    <div class="form-group">
                                        <label>Upload Certificate (PDF only)</label>
                                        <input type="file" name="certificate_file" accept=".pdf">
                                    </div>
                                </div>
                                
                                <button type="submit" class="add-more-btn">
                                    <i class="fas fa-plus"></i> Add Certification
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Achievements -->
                <div class="tab-content" id="achievements">
                    <div class="card">
                        <h2 style="margin-bottom: 1.5rem;">Achievements</h2>
                        
                        <?php foreach ($achievements as $ach): ?>
                        <div class="dynamic-item">
                            <form action="resume_actions.php" method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_achievement">
                                <input type="hidden" name="id" value="<?= $ach['id'] ?>">
                                <button type="submit" class="remove-btn" onclick="return confirm('Delete this achievement?')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                            
                            <p><strong><?= htmlspecialchars($ach['title']) ?></strong> (<?= $ach['level'] ?>)</p>
                            <p><?= htmlspecialchars($ach['description']) ?></p>
                        </div>
                        <?php endforeach; ?>

                        <div class="dynamic-list">
                            <h3>Add New Achievement</h3>
                            <form action="resume_actions.php" method="POST">
                                <input type="hidden" name="action" value="add_achievement">
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Title *</label>
                                        <input type="text" name="title" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Level</label>
                                        <select name="level">
                                            <option value="College">College</option>
                                            <option value="State">State</option>
                                            <option value="National">National</option>
                                            <option value="International">International</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Date</label>
                                        <input type="date" name="date">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description"></textarea>
                                </div>
                                
                                <button type="submit" class="add-more-btn">
                                    <i class="fas fa-plus"></i> Add Achievement
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Placement Eligibility -->
                <div class="tab-content" id="eligibility">
                    <div class="card">
                        <h2 style="margin-bottom: 1.5rem;">Placement Eligibility</h2>
                        <form action="resume_actions.php" method="POST">
                            <input type="hidden" name="action" value="save_eligibility">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Willing to Relocate? *</label>
                                    <select name="willing_relocate" required>
                                        <option value="1" <?= ($eligibility['willing_relocate'] ?? 1) == 1 ? 'selected' : '' ?>>Yes</option>
                                        <option value="0" <?= ($eligibility['willing_relocate'] ?? 1) == 0 ? 'selected' : '' ?>>No</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Preferred Locations</label>
                                    <input type="text" name="preferred_locations" value="<?= $eligibility['preferred_locations'] ?? '' ?>" placeholder="Mumbai, Delhi, Bangalore">
                                </div>
                                <div class="form-group">
                                    <label>Expected CTC (in LPA)</label>
                                    <input type="number" name="expected_ctc" step="0.1" value="<?= $eligibility['expected_ctc'] ?? '' ?>" placeholder="3.5">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Eligibility
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        // Check for tab parameter in URL and activate it
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            if (tabParam) {
                // Find and activate the tab button
                const tabBtn = document.querySelector(`.tab-btn[data-tab="${tabParam}"]`);
                if (tabBtn) {
                    // Remove active class from all tabs
                    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                    
                    // Activate the requested tab
                    tabBtn.classList.add('active');
                    const tabContent = document.getElementById(tabParam);
                    if (tabContent) {
                        tabContent.classList.add('active');
                    }
                }
            }
        });
        
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tab = btn.dataset.tab;
                
                // Update buttons
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Update content
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                document.getElementById(tab).classList.add('active');
            });
        });
    </script>
</body>
</html>

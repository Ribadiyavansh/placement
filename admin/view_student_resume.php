<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_GET['id'] ?? 0;

// Fetch all resume data for the student
try {
    // User Info
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'student'");
    $stmt->execute([$student_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        die("Student not found");
    }

    // Personal Info
    $stmt = $conn->prepare("SELECT * FROM resume_personal WHERE user_id = ?");
    $stmt->execute([$student_id]);
    $personal = $stmt->fetch() ?: [];

    // Education
    $stmt = $conn->prepare("SELECT * FROM resume_education WHERE user_id = ? ORDER BY start_year DESC");
    $stmt->execute([$student_id]);
    $education = $stmt->fetchAll();

    // Skills
    $stmt = $conn->prepare("SELECT * FROM resume_skills WHERE user_id = ? ORDER BY type, proficiency DESC");
    $stmt->execute([$student_id]);
    $skills = $stmt->fetchAll();

    // Projects
    $stmt = $conn->prepare("SELECT * FROM resume_projects WHERE user_id = ? ORDER BY start_date DESC");
    $stmt->execute([$student_id]);
    $projects = $stmt->fetchAll();

    // Experience
    $stmt = $conn->prepare("SELECT * FROM resume_experience WHERE user_id = ? ORDER BY start_date DESC");
    $stmt->execute([$student_id]);
    $experience = $stmt->fetchAll();

    // Certifications
    $stmt = $conn->prepare("SELECT * FROM resume_certifications WHERE user_id = ? ORDER BY issue_date DESC");
    $stmt->execute([$student_id]);
    $certifications = $stmt->fetchAll();

    // Achievements
    $stmt = $conn->prepare("SELECT * FROM resume_achievements WHERE user_id = ? ORDER BY date DESC");
    $stmt->execute([$student_id]);
    $achievements = $stmt->fetchAll();

    // Eligibility
    $stmt = $conn->prepare("SELECT * FROM placement_eligibility WHERE user_id = ?");
    $stmt->execute([$student_id]);
    $eligibility = $stmt->fetch() ?: [];

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// If download parameter is set, trigger print dialog
$auto_print = isset($_GET['download']) ? 'true' : 'false';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume - <?= htmlspecialchars($user['name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
        }

        .no-print {
            padding: 1rem;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            margin-left: 0.5rem;
        }

        .btn-primary {
            background: #4f46e5;
            color: white;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .resume-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .resume-header {
            text-align: center;
            border-bottom: 3px solid #4f46e5;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .resume-header h1 {
            font-size: 2.5rem;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .contact-info {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 0.9rem;
            color: #6b7280;
        }

        .contact-info span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.5rem;
            color: #4f46e5;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .summary {
            text-align: justify;
            color: #4b5563;
            line-height: 1.8;
        }

        .entry {
            margin-bottom: 20px;
        }

        .entry-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 5px;
        }

        .entry-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: #1f2937;
        }

        .entry-subtitle {
            color: #6b7280;
            font-style: italic;
        }

        .entry-date {
            color: #9ca3af;
            font-size: 0.9rem;
        }

        .entry-description {
            color: #4b5563;
            margin-top: 5px;
        }

        .skills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
        }

        .skill-tag {
            background: #e0e7ff;
            color: #4f46e5;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            text-align: center;
        }

        .admin-note {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            margin-bottom: 2rem;
        }

        @media print {
            body {
                background: white;
            }

            .no-print {
                display: none !important;
            }

            .resume-container {
                box-shadow: none;
                padding: 20px;
                max-width: 100%;
            }

            .section {
                page-break-inside: avoid;
            }

            @page {
                margin: 1cm;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <div>
            <h2>Student Resume - Admin View</h2>
            <p style="color: #6b7280; margin-top: 0.25rem;">Resume Score: <strong><?= $eligibility['resume_score'] ?? 0 ?>%</strong></p>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-download"></i> Download PDF
            </button>
            <a href="student_resumes.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="resume-container">
        <!-- Admin Note -->
        <div class="admin-note no-print">
            <strong>Admin Information:</strong><br>
            Resume Score: <?= $eligibility['resume_score'] ?? 0 ?>% | 
            Expected CTC: <?= $eligibility['expected_ctc'] ?? 'Not specified' ?> LPA | 
            Willing to Relocate: <?= ($eligibility['willing_relocate'] ?? 0) ? 'Yes' : 'No' ?>
            <?php if (!empty($eligibility['preferred_locations'])): ?>
            | Preferred Locations: <?= htmlspecialchars($eligibility['preferred_locations']) ?>
            <?php endif; ?>
        </div>

        <!-- Header -->
        <div class="resume-header">
            <h1><?= htmlspecialchars($user['name']) ?></h1>
            <div class="contact-info">
                <?php if (!empty($personal['phone'])): ?>
                <span><i class="fas fa-phone"></i> <?= htmlspecialchars($personal['phone']) ?></span>
                <?php endif; ?>
                <span><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></span>
                <?php if (!empty($personal['address'])): ?>
                <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($personal['address']) ?></span>
                <?php endif; ?>
            </div>
            <div class="contact-info" style="margin-top: 10px;">
                <?php if (!empty($personal['linkedin_url'])): ?>
                <span><i class="fab fa-linkedin"></i> LinkedIn</span>
                <?php endif; ?>
                <?php if (!empty($personal['github_url'])): ?>
                <span><i class="fab fa-github"></i> GitHub</span>
                <?php endif; ?>
                <?php if (!empty($personal['portfolio_url'])): ?>
                <span><i class="fas fa-globe"></i> Portfolio</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Professional Summary -->
        <?php if (!empty($personal['summary'])): ?>
        <div class="section">
            <h2 class="section-title">Professional Summary</h2>
            <p class="summary"><?= nl2br(htmlspecialchars($personal['summary'])) ?></p>
        </div>
        <?php endif; ?>

        <!-- Education -->
        <?php if (!empty($education)): ?>
        <div class="section">
            <h2 class="section-title">Education</h2>
            <?php foreach ($education as $edu): ?>
            <div class="entry">
                <div class="entry-header">
                    <div>
                        <div class="entry-title"><?= htmlspecialchars($edu['degree']) ?> - <?= htmlspecialchars($edu['specialization']) ?></div>
                        <div class="entry-subtitle"><?= htmlspecialchars($edu['institution']) ?>, <?= htmlspecialchars($edu['university_board']) ?></div>
                    </div>
                    <div class="entry-date"><?= $edu['start_year'] ?> - <?= $edu['end_year'] ?></div>
                </div>
                <div class="entry-description">
                    CGPA/Percentage: <?= $edu['percentage_cgpa'] ?>% | Status: <?= $edu['current_status'] ?>
                    <?php if ($edu['backlogs'] > 0): ?>
                    | Backlogs: <?= $edu['backlogs'] ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Skills -->
        <?php if (!empty($skills)): ?>
        <div class="section">
            <h2 class="section-title">Skills</h2>
            
            <?php
            $technical = array_filter($skills, fn($s) => $s['type'] === 'Technical');
            $soft = array_filter($skills, fn($s) => $s['type'] === 'Soft');
            ?>
            
            <?php if (!empty($technical)): ?>
            <h3 style="margin-bottom: 10px; color: #6b7280;">Technical Skills</h3>
            <div class="skills-grid" style="margin-bottom: 20px;">
                <?php foreach ($technical as $skill): ?>
                <div class="skill-tag"><?= htmlspecialchars($skill['skill_name']) ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($soft)): ?>
            <h3 style="margin-bottom: 10px; color: #6b7280;">Soft Skills</h3>
            <div class="skills-grid">
                <?php foreach ($soft as $skill): ?>
                <div class="skill-tag"><?= htmlspecialchars($skill['skill_name']) ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Experience -->
        <?php if (!empty($experience)): ?>
        <div class="section">
            <h2 class="section-title">Work Experience</h2>
            <?php foreach ($experience as $exp): ?>
            <div class="entry">
                <div class="entry-header">
                    <div>
                        <div class="entry-title"><?= htmlspecialchars($exp['role']) ?></div>
                        <div class="entry-subtitle"><?= htmlspecialchars($exp['company_name']) ?> | <?= $exp['employment_type'] ?></div>
                    </div>
                    <div class="entry-date">
                        <?= date('M Y', strtotime($exp['start_date'])) ?> - 
                        <?= $exp['end_date'] ? date('M Y', strtotime($exp['end_date'])) : 'Present' ?>
                    </div>
                </div>
                <?php if (!empty($exp['description'])): ?>
                <div class="entry-description"><?= nl2br(htmlspecialchars($exp['description'])) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Projects -->
        <?php if (!empty($projects)): ?>
        <div class="section">
            <h2 class="section-title">Projects</h2>
            <?php foreach ($projects as $project): ?>
            <div class="entry">
                <div class="entry-header">
                    <div>
                        <div class="entry-title"><?= htmlspecialchars($project['title']) ?></div>
                        <div class="entry-subtitle">
                            <?= htmlspecialchars($project['role']) ?>
                            <?php if (!empty($project['tech_stack'])): ?>
                            | Tech: <?= htmlspecialchars($project['tech_stack']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($project['start_date']): ?>
                    <div class="entry-date">
                        <?= date('M Y', strtotime($project['start_date'])) ?>
                        <?php if ($project['end_date']): ?>
                        - <?= date('M Y', strtotime($project['end_date'])) ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($project['description'])): ?>
                <div class="entry-description"><?= nl2br(htmlspecialchars($project['description'])) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Certifications -->
        <?php if (!empty($certifications)): ?>
        <div class="section">
            <h2 class="section-title">Certifications</h2>
            <?php foreach ($certifications as $cert): ?>
            <div class="entry">
                <div class="entry-header">
                    <div>
                        <div class="entry-title"><?= htmlspecialchars($cert['certificate_name']) ?></div>
                        <div class="entry-subtitle"><?= htmlspecialchars($cert['issuing_org']) ?></div>
                        <?php if (!empty($cert['certificate_file'])): ?>
                        <div style="margin-top: 5px;">
                            <a href="../<?= htmlspecialchars($cert['certificate_file']) ?>" target="_blank" style="color: #4f46e5; text-decoration: none; font-size: 0.85rem;">
                                <i class="fas fa-file-pdf"></i> View Certificate
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($cert['issue_date']): ?>
                    <div class="entry-date"><?= date('M Y', strtotime($cert['issue_date'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Achievements -->
        <?php if (!empty($achievements)): ?>
        <div class="section">
            <h2 class="section-title">Achievements</h2>
            <?php foreach ($achievements as $ach): ?>
            <div class="entry">
                <div class="entry-header">
                    <div>
                        <div class="entry-title"><?= htmlspecialchars($ach['title']) ?></div>
                        <?php if (!empty($ach['description'])): ?>
                        <div class="entry-description"><?= htmlspecialchars($ach['description']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="entry-date"><?= $ach['level'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-generate filename for PDF
        window.onbeforeprint = function() {
            document.title = "Resume_<?= str_replace(' ', '_', $user['name']) ?>";
        };

        // Auto-trigger print if download parameter is set
        <?php if ($auto_print === 'true'): ?>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        <?php endif; ?>
    </script>
</body>
</html>

<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Filters
$skill_filter = $_GET['skill'] ?? '';
$min_score = $_GET['min_score'] ?? 0;
$relocate_filter = $_GET['relocate'] ?? '';
$min_ctc = $_GET['min_ctc'] ?? '';
$max_ctc = $_GET['max_ctc'] ?? '';

// Fetch students with resume data
try {
    $sql = "SELECT 
                u.id, u.name, u.email,
                pe.resume_score, pe.willing_relocate, pe.expected_ctc, pe.preferred_locations,
                GROUP_CONCAT(DISTINCT rs.skill_name SEPARATOR ', ') as skills,
                COUNT(DISTINCT rp.id) as project_count,
                COUNT(DISTINCT re.id) as experience_count
            FROM users u
            LEFT JOIN placement_eligibility pe ON u.id = pe.user_id
            LEFT JOIN resume_skills rs ON u.id = rs.user_id
            LEFT JOIN resume_projects rp ON u.id = rp.user_id
            LEFT JOIN resume_experience re ON u.id = re.user_id
            WHERE u.role = 'student'";
    
    $params = [];
    
    if ($skill_filter) {
        $sql .= " AND u.id IN (SELECT user_id FROM resume_skills WHERE skill_name LIKE ?)";
        $params[] = "%$skill_filter%";
    }
    
    if ($min_score > 0) {
        $sql .= " AND pe.resume_score >= ?";
        $params[] = $min_score;
    }
    
    if ($relocate_filter !== '') {
        $sql .= " AND pe.willing_relocate = ?";
        $params[] = $relocate_filter;
    }
    
    if ($min_ctc) {
        $sql .= " AND pe.expected_ctc >= ?";
        $params[] = $min_ctc;
    }
    
    if ($max_ctc) {
        $sql .= " AND pe.expected_ctc <= ?";
        $params[] = $max_ctc;
    }
    
    $sql .= " GROUP BY u.id ORDER BY pe.resume_score DESC, u.name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Resumes - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-panel {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-group label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        .filter-group input,
        .filter-group select {
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
        }
        .student-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .student-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .student-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        .score-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .student-info {
            flex: 1;
        }
        .student-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        .student-email {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        .student-stats {
            display: flex;
            gap: 1.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
        .stat {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        .skills-preview {
            margin-top: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        .skills-preview strong {
            color: var(--text-primary);
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
                <a href="manage_jobs.php" class="nav-link">
                    <i class="fas fa-briefcase"></i> Manage Jobs
                </a>
                <a href="manage_applications.php" class="nav-link">
                    <i class="fas fa-file-alt"></i> Applications
                </a>
                <a href="student_resumes.php" class="nav-link active">
                    <i class="fas fa-users"></i> Student Resumes
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
                    <h1 class="page-title">Student Resumes</h1>
                    <p style="color: var(--text-secondary);">Filter and view student profiles</p>
                </div>
            </header>

            <!-- Filter Panel -->
            <div class="filter-panel">
                <h3 style="margin-bottom: 1rem;">Filter Students</h3>
                <form method="GET" action="">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label>Skill</label>
                            <input type="text" name="skill" value="<?= htmlspecialchars($skill_filter) ?>" placeholder="e.g., Python">
                        </div>
                        <div class="filter-group">
                            <label>Min Resume Score</label>
                            <input type="number" name="min_score" value="<?= $min_score ?>" min="0" max="100">
                        </div>
                        <div class="filter-group">
                            <label>Willing to Relocate</label>
                            <select name="relocate">
                                <option value="">Any</option>
                                <option value="1" <?= $relocate_filter === '1' ? 'selected' : '' ?>>Yes</option>
                                <option value="0" <?= $relocate_filter === '0' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Min Expected CTC (LPA)</label>
                            <input type="number" name="min_ctc" value="<?= $min_ctc ?>" step="0.1" placeholder="3.0">
                        </div>
                        <div class="filter-group">
                            <label>Max Expected CTC (LPA)</label>
                            <input type="number" name="max_ctc" value="<?= $max_ctc ?>" step="0.1" placeholder="10.0">
                        </div>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="student_resumes.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    </div>
                </form>
            </div>

            <!-- Students List -->
            <div>
                <h3 style="margin-bottom: 1rem;">Found <?= count($students) ?> Students</h3>
                
                <?php if (empty($students)): ?>
                    <div class="card" style="text-align: center; padding: 3rem;">
                        <i class="fas fa-users" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                        <p style="color: var(--text-secondary);">No students found matching your criteria.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                    <div class="student-card">
                        <div class="student-header">
                            <div class="student-info">
                                <div class="student-name"><?= htmlspecialchars($student['name']) ?></div>
                                <div class="student-email">
                                    <i class="fas fa-envelope"></i> <?= htmlspecialchars($student['email']) ?>
                                </div>
                                
                                <?php if ($student['skills']): ?>
                                <div class="skills-preview">
                                    <strong>Skills:</strong> <?= htmlspecialchars(substr($student['skills'], 0, 100)) ?><?= strlen($student['skills']) > 100 ? '...' : '' ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($student['preferred_locations']): ?>
                                <div class="skills-preview">
                                    <strong>Preferred Locations:</strong> <?= htmlspecialchars($student['preferred_locations']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="score-badge">
                                <?= $student['resume_score'] ?? 0 ?>%
                            </div>
                        </div>
                        
                        <div class="student-stats">
                            <div class="stat">
                                <i class="fas fa-project-diagram"></i>
                                <span><?= $student['project_count'] ?> Projects</span>
                            </div>
                            <div class="stat">
                                <i class="fas fa-briefcase"></i>
                                <span><?= $student['experience_count'] ?> Experiences</span>
                            </div>
                            <?php if ($student['expected_ctc']): ?>
                            <div class="stat">
                                <i class="fas fa-rupee-sign"></i>
                                <span><?= $student['expected_ctc'] ?> LPA</span>
                            </div>
                            <?php endif; ?>
                            <div class="stat">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?= $student['willing_relocate'] ? 'Willing to Relocate' : 'Not Relocating' ?></span>
                            </div>
                        </div>
                        
                        <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                            <a href="view_student_resume.php?id=<?= $student['id'] ?>" class="btn btn-primary" target="_blank">
                                <i class="fas fa-eye"></i> View Resume
                            </a>
                            <a href="view_student_resume.php?id=<?= $student['id'] ?>&download=1" class="btn btn-outline">
                                <i class="fas fa-download"></i> Download PDF
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>

<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        // Personal Information
        case 'save_personal':
            $stmt = $conn->prepare("
                INSERT INTO resume_personal (user_id, gender, dob, phone, alternate_email, address, linkedin_url, github_url, portfolio_url, summary)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                gender = VALUES(gender),
                dob = VALUES(dob),
                phone = VALUES(phone),
                alternate_email = VALUES(alternate_email),
                address = VALUES(address),
                linkedin_url = VALUES(linkedin_url),
                github_url = VALUES(github_url),
                portfolio_url = VALUES(portfolio_url),
                summary = VALUES(summary)
            ");
            $stmt->execute([
                $user_id,
                $_POST['gender'] ?? null,
                $_POST['dob'],
                $_POST['phone'],
                $_POST['alternate_email'] ?? null,
                $_POST['address'],
                $_POST['linkedin_url'] ?? null,
                $_POST['github_url'] ?? null,
                $_POST['portfolio_url'] ?? null,
                $_POST['summary']
            ]);
            $_SESSION['success'] = "Personal information saved successfully!";
            break;

        // Education
        case 'add_education':
            $stmt = $conn->prepare("
                INSERT INTO resume_education (user_id, degree, specialization, institution, university_board, start_year, end_year, percentage_cgpa, backlogs, current_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $_POST['degree'],
                $_POST['specialization'],
                $_POST['institution'],
                $_POST['university_board'],
                $_POST['start_year'],
                $_POST['end_year'],
                $_POST['percentage_cgpa'],
                $_POST['backlogs'] ?? 0,
                $_POST['current_status']
            ]);
            $_SESSION['success'] = "Education added successfully!";
            break;

        case 'delete_education':
            $stmt = $conn->prepare("DELETE FROM resume_education WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['id'], $user_id]);
            $_SESSION['success'] = "Education deleted successfully!";
            break;

        // Skills
        case 'add_skill':
            $stmt = $conn->prepare("
                INSERT INTO resume_skills (user_id, skill_name, type, proficiency)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $_POST['skill_name'],
                $_POST['type'],
                $_POST['proficiency']
            ]);
            $_SESSION['success'] = "Skill added successfully!";
            break;

        case 'delete_skill':
            $stmt = $conn->prepare("DELETE FROM resume_skills WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['id'], $user_id]);
            $_SESSION['success'] = "Skill deleted successfully!";
            break;

        // Projects
        case 'add_project':
            $stmt = $conn->prepare("
                INSERT INTO resume_projects (user_id, title, role, tech_stack, description, start_date, end_date, project_link)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $_POST['title'],
                $_POST['role'],
                $_POST['tech_stack'] ?? null,
                $_POST['description'],
                $_POST['start_date'] ?? null,
                $_POST['end_date'] ?? null,
                $_POST['project_link'] ?? null
            ]);
            $_SESSION['success'] = "Project added successfully!";
            break;

        case 'delete_project':
            $stmt = $conn->prepare("DELETE FROM resume_projects WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['id'], $user_id]);
            $_SESSION['success'] = "Project deleted successfully!";
            break;

        // Experience
        case 'add_experience':
            $is_current = empty($_POST['end_date']) ? 1 : 0;
            $stmt = $conn->prepare("
                INSERT INTO resume_experience (user_id, company_name, role, employment_type, start_date, end_date, is_current, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $_POST['company_name'],
                $_POST['role'],
                $_POST['employment_type'],
                $_POST['start_date'],
                $_POST['end_date'] ?? null,
                $is_current,
                $_POST['description'] ?? null
            ]);
            $_SESSION['success'] = "Experience added successfully!";
            break;

        case 'delete_experience':
            $stmt = $conn->prepare("DELETE FROM resume_experience WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['id'], $user_id]);
            $_SESSION['success'] = "Experience deleted successfully!";
            break;

        // Certifications
        case 'add_certification':
            $certificate_file = null;
            
            // Handle file upload
            if (isset($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] == 0) {
                $upload_dir = '../assets/uploads/certificates/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $file_ext = strtolower(pathinfo($_FILES['certificate_file']['name'], PATHINFO_EXTENSION));
                if ($file_ext != 'pdf') {
                    $_SESSION['error'] = "Only PDF files are allowed for certificates.";
                    header("Location: resume_builder.php?tab=certifications");
                    exit();
                }
                
                $new_filename = 'certificate_' . $user_id . '_' . time() . '.' . $file_ext;
                if (move_uploaded_file($_FILES['certificate_file']['tmp_name'], $upload_dir . $new_filename)) {
                    $certificate_file = 'assets/uploads/certificates/' . $new_filename;
                }
            }
            
            $stmt = $conn->prepare("
                INSERT INTO resume_certifications (user_id, certificate_name, issuing_org, issue_date, credential_id, certificate_file)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $_POST['certificate_name'],
                $_POST['issuing_org'],
                $_POST['issue_date'] ?? null,
                $_POST['credential_id'] ?? null,
                $certificate_file
            ]);
            $_SESSION['success'] = "Certification added successfully!";
            break;

        case 'delete_certification':
            // Get the certificate file path before deleting
            $stmt = $conn->prepare("SELECT certificate_file FROM resume_certifications WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['id'], $user_id]);
            $cert = $stmt->fetch();
            
            // Delete the record
            $stmt = $conn->prepare("DELETE FROM resume_certifications WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['id'], $user_id]);
            
            // Delete the file if it exists
            if ($cert && !empty($cert['certificate_file'])) {
                $file_path = '../' . $cert['certificate_file'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            $_SESSION['success'] = "Certification deleted successfully!";
            break;

        // Achievements
        case 'add_achievement':
            $stmt = $conn->prepare("
                INSERT INTO resume_achievements (user_id, title, description, level, date)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $_POST['title'],
                $_POST['description'] ?? null,
                $_POST['level'] ?? 'Other',
                $_POST['date'] ?? null
            ]);
            $_SESSION['success'] = "Achievement added successfully!";
            break;

        case 'delete_achievement':
            $stmt = $conn->prepare("DELETE FROM resume_achievements WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['id'], $user_id]);
            $_SESSION['success'] = "Achievement deleted successfully!";
            break;

        // Placement Eligibility
        case 'save_eligibility':
            $stmt = $conn->prepare("
                INSERT INTO placement_eligibility (user_id, willing_relocate, preferred_locations, expected_ctc)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                willing_relocate = VALUES(willing_relocate),
                preferred_locations = VALUES(preferred_locations),
                expected_ctc = VALUES(expected_ctc)
            ");
            $stmt->execute([
                $user_id,
                $_POST['willing_relocate'],
                $_POST['preferred_locations'] ?? null,
                $_POST['expected_ctc'] ?? null
            ]);
            $_SESSION['success'] = "Eligibility information saved successfully!";
            break;

        default:
            $_SESSION['error'] = "Invalid action!";
    }

    // Update resume score after any change
    updateResumeScore($conn, $user_id);

} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

// Determine which tab to redirect to based on the action
$redirect_url = $_POST['redirect_to'] ?? null;

if ($redirect_url) {
    header("Location: " . $redirect_url);
} else {
    $tab_map = [
        'save_personal' => 'personal',
        'add_education' => 'education',
        'delete_education' => 'education',
        'add_skill' => 'skills',
        'delete_skill' => 'skills',
        'add_project' => 'projects',
        'delete_project' => 'projects',
        'add_experience' => 'experience',
        'delete_experience' => 'experience',
        'add_certification' => 'certifications',
        'delete_certification' => 'certifications',
        'add_achievement' => 'achievements',
        'delete_achievement' => 'achievements',
        'save_eligibility' => 'eligibility'
    ];
    
    $tab = $tab_map[$action] ?? 'personal';
    header("Location: resume_builder.php?tab=" . $tab);
}
exit();

function updateResumeScore($conn, $user_id) {
    $score = 0;
    
    // Personal Info (15%)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM resume_personal WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() > 0) $score += 15;
    
    // Education (20%)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM resume_education WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() > 0) $score += 20;
    
    // Skills (15%)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM resume_skills WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() >= 5) $score += 15;
    
    // Projects (20%)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM resume_projects WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() >= 2) $score += 20;
    
    // Experience (15%)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM resume_experience WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() >= 1) $score += 15;
    
    // Certifications (10%)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM resume_certifications WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() >= 1) $score += 10;
    
    // Eligibility (5%)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM placement_eligibility WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() > 0) $score += 5;
    
    // Update score in placement_eligibility table
    $stmt = $conn->prepare("
        INSERT INTO placement_eligibility (user_id, resume_score)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE resume_score = VALUES(resume_score)
    ");
    $stmt->execute([$user_id, $score]);
}
?>

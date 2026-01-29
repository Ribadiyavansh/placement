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
            $gender = $_POST['gender'] ?? null;
            $dob = $_POST['dob'] ?? null;
            $phone = trim($_POST['phone'] ?? '');
            $alternate_email = trim($_POST['alternate_email'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $linkedin_url = trim($_POST['linkedin_url'] ?? '');
            $github_url = trim($_POST['github_url'] ?? '');
            $portfolio_url = trim($_POST['portfolio_url'] ?? '');
            $summary = trim($_POST['summary'] ?? '');

            // Personal validations A
            if (!empty($phone) && !preg_match('/^[0-9]{10,15}$/', $phone)) {
                $_SESSION['error'] = "Phone must be 10-15 numeric digits only.";
                header("Location: resume_builder.php?tab=personal");
                exit();
            }
            if (!empty($alternate_email) && !filter_var($alternate_email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = "Alternate email must be valid format.";
                header("Location: resume_builder.php?tab=personal");
                exit();
            }
            if (!empty($linkedin_url) && !preg_match('/^https?:\/\//i', $linkedin_url)) {
                $_SESSION['error'] = "LinkedIn URL must start with http:// or https://.";
                header("Location: resume_builder.php?tab=personal");
                exit();
            }
            if (!empty($github_url) && !preg_match('/^https?:\/\//i', $github_url)) {
                $_SESSION['error'] = "GitHub URL must start with http:// or https://.";
                header("Location: resume_builder.php?tab=personal");
                exit();
            }
            if (!empty($portfolio_url) && !preg_match('/^https?:\/\//i', $portfolio_url)) {
                $_SESSION['error'] = "Portfolio URL must start with http:// or https://.";
                header("Location: resume_builder.php?tab=personal");
                exit();
            }
            if (!empty($dob)) {
                $birth = new DateTime($dob);
                $today = new DateTime();
                $age = $today->diff($birth)->y;
                if ($age < 16 || $age > 60) {
                    $_SESSION['error'] = "Age must be between 16 and 60 years.";
                    header("Location: resume_builder.php?tab=personal");
                    exit();
                }
            }
            if (strlen($summary) < 50 || strlen($summary) > 500) {
                $_SESSION['error'] = "Summary must be 50-500 characters.";
                header("Location: resume_builder.php?tab=personal");
                exit();
            }

            // Profile photo upload GV8 GV7 A.profile_photo
            $profile_photo = null;
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                if ($_FILES['profile_photo']['size'] > 1 * 1024 * 1024) {
                    $_SESSION['error'] = "Profile photo must be less than 1MB.";
                    header("Location: resume_builder.php?tab=personal");
                    exit();
                }
                $exts = ['jpg', 'jpeg', 'png'];
                $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $exts)) {
                    $_SESSION['error'] = "Profile photo must be JPG/JPEG or PNG.";
                    header("Location: resume_builder.php?tab=personal");
                    exit();
                }
                $upload_dir = '../assets/uploads/profiles/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $filename = $user_id . '_' . time() . '.' . $ext;
                $full_path = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $full_path)) {
                    $profile_photo = 'assets/uploads/profiles/' . $filename;
                } else {
                    $_SESSION['error'] = "Failed to upload profile photo.";
                    header("Location: resume_builder.php?tab=personal");
                    exit();
                }
            }

            $stmt = $conn->prepare("
                INSERT INTO resume_personal (user_id, profile_photo, gender, dob, phone, alternate_email, address, linkedin_url, github_url, portfolio_url, summary)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                profile_photo = VALUES(profile_photo),
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
                $profile_photo,
                $gender,
                $dob,
                $phone,
                $alternate_email,
                $address,
                $linkedin_url,
                $github_url,
                $portfolio_url,
                $summary
            ]);
            $_SESSION['success'] = "Personal information saved successfully!";
            break;


        // Education
        case 'add_education':
            // EDU validations B
            $count_stmt = $conn->prepare("SELECT COUNT(*) FROM resume_education WHERE user_id = ?");
            $count_stmt->execute([$user_id]);
            if ($count_stmt->fetchColumn() >= 5) {
                $_SESSION['error'] = "Maximum 5 education entries allowed (EDU4).";
                header("Location: resume_builder.php?tab=education");
                exit();
            }

            $degree = trim($_POST['degree'] ?? '');
            $institution = trim($_POST['institution'] ?? '');
            if (empty($degree) || empty($institution)) {
                $_SESSION['error'] = "Degree and Institution cannot be empty (EDU5).";
                header("Location: resume_builder.php?tab=education");
                exit();
            }

            $start_year = (int)$_POST['start_year'];
            $end_year = (int)$_POST['end_year'];
            if ($start_year > $end_year) {
                $_SESSION['error'] = "Start year must be <= end year (EDU1).";
                header("Location: resume_builder.php?tab=education");
                exit();
            }

            $percentage_cgpa = (float)$_POST['percentage_cgpa'];
            if ($percentage_cgpa < 0 || $percentage_cgpa > 100) {
                $_SESSION['error'] = "Percentage/CGPA must be between 0 and 100 (EDU2).";
                header("Location: resume_builder.php?tab=education");
                exit();
            }

            $backlogs = (int)($_POST['backlogs'] ?? 0);
            if ($backlogs < 0) {
                $_SESSION['error'] = "Backlogs must be >= 0 (EDU3).";
                header("Location: resume_builder.php?tab=education");
                exit();
            }

            $stmt = $conn->prepare("
                INSERT INTO resume_education (user_id, degree, specialization, institution, university_board, start_year, end_year, percentage_cgpa, backlogs, current_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $degree,
                $_POST['specialization'] ?? '',
                $institution,
                trim($_POST['university_board'] ?? ''),
                $start_year,
                $end_year,
                $percentage_cgpa,
                $backlogs,
                $_POST['current_status'] ?? 'Completed'
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
    
    // Personal Info 20%
    $stmt = $conn->prepare("SELECT COUNT(*) FROM resume_personal WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() > 0) $score += 20;
    
    // Education 20% >=1
    $stmt = $conn->prepare("SELECT COUNT(*) FROM resume_education WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() >= 1) $score += 20;
    
    // Skills 20% >=3
    $stmt = $conn->prepare("SELECT COUNT(*) FROM resume_skills WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() >= 3) $score += 20;
    
    // Projects 15% >=1
    $stmt = $conn->prepare("SELECT COUNT(*) FROM resume_projects WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() >= 1) $score += 15;
    
    // Experience 15% >=1
    $stmt = $conn->prepare("SELECT COUNT(*) FROM resume_experience WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() >= 1) $score += 15;
    
    // Certifications/Achievements 10% >=1 combined
    $stmt = $conn->prepare("SELECT (SELECT COUNT(*) FROM resume_certifications WHERE user_id = ?) + (SELECT COUNT(*) FROM resume_achievements WHERE user_id = ?) as total");
    $stmt->execute([$user_id, $user_id]);
    if ($stmt->fetchColumn() >= 1) $score += 10;
    
    // Update score
    $stmt = $conn->prepare("
        INSERT INTO placement_eligibility (user_id, resume_score)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE resume_score = VALUES(resume_score)
    ");
    $stmt->execute([$user_id, $score]);
}

?>

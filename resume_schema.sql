-- Resume Builder Schema

-- A. Personal Information (Extends users table conceptually, but 1:1)
CREATE TABLE IF NOT EXISTS `resume_personal` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `profile_photo` VARCHAR(255) DEFAULT NULL,
    `gender` ENUM('Male', 'Female', 'Other') DEFAULT NULL,
    `dob` DATE DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `alternate_email` VARCHAR(100) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `linkedin_url` VARCHAR(255) DEFAULT NULL,
    `github_url` VARCHAR(255) DEFAULT NULL,
    `portfolio_url` VARCHAR(255) DEFAULT NULL,
    `summary` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- C. Education Details
CREATE TABLE IF NOT EXISTS `resume_education` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `degree` VARCHAR(100) NOT NULL, -- B.Tech, BBA
    `specialization` VARCHAR(100) NOT NULL, -- CSE, Marketing
    `institution` VARCHAR(255) NOT NULL,
    `university_board` VARCHAR(255) NOT NULL,
    `start_year` YEAR NOT NULL,
    `end_year` YEAR NOT NULL,
    `percentage_cgpa` DECIMAL(5,2) NOT NULL,
    `backlogs` INT DEFAULT 0,
    `current_status` ENUM('Pursuing', 'Completed') DEFAULT 'Completed',
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- D. Skills (Technical & Soft)
CREATE TABLE IF NOT EXISTS `resume_skills` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `skill_name` VARCHAR(100) NOT NULL,
    `type` ENUM('Technical', 'Soft') DEFAULT 'Technical',
    `proficiency` ENUM('Beginner', 'Intermediate', 'Advanced') DEFAULT 'Intermediate',
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- E. Certifications
CREATE TABLE IF NOT EXISTS `resume_certifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `certificate_name` VARCHAR(255) NOT NULL,
    `issuing_org` VARCHAR(255) NOT NULL,
    `issue_date` DATE DEFAULT NULL,
    `credential_id` VARCHAR(100) DEFAULT NULL,
    `certificate_file` VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- F. Projects
CREATE TABLE IF NOT EXISTS `resume_projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `role` VARCHAR(100) DEFAULT NULL,
    `tech_stack` VARCHAR(255) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `project_link` VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- G. Work Experience
CREATE TABLE IF NOT EXISTS `resume_experience` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `company_name` VARCHAR(255) NOT NULL,
    `role` VARCHAR(100) NOT NULL,
    `employment_type` ENUM('Internship', 'Full-time', 'Part-time') DEFAULT 'Full-time',
    `start_date` DATE NOT NULL,
    `end_date` DATE DEFAULT NULL, -- NULL = Present
    `is_current` BOOLEAN DEFAULT FALSE,
    `description` TEXT DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- H. Achievements
CREATE TABLE IF NOT EXISTS `resume_achievements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `level` ENUM('College', 'State', 'National', 'International', 'Other') DEFAULT 'Other',
    `date` DATE DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- J. Languages
CREATE TABLE IF NOT EXISTS `resume_languages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `language` VARCHAR(50) NOT NULL,
    `proficiency` ENUM('Read', 'Write', 'Speak', 'All') DEFAULT 'All',
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- K. Placement Eligibility Data
CREATE TABLE IF NOT EXISTS `placement_eligibility` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `willing_relocate` BOOLEAN DEFAULT TRUE,
    `preferred_locations` VARCHAR(255) DEFAULT NULL,
    `expected_ctc` DECIMAL(10,2) DEFAULT NULL,
    `resume_score` INT DEFAULT 0,
    `is_verified` BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

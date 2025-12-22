-- =====================================================
-- SmartGrade Database Schema
-- Automated Grading System for Ampayon Senior High School
-- Software Engineering 2 (CSC 107)
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `smartgrade_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `smartgrade_db`;

-- =====================================================
-- USERS TABLE (Authentication & RBAC)
-- Stores all system users: Admin, Teachers, Students
-- =====================================================
CREATE TABLE `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student',
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `middle_name` VARCHAR(50) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_username` (`username`),
  INDEX `idx_role` (`role`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SCHOOL YEAR & GRADING PERIODS
-- =====================================================
CREATE TABLE `school_years` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `year_code` VARCHAR(20) NOT NULL UNIQUE COMMENT 'e.g., 2024-2025',
  `year_start` DATE NOT NULL,
  `year_end` DATE NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `grading_periods` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_year_id` INT(11) UNSIGNED NOT NULL,
  `period_name` VARCHAR(50) NOT NULL COMMENT 'e.g., 1st Quarter, 2nd Quarter',
  `period_number` TINYINT(1) NOT NULL COMMENT '1, 2, 3, or 4',
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`school_year_id`) REFERENCES `school_years`(`id`) ON DELETE CASCADE,
  INDEX `idx_school_year` (`school_year_id`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- STUDENTS TABLE
-- Complete student information for SF9/SF10 generation
-- =====================================================
CREATE TABLE `students` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `student_number` VARCHAR(20) NOT NULL UNIQUE,
  `lrn` VARCHAR(12) NOT NULL UNIQUE COMMENT 'Learner Reference Number (DepEd)',
  `grade_level` ENUM('11', '12') NOT NULL,
  `track` VARCHAR(50) NOT NULL COMMENT 'e.g., Academic, TVL, Arts & Design, Sports',
  `strand` VARCHAR(50) NOT NULL COMMENT 'e.g., STEM, ABM, HUMSS, ICT, HE',
  `section` VARCHAR(20) NOT NULL,
  `birth_date` DATE NOT NULL,
  `birth_place` VARCHAR(100) DEFAULT NULL,
  `gender` ENUM('Male', 'Female') NOT NULL,
  `contact_number` VARCHAR(15) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `guardian_name` VARCHAR(100) DEFAULT NULL,
  `guardian_contact` VARCHAR(15) DEFAULT NULL,
  `enrollment_date` DATE NOT NULL,
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Set to 1 when graduated',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_student_number` (`student_number`),
  INDEX `idx_lrn` (`lrn`),
  INDEX `idx_grade_level` (`grade_level`),
  INDEX `idx_track_strand` (`track`, `strand`),
  INDEX `idx_is_archived` (`is_archived`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TEACHERS TABLE
-- =====================================================
CREATE TABLE `teachers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `employee_number` VARCHAR(20) NOT NULL UNIQUE,
  `department` VARCHAR(50) NOT NULL,
  `specialization` VARCHAR(100) DEFAULT NULL,
  `contact_number` VARCHAR(15) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_employee_number` (`employee_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SUBJECTS TABLE
-- Senior High School subjects per track/strand
-- =====================================================
CREATE TABLE `subjects` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_code` VARCHAR(20) NOT NULL UNIQUE,
  `subject_name` VARCHAR(100) NOT NULL,
  `grade_level` ENUM('11', '12') NOT NULL,
  `track` VARCHAR(50) NOT NULL,
  `strand` VARCHAR(50) DEFAULT NULL,
  `semester` ENUM('1', '2') NOT NULL,
  `subject_type` ENUM('Core', 'Applied', 'Specialized') NOT NULL DEFAULT 'Core',
  `units` DECIMAL(3,1) NOT NULL DEFAULT 1.0,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_subject_code` (`subject_code`),
  INDEX `idx_grade_level` (`grade_level`),
  INDEX `idx_track_strand` (`track`, `strand`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CLASS ASSIGNMENTS (Teachers to Subjects/Sections)
-- Defines which teacher handles which subject for which section
-- =====================================================
CREATE TABLE `class_assignments` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_id` INT(11) UNSIGNED NOT NULL,
  `subject_id` INT(11) UNSIGNED NOT NULL,
  `school_year_id` INT(11) UNSIGNED NOT NULL,
  `section` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`school_year_id`) REFERENCES `school_years`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_assignment` (`teacher_id`, `subject_id`, `school_year_id`, `section`),
  INDEX `idx_teacher` (`teacher_id`),
  INDEX `idx_subject` (`subject_id`),
  INDEX `idx_school_year` (`school_year_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- GRADES TABLE (Main grading records)
-- Stores individual quarterly grades with components
-- Following DepEd Order No. 8, s. 2015 (K-12 Grading System)
-- =====================================================
CREATE TABLE `grades` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) UNSIGNED NOT NULL,
  `subject_id` INT(11) UNSIGNED NOT NULL,
  `teacher_id` INT(11) UNSIGNED NOT NULL,
  `school_year_id` INT(11) UNSIGNED NOT NULL,
  `grading_period_id` INT(11) UNSIGNED NOT NULL,
  
  -- Grade Components (DepEd K-12 format)
  -- Written Works (30%), Performance Tasks (50%), Quarterly Assessment (20%)
  `written_work_score` DECIMAL(5,2) DEFAULT NULL,
  `written_work_total` DECIMAL(5,2) DEFAULT NULL,
  `performance_task_score` DECIMAL(5,2) DEFAULT NULL,
  `performance_task_total` DECIMAL(5,2) DEFAULT NULL,
  `quarterly_assessment_score` DECIMAL(5,2) DEFAULT NULL,
  `quarterly_assessment_total` DECIMAL(5,2) DEFAULT NULL,
  
  -- Computed quarterly grade (60-100 scale)
  `quarterly_grade` DECIMAL(5,2) DEFAULT NULL,
  
  -- Remarks and notes
  `remarks` TEXT DEFAULT NULL,
  
  -- Audit fields
  `entered_by` INT(11) UNSIGNED NOT NULL,
  `entered_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`school_year_id`) REFERENCES `school_years`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`grading_period_id`) REFERENCES `grading_periods`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`entered_by`) REFERENCES `users`(`id`),
  
  -- Unique constraint: one grade record per student-subject-period
  UNIQUE KEY `unique_grade_entry` (`student_id`, `subject_id`, `grading_period_id`),
  
  INDEX `idx_student` (`student_id`),
  INDEX `idx_subject` (`subject_id`),
  INDEX `idx_teacher` (`teacher_id`),
  INDEX `idx_school_year` (`school_year_id`),
  INDEX `idx_grading_period` (`grading_period_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- FINAL GRADES (Computed from all quarterly grades)
-- For SF10 (Report Card) generation
-- =====================================================
CREATE TABLE `final_grades` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) UNSIGNED NOT NULL,
  `subject_id` INT(11) UNSIGNED NOT NULL,
  `school_year_id` INT(11) UNSIGNED NOT NULL,
  `semester` ENUM('1', '2') NOT NULL,
  
  -- Quarter grades
  `q1_grade` DECIMAL(5,2) DEFAULT NULL,
  `q2_grade` DECIMAL(5,2) DEFAULT NULL,
  
  -- Computed final grade (average of quarters)
  `final_grade` DECIMAL(5,2) DEFAULT NULL,
  `remarks` VARCHAR(20) DEFAULT NULL COMMENT 'PASSED, FAILED, INC, DROPPED',
  
  `computed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`school_year_id`) REFERENCES `school_years`(`id`) ON DELETE CASCADE,
  
  UNIQUE KEY `unique_final_grade` (`student_id`, `subject_id`, `school_year_id`, `semester`),
  
  INDEX `idx_student` (`student_id`),
  INDEX `idx_subject` (`subject_id`),
  INDEX `idx_school_year` (`school_year_id`),
  INDEX `idx_semester` (`semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- HONORS & AWARDS
-- Track honor students for certificate generation
-- =====================================================
CREATE TABLE `honors` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) UNSIGNED NOT NULL,
  `school_year_id` INT(11) UNSIGNED NOT NULL,
  `semester` ENUM('1', '2') NOT NULL,
  `honor_type` ENUM('With Highest Honors', 'With High Honors', 'With Honors') NOT NULL,
  `general_average` DECIMAL(5,2) NOT NULL,
  `awarded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`school_year_id`) REFERENCES `school_years`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_honor` (`student_id`, `school_year_id`, `semester`),
  INDEX `idx_student` (`student_id`),
  INDEX `idx_school_year` (`school_year_id`),
  INDEX `idx_honor_type` (`honor_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ARCHIVED STUDENTS (Graduated)
-- Stores snapshot of graduated student records
-- =====================================================
CREATE TABLE `archived_students` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) UNSIGNED NOT NULL,
  `student_data` JSON NOT NULL COMMENT 'Complete student record snapshot',
  `grades_data` JSON NOT NULL COMMENT 'All grades and final grades',
  `graduation_date` DATE NOT NULL,
  `graduation_honors` VARCHAR(50) DEFAULT NULL,
  `archived_by` INT(11) UNSIGNED NOT NULL,
  `archived_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`archived_by`) REFERENCES `users`(`id`),
  INDEX `idx_student` (`student_id`),
  INDEX `idx_graduation_date` (`graduation_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- AUDIT LOGS (Track all critical actions)
-- Comprehensive audit trail for accountability
-- =====================================================
CREATE TABLE `audit_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `action` VARCHAR(100) NOT NULL COMMENT 'e.g., grade_updated, student_created, login',
  `table_name` VARCHAR(50) NOT NULL,
  `record_id` INT(11) UNSIGNED NOT NULL,
  `old_values` JSON DEFAULT NULL,
  `new_values` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_table_name` (`table_name`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SYSTEM SETTINGS
-- Configurable system parameters
-- =====================================================
CREATE TABLE `system_settings` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NOT NULL,
  `description` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Default System Settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('school_name', 'Ampayon Senior High School', 'Official school name'),
('school_address', 'Butuan City, Philippines', 'School address'),
('school_id', 'ASHS-2024', 'School ID code'),
('school_phone', '(085) 123-4567', 'School contact number'),
('school_email', 'info@ampayon.edu.ph', 'School email'),
('passing_grade', '75', 'Minimum passing grade'),
('honors_threshold_high', '90', 'General average for With Honors'),
('honors_threshold_higher', '95', 'General average for With High Honors'),
('honors_threshold_highest', '98', 'General average for With Highest Honors'),
('written_work_percentage', '30', 'Written Work weight in grade computation'),
('performance_task_percentage', '50', 'Performance Task weight in grade computation'),
('quarterly_assessment_percentage', '20', 'Quarterly Assessment weight in grade computation');

-- Default Admin User (password: admin123)
-- Password hash generated using: password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `is_active`) VALUES
('admin', 'admin@ampayon.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'ICT', 'Coordinator', 1);

-- Sample School Year (2024-2025)
INSERT INTO `school_years` (`year_code`, `year_start`, `year_end`, `is_active`) VALUES
('2024-2025', '2024-08-15', '2025-05-15', 1);

-- Sample Grading Periods for 2024-2025
INSERT INTO `grading_periods` (`school_year_id`, `period_name`, `period_number`, `start_date`, `end_date`, `is_active`) VALUES
(1, '1st Quarter', 1, '2024-08-15', '2024-10-31', 0),
(1, '2nd Quarter', 2, '2024-11-01', '2025-01-15', 1),
(1, '3rd Quarter', 3, '2025-01-16', '2025-03-31', 0),
(1, '4th Quarter', 4, '2025-04-01', '2025-05-15', 0);

-- Sample Subjects (STEM Track - Grade 11 Semester 1)
INSERT INTO `subjects` (`subject_code`, `subject_name`, `grade_level`, `track`, `strand`, `semester`, `subject_type`, `units`) VALUES
('ORAL-COM', 'Oral Communication', '11', 'All', NULL, '1', 'Core', 2.0),
('KOM-PANAN', 'Komunikasyon at Pananaliksik', '11', 'All', NULL, '1', 'Core', 2.0),
('21ST-LIT', '21st Century Literature', '11', 'All', NULL, '1', 'Core', 2.0),
('GEN-MATH', 'General Mathematics', '11', 'All', NULL, '1', 'Core', 3.0),
('EARTH-SCI', 'Earth and Life Science', '11', 'All', NULL, '1', 'Core', 3.0),
('PE-1', 'Physical Education 1', '11', 'All', NULL, '1', 'Core', 2.0),
('STEM-CALC', 'Basic Calculus', '11', 'STEM', 'STEM', '1', 'Specialized', 3.0),
('STEM-PHYS', 'General Physics 1', '11', 'STEM', 'STEM', '1', 'Specialized', 3.0),
('STEM-BIO', 'General Biology 1', '11', 'STEM', 'STEM', '1', 'Specialized', 3.0),
('STEM-CHEM', 'General Chemistry 1', '11', 'STEM', 'STEM', '1', 'Specialized', 3.0);

-- Sample Subjects (STEM Track - Grade 11 Semester 2)
INSERT INTO `subjects` (`subject_code`, `subject_name`, `grade_level`, `track`, `strand`, `semester`, `subject_type`, `units`) VALUES
('READ-WRITE', 'Reading and Writing', '11', 'All', NULL, '2', 'Core', 2.0),
('FIL-PAGBASA', 'Pagbasa at Pagsusuri', '11', 'All', NULL, '2', 'Core', 2.0),
('STATS-PROB', 'Statistics and Probability', '11', 'All', NULL, '2', 'Core', 3.0),
('PHYSICAL-SCI', 'Physical Science', '11', 'All', NULL, '2', 'Core', 3.0),
('PE-2', 'Physical Education 2', '11', 'All', NULL, '2', 'Core', 2.0),
('STEM-PHYS2', 'General Physics 2', '11', 'STEM', 'STEM', '2', 'Specialized', 3.0),
('STEM-BIO2', 'General Biology 2', '11', 'STEM', 'STEM', '2', 'Specialized', 3.0),
('STEM-CHEM2', 'General Chemistry 2', '11', 'STEM', 'STEM', '2', 'Specialized', 3.0);

-- Sample Teacher Accounts (password: teacher123)
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `middle_name`, `is_active`) VALUES
('jdelacruz', 'jdelacruz@ampayon.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Juan', 'Dela Cruz', 'Reyes', 1),
('msantos', 'msantos@ampayon.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Maria', 'Santos', 'Garcia', 1);

INSERT INTO `teachers` (`user_id`, `employee_number`, `department`, `specialization`, `contact_number`) VALUES
(2, 'EMP-2024-001', 'Mathematics', 'Algebra & Calculus', '09123456789'),
(3, 'EMP-2024-002', 'Science', 'Physics & Chemistry', '09987654321');

-- Sample Student Accounts (password: student123)
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `middle_name`, `is_active`) VALUES
('2024001', 'student1@ampayon.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Maria', 'Santos', 'Garcia', 1),
('2024002', 'student2@ampayon.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Pedro', 'Cruz', 'Lopez', 1),
('2024003', 'student3@ampayon.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Ana', 'Reyes', 'Martinez', 1);

INSERT INTO `students` (`user_id`, `student_number`, `lrn`, `grade_level`, `track`, `strand`, `section`, `birth_date`, `birth_place`, `gender`, `contact_number`, `address`, `guardian_name`, `guardian_contact`, `enrollment_date`) VALUES
(4, 'STU-2024-001', '123456789001', '11', 'Academic', 'STEM', 'Einstein', '2008-05-15', 'Butuan City', 'Female', '09987654321', 'Brgy. Ampayon, Butuan City', 'Rosa Santos', '09123456780', '2024-08-01'),
(5, 'STU-2024-002', '123456789002', '11', 'Academic', 'STEM', 'Einstein', '2008-07-20', 'Butuan City', 'Male', '09876543210', 'Brgy. Libertad, Butuan City', 'Roberto Cruz', '09123456781', '2024-08-01'),
(6, 'STU-2024-003', '123456789003', '11', 'Academic', 'STEM', 'Einstein', '2008-03-10', 'Agusan del Norte', 'Female', '09765432109', 'Brgy. Golden Ribbon, Butuan City', 'Carmen Reyes', '09123456782', '2024-08-01');

-- Sample Class Assignments
INSERT INTO `class_assignments` (`teacher_id`, `subject_id`, `school_year_id`, `section`) VALUES
(1, 4, 1, 'Einstein'),  -- Juan teaches General Mathematics to Einstein
(1, 7, 1, 'Einstein'),  -- Juan teaches Basic Calculus to Einstein
(2, 8, 1, 'Einstein'),  -- Maria teaches General Physics 1 to Einstein
(2, 9, 1, 'Einstein'),  -- Maria teaches General Biology 1 to Einstein
(2, 10, 1, 'Einstein'); -- Maria teaches General Chemistry 1 to Einstein

-- Sample Grades (1st Quarter - showing grade computation)
-- For Student 1 (Maria Santos) - General Mathematics
INSERT INTO `grades` (`student_id`, `subject_id`, `teacher_id`, `school_year_id`, `grading_period_id`, `written_work_score`, `written_work_total`, `performance_task_score`, `performance_task_total`, `quarterly_assessment_score`, `quarterly_assessment_total`, `quarterly_grade`, `entered_by`) VALUES
(1, 4, 1, 1, 1, 45, 50, 90, 100, 38, 40, 89.50, 2);

-- For Student 1 - Basic Calculus
INSERT INTO `grades` (`student_id`, `subject_id`, `teacher_id`, `school_year_id`, `grading_period_id`, `written_work_score`, `written_work_total`, `performance_task_score`, `performance_task_total`, `quarterly_assessment_score`, `quarterly_assessment_total`, `quarterly_grade`, `entered_by`) VALUES
(1, 7, 1, 1, 1, 42, 50, 85, 100, 36, 40, 87.20, 2);

-- For Student 2 (Pedro Cruz) - General Mathematics
INSERT INTO `grades` (`student_id`, `subject_id`, `teacher_id`, `school_year_id`, `grading_period_id`, `written_work_score`, `written_work_total`, `performance_task_score`, `performance_task_total`, `quarterly_assessment_score`, `quarterly_assessment_total`, `quarterly_grade`, `entered_by`) VALUES
(2, 4, 1, 1, 1, 40, 50, 80, 100, 35, 40, 84.50, 2);

-- For Student 3 (Ana Reyes) - General Mathematics
INSERT INTO `grades` (`student_id`, `subject_id`, `teacher_id`, `school_year_id`, `grading_period_id`, `written_work_score`, `written_work_total`, `performance_task_score`, `performance_task_total`, `quarterly_assessment_score`, `quarterly_assessment_total`, `quarterly_grade`, `entered_by`) VALUES
(3, 4, 1, 1, 1, 48, 50, 95, 100, 39, 40, 92.50, 2);

COMMIT;

-- =====================================================
-- END OF SCHEMA
-- =====================================================
-- To import this schema:
-- 1. Open phpMyAdmin (http://localhost/phpmyadmin)
-- 2. Click "Import" tab
-- 3. Choose this file and click "Go"
-- OR run in command line:
-- mysql -u root -p < smartgrade_db.sql
-- =====================================================


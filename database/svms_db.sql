-- ============================================
-- SVMS - Student Violation Management System
-- Lyceum of Subic Bay
-- Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS svms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE svms_db;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    role ENUM('admin', 'discipline_officer', 'teacher', 'student') NOT NULL,
    student_id INT DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- STUDENTS TABLE
-- ============================================
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) DEFAULT NULL,
    gender ENUM('Male', 'Female', 'Other') DEFAULT 'Male',
    date_of_birth DATE DEFAULT NULL,
    grade_level VARCHAR(20) NOT NULL,
    section VARCHAR(50) NOT NULL,
    contact VARCHAR(20) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    guardian_name VARCHAR(100) DEFAULT NULL,
    guardian_contact VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive', 'graduated', 'transferred') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Foreign key for users -> students
ALTER TABLE users ADD CONSTRAINT fk_users_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL;

-- ============================================
-- VIOLATION TYPES TABLE
-- ============================================
CREATE TABLE violation_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    severity ENUM('minor', 'major', 'critical') NOT NULL DEFAULT 'minor',
    description TEXT DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- VIOLATIONS TABLE
-- ============================================
CREATE TABLE violations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    violation_type_id INT NOT NULL,
    reported_by INT NOT NULL,
    description TEXT DEFAULT NULL,
    evidence_path VARCHAR(255) DEFAULT NULL,
    location VARCHAR(100) DEFAULT NULL,
    date_occurred DATETIME NOT NULL,
    status ENUM('pending', 'reviewed', 'resolved', 'dismissed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_violations_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_violations_type FOREIGN KEY (violation_type_id) REFERENCES violation_types(id) ON DELETE RESTRICT,
    CONSTRAINT fk_violations_reporter FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================
-- DISCIPLINARY ACTIONS TABLE
-- ============================================
CREATE TABLE disciplinary_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    violation_id INT NOT NULL,
    action_type ENUM('warning', 'detention', 'suspension', 'expulsion', 'community_service', 'counseling') NOT NULL,
    description TEXT DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    issued_by INT NOT NULL,
    status ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending',
    remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_actions_violation FOREIGN KEY (violation_id) REFERENCES violations(id) ON DELETE CASCADE,
    CONSTRAINT fk_actions_issuer FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================
-- QR CODES TABLE
-- ============================================
CREATE TABLE qr_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL UNIQUE,
    qr_data VARCHAR(100) NOT NULL UNIQUE,
    qr_image_path VARCHAR(255) DEFAULT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_qr_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- ACTIVITY LOG TABLE
-- ============================================
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- NOTIFICATIONS TABLE
-- ============================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- PUSH SUBSCRIPTIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint TEXT NOT NULL,
    p256dh VARCHAR(255) NOT NULL,
    auth VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, endpoint(255))
) ENGINE=InnoDB;

-- ============================================
-- UNIFORM PASSES TABLE
-- ============================================
CREATE TABLE uniform_passes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    pass_code VARCHAR(100) NOT NULL UNIQUE,
    reason TEXT NOT NULL,
    issued_by INT NOT NULL,
    valid_date DATE NOT NULL,
    status ENUM('active', 'expired', 'revoked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pass_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_pass_issuer FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================
-- INDEXES
-- ============================================
CREATE INDEX idx_notification_user ON notifications(user_id);
CREATE INDEX idx_students_number ON students(student_number);
CREATE INDEX idx_students_name ON students(last_name, first_name);
CREATE INDEX idx_students_grade ON students(grade_level, section);
CREATE INDEX idx_violations_student ON violations(student_id);
CREATE INDEX idx_violations_date ON violations(date_occurred);
CREATE INDEX idx_violations_status ON violations(status);
CREATE INDEX idx_actions_violation ON disciplinary_actions(violation_id);
CREATE INDEX idx_activity_user ON activity_log(user_id);
CREATE INDEX idx_activity_date ON activity_log(created_at);
CREATE INDEX idx_pass_code ON uniform_passes(pass_code);
CREATE INDEX idx_pass_student ON uniform_passes(student_id);
CREATE INDEX idx_pass_date ON uniform_passes(valid_date);

-- ============================================
-- SEED DATA
-- ============================================

-- Default Admin User (password: admin123)
INSERT INTO users (username, password, full_name, email, role, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@lsb.edu.ph', 'admin', 'active');

-- Default Discipline Officer (password: officer123)
INSERT INTO users (username, password, full_name, email, role, status) VALUES
('discipline', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria Santos', 'msantos@lsb.edu.ph', 'discipline_officer', 'active');

-- Default Teacher (password: teacher123)
INSERT INTO users (username, password, full_name, email, role, status) VALUES
('teacher1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Dela Cruz', 'jdelacruz@lsb.edu.ph', 'teacher', 'active');

-- Violation Types
INSERT INTO violation_types (name, severity, description) VALUES
('Tardiness', 'minor', 'Arriving late to class without valid reason'),
('Cutting Classes', 'major', 'Absence from class without permission'),
('Dress Code Violation', 'minor', 'Not wearing proper school uniform'),
('Bullying', 'critical', 'Physical or verbal harassment of another student'),
('Vandalism', 'major', 'Intentional destruction of school property'),
('Cheating', 'major', 'Dishonesty during examinations or assignments'),
('Smoking', 'major', 'Smoking within school premises'),
('Fighting', 'critical', 'Physical altercation with another person'),
('Disrespect to Authority', 'major', 'Disrespectful behavior toward faculty or staff'),
('Littering', 'minor', 'Improper disposal of waste within school grounds'),
('Using Mobile Phone in Class', 'minor', 'Using mobile devices during class hours without permission'),
('Theft', 'critical', 'Taking property belonging to others without consent'),
('Bringing Prohibited Items', 'major', 'Bringing items not allowed within school premises'),
('Academic Dishonesty', 'major', 'Plagiarism or falsification of academic records');

-- Sample Students
INSERT INTO students (student_number, first_name, last_name, middle_name, gender, grade_level, section, contact, guardian_name, guardian_contact) VALUES
('2024-0001', 'Juan', 'Dela Cruz', 'Santos', 'Male', 'Grade 10', 'Section A', '09171234567', 'Maria Dela Cruz', '09181234567'),
('2024-0002', 'Maria', 'Garcia', 'Lopez', 'Female', 'Grade 10', 'Section A', '09172345678', 'Jose Garcia', '09182345678'),
('2024-0003', 'Pedro', 'Reyes', 'Cruz', 'Male', 'Grade 11', 'Section B', '09173456789', 'Ana Reyes', '09183456789'),
('2024-0004', 'Ana', 'Santos', 'Rivera', 'Female', 'Grade 11', 'Section B', '09174567890', 'Carlos Santos', '09184567890'),
('2024-0005', 'Carlos', 'Rivera', 'Mendoza', 'Male', 'Grade 12', 'Section A', '09175678901', 'Rosa Rivera', '09185678901');

-- Student user accounts (password: student123)
INSERT INTO users (username, password, full_name, email, role, student_id, status) VALUES
('2024-0001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Dela Cruz', 'juan.delacruz@lsb.edu.ph', 'student', 1, 'active'),
('2024-0002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria Garcia', 'maria.garcia@lsb.edu.ph', 'student', 2, 'active');

-- QR Codes for sample students
INSERT INTO qr_codes (student_id, qr_data) VALUES
(1, 'LSB-STU-a1b2c3d4-e5f6-7890-abcd-ef1234567890'),
(2, 'LSB-STU-b2c3d4e5-f6a7-8901-bcde-f12345678901'),
(3, 'LSB-STU-c3d4e5f6-a7b8-9012-cdef-123456789012'),
(4, 'LSB-STU-d4e5f6a7-b8c9-0123-defa-234567890123'),
(5, 'LSB-STU-e5f6a7b8-c9d0-1234-efab-345678901234');

-- Sample Violations
INSERT INTO violations (student_id, violation_type_id, reported_by, description, location, date_occurred, status) VALUES
(1, 1, 3, 'Student arrived 15 minutes late to first period class', 'Room 201', '2026-03-10 07:45:00', 'reviewed'),
(1, 3, 3, 'Student not wearing proper ID and school uniform', 'Main Gate', '2026-03-11 07:30:00', 'pending'),
(2, 6, 3, 'Caught using notes during quiz', 'Room 305', '2026-03-09 10:00:00', 'resolved');

-- Sample Disciplinary Actions
INSERT INTO disciplinary_actions (violation_id, action_type, description, issued_by, status) VALUES
(1, 'warning', 'First offense verbal warning issued. Student acknowledged and promised to be punctual.', 2, 'completed'),
(3, 'detention', 'One day detention assigned for academic dishonesty. Parent notified.', 2, 'completed');

-- ============================================
-- SVMS - Student Violation Management System
-- Lyceum of Subic Bay
-- Database Schema with Realistic Seed Data
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
-- ACADEMIC PERIODS TABLE
-- ============================================
CREATE TABLE academic_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- VIOLATIONS TABLE
-- ============================================
CREATE TABLE violations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    academic_period_id INT NOT NULL,
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
    CONSTRAINT fk_violations_period FOREIGN KEY (academic_period_id) REFERENCES academic_periods(id) ON DELETE RESTRICT,
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
    academic_period_id INT NOT NULL,
    pass_code VARCHAR(100) NOT NULL UNIQUE,
    reason TEXT NOT NULL,
    issued_by INT NOT NULL,
    valid_date DATE NOT NULL,
    status ENUM('active', 'expired', 'revoked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pass_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_pass_period FOREIGN KEY (academic_period_id) REFERENCES academic_periods(id) ON DELETE RESTRICT,
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
CREATE INDEX idx_pass_period ON uniform_passes(academic_period_id);
CREATE INDEX idx_pass_date ON uniform_passes(valid_date);
CREATE INDEX idx_violations_period ON violations(academic_period_id);

-- ============================================
-- SEED DATA
-- ============================================

-- Admin Users (password: admin123)
INSERT INTO users (username, password, full_name, email, role, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Atty. Ricardo Villanueva', 'rvillanueva@lsb.edu.ph', 'admin', 'active'),
('admin2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Carmela Bautista', 'cbautista@lsb.edu.ph', 'admin', 'active');

-- Discipline Officers (password: officer123)
INSERT INTO users (username, password, full_name, email, role, status) VALUES
('discipline', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Prof. Angelica Mercado', 'amercado@lsb.edu.ph', 'discipline_officer', 'active'),
('discipline2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mr. Ferdinand Aquino', 'faquino@lsb.edu.ph', 'discipline_officer', 'active');

-- Teachers (password: teacher123)
INSERT INTO users (username, password, full_name, email, role, status) VALUES
('teacher', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ms. Beatriz Fernandez', 'bfernandez@lsb.edu.ph', 'teacher', 'active'),
('teacher2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mr. Lorenzo Castillo', 'lcastillo@lsb.edu.ph', 'teacher', 'active'),
('teacher3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mrs. Rosalinda Navarro', 'rnavarro@lsb.edu.ph', 'teacher', 'active'),
('teacher4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Prof. Emmanuel Soriano', 'esoriano@lsb.edu.ph', 'teacher', 'active'),
('teacher5', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ms. Veronica Pascual', 'vpascual@lsb.edu.ph', 'teacher', 'active');

-- Violation Types
INSERT INTO violation_types (name, severity, description) VALUES
('Tardiness', 'minor', 'Arriving late to class without valid reason'),
('Cutting Classes', 'major', 'Absence from class without permission'),
('Dress Code Violation', 'minor', 'Not wearing proper school uniform or ID'),
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
('Academic Dishonesty', 'major', 'Plagiarism or falsification of academic records'),
('Loitering', 'minor', 'Staying in unauthorized areas during class hours'),
('Gambling', 'major', 'Engaging in games of chance for money or valuables'),
('Public Display of Affection', 'minor', 'Inappropriate physical contact in public areas'),
('Unauthorized Absence', 'major', 'Leaving school premises without permission');

-- Academic Periods
INSERT INTO academic_periods (name, start_date, end_date, is_active) VALUES
('SY 2023-2024', '2023-06-01', '2024-05-31', 0),
('SY 2024-2025', '2024-06-01', '2025-05-31', 1),
('SY 2025-2026', '2025-06-01', '2026-05-31', 0);

-- Students - Grade 11 (Enrolled 2023)
INSERT INTO students (student_number, first_name, last_name, middle_name, gender, date_of_birth, grade_level, section, contact, email, guardian_name, guardian_contact, address) VALUES
('2023010001', 'Mikhail', 'Alvarez', 'Santos', 'Male', '2007-03-15', 'Grade 11', 'STEM-A', '09171234501', 'malvarez@lsb.edu.ph', 'Leonora Alvarez', '09181234501', 'Brgy. Cawag, Subic, Zambales'),
('2023010002', 'Cassandra', 'Bautista', 'Reyes', 'Female', '2007-07-22', 'Grade 11', 'STEM-A', '09171234502', 'cbautista@lsb.edu.ph', 'Roberto Bautista', '09181234502', 'Brgy. Ilwas, Subic, Zambales'),
('2023010003', 'Tristan', 'Cordero', 'Villanueva', 'Male', '2007-01-10', 'Grade 11', 'STEM-A', '09171234503', 'tcordero@lsb.edu.ph', 'Maricel Cordero', '09181234503', 'Brgy. Matain, Subic, Zambales'),
('2023010004', 'Bianca', 'Domingo', 'Cruz', 'Female', '2007-09-05', 'Grade 11', 'STEM-B', '09171234504', 'bdomingo@lsb.edu.ph', 'Eduardo Domingo', '09181234504', 'Brgy. Aningway, Subic, Zambales'),
('2023010005', 'Rafael', 'Estrada', 'Mendoza', 'Male', '2007-11-18', 'Grade 11', 'STEM-B', '09171234505', 'restrada@lsb.edu.ph', 'Angelina Estrada', '09181234505', 'Brgy. Baraca-Camachile, Subic, Zambales'),
('2023010006', 'Francesca', 'Galang', 'Torres', 'Female', '2007-04-30', 'Grade 11', 'STEM-B', '09171234506', 'fgalang@lsb.edu.ph', 'Vicente Galang', '09181234506', 'Brgy. Calapandayan, Subic, Zambales'),
('2023010007', 'Lorenzo', 'Hernandez', 'Ramos', 'Male', '2007-06-12', 'Grade 11', 'ABM-A', '09171234507', 'lhernandez@lsb.edu.ph', 'Rosario Hernandez', '09181234507', 'Brgy. Mangan-Vaca, Subic, Zambales'),
('2023010008', 'Isabela', 'Jimenez', 'Garcia', 'Female', '2007-08-25', 'Grade 11', 'ABM-A', '09171234508', 'ijimenez@lsb.edu.ph', 'Alfonso Jimenez', '09181234508', 'Brgy. Naugsol, Subic, Zambales'),
('2023010009', 'Sebastian', 'Lacson', 'Fernandez', 'Male', '2007-02-14', 'Grade 11', 'ABM-A', '09171234509', 'slacson@lsb.edu.ph', 'Carmela Lacson', '09181234509', 'Brgy. Pamatawan, Subic, Zambales'),
('2023010010', 'Gabriela', 'Mercado', 'Aquino', 'Female', '2007-12-03', 'Grade 11', 'HUMSS-A', '09171234510', 'gmercado@lsb.edu.ph', 'Rodrigo Mercado', '09181234510', 'Brgy. San Isidro, Subic, Zambales'),
('2023010011', 'Joaquin', 'Navarro', 'Castillo', 'Male', '2007-05-20', 'Grade 11', 'HUMSS-A', '09171234511', 'jnavarro@lsb.edu.ph', 'Teresita Navarro', '09181234511', 'Brgy. Wawandue, Subic, Zambales'),
('2023010012', 'Valentina', 'Ocampo', 'Soriano', 'Female', '2007-10-08', 'Grade 11', 'HUMSS-A', '09171234512', 'vocampo@lsb.edu.ph', 'Benjamin Ocampo', '09181234512', 'Brgy. Asinan Poblacion, Subic, Zambales'),
('2023010013', 'Mateo', 'Pascual', 'Rivera', 'Male', '2007-03-27', 'Grade 11', 'TVL-ICT', '09171234513', 'mpascual@lsb.edu.ph', 'Luzviminda Pascual', '09181234513', 'Brgy. Calapacuan, Subic, Zambales'),
('2023010014', 'Catalina', 'Quizon', 'Morales', 'Female', '2007-07-16', 'Grade 11', 'TVL-ICT', '09171234514', 'cquizon@lsb.edu.ph', 'Gregorio Quizon', '09181234514', 'Brgy. Mangan-Vaca, Subic, Zambales'),
('2023010015', 'Alejandro', 'Romualdez', 'Santos', 'Male', '2007-01-29', 'Grade 11', 'TVL-ICT', '09171234515', 'aromualdez@lsb.edu.ph', 'Patricia Romualdez', '09181234515', 'Brgy. Baraca-Camachile, Subic, Zambales');

-- Students - Grade 12 (Enrolled 2022)
INSERT INTO students (student_number, first_name, last_name, middle_name, gender, date_of_birth, grade_level, section, contact, email, guardian_name, guardian_contact, address) VALUES
('2022010001', 'Nathaniel', 'Salazar', 'Cruz', 'Male', '2006-04-11', 'Grade 12', 'STEM-A', '09171234516', 'nsalazar@lsb.edu.ph', 'Gloria Salazar', '09181234516', 'Brgy. Cawag, Subic, Zambales'),
('2022010002', 'Angelique', 'Tan', 'Lopez', 'Female', '2006-08-19', 'Grade 12', 'STEM-A', '09171234517', 'atan@lsb.edu.ph', 'William Tan', '09181234517', 'Brgy. Ilwas, Subic, Zambales'),
('2022010003', 'Dominic', 'Uy', 'Reyes', 'Male', '2006-02-07', 'Grade 12', 'STEM-A', '09171234518', 'duy@lsb.edu.ph', 'Cecilia Uy', '09181234518', 'Brgy. Matain, Subic, Zambales'),
('2022010004', 'Clarissa', 'Velasco', 'Mendoza', 'Female', '2006-11-23', 'Grade 12', 'STEM-B', '09171234519', 'cvelasco@lsb.edu.ph', 'Ramon Velasco', '09181234519', 'Brgy. Aningway, Subic, Zambales'),
('2022010005', 'Benedict', 'Wong', 'Torres', 'Male', '2006-05-14', 'Grade 12', 'STEM-B', '09171234520', 'bwong@lsb.edu.ph', 'Rosemarie Wong', '09181234520', 'Brgy. Baraca-Camachile, Subic, Zambales'),
('2022010006', 'Daniela', 'Yap', 'Garcia', 'Female', '2006-09-30', 'Grade 12', 'STEM-B', '09171234521', 'dyap@lsb.edu.ph', 'Antonio Yap', '09181234521', 'Brgy. Calapandayan, Subic, Zambales'),
('2022010007', 'Ezekiel', 'Zamora', 'Ramos', 'Male', '2006-03-18', 'Grade 12', 'ABM-A', '09171234522', 'ezamora@lsb.edu.ph', 'Milagros Zamora', '09181234522', 'Brgy. Mangan-Vaca, Subic, Zambales'),
('2022010008', 'Felicity', 'Aguilar', 'Fernandez', 'Female', '2006-07-06', 'Grade 12', 'ABM-A', '09171234523', 'faguilar@lsb.edu.ph', 'Ernesto Aguilar', '09181234523', 'Brgy. Naugsol, Subic, Zambales'),
('2022010009', 'Gabriel', 'Bonifacio', 'Aquino', 'Male', '2006-12-21', 'Grade 12', 'ABM-A', '09171234524', 'gbonifacio@lsb.edu.ph', 'Estrella Bonifacio', '09181234524', 'Brgy. Pamatawan, Subic, Zambales'),
('2022010010', 'Helena', 'Cortez', 'Castillo', 'Female', '2006-06-09', 'Grade 12', 'HUMSS-A', '09171234525', 'hcortez@lsb.edu.ph', 'Francisco Cortez', '09181234525', 'Brgy. San Isidro, Subic, Zambales'),
('2022010011', 'Isaiah', 'Dizon', 'Soriano', 'Male', '2006-10-26', 'Grade 12', 'HUMSS-A', '09171234526', 'idizon@lsb.edu.ph', 'Lourdes Dizon', '09181234526', 'Brgy. Wawandue, Subic, Zambales'),
('2022010012', 'Jasmine', 'Enriquez', 'Rivera', 'Female', '2006-04-13', 'Grade 12', 'HUMSS-A', '09171234527', 'jenriquez@lsb.edu.ph', 'Manuel Enriquez', '09181234527', 'Brgy. Asinan Poblacion, Subic, Zambales'),
('2022010013', 'Kristoffer', 'Flores', 'Morales', 'Male', '2006-08-01', 'Grade 12', 'TVL-ICT', '09171234528', 'kflores@lsb.edu.ph', 'Norma Flores', '09181234528', 'Brgy. Calapacuan, Subic, Zambales'),
('2022010014', 'Larissa', 'Gonzales', 'Santos', 'Female', '2006-01-17', 'Grade 12', 'TVL-ICT', '09171234529', 'lgonzales@lsb.edu.ph', 'Oscar Gonzales', '09181234529', 'Brgy. Mangan-Vaca, Subic, Zambales'),
('2022010015', 'Marcus', 'Hidalgo', 'Cruz', 'Male', '2006-05-24', 'Grade 12', 'TVL-ICT', '09171234530', 'mhidalgo@lsb.edu.ph', 'Priscilla Hidalgo', '09181234530', 'Brgy. Baraca-Camachile, Subic, Zambales');

-- Students - Grade 10 (Enrolled 2024)
INSERT INTO students (student_number, first_name, last_name, middle_name, gender, date_of_birth, grade_level, section, contact, email, guardian_name, guardian_contact, address) VALUES
('2024010001', 'Adriano', 'Ignacio', 'Lopez', 'Male', '2008-03-12', 'Grade 10', 'Diamond', '09171234531', 'aignacio@lsb.edu.ph', 'Remedios Ignacio', '09181234531', 'Brgy. Cawag, Subic, Zambales'),
('2024010002', 'Beatrice', 'Javier', 'Reyes', 'Female', '2008-07-28', 'Grade 10', 'Diamond', '09171234532', 'bjavier@lsb.edu.ph', 'Salvador Javier', '09181234532', 'Brgy. Ilwas, Subic, Zambales'),
('2024010003', 'Cedric', 'Kalaw', 'Mendoza', 'Male', '2008-01-15', 'Grade 10', 'Diamond', '09171234533', 'ckalaw@lsb.edu.ph', 'Soledad Kalaw', '09181234533', 'Brgy. Matain, Subic, Zambales'),
('2024010004', 'Daphne', 'Laurel', 'Torres', 'Female', '2008-09-03', 'Grade 10', 'Emerald', '09171234534', 'dlaurel@lsb.edu.ph', 'Teodoro Laurel', '09181234534', 'Brgy. Aningway, Subic, Zambales'),
('2024010005', 'Elijah', 'Magno', 'Garcia', 'Male', '2008-11-20', 'Grade 10', 'Emerald', '09171234535', 'emagno@lsb.edu.ph', 'Trinidad Magno', '09181234535', 'Brgy. Baraca-Camachile, Subic, Zambales'),
('2024010006', 'Fiona', 'Natividad', 'Ramos', 'Female', '2008-04-07', 'Grade 10', 'Emerald', '09171234536', 'fnatividad@lsb.edu.ph', 'Urbano Natividad', '09181234536', 'Brgy. Calapandayan, Subic, Zambales'),
('2024010007', 'Gideon', 'Olivares', 'Fernandez', 'Male', '2008-06-14', 'Grade 10', 'Ruby', '09171234537', 'golivares@lsb.edu.ph', 'Violeta Olivares', '09181234537', 'Brgy. Mangan-Vaca, Subic, Zambales'),
('2024010008', 'Hannah', 'Padilla', 'Aquino', 'Female', '2008-08-29', 'Grade 10', 'Ruby', '09171234538', 'hpadilla@lsb.edu.ph', 'Wilfredo Padilla', '09181234538', 'Brgy. Naugsol, Subic, Zambales'),
('2024010009', 'Ivan', 'Quezon', 'Castillo', 'Male', '2008-02-16', 'Grade 10', 'Ruby', '09171234539', 'iquezon@lsb.edu.ph', 'Yolanda Quezon', '09181234539', 'Brgy. Pamatawan, Subic, Zambales'),
('2024010010', 'Juliana', 'Recto', 'Soriano', 'Female', '2008-12-05', 'Grade 10', 'Sapphire', '09171234540', 'jrecto@lsb.edu.ph', 'Zenaida Recto', '09181234540', 'Brgy. San Isidro, Subic, Zambales'),
('2024010011', 'Kenzo', 'Santiago', 'Rivera', 'Male', '2008-05-22', 'Grade 10', 'Sapphire', '09171234541', 'ksantiago@lsb.edu.ph', 'Adelaida Santiago', '09181234541', 'Brgy. Wawandue, Subic, Zambales'),
('2024010012', 'Leandra', 'Tolentino', 'Morales', 'Female', '2008-10-10', 'Grade 10', 'Sapphire', '09171234542', 'ltolentino@lsb.edu.ph', 'Bernardo Tolentino', '09181234542', 'Brgy. Asinan Poblacion, Subic, Zambales'),
('2024010013', 'Miguel', 'Umali', 'Santos', 'Male', '2008-03-29', 'Grade 10', 'Topaz', '09171234543', 'mumali@lsb.edu.ph', 'Corazon Umali', '09181234543', 'Brgy. Calapacuan, Subic, Zambales'),
('2024010014', 'Natasha', 'Valdez', 'Cruz', 'Female', '2008-07-18', 'Grade 10', 'Topaz', '09171234544', 'nvaldez@lsb.edu.ph', 'Danilo Valdez', '09181234544', 'Brgy. Mangan-Vaca, Subic, Zambales'),
('2024010015', 'Oliver', 'Wenceslao', 'Lopez', 'Male', '2008-01-31', 'Grade 10', 'Topaz', '09171234545', 'owenceslao@lsb.edu.ph', 'Erlinda Wenceslao', '09181234545', 'Brgy. Baraca-Camachile, Subic, Zambales');

-- Student User Accounts (password: student123)
INSERT INTO users (username, password, full_name, email, role, student_id, status) VALUES
('2023010001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mikhail Alvarez', 'malvarez@lsb.edu.ph', 'student', 1, 'active'),
('2023010002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Cassandra Bautista', 'cbautista@lsb.edu.ph', 'student', 2, 'active'),
('2023010003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tristan Cordero', 'tcordero@lsb.edu.ph', 'student', 3, 'active'),
('2023010004', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bianca Domingo', 'bdomingo@lsb.edu.ph', 'student', 4, 'active'),
('2023010005', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rafael Estrada', 'restrada@lsb.edu.ph', 'student', 5, 'active'),
('2022010001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nathaniel Salazar', 'nsalazar@lsb.edu.ph', 'student', 16, 'active'),
('2022010002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Angelique Tan', 'atan@lsb.edu.ph', 'student', 17, 'active'),
('2022010003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dominic Uy', 'duy@lsb.edu.ph', 'student', 18, 'active'),
('2024010001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Adriano Ignacio', 'aignacio@lsb.edu.ph', 'student', 31, 'active'),
('2024010002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Beatrice Javier', 'bjavier@lsb.edu.ph', 'student', 32, 'active'),
('2024010003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Cedric Kalaw', 'ckalaw@lsb.edu.ph', 'student', 33, 'active');

-- QR Codes for Students
INSERT INTO qr_codes (student_id, qr_data) VALUES
(1, 'LSB-STU-2023010001-a1b2c3d4e5f6'),
(2, 'LSB-STU-2023010002-b2c3d4e5f6a7'),
(3, 'LSB-STU-2023010003-c3d4e5f6a7b8'),
(4, 'LSB-STU-2023010004-d4e5f6a7b8c9'),
(5, 'LSB-STU-2023010005-e5f6a7b8c9d0'),
(6, 'LSB-STU-2023010006-f6a7b8c9d0e1'),
(7, 'LSB-STU-2023010007-a7b8c9d0e1f2'),
(8, 'LSB-STU-2023010008-b8c9d0e1f2a3'),
(9, 'LSB-STU-2023010009-c9d0e1f2a3b4'),
(10, 'LSB-STU-2023010010-d0e1f2a3b4c5'),
(11, 'LSB-STU-2023010011-e1f2a3b4c5d6'),
(12, 'LSB-STU-2023010012-f2a3b4c5d6e7'),
(13, 'LSB-STU-2023010013-a3b4c5d6e7f8'),
(14, 'LSB-STU-2023010014-b4c5d6e7f8a9'),
(15, 'LSB-STU-2023010015-c5d6e7f8a9b0'),
(16, 'LSB-STU-2022010001-d6e7f8a9b0c1'),
(17, 'LSB-STU-2022010002-e7f8a9b0c1d2'),
(18, 'LSB-STU-2022010003-f8a9b0c1d2e3'),
(19, 'LSB-STU-2022010004-a9b0c1d2e3f4'),
(20, 'LSB-STU-2022010005-b0c1d2e3f4a5'),
(21, 'LSB-STU-2022010006-c1d2e3f4a5b6'),
(22, 'LSB-STU-2022010007-d2e3f4a5b6c7'),
(23, 'LSB-STU-2022010008-e3f4a5b6c7d8'),
(24, 'LSB-STU-2022010009-f4a5b6c7d8e9'),
(25, 'LSB-STU-2022010010-a5b6c7d8e9f0'),
(26, 'LSB-STU-2022010011-b6c7d8e9f0a1'),
(27, 'LSB-STU-2022010012-c7d8e9f0a1b2'),
(28, 'LSB-STU-2022010013-d8e9f0a1b2c3'),
(29, 'LSB-STU-2022010014-e9f0a1b2c3d4'),
(30, 'LSB-STU-2022010015-f0a1b2c3d4e5'),
(31, 'LSB-STU-2024010001-a1b2c3d4e5f7'),
(32, 'LSB-STU-2024010002-b2c3d4e5f7a8'),
(33, 'LSB-STU-2024010003-c3d4e5f7a8b9'),
(34, 'LSB-STU-2024010004-d4e5f7a8b9c0'),
(35, 'LSB-STU-2024010005-e5f7a8b9c0d1'),
(36, 'LSB-STU-2024010006-f7a8b9c0d1e2'),
(37, 'LSB-STU-2024010007-a8b9c0d1e2f3'),
(38, 'LSB-STU-2024010008-b9c0d1e2f3a4'),
(39, 'LSB-STU-2024010009-c0d1e2f3a4b5'),
(40, 'LSB-STU-2024010010-d1e2f3a4b5c6'),
(41, 'LSB-STU-2024010011-e2f3a4b5c6d7'),
(42, 'LSB-STU-2024010012-f3a4b5c6d7e8'),
(43, 'LSB-STU-2024010013-a4b5c6d7e8f9'),
(44, 'LSB-STU-2024010014-b5c6d7e8f9a0'),
(45, 'LSB-STU-2024010015-c6d7e8f9a0b1');

-- Violations (Past, Present, Future scenarios)
-- PAST VIOLATIONS (January - February 2026)
INSERT INTO violations (student_id, academic_period_id, violation_type_id, reported_by, description, location, date_occurred, status) VALUES
(16, 2, 1, 5, 'Student arrived 25 minutes late without excuse slip', 'Room 402', '2026-01-15 07:55:00', 'resolved'),
(17, 2, 6, 6, 'Caught copying answers during Math quiz', 'Room 305', '2026-01-18 10:30:00', 'resolved'),
(18, 2, 3, 7, 'Not wearing proper school uniform - wearing jacket over uniform', 'Main Gate', '2026-01-22 07:30:00', 'resolved'),
(19, 2, 11, 8, 'Using mobile phone to watch videos during class', 'Room 201', '2026-01-25 14:00:00', 'resolved'),
(20, 2, 10, 5, 'Littering in school quadrangle during lunch', 'School Quadrangle', '2026-01-29 12:30:00', 'resolved'),
(1, 2, 2, 6, 'Cutting 2nd period class, found at library', 'School Library', '2026-02-05 09:00:00', 'resolved'),
(2, 2, 15, 9, 'Loitering in hallway during class hours', 'Hallway 3rd Floor', '2026-02-08 10:15:00', 'resolved'),
(3, 2, 9, 7, 'Disrespectful remarks towards substitute teacher', 'Room 301', '2026-02-12 13:30:00', 'resolved'),
(4, 2, 1, 5, 'Late for 4 consecutive days', 'Main Gate', '2026-02-15 07:50:00', 'resolved'),
(5, 2, 5, 8, 'Carved initials on classroom chair', 'Room 205', '2026-02-19 15:00:00', 'resolved'),
(31, 2, 11, 6, 'Playing mobile games during Filipino class', 'Room 102', '2026-02-22 11:00:00', 'resolved'),
(32, 2, 3, 5, 'Wearing earrings and colored hair accessories', 'Main Gate', '2026-02-26 07:30:00', 'resolved'),

-- PRESENT VIOLATIONS (March 2026 - Current Month)
(1, 2, 1, 5, 'Student arrived 20 minutes late without excuse slip', 'Room 301', '2026-03-10 07:50:00', 'reviewed'),
(1, 2, 3, 6, 'Not wearing school ID and improper uniform (wearing sneakers instead of black shoes)', 'Main Gate', '2026-03-15 07:30:00', 'pending'),
(2, 2, 11, 5, 'Using mobile phone to text during Mathematics class', 'Room 301', '2026-03-12 09:15:00', 'resolved'),
(3, 2, 6, 7, 'Caught with cheat sheet during Quarterly Exam', 'Room 205', '2026-03-08 10:30:00', 'resolved'),
(4, 2, 10, 8, 'Throwing candy wrapper on hallway floor', 'Hallway 2nd Floor', '2026-03-14 11:45:00', 'reviewed'),
(5, 2, 2, 6, 'Absent from 3rd and 4th period classes, found at canteen', 'School Canteen', '2026-03-11 10:00:00', 'pending'),
(16, 2, 8, 9, 'Physical altercation with another student during lunch break', 'School Quadrangle', '2026-03-09 12:15:00', 'resolved'),
(17, 2, 4, 5, 'Repeatedly making derogatory comments towards classmate', 'Room 402', '2026-03-13 14:30:00', 'reviewed'),
(18, 2, 9, 7, 'Talking back and using inappropriate language towards teacher', 'Room 305', '2026-03-07 13:00:00', 'resolved'),
(19, 2, 5, 8, 'Writing graffiti on classroom desk', 'Room 201', '2026-03-16 15:00:00', 'pending'),
(20, 2, 1, 6, 'Late for 3 consecutive days without valid reason', 'Main Gate', '2026-03-17 07:45:00', 'reviewed'),
(31, 2, 3, 5, 'Wearing PE uniform on regular class day', 'Room 101', '2026-03-18 08:00:00', 'pending'),
(32, 2, 15, 9, 'Found loitering in hallway during class hours', 'Hallway 1st Floor', '2026-03-19 10:30:00', 'reviewed'),
(33, 2, 11, 7, 'Playing mobile games during English class', 'Room 103', '2026-03-20 11:00:00', 'pending'),
(1, 2, 1, 5, 'Third tardiness offense this month', 'Room 301', '2026-03-21 07:55:00', 'pending'),
(16, 2, 13, 6, 'Bringing playing cards to school premises', 'School Gate', '2026-03-22 07:30:00', 'reviewed'),
(17, 2, 17, 8, 'Inappropriate physical contact with girlfriend in hallway', 'Hallway 3rd Floor', '2026-03-23 12:30:00', 'pending'),
(3, 2, 14, 7, 'Submitted plagiarized essay for English assignment', 'Faculty Room', '2026-03-24 14:00:00', 'reviewed'),
(4, 2, 1, 5, 'Arrived 30 minutes late, missed first period quiz', 'Room 205', '2026-03-25 08:00:00', 'pending'),
(5, 2, 11, 9, 'Caught taking selfies during Science class', 'Science Laboratory', '2026-03-26 09:30:00', 'reviewed'),
(33, 2, 1, 6, 'Habitual tardiness - 5th offense this quarter', 'Main Gate', '2026-03-27 07:58:00', 'pending'),
(2, 2, 3, 7, 'Missing school ID for 3 consecutive days', 'Main Gate', '2026-03-28 07:30:00', 'pending'),
(6, 2, 10, 8, 'Throwing trash outside designated bins', 'School Canteen', '2026-03-29 12:15:00', 'pending'),
(7, 2, 11, 5, 'Texting during class discussion', 'Room 307', '2026-03-30 10:00:00', 'pending'),
(8, 2, 1, 9, 'Arrived 15 minutes late without valid reason', 'Room 308', '2026-03-31 07:45:00', 'pending');

-- Disciplinary Actions (Past, Present, Future)
-- Note: Violation IDs are auto-incremented 1-37 based on insertion order above
-- PAST ACTIONS (Completed) - For violations 1-12
INSERT INTO disciplinary_actions (violation_id, action_type, description, issued_by, start_date, end_date, status, remarks) VALUES
(1, 'warning', 'First offense - verbal warning issued for tardiness.', 3, '2026-01-15', NULL, 'completed', 'Student acknowledged'),
(2, 'suspension', 'Two-day suspension for cheating during exam.', 3, '2026-01-19', '2026-01-20', 'completed', 'Parent conference held'),
(3, 'warning', 'Warning for dress code violation. Reminded of school policies.', 4, '2026-01-22', NULL, 'completed', 'Student complied'),
(4, 'detention', 'One-day detention for mobile phone use during class.', 3, '2026-01-26', '2026-01-26', 'completed', 'Served detention'),
(5, 'community_service', 'Four hours cleaning school grounds for littering.', 4, '2026-01-30', '2026-02-06', 'completed', 'Completed all hours'),
(6, 'detention', 'Two-day detention for cutting classes.', 3, '2026-02-06', '2026-02-07', 'completed', 'Attended both days'),
(7, 'warning', 'Final warning for loitering. Next offense will result in detention.', 3, '2026-02-08', NULL, 'completed', 'Student signed form'),
(8, 'counseling', 'Mandatory counseling for disrespectful behavior.', 4, '2026-02-13', '2026-02-27', 'completed', 'Attended 3 sessions'),
(9, 'detention', 'Three-day detention for habitual tardiness.', 3, '2026-02-16', '2026-02-18', 'completed', 'Completed successfully'),
(10, 'community_service', 'Six hours repairing damaged school property.', 4, '2026-02-20', '2026-02-27', 'completed', 'Vandalism addressed'),
(11, 'warning', 'Warning for mobile phone use during class.', 3, '2026-02-22', NULL, 'completed', 'Student acknowledged'),
(12, 'warning', 'Warning for dress code violation.', 4, '2026-02-26', NULL, 'completed', 'Student complied'),

-- PRESENT ACTIONS (Active/Ongoing) - For violations 13-35
(13, 'warning', 'First offense - verbal warning issued. Student counseled about punctuality.', 3, '2026-03-10', NULL, 'completed', 'Student acknowledged'),
(15, 'detention', 'One-day detention for using mobile phone during class. Parent notified.', 3, '2026-03-13', '2026-03-13', 'completed', 'Served detention'),
(16, 'suspension', 'Three-day suspension for academic dishonesty. Parent conference conducted.', 3, '2026-03-09', '2026-03-11', 'completed', 'Parent agreed with action'),
(19, 'suspension', 'Five-day suspension for fighting. Mandatory anger management counseling required.', 3, '2026-03-10', '2026-03-14', 'completed', 'Completed counseling'),
(20, 'counseling', 'Mandatory counseling sessions for bullying behavior. Three sessions scheduled.', 4, '2026-03-14', '2026-04-04', 'active', 'Attended 2 out of 3 sessions'),
(21, 'detention', 'Two-day detention for disrespect to authority. Written apology required.', 3, '2026-03-08', '2026-03-09', 'completed', 'Submitted apology'),
(23, 'warning', 'Final warning for repeated tardiness. Next offense will result in detention.', 3, '2026-03-17', NULL, 'completed', 'Student signed acknowledgment'),
(25, 'community_service', 'Eight hours of community service - cleaning school grounds after classes.', 4, '2026-03-20', '2026-04-03', 'active', 'Completed 5 hours so far'),
(28, 'warning', 'Warning issued for bringing prohibited items. Items confiscated.', 3, '2026-03-22', NULL, 'completed', 'Items returned to parent'),
(30, 'counseling', 'Academic integrity counseling required. Must attend workshop on proper citation.', 4, '2026-03-25', '2026-04-08', 'active', 'Workshop scheduled'),
(34, 'detention', 'One-day detention for habitual tardiness.', 3, '2026-03-28', '2026-03-28', 'active', 'Scheduled for today'),
(35, 'warning', 'Warning for missing school ID. Must present ID tomorrow.', 4, '2026-03-28', NULL, 'active', 'Pending compliance'),

-- FUTURE ACTIONS (Pending/Scheduled) - For violations 36-37
(36, 'detention', 'One-day detention scheduled for littering offense.', 3, '2026-04-02', '2026-04-02', 'pending', 'Parent notified'),
(37, 'warning', 'Warning to be issued for mobile phone use.', 4, '2026-04-01', NULL, 'pending', 'Awaiting student conference');

-- Uniform Passes
INSERT INTO uniform_passes (student_id, academic_period_id, pass_code, reason, issued_by, valid_date, status) VALUES
(2, 2, 'TUP-2026-03-31-001', 'Uniform damaged during PE class, waiting for replacement', 3, '2026-03-31', 'active'),
(5, 2, 'TUP-2026-03-30-002', 'School uniform being laundered after accidental spill', 4, '2026-03-30', 'expired'),
(19, 2, 'TUP-2026-03-31-003', 'Lost school ID, replacement being processed', 3, '2026-03-31', 'active'),
(32, 2, 'TUP-2026-03-29-004', 'Shoes damaged, new pair being purchased', 4, '2026-03-29', 'expired'),
(4, 2, 'TUP-2026-03-31-005', 'Uniform pants torn, emergency pass for today', 3, '2026-03-31', 'active');

-- Notifications
INSERT INTO notifications (user_id, type, title, message, link, is_read) VALUES
(12, 'warning', 'Disciplinary Action Issued', 'A new action (Warning) has been issued following your recent violation review.', '/student/violations.php', 0),
(13, 'danger', 'New Violation Recorded', 'A new report for ''Bullying'' has been filed against you. Please check your violations list.', '/student/violations.php', 0),
(14, 'warning', 'Disciplinary Action Issued', 'A new action (Detention) has been issued following your recent violation review.', '/student/violations.php', 1),
(20, 'info', 'Uniform Pass Approved', 'Your temporary uniform pass has been approved and is now active.', '/student/uniform_pass.php', 0),
(3, 'info', 'New Violation Reported', 'A teacher has reported a new violation for Student #2023010001', '/discipline/violations.php', 1),
(4, 'info', 'New Violation Reported', 'A teacher has reported a new violation for Student #2022010001', '/discipline/violations.php', 0);

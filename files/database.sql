-- ============================================================
--  EduTrack Attendance Management System — Database Setup
--  Run this entire file in phpMyAdmin (XAMPP)
-- ============================================================

-- Step 1: Create the database
CREATE DATABASE IF NOT EXISTS attendance_system;

-- Step 2: Select the database
USE attendance_system;

-- ============================================================
-- TABLE 1: admin
-- Stores admin login credentials
-- ============================================================
CREATE TABLE IF NOT EXISTS admin (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50)  NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL  -- We store hashed passwords
);

-- Insert a default admin account
-- Username: admin | Password: admin123
INSERT INTO admin (username, password)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
-- NOTE: The above hash is for the password "admin123"
-- You can change the password after login if you like

-- ============================================================
-- TABLE 2: classes
-- Stores class names like "BCA 1st Year", "BSc 2nd Year"
-- ============================================================
CREATE TABLE IF NOT EXISTS classes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample classes
INSERT INTO classes (class_name) VALUES
    ('BCA 1st Year'),
    ('BCA 2nd Year'),
    ('BSc 3rd Year');

-- ============================================================
-- TABLE 3: subjects
-- Stores subjects linked to a class
-- ============================================================
CREATE TABLE IF NOT EXISTS subjects (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    class_id     INT NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- Sample subjects
INSERT INTO subjects (class_id, subject_name) VALUES
    (1, 'Mathematics'),
    (1, 'Programming in C'),
    (1, 'English'),
    (2, 'Data Structures'),
    (2, 'DBMS'),
    (3, 'Physics'),
    (3, 'Chemistry');

-- ============================================================
-- TABLE 4: students
-- Stores student details
-- ============================================================
CREATE TABLE IF NOT EXISTS students (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    roll_number  VARCHAR(20)  NOT NULL UNIQUE,
    full_name    VARCHAR(100) NOT NULL,
    email        VARCHAR(100),
    class_id     INT NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- Sample students
INSERT INTO students (roll_number, full_name, email, class_id) VALUES
    ('BCA101', 'Aryan Sharma',   'aryan@college.edu',  1),
    ('BCA102', 'Priya Patel',    'priya@college.edu',  1),
    ('BCA103', 'Rohit Verma',    'rohit@college.edu',  1),
    ('BCA201', 'Sneha Joshi',    'sneha@college.edu',  2),
    ('BCA202', 'Karan Mehta',    'karan@college.edu',  2),
    ('BSC301', 'Divya Singh',    'divya@college.edu',  3);

-- ============================================================
-- TABLE 5: attendance
-- Records daily attendance per student per subject
-- ============================================================
CREATE TABLE IF NOT EXISTS attendance (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    student_id  INT  NOT NULL,
    subject_id  INT  NOT NULL,
    class_id    INT  NOT NULL,
    att_date    DATE NOT NULL,
    status      ENUM('Present', 'Absent', 'Leave') DEFAULT 'Absent',
    marked_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id)  ON DELETE CASCADE,
    FOREIGN KEY (class_id)   REFERENCES classes(id)   ON DELETE CASCADE,
    -- Prevent duplicate entries for same student+subject+date
    UNIQUE KEY unique_attendance (student_id, subject_id, att_date)
);

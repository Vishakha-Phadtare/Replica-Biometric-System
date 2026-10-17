CREATE DATABASE IF NOT EXISTS teacher_attendance;
USE teacher_attendance;

CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS teachers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(150) NOT NULL,
    department VARCHAR(150) NOT NULL,
    email VARCHAR(150),
    phone VARCHAR(30),
    password_hash VARCHAR(255) NULL,
    face_descriptor LONGTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    attendance_time TIME NOT NULL,
    status VARCHAR(30) DEFAULT 'Present',
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    location_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_date (teacher_id, attendance_date)
);

-- Insert demo admin (username: admin, password: admin123)
-- Hash generated for 'admin123' using PASSWORD_DEFAULT in PHP
INSERT IGNORE INTO admins (username, password_hash) VALUES ('admin', '$2y$12$22jc8fQLYabtY4nXJPrpxOirppv0rn9/SccA1AAJk15queY.q8eEK');

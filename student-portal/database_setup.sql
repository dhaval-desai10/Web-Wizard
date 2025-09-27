-- Student Portal Database Setup
-- Run this in phpMyAdmin or MySQL console

CREATE DATABASE IF NOT EXISTS student_portal;
USE student_portal;

-- Departments table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15),
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    address TEXT,
    department_id INT,
    profile_image VARCHAR(255),
    
    -- Father details
    father_name VARCHAR(100),
    father_phone VARCHAR(15),
    father_occupation VARCHAR(100),
    father_email VARCHAR(100),
    
    -- Education details
    tenth_percentage DECIMAL(5,2),
    tenth_board VARCHAR(100),
    tenth_year YEAR,
    twelfth_percentage DECIMAL(5,2),
    twelfth_board VARCHAR(100),
    twelfth_year YEAR,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    file_path VARCHAR(500),
    file_type ENUM('pdf', 'image'),
    department_id INT NULL, -- NULL means for all departments
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    department_id INT NULL, -- NULL means for all departments
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- Insert default departments
INSERT INTO departments (name) VALUES 
('Computer Science'),
('Information Technology'),
('Electronics'),
('Mechanical'),
('Civil'),
('Electrical');
-- Create database
CREATE DATABASE IF NOT EXISTS admission_system;
USE admission_system;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create applications table
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    dob DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    nationality VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    emergency_contact_name VARCHAR(255),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_relationship VARCHAR(100),
    high_school VARCHAR(255) NOT NULL,
    high_school_graduation_year INT NOT NULL,
    high_school_gpa VARCHAR(10),
    program_first_choice INT NOT NULL,
    program_second_choice INT,
    program_third_choice INT,
    status ENUM('Not Started', 'In Progress', 'Submitted', 'Approved', 'Rejected') DEFAULT 'Not Started',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create documents table
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    document_type ENUM('id_card', 'high_school_transcript', 'birth_certificate', 'passport_photo', 'recommendation_letter', 'personal_statement') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    UNIQUE KEY (application_id, document_type)
);

-- Create programs table
CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    duration VARCHAR(50),
    requirements TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample programs
INSERT INTO programs (name, description, duration, requirements) VALUES
('Computer Science', 'Bachelor of Science in Computer Science', '4 years', 'High School Diploma with credits in Mathematics and Science'),
('Electrical Engineering', 'Bachelor of Engineering in Electrical Engineering', '4 years', 'High School Diploma with credits in Mathematics and Physics'),
('Business Administration', 'Bachelor of Business Administration', '4 years', 'High School Diploma with credits in Mathematics and English'),
('Environmental Science', 'Bachelor of Science in Environmental Science', '4 years', 'High School Diploma with credits in Biology and Chemistry'),
('Mechanical Engineering', 'Bachelor of Engineering in Mechanical Engineering', '4 years', 'High School Diploma with credits in Mathematics and Physics'),
('Information Technology', 'Bachelor of Science in Information Technology', '4 years', 'High School Diploma with credits in Mathematics'),
('Petroleum Engineering', 'Bachelor of Engineering in Petroleum Engineering', '4 years', 'High School Diploma with credits in Mathematics, Physics and Chemistry'),
('Renewable Energy', 'Bachelor of Science in Renewable Energy', '4 years', 'High School Diploma with credits in Mathematics and Physics');


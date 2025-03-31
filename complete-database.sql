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
  admin_notes TEXT,
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

-- Create admins table
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL,
  role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create AI recommendations table
CREATE TABLE IF NOT EXISTS ai_recommendations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  application_id INT NOT NULL,
  primary_program VARCHAR(255) NOT NULL,
  primary_match_score INT NOT NULL,
  alternate_program VARCHAR(255),
  alternate_match_score INT,
  confidence_score INT NOT NULL,
  analysis TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
  UNIQUE KEY (application_id)
);

-- Create email logs table
CREATE TABLE IF NOT EXISTS email_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  application_id INT NOT NULL,
  recipient VARCHAR(255) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  sent_by INT NOT NULL,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
  FOREIGN KEY (sent_by) REFERENCES admins(id) ON DELETE CASCADE
);

-- Create email templates table
CREATE TABLE IF NOT EXISTS email_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create system settings table
CREATE TABLE IF NOT EXISTS system_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT NOT NULL,
  description VARCHAR(255),
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create activity logs table
CREATE TABLE IF NOT EXISTS activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_type ENUM('admin', 'user') NOT NULL,
  user_id INT NOT NULL,
  action VARCHAR(255) NOT NULL,
  details TEXT,
  ip_address VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO admins (username, password, name, email, role) VALUES
('admin', '$2y$10$8KOO.VVOXQM7EV3r3KgUxeEGBMlZCB4yQwURrxvBs9z5wQV0N5emy', 'Admin User', 'admin@uenr.edu.gh', 'admin');

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

-- Insert default email templates
INSERT INTO email_templates (name, subject, message) VALUES
('Application Received', 'Your Application Has Been Received', 'Dear [FIRST_NAME],\n\nThank you for submitting your application to the University of Energy and Natural Resources. We are pleased to confirm that we have received your application for the [PROGRAM] program.\n\nYour application is currently under review, and we will notify you of any updates or decisions regarding your admission status.\n\nIf you have any questions, please don\'t hesitate to contact our admissions office.\n\nBest regards,\nAdmissions Team\nUniversity of Energy and Natural Resources'),
('Application Approved', 'Congratulations! Your Application Has Been Approved', 'Dear [FIRST_NAME],\n\nCongratulations! We are pleased to inform you that your application to the [PROGRAM] program at the University of Energy and Natural Resources has been approved.\n\nPlease log in to your account to view your admission letter and next steps for enrollment.\n\nWe look forward to welcoming you to our university community.\n\nBest regards,\nAdmissions Team\nUniversity of Energy and Natural Resources'),
('Application Rejected', 'Update on Your Application Status', 'Dear [FIRST_NAME],\n\nThank you for your interest in the University of Energy and Natural Resources and for submitting your application to the [PROGRAM] program.\n\nAfter careful review of your application, we regret to inform you that we are unable to offer you admission at this time.\n\nWe encourage you to explore other programs that may better align with your qualifications and interests.\n\nBest regards,\nAdmissions Team\nUniversity of Energy and Natural Resources'),
('Additional Documents Required', 'Additional Documents Required for Your Application', 'Dear [FIRST_NAME],\n\nThank you for submitting your application to the [PROGRAM] program at the University of Energy and Natural Resources.\n\nUpon reviewing your application, we find that we need additional documents to complete the evaluation process. Please log in to your account and upload the following documents:\n\n- [DOCUMENT_LIST]\n\nPlease submit these documents at your earliest convenience to avoid delays in processing your application.\n\nBest regards,\nAdmissions Team\nUniversity of Energy and Natural Resources'),
('AI Recommendation', 'Program Recommendation for Your Application', 'Dear [FIRST_NAME],\n\nBased on your academic background and qualifications, our system has analyzed your application and recommends the following program(s):\n\n- Primary Recommendation: [PRIMARY_PROGRAM]\n- Alternate Recommendation: [ALTERNATE_PROGRAM]\n\nThese recommendations are based on your academic profile and the requirements of our programs. You can log in to your account to view more details about these recommendations.\n\nIf you have any questions or would like to discuss these recommendations further, please contact our admissions office.\n\nBest regards,\nAdmissions Team\nUniversity of Energy and Natural Resources');

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('application_deadline', '2025-08-31', 'Deadline for application submissions'),
('academic_year', '2025/2026', 'Current academic year'),
('ai_confidence_threshold', '75', 'Minimum confidence score for AI recommendations'),
('auto_approve_applications', 'false', 'Automatically approve applications with high AI confidence'),
('email_notifications_enabled', 'true', 'Enable email notifications'),
('system_email', 'admissions@uenr.edu.gh', 'System email address for sending notifications');


-- Create database
CREATE DATABASE IF NOT EXISTS security_management;
USE security_management;

-- Users table for all types of users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'guard', 'organization') NOT NULL,
    phone VARCHAR(20),
    profile_image VARCHAR(255),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Organizations table
CREATE TABLE organizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    contact_person VARCHAR(100),
    contact_phone VARCHAR(20),
    logo VARCHAR(255),
    industry VARCHAR(100),
    contract_start_date DATE,
    contract_end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Guards table (updated with organization_id)
CREATE TABLE guards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    organization_id INT NOT NULL,
    id_number VARCHAR(20) NOT NULL UNIQUE,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    address TEXT,
    emergency_contact VARCHAR(100),
    emergency_phone VARCHAR(20),
    joining_date DATE,
    qualification TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

-- Locations/Sites table (updated with organization_id)
CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    contact_person VARCHAR(100),
    contact_phone VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

-- Shifts table
CREATE TABLE shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Duty Assignment table
CREATE TABLE duty_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guard_id INT NOT NULL,
    location_id INT NOT NULL,
    shift_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guard_id) REFERENCES guards(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Attendance table
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    duty_assignment_id INT NOT NULL,
    check_in_time DATETIME,
    check_in_latitude DECIMAL(10, 8),
    check_in_longitude DECIMAL(11, 8),
    check_out_time DATETIME,
    check_out_latitude DECIMAL(10, 8),
    check_out_longitude DECIMAL(11, 8),
    status ENUM('present', 'absent', 'late', 'early_departure') NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (duty_assignment_id) REFERENCES duty_assignments(id) ON DELETE CASCADE
);

-- Incidents table
CREATE TABLE incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reported_by INT NOT NULL,
    location_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    incident_time DATETIME NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    status ENUM('reported', 'investigating', 'resolved', 'closed') DEFAULT 'reported',
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES users(id),
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
);

-- Incident Media table
CREATE TABLE incident_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE
);

-- Guard Requests table (updated with organization_id)
CREATE TABLE guard_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    location_id INT NOT NULL,
    number_of_guards INT NOT NULL,
    shift_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES shifts(id)
);

-- Messages table
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50),
    is_read BOOLEAN DEFAULT 0,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Performance Evaluations table
CREATE TABLE performance_evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guard_id INT NOT NULL,
    evaluator_id INT NOT NULL,
    evaluation_date DATE NOT NULL,
    punctuality INT NOT NULL COMMENT 'Rating from 1-5',
    appearance INT NOT NULL COMMENT 'Rating from 1-5',
    communication INT NOT NULL COMMENT 'Rating from 1-5',
    job_knowledge INT NOT NULL COMMENT 'Rating from 1-5',
    overall_rating INT NOT NULL COMMENT 'Rating from 1-5',
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guard_id) REFERENCES guards(id) ON DELETE CASCADE,
    FOREIGN KEY (evaluator_id) REFERENCES users(id)
);

-- Activity Logs table
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'general',
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Reports table
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    report_type VARCHAR(50) NOT NULL,
    created_by INT NOT NULL,
    start_date DATE,
    end_date DATE,
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role, phone, status) 
VALUES ('System Administrator', 'admin@secureconnect.co.ke', '$2y$12$Ht0vYK6EvpJX8MjqPjmPweSgNMRlk0BZqFF2v.OANu1uQkzX3Q6AG', 'admin', '+254700000000', 'active');

-- Insert default shifts
INSERT INTO shifts (name, start_time, end_time, description)
VALUES 
('Morning Shift', '06:00:00', '14:00:00', 'Morning shift from 6am to 2pm'),
('Afternoon Shift', '14:00:00', '22:00:00', 'Afternoon shift from 2pm to 10pm'),
('Night Shift', '22:00:00', '06:00:00', 'Night shift from 10pm to 6am');
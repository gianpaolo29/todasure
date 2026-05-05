-- ============================================
-- TODASURE - Complete Database Setup
-- GPS-Based Digital Fare Meter with
-- Centralized Monitoring System
--
-- Run this file once to set up the database
-- ============================================

CREATE DATABASE IF NOT EXISTS todasure_db;
USE todasure_db;

-- ============================================
-- Users (admin, driver, passenger, barangay)
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) DEFAULT '',
    phone VARCHAR(20),
    role ENUM('admin', 'driver', 'passenger', 'barangay') NOT NULL DEFAULT 'passenger',
    barangay_id INT NULL,
    status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Barangays (pre-populated with Nasugbu)
-- ============================================
CREATE TABLE barangays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    municipality VARCHAR(100) NOT NULL DEFAULT 'Nasugbu',
    province VARCHAR(100) NOT NULL DEFAULT 'Batangas',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TODA (Tricycle Operators & Drivers Association)
-- ============================================
CREATE TABLE todas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    barangay_id INT NOT NULL,
    president VARCHAR(100),
    contact_number VARCHAR(20),
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (barangay_id) REFERENCES barangays(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================
-- Drivers
-- ============================================
CREATE TABLE drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    toda_id INT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) DEFAULT '',
    middle_name VARCHAR(50),
    contact_number VARCHAR(20),
    address TEXT,
    status ENUM('active', 'suspended', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (toda_id) REFERENCES todas(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Tricycles
-- ============================================
CREATE TABLE tricycles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NULL,
    plate_number VARCHAR(20) NOT NULL UNIQUE,
    body_number VARCHAR(20) NOT NULL,
    color VARCHAR(30),
    model VARCHAR(50),
    status ENUM('active', 'inactive', 'maintenance') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Fare Rates (per barangay)
-- ============================================
CREATE TABLE fare_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barangay_id INT NOT NULL,
    base_fare DECIMAL(10,2) NOT NULL,
    base_distance DECIMAL(10,2) NOT NULL,
    per_km_rate DECIMAL(10,2) NOT NULL,
    discount_senior DECIMAL(5,2) DEFAULT 20.00,
    discount_student DECIMAL(5,2) DEFAULT 10.00,
    effective_date DATE NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (barangay_id) REFERENCES barangays(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================
-- Trips (GPS-tracked)
-- ============================================
CREATE TABLE trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tricycle_id INT NULL,
    driver_id INT NOT NULL,
    fare_rate_id INT,
    origin VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    distance_km DECIMAL(10,2) NOT NULL DEFAULT 0,
    computed_fare DECIMAL(10,2),
    actual_fare DECIMAL(10,2),
    passenger_count INT DEFAULT 1,
    status ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    start_lat DECIMAL(10,7) NULL,
    start_lng DECIMAL(10,7) NULL,
    end_lat DECIMAL(10,7) NULL,
    end_lng DECIMAL(10,7) NULL,
    current_lat DECIMAL(10,7) NULL,
    current_lng DECIMAL(10,7) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tricycle_id) REFERENCES tricycles(id) ON DELETE SET NULL,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE RESTRICT,
    FOREIGN KEY (fare_rate_id) REFERENCES fare_rates(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Trip GPS Breadcrumbs
-- ============================================
CREATE TABLE trip_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    distance_from_prev DECIMAL(10,4) DEFAULT 0,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    INDEX idx_trip_id (trip_id)
) ENGINE=InnoDB;

-- ============================================
-- Complaints
-- ============================================
CREATE TABLE complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tricycle_id INT NOT NULL,
    driver_id INT NOT NULL,
    trip_id INT,
    passenger_name VARCHAR(100),
    passenger_contact VARCHAR(50),
    complaint_type ENUM('overcharging', 'rude_behavior', 'reckless_driving', 'refusal_of_service', 'other') NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'investigating', 'resolved', 'dismissed') NOT NULL DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tricycle_id) REFERENCES tricycles(id) ON DELETE RESTRICT,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE RESTRICT,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Violations
-- ============================================
CREATE TABLE violations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    trip_id INT,
    complaint_id INT,
    violation_type ENUM('fare_overcharge', 'fare_undercharge', 'unauthorized_route', 'complaint_based', 'other') NOT NULL,
    description TEXT,
    severity ENUM('minor', 'moderate', 'major') NOT NULL DEFAULT 'minor',
    penalty VARCHAR(255),
    status ENUM('pending', 'confirmed', 'appealed', 'resolved') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE RESTRICT,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE SET NULL,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Bookings (ride requests)
-- ============================================
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    passenger_id INT NOT NULL,
    driver_id INT NULL,
    tricycle_id INT NULL,
    pickup_address VARCHAR(255) NOT NULL,
    pickup_lat DECIMAL(10,7) NOT NULL,
    pickup_lng DECIMAL(10,7) NOT NULL,
    dropoff_address VARCHAR(255) NOT NULL,
    dropoff_lat DECIMAL(10,7) NOT NULL,
    dropoff_lng DECIMAL(10,7) NOT NULL,
    estimated_distance DECIMAL(10,2) NULL,
    estimated_fare DECIMAL(10,2) NULL,
    status ENUM('pending','accepted','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    trip_id INT NULL,
    cancelled_by ENUM('passenger','driver','system') NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (passenger_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL,
    FOREIGN KEY (tricycle_id) REFERENCES tricycles(id) ON DELETE SET NULL,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_driver (driver_id),
    INDEX idx_passenger (passenger_id)
) ENGINE=InnoDB;

-- ============================================
-- Notifications
-- ============================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    role_target ENUM('admin', 'driver', 'passenger', 'barangay') NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_role_read (role_target, is_read)
) ENGINE=InnoDB;

-- ============================================
-- Default Admin Account
-- Email: admin@todasure.com | Password: admin123
-- ============================================
INSERT INTO users (username, email, password, first_name, last_name, role) VALUES
('admin', 'admin@todasure.com', '$2y$10$9jVcLLrnnZTyspe/OypgXeCxUhdYkqGXhMZTm5Ar9rDhyVluWjreW', 'System', 'Administrator', 'admin');

-- ============================================
-- Nasugbu Barangays (44 barangays)
-- ============================================
INSERT INTO barangays (name) VALUES
('Aga'), ('Balaytigui'), ('Banilad'),
('Barangay 1 (Poblacion)'), ('Barangay 2 (Poblacion)'), ('Barangay 3 (Poblacion)'),
('Barangay 4 (Poblacion)'), ('Barangay 5 (Poblacion)'), ('Barangay 6 (Poblacion)'),
('Barangay 7 (Poblacion)'), ('Barangay 8 (Poblacion)'), ('Barangay 9 (Poblacion)'),
('Barangay 10 (Poblacion)'), ('Barangay 11 (Poblacion)'), ('Barangay 12 (Poblacion)'),
('Bilaran'), ('Bucana'), ('Bulihan'), ('Bunducan'), ('Butucan'),
('Calayo'), ('Catandaan'), ('Cogunan'), ('Dayap'),
('Kaylaway'), ('Kayrilaw'), ('Latag'), ('Looc'), ('Lumbangan'),
('Malapad na Bato'), ('Mataas na Pulo'), ('Natipuan'), ('Pantalan'), ('Papaya'),
('Putat'), ('Red Gate'), ('Reparo'), ('San Diego'), ('San Isidro'),
('Santa Cruz'), ('Sinala'), ('Tumalim'), ('Utod'), ('Wawa');

-- ============================================
-- Default TODA
-- ============================================
INSERT INTO todas (name, barangay_id) VALUES ('Nasugbu TODA', 1);

-- ============================================
-- Sample Fare Rates
-- ============================================
INSERT INTO fare_rates (barangay_id, base_fare, base_distance, per_km_rate, effective_date) VALUES
(1, 15.00, 1.00, 5.00, '2026-01-01'),
(39, 15.00, 1.00, 5.50, '2026-01-01'),
(40, 12.00, 0.80, 5.00, '2026-01-01');

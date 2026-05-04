-- ============================================
-- Migration: Add role-based authentication
-- Run this in phpMyAdmin after initial schema
-- ============================================

USE todasure_db;

-- Add new columns to users table
ALTER TABLE users
    ADD COLUMN email VARCHAR(100) UNIQUE AFTER username,
    ADD COLUMN first_name VARCHAR(50) NOT NULL DEFAULT '' AFTER password,
    ADD COLUMN last_name VARCHAR(50) NOT NULL DEFAULT '' AFTER first_name,
    ADD COLUMN phone VARCHAR(20) AFTER last_name,
    MODIFY COLUMN role ENUM('admin', 'driver', 'resident') NOT NULL DEFAULT 'resident',
    MODIFY COLUMN status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'active';

-- Update existing admin account with email
UPDATE users SET email = 'admin@todashare.com', first_name = 'System', last_name = 'Admin' WHERE username = 'admin';

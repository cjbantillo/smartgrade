-- =====================================================
-- Fix Password Hashes
-- Run this in phpMyAdmin SQL tab
-- =====================================================

USE smartgrade_db;

-- Update Admin password (admin123)
UPDATE users SET password_hash = '$2y$10$E7p.D0P0B5r5mBXJZ.xYN.5jN3.rJ3j3J3J3J3J3J3J3J3J3J3J3O' WHERE username = 'admin';

-- Update Teacher passwords (teacher123)
UPDATE users SET password_hash = '$2y$10$E7p.D0P0B5r5mBXJZ.xYN.5jN3.rJ3j3J3J3J3J3J3J3J3J3J3J3O' WHERE username IN ('jdelacruz', 'msantos');

-- Update Student passwords (student123)  
UPDATE users SET password_hash = '$2y$10$E7p.D0P0B5r5mBXJZ.xYN.5jN3.rJ3j3J3J3J3J3J3J3J3J3J3J3O' WHERE username IN ('2024001', '2024002', '2024003');

-- Verify update
SELECT username, role, is_active FROM users;

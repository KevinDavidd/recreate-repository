-- =====================================================================
-- CECT Research Repository — Database Schema
-- Wesleyan University-Philippines, College of Engineering, Computer
-- and Technology
-- =====================================================================

CREATE DATABASE IF NOT EXISTS cect_repository
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE cect_repository;

-- ---------------------------------------------------------------------
-- Admins (Faculty / Department Head)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Students
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL UNIQUE,   -- format 00-0000-000
    full_name VARCHAR(150) NOT NULL,
    course ENUM('BSIT', 'BSCpE', 'BSCS') NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('pending', 'active', 'suspended', 'rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME NULL,
    last_login DATETIME NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Researches
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS researches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    abstract TEXT NOT NULL,
    authors VARCHAR(255) NOT NULL,
    adviser VARCHAR(150) NULL,
    course ENUM('BSIT', 'BSCpE', 'BSCS') NOT NULL,
    research_type ENUM('Thesis', 'Capstone', 'Research Paper', 'Case Study') NOT NULL DEFAULT 'Capstone',
    year_published YEAR NOT NULL,
    file_name VARCHAR(255) NOT NULL,      -- stored (uniqid) file name
    original_file_name VARCHAR(255) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    download_count INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('active', 'archived') NOT NULL DEFAULT 'active',
    archived_at DATETIME NULL,
    archived_by INT UNSIGNED NULL,
    archive_reason VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    FOREIGN KEY (uploaded_by) REFERENCES admins(id) ON DELETE RESTRICT,
    FOREIGN KEY (archived_by) REFERENCES admins(id) ON DELETE SET NULL,
    FULLTEXT KEY ft_search (title, abstract, authors)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Research Categories (many-to-many: a research may belong to several)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS research_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    research_id INT UNSIGNED NOT NULL,
    category VARCHAR(50) NOT NULL,
    FOREIGN KEY (research_id) REFERENCES researches(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_research_category (research_id, category)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Activity Logs
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('admin', 'student') NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    user_name VARCHAR(150) NOT NULL,
    action VARCHAR(100) NOT NULL,
    details VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

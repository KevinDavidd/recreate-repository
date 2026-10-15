-- ============================================================
-- CECT Research Archive System
-- Wesleyan University-Philippines — CECT Department
-- Database Schema (schema only — seed data via setup.php)
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `cect_archive`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `cect_archive`;

-- ─────────────────────────────────────────
-- Table: admins
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admins` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(100) NOT NULL,
  `password`   VARCHAR(255) NOT NULL COMMENT 'bcrypt hashed',
  `full_name`  VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- Table: students
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `students` (
  `id`            INT          NOT NULL AUTO_INCREMENT,
  `student_id`    VARCHAR(20)  NOT NULL COMMENT 'Format: XX-XXXX-XXX',
  `full_name`     VARCHAR(255) NOT NULL,
  `course`        ENUM('IT','Computer Engineering','Computer Science') NOT NULL,
  `year_level`    ENUM('1st Year','2nd Year','3rd Year','4th Year')    NOT NULL,
  `email`         VARCHAR(255) NOT NULL,
  `password`      VARCHAR(255) NOT NULL COMMENT 'bcrypt hashed',
  `status`        ENUM('pending','active','suspended','rejected') NOT NULL DEFAULT 'pending',
  `registered_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_at`   TIMESTAMP    NULL DEFAULT NULL,
  `approved_by`   INT          NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_student_id` (`student_id`),
  UNIQUE KEY `uq_email`      (`email`),
  KEY `fk_approved_by`       (`approved_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- Table: researches
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `researches` (
  `id`             INT          NOT NULL AUTO_INCREMENT,
  `title`          VARCHAR(500) NOT NULL,
  `authors`        TEXT         NOT NULL COMMENT 'comma-separated full names',
  `course`         ENUM('IT','Computer Engineering','Computer Science') NOT NULL,
  `research_type`  ENUM('Web App','Mobile App','IoT','Machine Learning/AI','Desktop App','Cybersecurity','Data Analytics') NOT NULL,
  `year`           YEAR         NOT NULL,
  `abstract`       LONGTEXT     NOT NULL,
  `adviser`        VARCHAR(255) NOT NULL,
  `panel_chair`    VARCHAR(255) NOT NULL,
  `panel_members`  TEXT         DEFAULT NULL COMMENT 'comma-separated',
  `pdf_file`       VARCHAR(500) NOT NULL COMMENT 'stored filename only, served via download.php',
  `download_count` INT          NOT NULL DEFAULT 0,
  `view_count`     INT          NOT NULL DEFAULT 0,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- Table: activity_logs
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id`           INT         NOT NULL AUTO_INCREMENT,
  `user_type`    ENUM('admin','student') NOT NULL,
  `user_id`      INT         NOT NULL,
  `action`       VARCHAR(255) NOT NULL,
  `research_id`  INT         NULL DEFAULT NULL,
  `ip_address`   VARCHAR(45) DEFAULT NULL,
  `performed_at` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user`      (`user_type`, `user_id`),
  KEY `idx_research`  (`research_id`),
  KEY `idx_performed` (`performed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

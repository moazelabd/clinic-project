-- ============================================================
-- Clinic / Pharmacy Management - Database Schema
-- Import this once into MySQL:
--   mysql -u root -p < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS clinic_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE clinic_db;

-- ------------------------------------------------------------
-- Users (added manually by you, see create_admin.php)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Brute-force protection log for the login page
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL,
  INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Storages (warehouses / pharmacy stores)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS storages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Medicines that live inside a storage
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS medicines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  storage_id INT NOT NULL,
  name VARCHAR(200) NOT NULL,
  image_path VARCHAR(255) NULL,
  stock INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (storage_id) REFERENCES storages(id) ON DELETE CASCADE,
  INDEX idx_medicine_name (name)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Doctors
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS doctors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  title VARCHAR(255) NULL,               -- "عنوان" (address / job title line)
  image_path VARCHAR(255) NULL,
  is_blacklisted TINYINT(1) NOT NULL DEFAULT 0,
  blacklist_reason TEXT NULL,
  general_note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Doctor visits
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS visits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  doctor_id INT NOT NULL,
  visit_date DATE NOT NULL,
  note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Migration: Roles, Groups & Join Requests
-- Run this on your EXISTING database (locally + on InfinityFree)
--   mysql -u root -p clinic_db < migration_groups.sql
-- or paste it into phpMyAdmin's SQL tab.
-- ============================================================

USE clinic_db;

-- 1) Roles + current group for every user
ALTER TABLE users
  ADD COLUMN role ENUM('admin','user') NOT NULL DEFAULT 'user',
  ADD COLUMN group_id INT NULL;

-- 2) Groups (created by admins only)
CREATE TABLE IF NOT EXISTS groups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  join_code VARCHAR(20) NOT NULL UNIQUE,
  created_by INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- 3) Join requests (user sends code -> admin approves/rejects)
CREATE TABLE IF NOT EXISTS group_join_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  group_id INT NOT NULL,
  user_id INT NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decided_at DATETIME NULL,
  FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Link users to their group (SET NULL if the group is later deleted)
ALTER TABLE users ADD CONSTRAINT fk_users_group FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE SET NULL;

-- 4) Scope existing data to a group
ALTER TABLE storages ADD COLUMN group_id INT NULL;
ALTER TABLE doctors  ADD COLUMN group_id INT NULL;

-- Add the foreign keys separately (safe even if columns already had data = NULL)
ALTER TABLE storages ADD CONSTRAINT fk_storages_group FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE;
ALTER TABLE doctors  ADD CONSTRAINT fk_doctors_group  FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE;

-- 5) Make your existing admin account an actual admin.
--    Replace 'admin' with your real username.
UPDATE users SET role = 'admin' WHERE username = 'admin';

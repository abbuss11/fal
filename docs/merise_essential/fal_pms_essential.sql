-- ============================================================
-- FAL PMS (Noyau Essentiel) - SQL
-- Cible: MySQL 8+ / MariaDB 10.6+
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS timesheets;
DROP TABLE IF EXISTS project_files;
DROP TABLE IF EXISTS task_comments;
DROP TABLE IF EXISTS task_subtasks;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS project_user;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS clients;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE DATABASE IF NOT EXISTS fal_pms_essential
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE fal_pms_essential;

-- ------------------------------------------------------------
-- 1) Utilisateurs
-- ------------------------------------------------------------
CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(32) NOT NULL DEFAULT 'member',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY users_email_unique (email),
  KEY users_role_index (role),
  KEY users_is_active_index (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2) Clients
-- ------------------------------------------------------------
CREATE TABLE clients (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NULL,
  phone VARCHAR(60) NULL,
  company VARCHAR(255) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY clients_name_index (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3) Projets
-- ------------------------------------------------------------
CREATE TABLE projects (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_id BIGINT UNSIGNED NOT NULL,
  client_id BIGINT UNSIGNED NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'planning',
  priority VARCHAR(20) NOT NULL DEFAULT 'medium',
  start_date DATE NULL,
  due_date DATE NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY projects_owner_id_index (owner_id),
  KEY projects_client_id_index (client_id),
  KEY projects_status_index (status),
  CONSTRAINT projects_owner_id_fk
    FOREIGN KEY (owner_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT projects_client_id_fk
    FOREIGN KEY (client_id) REFERENCES clients (id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4) Membres projet (pivot)
-- ------------------------------------------------------------
CREATE TABLE project_user (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  project_role VARCHAR(32) NOT NULL DEFAULT 'member',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY project_user_project_user_unique (project_id, user_id),
  KEY project_user_user_id_index (user_id),
  CONSTRAINT project_user_project_id_fk
    FOREIGN KEY (project_id) REFERENCES projects (id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT project_user_user_id_fk
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5) Tâches
-- ------------------------------------------------------------
CREATE TABLE tasks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id BIGINT UNSIGNED NOT NULL,
  assigned_to BIGINT UNSIGNED NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'todo',
  priority VARCHAR(20) NOT NULL DEFAULT 'medium',
  due_date DATETIME NULL,
  estimated_hours DECIMAL(6,2) NULL,
  completed_at DATETIME NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY tasks_project_id_index (project_id),
  KEY tasks_assigned_to_index (assigned_to),
  KEY tasks_status_index (status),
  KEY tasks_due_date_index (due_date),
  CONSTRAINT tasks_project_id_fk
    FOREIGN KEY (project_id) REFERENCES projects (id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tasks_assigned_to_fk
    FOREIGN KEY (assigned_to) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6) Sous-tâches
-- ------------------------------------------------------------
CREATE TABLE task_subtasks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  task_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  is_completed TINYINT(1) NOT NULL DEFAULT 0,
  completed_by BIGINT UNSIGNED NULL,
  completed_at DATETIME NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY task_subtasks_task_id_index (task_id),
  KEY task_subtasks_completed_by_index (completed_by),
  CONSTRAINT task_subtasks_task_id_fk
    FOREIGN KEY (task_id) REFERENCES tasks (id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT task_subtasks_completed_by_fk
    FOREIGN KEY (completed_by) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7) Commentaires tâche
-- ------------------------------------------------------------
CREATE TABLE task_comments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  task_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  content TEXT NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY task_comments_task_id_index (task_id),
  KEY task_comments_user_id_index (user_id),
  CONSTRAINT task_comments_task_id_fk
    FOREIGN KEY (task_id) REFERENCES tasks (id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT task_comments_user_id_fk
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8) Fichiers projet
-- ------------------------------------------------------------
CREATE TABLE project_files (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id BIGINT UNSIGNED NOT NULL,
  task_id BIGINT UNSIGNED NULL,
  uploaded_by BIGINT UNSIGNED NULL,
  logical_name VARCHAR(255) NULL,
  stored_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NULL,
  size BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY project_files_project_id_index (project_id),
  KEY project_files_task_id_index (task_id),
  KEY project_files_uploaded_by_index (uploaded_by),
  CONSTRAINT project_files_project_id_fk
    FOREIGN KEY (project_id) REFERENCES projects (id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT project_files_task_id_fk
    FOREIGN KEY (task_id) REFERENCES tasks (id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT project_files_uploaded_by_fk
    FOREIGN KEY (uploaded_by) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9) Timesheets
-- ------------------------------------------------------------
CREATE TABLE timesheets (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  project_id BIGINT UNSIGNED NOT NULL,
  task_id BIGINT UNSIGNED NULL,
  hours DECIMAL(6,2) NOT NULL,
  work_date DATE NOT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY timesheets_user_id_index (user_id),
  KEY timesheets_project_id_index (project_id),
  KEY timesheets_task_id_index (task_id),
  KEY timesheets_work_date_index (work_date),
  CONSTRAINT timesheets_user_id_fk
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT timesheets_project_id_fk
    FOREIGN KEY (project_id) REFERENCES projects (id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT timesheets_task_id_fk
    FOREIGN KEY (task_id) REFERENCES tasks (id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

////////
//////
///////////
////////////














CREATE TABLE users (
 id BIGINT PRIMARY KEY,
 name VARCHAR(255),
 email VARCHAR(255),
 password VARCHAR(255),
 role VARCHAR(32),
 is_active BOOLEAN
);

CREATE TABLE clients (
 id BIGINT PRIMARY KEY,
 name VARCHAR(255),
 email VARCHAR(255),
 phone VARCHAR(60),
 company VARCHAR(255)
);

CREATE TABLE projects (
 id BIGINT PRIMARY KEY,
 owner_id BIGINT,
 client_id BIGINT,
 name VARCHAR(255),
 status VARCHAR(32),
 priority VARCHAR(20),
 start_date DATE,
 due_date DATE
);

CREATE TABLE project_user (
 id BIGINT PRIMARY KEY,
 project_id BIGINT,
 user_id BIGINT,
 project_role VARCHAR(32)
);

CREATE TABLE tasks (
 id BIGINT PRIMARY KEY,
 project_id BIGINT,
 assigned_to BIGINT,
 title VARCHAR(255),
 status VARCHAR(32),
 priority VARCHAR(20)
);

CREATE TABLE task_subtasks (
 id BIGINT PRIMARY KEY,
 task_id BIGINT,
 completed_by BIGINT,
 title VARCHAR(255),
 is_completed BOOLEAN
);

CREATE TABLE task_comments (
 id BIGINT PRIMARY KEY,
 task_id BIGINT,
 user_id BIGINT,
 content TEXT
);

CREATE TABLE project_files (
 id BIGINT PRIMARY KEY,
 project_id BIGINT,
 task_id BIGINT,
 uploaded_by BIGINT
);

CREATE TABLE timesheets (
 id BIGINT PRIMARY KEY,
 user_id BIGINT,
 project_id BIGINT,
 task_id BIGINT,
 hours DECIMAL(6,2)
);

ALTER TABLE projects ADD FOREIGN KEY (owner_id) REFERENCES users(id);
ALTER TABLE projects ADD FOREIGN KEY (client_id) REFERENCES clients(id);

ALTER TABLE project_user ADD FOREIGN KEY (project_id) REFERENCES projects(id);
ALTER TABLE project_user ADD FOREIGN KEY (user_id) REFERENCES users(id);

ALTER TABLE tasks ADD FOREIGN KEY (project_id) REFERENCES projects(id);
ALTER TABLE tasks ADD FOREIGN KEY (assigned_to) REFERENCES users(id);

ALTER TABLE task_subtasks ADD FOREIGN KEY (task_id) REFERENCES tasks(id);
ALTER TABLE task_subtasks ADD FOREIGN KEY (completed_by) REFERENCES users(id);

ALTER TABLE task_comments ADD FOREIGN KEY (task_id) REFERENCES tasks(id);
ALTER TABLE task_comments ADD FOREIGN KEY (user_id) REFERENCES users(id);

ALTER TABLE project_files ADD FOREIGN KEY (project_id) REFERENCES projects(id);
ALTER TABLE project_files ADD FOREIGN KEY (task_id) REFERENCES tasks(id);
ALTER TABLE project_files ADD FOREIGN KEY (uploaded_by) REFERENCES users(id);

ALTER TABLE timesheets ADD FOREIGN KEY (user_id) REFERENCES users(id);
ALTER TABLE timesheets ADD FOREIGN KEY (project_id) REFERENCES projects(id);
ALTER TABLE timesheets ADD FOREIGN KEY (task_id) REFERENCES tasks(id);
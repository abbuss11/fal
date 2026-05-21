-- MPD Essentiel généré automatiquement
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

CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NULL,
  email VARCHAR(255) NULL,
  role VARCHAR(32) NULL DEFAULT 'member',
  is_active TINYINT(1) NULL DEFAULT 1,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clients (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NULL,
  email VARCHAR(255) NULL,
  company VARCHAR(255) NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE projects (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_id BIGINT UNSIGNED NOT NULL,
  KEY projects_owner_id_index (owner_id),
  client_id BIGINT UNSIGNED NULL,
  KEY projects_client_id_index (client_id),
  name VARCHAR(255) NULL,
  status VARCHAR(32) NULL DEFAULT 'todo',
  priority VARCHAR(20) NULL DEFAULT 'medium',
  start_date DATE NULL,
  due_date DATE NULL,
  PRIMARY KEY (id),
  CONSTRAINT projects_owner_id_fk FOREIGN KEY (owner_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT projects_client_id_fk FOREIGN KEY (client_id) REFERENCES clients (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE project_user (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id BIGINT UNSIGNED NOT NULL,
  KEY project_user_project_id_index (project_id),
  user_id BIGINT UNSIGNED NOT NULL,
  KEY project_user_user_id_index (user_id),
  project_role VARCHAR(32) NULL,
  is_active TINYINT(1) NULL DEFAULT 1,
  PRIMARY KEY (id),
  CONSTRAINT project_user_project_id_fk FOREIGN KEY (project_id) REFERENCES projects (id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT project_user_user_id_fk FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tasks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id BIGINT UNSIGNED NOT NULL,
  KEY tasks_project_id_index (project_id),
  assigned_to BIGINT UNSIGNED NULL,
  KEY tasks_assigned_to_index (assigned_to),
  title VARCHAR(255) NULL,
  status VARCHAR(32) NULL DEFAULT 'todo',
  priority VARCHAR(20) NULL DEFAULT 'medium',
  due_date DATETIME NULL,
  PRIMARY KEY (id),
  CONSTRAINT tasks_project_id_fk FOREIGN KEY (project_id) REFERENCES projects (id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tasks_assigned_to_fk FOREIGN KEY (assigned_to) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE task_subtasks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  task_id BIGINT UNSIGNED NOT NULL,
  KEY task_subtasks_task_id_index (task_id),
  title VARCHAR(255) NULL,
  is_completed TINYINT(1) NULL DEFAULT 0,
  completed_by BIGINT UNSIGNED NULL,
  KEY task_subtasks_completed_by_index (completed_by),
  PRIMARY KEY (id),
  CONSTRAINT task_subtasks_task_id_fk FOREIGN KEY (task_id) REFERENCES tasks (id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT task_subtasks_completed_by_fk FOREIGN KEY (completed_by) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE task_comments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  task_id BIGINT UNSIGNED NOT NULL,
  KEY task_comments_task_id_index (task_id),
  user_id BIGINT UNSIGNED NOT NULL,
  KEY task_comments_user_id_index (user_id),
  content TEXT NULL,
  PRIMARY KEY (id),
  CONSTRAINT task_comments_task_id_fk FOREIGN KEY (task_id) REFERENCES tasks (id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT task_comments_user_id_fk FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE project_files (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id BIGINT UNSIGNED NOT NULL,
  KEY project_files_project_id_index (project_id),
  task_id BIGINT UNSIGNED NULL,
  KEY project_files_task_id_index (task_id),
  uploaded_by BIGINT UNSIGNED NULL,
  KEY project_files_uploaded_by_index (uploaded_by),
  logical_name VARCHAR(255) NULL,
  file_path VARCHAR(255) NULL,
  PRIMARY KEY (id),
  CONSTRAINT project_files_project_id_fk FOREIGN KEY (project_id) REFERENCES projects (id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT project_files_task_id_fk FOREIGN KEY (task_id) REFERENCES tasks (id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT project_files_uploaded_by_fk FOREIGN KEY (uploaded_by) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE timesheets (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  KEY timesheets_user_id_index (user_id),
  project_id BIGINT UNSIGNED NOT NULL,
  KEY timesheets_project_id_index (project_id),
  task_id BIGINT UNSIGNED NULL,
  KEY timesheets_task_id_index (task_id),
  hours DECIMAL(6,2) NULL,
  work_date DATE NULL,
  PRIMARY KEY (id),
  CONSTRAINT timesheets_user_id_fk FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT timesheets_project_id_fk FOREIGN KEY (project_id) REFERENCES projects (id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT timesheets_task_id_fk FOREIGN KEY (task_id) REFERENCES tasks (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

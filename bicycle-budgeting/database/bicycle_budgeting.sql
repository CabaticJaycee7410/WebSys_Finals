CREATE DATABASE IF NOT EXISTS bicycle_budgeting CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bicycle_budgeting;

CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bicycles (
  bicycle_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  bicycle_name VARCHAR(150) NOT NULL,
  bicycle_type VARCHAR(80) NOT NULL,
  brand VARCHAR(120) NULL,
  model VARCHAR(120) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_bicycles_user (user_id),
  CONSTRAINT fk_bicycles_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projects (
  project_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  bicycle_id INT NULL,
  project_name VARCHAR(150) NOT NULL,
  budget_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  start_date DATE NULL,
  end_date DATE NULL,
  status ENUM('Planning','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Planning',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_projects_user (user_id),
  KEY idx_projects_bicycle (bicycle_id),
  CONSTRAINT fk_projects_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_projects_bicycle FOREIGN KEY (bicycle_id) REFERENCES bicycles(bicycle_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS builder_plans (
  project_id INT PRIMARY KEY,
  bicycle_type VARCHAR(80) NOT NULL,
  bicycle_brand VARCHAR(120) NULL,
  bicycle_model VARCHAR(120) NULL,
  build_name VARCHAR(150) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_builder_plans_project FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_build_parts (
  build_part_id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  component_key VARCHAR(60) NOT NULL,
  component_label VARCHAR(100) NOT NULL,
  product_title VARCHAR(255) NOT NULL,
  brand VARCHAR(120) NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  image_url TEXT NULL,
  product_url TEXT NULL,
  source VARCHAR(50) NOT NULL DEFAULT 'Lazada',
  provider_item_id VARCHAR(100) NULL,
  provider_sku_id VARCHAR(100) NULL,
  compatibility_json LONGTEXT NULL,
  compatibility_status ENUM('compatible','needs_review','incompatible') NOT NULL DEFAULT 'needs_review',
  forced_incompatible TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_project_build_component (project_id, component_key),
  KEY idx_build_parts_project (project_id),
  CONSTRAINT fk_project_build_parts_project FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parts_catalog (
  catalog_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  component_key VARCHAR(60) NOT NULL,
  product_title VARCHAR(255) NOT NULL,
  brand VARCHAR(120) NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  image_url TEXT NULL,
  product_url TEXT NULL,
  source VARCHAR(50) NOT NULL DEFAULT 'Manual',
  provider_item_id VARCHAR(100) NULL,
  provider_sku_id VARCHAR(100) NULL,
  compatibility_json LONGTEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_catalog_user (user_id),
  KEY idx_catalog_component (component_key),
  CONSTRAINT fk_catalog_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS actual_expenses (
  expense_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  project_id INT NULL,
  category VARCHAR(100) NOT NULL,
  description VARCHAR(255) NOT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  expense_date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_expenses_user (user_id),
  KEY idx_expenses_project (project_id),
  KEY idx_expenses_date (expense_date),
  CONSTRAINT fk_expenses_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_expenses_project FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS savings_records (
  saving_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  project_id INT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  saving_date DATE NOT NULL,
  notes VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_savings_user (user_id),
  KEY idx_savings_project (project_id),
  CONSTRAINT fk_savings_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_savings_project FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Legacy normalized catalog tables are retained for compatibility with older project data.
CREATE TABLE IF NOT EXISTS parts (
  part_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  part_name VARCHAR(255) NOT NULL,
  category VARCHAR(100) NOT NULL DEFAULT 'Other',
  brand VARCHAR(120) NULL,
  description TEXT NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  image_url TEXT NULL,
  product_url TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_parts_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_parts (
  project_part_id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  part_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  planned_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  status VARCHAR(30) NOT NULL DEFAULT 'Planned',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_project_parts_project (project_id),
  KEY idx_project_parts_part (part_id),
  CONSTRAINT fk_legacy_project_parts_project FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_legacy_project_parts_part FOREIGN KEY (part_id) REFERENCES parts(part_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

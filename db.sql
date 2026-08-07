CREATE DATABASE IF NOT EXISTS library
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE library;


CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(32) NOT NULL DEFAULT 'admin',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  last_login_ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(64) NOT NULL PRIMARY KEY,
  `value` LONGTEXT NOT NULL
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  description TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS books (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  book_name VARCHAR(255) NOT NULL,
  book_author VARCHAR(255) NULL,
  book_year INT NULL,
  description TEXT NULL,
  book_cover_path VARCHAR(500) NULL,
  book_pdf_path VARCHAR(500) NULL,

  read_count INT UNSIGNED NOT NULL DEFAULT 0,
  rating_avg DECIMAL(4,2) NOT NULL DEFAULT 0.00,
  rating_count INT UNSIGNED NOT NULL DEFAULT 0,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_books_category
    FOREIGN KEY (category_id) REFERENCES categories(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,

  CONSTRAINT chk_book_year
    CHECK (book_year IS NULL OR (book_year >= 1 AND book_year <= 2100))
) ENGINE=InnoDB;

CREATE INDEX idx_books_created_at ON books(created_at);
CREATE INDEX idx_books_category_id ON books(category_id);


CREATE TABLE IF NOT EXISTS book_categories (
  book_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (book_id, category_id),
  CONSTRAINT fk_bc_book
    FOREIGN KEY (book_id) REFERENCES books(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_bc_category
    FOREIGN KEY (category_id) REFERENCES categories(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_bc_category_id ON book_categories(category_id);

CREATE TABLE IF NOT EXISTS book_reads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  book_id INT UNSIGNED NOT NULL,
  visitor_hash CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_book_visitor (book_id, visitor_hash),
  CONSTRAINT fk_reads_book
    FOREIGN KEY (book_id) REFERENCES books(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS news (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  image_path VARCHAR(500) NULL,
  link_url VARCHAR(500) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_news_active_sort ON news(is_active, sort_order, id);


CREATE TABLE IF NOT EXISTS media_sections (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NULL,
  quote TEXT NOT NULL,
  author VARCHAR(255) NOT NULL,
  image_path VARCHAR(500) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_media_active_sort ON media_sections(is_active, sort_order, id);


INSERT INTO settings(`key`,`value`) VALUES
('site_title','Online Library'),
('logo_path','assets/img/logo.png'),
('navbar_json','[{"label":"Bosh sahifa","href":"index.php"},{"label":"Categoriyalar","href":"category_list.php"}]'),
('footer_contacts',''),
('footer_links_json','[{"label":"Home","href":"index.php"},{"label":"Admin","href":"admin/login.php"}]')
ON DUPLICATE KEY UPDATE value=VALUES(value);


CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  phone VARCHAR(20) UNIQUE NOT NULL,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','editor') DEFAULT 'editor',
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE books ADD INDEX idx_books_created (id);
ALTER TABLE book_categories ADD INDEX idx_bc_cat (category_id);
ALTER TABLE book_categories ADD INDEX idx_bc_book (book_id);


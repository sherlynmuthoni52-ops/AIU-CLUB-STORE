-- AIU Student Club Merch and Event Ticket Store
-- Import this file from phpMyAdmin: Import > Choose File > database.sql > Go
-- This file creates the schema AND seeds initial demo data.

CREATE DATABASE IF NOT EXISTS aiu_club_store
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE aiu_club_store;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('student', 'club_admin', 'super_admin') NOT NULL DEFAULT 'student'
) ENGINE=InnoDB;

CREATE TABLE clubs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  description TEXT,
  logo VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE club_admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  club_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_club (user_id, club_id),
  CONSTRAINT fk_club_admins_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_club_admins_club
    FOREIGN KEY (club_id) REFERENCES clubs(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  image VARCHAR(255),
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  category VARCHAR(100) NOT NULL,
  CONSTRAINT fk_products_club
    FOREIGN KEY (club_id) REFERENCES clubs(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE product_sizes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  size VARCHAR(20) NOT NULL,
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY unique_product_size (product_id, size),
  CONSTRAINT fk_product_sizes_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  venue VARCHAR(150) NOT NULL,
  `date` DATETIME NOT NULL,
  capacity INT UNSIGNED NOT NULL,
  ticket_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  poster VARCHAR(255),
  CONSTRAINT fk_events_club
    FOREIGN KEY (club_id) REFERENCES clubs(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  order_status ENUM('pending', 'processing', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  size VARCHAR(20) NULL,
  quantity INT UNSIGNED NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_order_items_order
    FOREIGN KEY (order_id) REFERENCES orders(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE tickets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  ticket_code VARCHAR(64) NOT NULL UNIQUE,
  qr_code VARCHAR(255),
  payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  checked_in TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_tickets_event
    FOREIGN KEY (event_id) REFERENCES events(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_tickets_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- A payment belongs to exactly one order OR one ticket. Leave the other ID NULL.
CREATE TABLE payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED NULL,
  ticket_id INT UNSIGNED NULL,
  amount DECIMAL(10,2) NOT NULL,
  method VARCHAR(50) NOT NULL,
  status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  reference VARCHAR(100) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_payments_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_payments_order
    FOREIGN KEY (order_id) REFERENCES orders(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_payments_ticket
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_payment_target
    CHECK ((order_id IS NOT NULL AND ticket_id IS NULL) OR
           (order_id IS NULL AND ticket_id IS NOT NULL))
) ENGINE=InnoDB;

CREATE INDEX idx_products_club ON products(club_id);
CREATE INDEX idx_events_club ON events(club_id);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_tickets_event ON tickets(event_id);
CREATE INDEX idx_tickets_user ON tickets(user_id);

-- ---------------------------------------------------------------------------
-- Seed data
-- ---------------------------------------------------------------------------

-- Insert clubs first (required for products and events).
INSERT INTO clubs (name, description, logo) VALUES
('Sports Club', 'Jerseys, caps, and match-day essentials.', NULL),
('Tech Club', 'Campus innovation, hackathons, and technology gear.', NULL),
('Drama Club', 'Creative performances and memorable shows.', NULL);

-- Insert sample products.
INSERT INTO products (club_id, name, price, image, stock, category) VALUES
(1, 'Sports Club T-Shirt', 1200.00, NULL, 25, 'Clothing'),
(1, 'AIU Club Cap', 800.00, NULL, 18, 'Accessories'),
(2, 'Tech Club Tote Bag', 700.00, NULL, 20, 'Accessories'),
(3, 'Drama Club Hoodie', 1800.00, NULL, 12, 'Clothing');

-- Insert sample product sizes.
INSERT INTO product_sizes (product_id, size, stock) VALUES
(1, 'S', 8), (1, 'M', 10), (1, 'L', 7),
(2, 'S', 6), (2, 'M', 6), (2, 'L', 6),
(3, 'Standard', 20),
(4, 'M', 6), (4, 'L', 6);

-- Insert sample events.
INSERT INTO events (club_id, title, description, venue, `date`, capacity, ticket_price) VALUES
(3, 'AIU Talent Night', 'An evening of music, drama, dance, and spoken word.', 'Main Auditorium', '2026-08-15 17:00:00', 150, 300.00),
(2, 'Campus Hackathon', 'A one-day student coding challenge and innovation workshop.', 'Innovation Lab', '2026-08-22 09:00:00', 60, 0.00),
(1, 'Interclub Finals', 'Support AIU teams in the interclub sports finals.', 'AIU Sports Ground', '2026-08-30 14:00:00', 300, 150.00);

-- Insert sample users.
-- Demo password for ALL three accounts is "password123".
-- Hash below was generated with password_hash('password123', PASSWORD_DEFAULT).
INSERT INTO users (name, email, password, role) VALUES
('Super Admin', 'super@aiu.edu', '$2y$10$nJqBwW5YDTQIXWKyYjidj.v9hkQoG6q2VfGbQGhQA.yghv02Pvb.a', 'super_admin'),
('John Club Admin', 'john@aiu.edu', '$2y$10$nJqBwW5YDTQIXWKyYjidj.v9hkQoG6q2VfGbQGhQA.yghv02Pvb.a', 'club_admin'),
('Jane Student', 'jane@aiu.edu', '$2y$10$nJqBwW5YDTQIXWKyYjidj.v9hkQoG6q2VfGbQGhQA.yghv02Pvb.a', 'student');

-- Insert sample club allocations.
-- Assumes: user ID 2 = John Club Admin, club IDs 1 = Sports Club, 2 = Tech Club.
INSERT INTO club_admins (user_id, club_id) VALUES
(2, 1),
(2, 2);

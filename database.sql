CREATE DATABASE IF NOT EXISTS bamstay
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE bamstay;

CREATE TABLE IF NOT EXISTS inquiries (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  email VARCHAR(254) NOT NULL DEFAULT '',
  room_id VARCHAR(20) NOT NULL,
  bedspace_id VARCHAR(30) NULL,
  message VARCHAR(1000) NOT NULL DEFAULT '',
  status ENUM('new', 'contacted', 'closed') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX inquiries_created_at_idx (created_at),
  INDEX inquiries_room_bed_idx (room_id, bedspace_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

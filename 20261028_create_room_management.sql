USE bamstay;

CREATE TABLE IF NOT EXISTS property_rooms (
  id VARCHAR(20) NOT NULL PRIMARY KEY,
  room_number VARCHAR(60) NOT NULL,
  name VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  features JSON NOT NULL,
  monthly_rate DECIMAL(10,2) NOT NULL DEFAULT 1800.00,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY property_rooms_number_unique (room_number),
  INDEX property_rooms_status_name_idx (status, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS property_bedspaces (
  id VARCHAR(30) NOT NULL PRIMARY KEY,
  room_id VARCHAR(20) NOT NULL,
  label VARCHAR(60) NOT NULL,
  status ENUM('available', 'maintenance') NOT NULL DEFAULT 'available',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY property_bedspaces_room_label_unique (room_id, label),
  CONSTRAINT property_bedspaces_room_fk
    FOREIGN KEY (room_id) REFERENCES property_rooms (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX property_bedspaces_room_status_idx (room_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO property_rooms (id, room_number, name, description, features, monthly_rate, status) VALUES
  ('room-1', 'Room 1', 'Room 1', 'A Bright and Cozy Bunk Bed Room Beside the Balcony', JSON_ARRAY('Ceiling Fan', 'Storage Space', 'Electrical Outlet', 'Natural Lighting'), 1800.00, 'active'),
  ('room-2', 'Room 2', 'Room 2', 'A Spacious and Bright Room Beside the Window and Balcony', JSON_ARRAY('Ceiling Fan', 'Study Table', 'Electrical Outlet', 'Natural Lighting'), 1800.00, 'active'),
  ('room-3', 'Room 3', 'Room 3', 'A Cozy and Comfortable Room Near the Shared Kitchen and Living Room', JSON_ARRAY('Ceiling Fan', 'Storage Space', 'Study Table'), 1800.00, 'active'),
  ('room-4', 'Room 4', 'Room 4', 'A cozy and minimalist space designed for a simple, comfortable, and relaxing living experience.', JSON_ARRAY(), 1800.00, 'active'),
  ('room-5', 'Room 5', 'Room 5', 'A Cozy and Convenient Room Near the Shared Kitchen.', JSON_ARRAY(), 1800.00, 'active');

INSERT IGNORE INTO property_bedspaces (id, room_id, label, status) VALUES
  ('room-1-b1', 'room-1', 'Bed A', 'available'), ('room-1-b2', 'room-1', 'Bed B', 'available'),
  ('room-1-b3', 'room-1', 'Bed C', 'available'), ('room-1-b4', 'room-1', 'Bed D', 'available'),
  ('room-1-b5', 'room-1', 'Bed E', 'available'), ('room-1-b6', 'room-1', 'Bed F', 'available'),
  ('room-2-b1', 'room-2', 'Bed A', 'available'), ('room-2-b2', 'room-2', 'Bed B', 'available'),
  ('room-2-b3', 'room-2', 'Bed C', 'available'), ('room-2-b4', 'room-2', 'Bed D', 'available'),
  ('room-3-b1', 'room-3', 'Bed A', 'available'), ('room-3-b2', 'room-3', 'Bed B', 'available'),
  ('room-3-b3', 'room-3', 'Bed C', 'available'), ('room-3-b4', 'room-3', 'Bed D', 'available'),
  ('room-3-b5', 'room-3', 'Bed E', 'available'), ('room-3-b6', 'room-3', 'Bed F', 'available'),
  ('room-3-b7', 'room-3', 'Bed G', 'available'), ('room-3-b8', 'room-3', 'Bed H', 'available'),
  ('room-4-b1', 'room-4', 'Bed A', 'available'), ('room-4-b2', 'room-4', 'Bed B', 'available'),
  ('room-4-b3', 'room-4', 'Bed C', 'available'), ('room-4-b4', 'room-4', 'Bed D', 'available'),
  ('room-5-b1', 'room-5', 'Bed A', 'available'), ('room-5-b2', 'room-5', 'Bed B', 'available'),
  ('room-5-b3', 'room-5', 'Bed C', 'available'), ('room-5-b4', 'room-5', 'Bed D', 'available');

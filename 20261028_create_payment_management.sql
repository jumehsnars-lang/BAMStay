USE bamstay;

CREATE TABLE IF NOT EXISTS admin_users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY admin_users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tenants (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  must_change_password TINYINT(1) NOT NULL DEFAULT 1,
  phone VARCHAR(30) NOT NULL,
  emergency_contact VARCHAR(150) NOT NULL DEFAULT '',
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  room_id VARCHAR(20) NOT NULL,
  bedspace_id VARCHAR(30) NOT NULL,
  active_bedspace VARCHAR(60) GENERATED ALWAYS AS (
    CASE WHEN status = 'active' THEN CONCAT(room_id, ':', bedspace_id) ELSE NULL END
  ) STORED,
  move_in_date DATE NOT NULL,
  monthly_rent DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY tenants_email_unique (email),
  UNIQUE KEY tenants_active_bedspace_unique (active_bedspace),
  INDEX tenants_status_name_idx (status, full_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rental_obligations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  billing_month DATE NOT NULL,
  amount_due DECIMAL(10,2) NOT NULL,
  late_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  due_date DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY rental_obligations_tenant_month_unique (tenant_id, billing_month),
  CONSTRAINT rental_obligations_tenant_fk FOREIGN KEY (tenant_id) REFERENCES tenants (id),
  INDEX rental_obligations_due_idx (due_date, tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
  id CHAR(36) NOT NULL PRIMARY KEY,
  receipt_number VARCHAR(40) NOT NULL,
  obligation_id BIGINT UNSIGNED NOT NULL,
  amount_received DECIMAL(10,2) NOT NULL,
  payment_method ENUM('Cash', 'GCash', 'Maya', 'Bank Transfer', 'Other') NOT NULL,
  reference_number VARCHAR(100) NULL,
  payment_date DATE NOT NULL,
  status ENUM('pending', 'confirmed', 'reversed', 'void') NOT NULL DEFAULT 'pending',
  notes VARCHAR(1000) NOT NULL DEFAULT '',
  idempotency_key CHAR(36) NOT NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  verified_by BIGINT UNSIGNED NULL,
  verified_at DATETIME NULL,
  reversed_by BIGINT UNSIGNED NULL,
  reversed_at DATETIME NULL,
  reversal_reason VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY payments_receipt_unique (receipt_number),
  UNIQUE KEY payments_idempotency_unique (idempotency_key),
  UNIQUE KEY payments_method_reference_unique (payment_method, reference_number),
  CONSTRAINT payments_obligation_fk FOREIGN KEY (obligation_id) REFERENCES rental_obligations (id),
  CONSTRAINT payments_creator_fk FOREIGN KEY (created_by) REFERENCES admin_users (id),
  CONSTRAINT payments_verifier_fk FOREIGN KEY (verified_by) REFERENCES admin_users (id),
  CONSTRAINT payments_reverser_fk FOREIGN KEY (reversed_by) REFERENCES admin_users (id),
  INDEX payments_obligation_status_idx (obligation_id, status),
  INDEX payments_date_status_idx (payment_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  payment_id CHAR(36) NOT NULL,
  actor_id BIGINT UNSIGNED NOT NULL,
  action ENUM('created', 'edited', 'confirmed', 'reversed', 'voided') NOT NULL,
  before_state JSON NULL,
  after_state JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT payment_events_payment_fk FOREIGN KEY (payment_id) REFERENCES payments (id),
  CONSTRAINT payment_events_actor_fk FOREIGN KEY (actor_id) REFERENCES admin_users (id),
  INDEX payment_events_payment_date_idx (payment_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

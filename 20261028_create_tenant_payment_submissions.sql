USE bamstay;

CREATE TABLE IF NOT EXISTS payment_submissions (
  id CHAR(36) NOT NULL PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  billing_month DATE NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_method ENUM('Cash', 'GCash') NOT NULL,
  reference_number VARCHAR(100) NULL,
  payment_date DATE NOT NULL,
  status ENUM('pending', 'accepted', 'declined') NOT NULL DEFAULT 'pending',
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  review_note VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY payment_submissions_reference_unique (payment_method, reference_number),
  CONSTRAINT payment_submissions_tenant_fk FOREIGN KEY (tenant_id) REFERENCES tenants (id),
  CONSTRAINT payment_submissions_reviewer_fk FOREIGN KEY (reviewed_by) REFERENCES admin_users (id),
  INDEX payment_submissions_tenant_status_idx (tenant_id, status, created_at),
  INDEX payment_submissions_review_idx (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration 002: Admin authentication table
CREATE TABLE IF NOT EXISTS admins (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(80)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login    TIMESTAMP NULL
);

-- Default admin: username=admin, password=niit@admin2025
-- IMPORTANT: Change this password immediately after first login!
INSERT INTO admins (username, password_hash) VALUES
('admin', '$argon2id$v=19$m=65536,t=4,p=1$SUxvQm9aUmI4Y2VZajcxSA$/qWevHkYwPksv9eE1m5zpdhQMPNJQgSh+UwKgtYPHbc');

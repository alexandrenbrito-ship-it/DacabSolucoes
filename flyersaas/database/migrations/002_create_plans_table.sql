-- Migration 002: Create plans table
CREATE TABLE IF NOT EXISTS plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    duration_days INT NOT NULL DEFAULT 30,
    images_limit INT NOT NULL DEFAULT 10,
    flyers_limit INT NOT NULL DEFAULT 2,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default plans
INSERT INTO plans (name, description, price, duration_days, images_limit, flyers_limit, status) VALUES
('Básico', 'Plano ideal para pequenos mercados', 29.90, 30, 50, 2, 'active'),
('Profissional', 'Para mercados em crescimento', 59.90, 30, 200, 5, 'active'),
('Enterprise', 'Solução completa para grandes redes', 149.90, 30, 1000, 20, 'active')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Migration 007: Create system_config table
CREATE TABLE IF NOT EXISTS system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default configurations
INSERT INTO system_config (config_key, config_value, description) VALUES
('site_name', 'FlyerSaaS', 'Nome do sistema'),
('site_email', 'contato@flyersaas.com', 'E-mail de contato'),
('maintenance_mode', '0', 'Modo de manutenção (0=desligado, 1=ligado)'),
('max_upload_size', '5242880', 'Tamanho máximo de upload em bytes (5MB)')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);

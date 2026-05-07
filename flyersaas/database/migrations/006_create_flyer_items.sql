-- Migration 006: Create flyer_items table
CREATE TABLE IF NOT EXISTS flyer_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flyer_id INT NOT NULL,
    product_name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    image_id INT,
    position_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (flyer_id) REFERENCES flyers(id) ON DELETE CASCADE,
    FOREIGN KEY (image_id) REFERENCES gallery_images(id) ON DELETE SET NULL,
    INDEX idx_flyer_id (flyer_id),
    INDEX idx_position (position_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

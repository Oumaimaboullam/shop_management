-- Create draft orders table
CREATE TABLE IF NOT EXISTS draft_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    client_id INT NULL,
    document_type ENUM('sale', 'invoice', 'quote') NOT NULL,
    payment_mode_id INT NULL,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    status ENUM('draft', 'confirmed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
);

-- Create draft items table
CREATE TABLE IF NOT EXISTS draft_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    draft_id INT NOT NULL,
    article_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (draft_id) REFERENCES draft_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES articles(id)
);

-- If table already exists, modify it to allow NULL client_id
ALTER TABLE draft_orders MODIFY COLUMN client_id INT NULL;
ALTER TABLE draft_orders DROP FOREIGN KEY IF EXISTS draft_orders_ibfk_2;
ALTER TABLE draft_orders ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL;

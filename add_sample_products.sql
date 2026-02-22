-- Add sample products for testing POS search functionality
-- Run this script in your MySQL database

USE shop_management;

-- Insert sample categories if they don't exist
INSERT IGNORE INTO categories (id, name) VALUES 
(1, 'Electronics'),
(2, 'Accessories'),
(3, 'Office Supplies');

-- Insert sample products
INSERT IGNORE INTO articles (id, barcode, reference, category_id, name, description, purchase_price, sale_price, wholesale, is_active) VALUES 
(1, '123456789', 'LPT-001', 1, 'Laptop Computer', 'High-performance laptop for business use', 420.00, 599.99, 499.99, 1),
(2, '234567890', 'MOU-001', 2, 'Wireless Mouse', 'Ergonomic wireless mouse', 21.00, 29.99, 19.99, 1),
(3, '345678901', 'KEY-001', 2, 'USB Keyboard', 'Mechanical USB keyboard', 35.00, 49.99, 39.99, 1),
(4, '456789012', 'MON-001', 1, 'Monitor 24"', '24-inch LED monitor', 140.00, 199.99, 159.99, 1),
(5, '567890123', 'CAM-001', 2, 'Webcam HD', 'High-definition webcam', 56.00, 79.99, 64.99, 1),
(6, '678901234', 'HEAD-001', 2, 'Headphones', 'Noise-cancelling headphones', 63.00, 89.99, 69.99, 1),
(7, '789012345', 'USB-001', 3, 'USB Flash Drive 32GB', '32GB USB flash drive', 11.00, 15.99, 12.99, 1),
(8, '890123456', 'PWR-001', 2, 'Power Bank', 'Portable power bank 10000mAh', 28.00, 39.99, 29.99, 1),
(9, '901234567', 'CAB-001', 3, 'HDMI Cable', 'High-speed HDMI cable 2m', 7.00, 9.99, 7.99, 1),
(10, '012345678', 'DOC-001', 3, 'Document Scanner', 'Portable document scanner', 105.00, 149.99, 119.99, 1);

-- Insert stock records
INSERT IGNORE INTO stock (article_id, quantity) VALUES 
(1, 10),
(2, 25),
(3, 15),
(4, 8),
(5, 12),
(6, 20),
(7, 50),
(8, 30),
(9, 40),
(10, 6);

-- Create a test user if not exists (password: admin)
INSERT IGNORE INTO users (id, name, username, password_hash, role, is_active) VALUES 
(1, 'Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);

-- Verify the data was inserted
SELECT 
    a.id, 
    a.barcode, 
    a.name, 
    a.sale_price, 
    a.wholesale, 
    COALESCE(s.quantity, 0) as stock
FROM articles a
LEFT JOIN stock s ON a.id = s.article_id
WHERE a.is_active = 1
ORDER BY a.name;

-- SQL to add sample data for testing analytics
-- Run this in your MySQL database to populate with test data

USE shop_management;

-- Insert sample categories
INSERT IGNORE INTO categories (id, name) VALUES 
(1, 'Electronics'),
(2, 'Clothing'),
(3, 'Food & Beverages'),
(4, 'Office Supplies'),
(5, 'Home & Garden');

-- Insert sample articles/products
INSERT IGNORE INTO articles (id, barcode, reference, category_id, name, description, purchase_price, sale_price, tax_rate, stock_alert_level, is_active) VALUES 
(1, '1001', 'LAP001', 1, 'Laptop Pro 15"', 'High-performance laptop with 16GB RAM', 800.00, 1200.00, 20.00, 10, TRUE),
(2, '1002', 'LAP002', 1, 'Wireless Mouse', 'Ergonomic wireless mouse', 15.00, 25.00, 20.00, 50, TRUE),
(3, '1003', 'LAP003', 1, 'USB-C Hub', '7-in-1 USB hub with HDMI', 30.00, 45.00, 20.00, 25, TRUE),
(4, '2001', 'CLO001', 2, 'Cotton T-Shirt', 'Premium cotton t-shirt', 8.00, 15.00, 20.00, 100, TRUE),
(5, '2002', 'CLO002', 2, 'Denim Jeans', 'Classic blue denim jeans', 20.00, 35.00, 20.00, 50, TRUE),
(6, '2003', 'CLO003', 2, 'Winter Jacket', 'Warm winter jacket', 40.00, 65.00, 20.00, 30, TRUE),
(7, '3001', 'FOO001', 3, 'Coffee Beans', 'Premium arabica coffee beans', 12.00, 20.00, 10.00, 20, TRUE),
(8, '3002', 'FOO002', 3, 'Green Tea', 'Organic green tea leaves', 8.00, 15.00, 10.00, 30, TRUE),
(9, '3003', 'FOO003', 3, 'Mineral Water', 'Natural mineral water', 0.50, 1.50, 10.00, 200, TRUE),
(10, '4001', 'OFF001', 4, 'Notebook Set', 'Premium notebook set', 5.00, 12.00, 20.00, 40, TRUE),
(11, '4002', 'OFF002', 4, 'Desk Lamp', 'LED desk lamp with adjustable brightness', 15.00, 30.00, 20.00, 20, TRUE),
(12, '5001', 'HOM001', 5, 'Plant Pot', 'Ceramic plant pot with drainage', 8.00, 15.00, 20.00, 60, TRUE),
(13, '5002', 'HOM002', 5, 'Garden Tools Set', 'Basic gardening tools set', 25.00, 45.00, 20.00, 15, TRUE);

-- Insert stock data
INSERT IGNORE INTO stock (article_id, quantity, location) VALUES 
(1, 25, 'Warehouse A'),
(2, 75, 'Warehouse A'),
(3, 40, 'Warehouse A'),
(4, 120, 'Warehouse B'),
(5, 65, 'Warehouse B'),
(6, 35, 'Warehouse B'),
(7, 45, 'Warehouse C'),
(8, 80, 'Warehouse C'),
(9, 200, 'Warehouse C'),
(10, 55, 'Warehouse D'),
(11, 30, 'Warehouse D'),
(12, 85, 'Warehouse E'),
(13, 25, 'Warehouse E');

-- Insert sample suppliers
INSERT IGNORE INTO suppliers (id, name, phone, email, address, balance) VALUES 
(1, 'TechSupply Co', '+1-555-0123', 'info@techsupply.com', '123 Tech Street, Silicon Valley, CA', 1500.00),
(2, 'Fashion Wholesale', '+1-555-0456', 'sales@fashionwholesale.com', '456 Fashion Ave, New York, NY', 800.00),
(3, 'Food Distributors Inc', '+1-555-0789', 'orders@fooddist.com', '789 Food Blvd, Chicago, IL', 500.00),
(4, 'Office Depot Supplier', '+1-555-0321', 'contact@officedepot.com', '321 Office Park, Houston, TX', 300.00),
(5, 'Home & Garden Co', '+1-555-0654', 'info@homegarden.com', '654 Garden Lane, Portland, OR', 600.00);

-- Insert sample clients
INSERT IGNORE INTO clients (id, name, phone, email, address, balance) VALUES 
(1, 'John Smith', '+1-555-0101', 'john.smith@email.com', '123 Main St, Anytown, USA', 0.00),
(2, 'Sarah Johnson', '+1-555-0102', 'sarah.j@email.com', '456 Oak Ave, Somewhere, USA', 150.00),
(3, 'Mike Davis', '+1-555-0103', 'mike.davis@email.com', '789 Pine Rd, Elsewhere, USA', 0.00),
(4, 'Emily Wilson', '+1-555-0104', 'emily.w@email.com', '321 Elm St, Nowhere, USA', 75.00),
(5, 'Robert Brown', '+1-555-0105', 'robert.brown@email.com', '654 Maple Dr, Anywhere, USA', 0.00),
(6, 'Lisa Anderson', '+1-555-0106', 'lisa.a@email.com', '987 Cedar Ln, Everywhere, USA', 200.00),
(7, 'David Martinez', '+1-555-0107', 'david.m@email.com', '147 Birch Way, Someplace, USA', 0.00),
(8, 'Jennifer Taylor', '+1-555-0108', 'jennifer.t@email.com', '258 Spruce Ct, Anycity, USA', 100.00);

-- Insert sample users
INSERT IGNORE INTO users (id, name, username, password_hash, role, is_active) VALUES 
(1, 'Admin User', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE),
(2, 'Manager User', 'manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', TRUE),
(3, 'Cashier User', 'cashier', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier', TRUE);

-- Insert payment modes
INSERT IGNORE INTO payment_modes (id, name) VALUES 
(1, 'Cash'),
(2, 'Credit Card'),
(3, 'Bank Transfer'),
(4, 'Check');

-- Insert sample purchases (last 6 months)
INSERT IGNORE INTO purchases (id, supplier_id, invoice_number, total_amount, paid_amount, status, type_of_payment, created_at) VALUES 
(1, 1, 'PUR-001', 5000.00, 5000.00, 'paid', 'transfer', '2024-08-15 10:30:00'),
(2, 2, 'PUR-002', 3000.00, 2000.00, 'partial', 'check', '2024-09-10 14:20:00'),
(3, 3, 'PUR-003', 2500.00, 2500.00, 'paid', 'cash', '2024-10-05 09:15:00'),
(4, 4, 'PUR-004', 1800.00, 1800.00, 'paid', 'transfer', '2024-11-12 11:45:00'),
(5, 5, 'PUR-005', 3200.00, 1600.00, 'partial', 'check', '2024-12-08 16:30:00'),
(6, 1, 'PUR-006', 4500.00, 4500.00, 'paid', 'transfer', '2025-01-10 13:20:00'),
(7, 2, 'PUR-007', 2800.00, 2800.00, 'paid', 'cash', '2025-01-25 10:45:00'),
(8, 3, 'PUR-008', 2200.00, 2200.00, 'paid', 'transfer', '2025-02-05 15:30:00');

-- Insert purchase items
INSERT IGNORE INTO purchase_items (purchase_id, article_id, quantity, unit_price, total_price) VALUES 
(1, 1, 5, 800.00, 4000.00),
(1, 2, 20, 15.00, 300.00),
(1, 3, 10, 30.00, 300.00),
(2, 4, 50, 8.00, 400.00),
(2, 5, 30, 20.00, 600.00),
(2, 6, 20, 40.00, 800.00),
(3, 7, 30, 12.00, 360.00),
(3, 8, 40, 8.00, 320.00),
(3, 9, 100, 0.50, 50.00),
(4, 10, 15, 5.00, 75.00),
(4, 11, 20, 15.00, 300.00),
(5, 12, 25, 8.00, 200.00),
(5, 13, 15, 25.00, 375.00),
(6, 1, 8, 800.00, 6400.00),
(6, 2, 25, 15.00, 375.00),
(7, 4, 35, 8.00, 280.00),
(7, 5, 40, 20.00, 800.00),
(8, 7, 20, 12.00, 240.00),
(8, 8, 25, 8.00, 200.00);

-- Insert sample sales (last 6 months with varied dates)
INSERT IGNORE INTO sales (id, client_id, user_id, document_type, payment_mode_id, invoice_number, subtotal_amount, discount_type, discount_value, discount_amount, total_amount, paid_amount, status, created_at) VALUES 
(1, 1, 1, 'sale', 1, 'SALE-001', 2400.00, 'percent', 5.00, 120.00, 2280.00, 2280.00, 'paid', '2024-08-20 14:30:00'),
(2, 2, 2, 'sale', 2, 'SALE-002', 1800.00, 'fixed', 100.00, 100.00, 1700.00, 1700.00, 'paid', '2024-09-15 16:45:00'),
(3, 3, 3, 'sale', 1, 'SALE-003', 3200.00, 'percent', 10.00, 320.00, 2880.00, 2000.00, 'partial', '2024-10-10 11:20:00'),
(4, 4, 1, 'sale', 3, 'SALE-004', 1500.00, NULL, 0.00, 0.00, 1500.00, 1500.00, 'paid', '2024-11-05 13:15:00'),
(5, 5, 2, 'sale', 2, 'SALE-005', 2800.00, 'percent', 5.00, 140.00, 2660.00, 2660.00, 'paid', '2024-12-12 10:30:00'),
(6, 6, 3, 'sale', 1, 'SALE-006', 950.00, NULL, 0.00, 0.00, 950.00, 950.00, 'paid', '2024-12-18 15:45:00'),
(7, 7, 1, 'sale', 4, 'SALE-007', 4200.00, 'percent', 8.00, 336.00, 3864.00, 3864.00, 'paid', '2025-01-08 12:20:00'),
(8, 8, 2, 'sale', 3, 'SALE-008', 2100.00, 'fixed', 150.00, 150.00, 1950.00, 1950.00, 'paid', '2025-01-22 14:10:00'),
(9, 1, 3, 'sale', 2, 'SALE-009', 1800.00, NULL, 0.00, 0.00, 1800.00, 900.00, 'partial', '2025-02-05 16:30:00'),
(10, 3, 1, 'sale', 1, 'SALE-010', 3500.00, 'percent', 5.00, 175.00, 3325.00, 3325.00, 'paid', '2025-02-14 09:45:00');

-- Insert sale items
INSERT IGNORE INTO sale_items (sale_id, article_id, quantity, unit_price, total_price) VALUES 
(1, 1, 1, 1200.00, 1200.00),
(1, 2, 2, 25.00, 50.00),
(1, 3, 1, 45.00, 45.00),
(2, 4, 3, 15.00, 45.00),
(2, 5, 2, 35.00, 70.00),
(2, 6, 1, 65.00, 65.00),
(3, 1, 2, 1200.00, 2400.00),
(4, 2, 1, 25.00, 25.00),
(4, 7, 2, 20.00, 40.00),
(4, 8, 1, 15.00, 15.00),
(5, 4, 4, 15.00, 60.00),
(5, 5, 3, 35.00, 105.00),
(5, 6, 2, 65.00, 130.00),
(6, 7, 2, 20.00, 40.00),
(6, 8, 3, 15.00, 45.00),
(6, 9, 5, 1.50, 7.50),
(7, 1, 3, 1200.00, 3600.00),
(7, 2, 2, 25.00, 50.00),
(8, 10, 1, 30.00, 30.00),
(8, 11, 1, 15.00, 15.00),
(9, 1, 1, 1200.00, 1200.00),
(9, 3, 2, 20.00, 40.00),
(10, 2, 1, 25.00, 25.00),
(10, 4, 2, 15.00, 30.00);

-- Insert some settings
INSERT IGNORE INTO settings (key_name, value) VALUES 
('currency_symbol', 'DH'),
('company_name', 'Shop Management System'),
('tax_rate', '20.00');

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_sales_created_at ON sales(created_at);
CREATE INDEX IF NOT EXISTS idx_purchases_created_at ON purchases(created_at);
CREATE INDEX IF NOT EXISTS idx_sales_client_id ON sales(client_id);
CREATE INDEX IF NOT EXISTS idx_sales_user_id ON sales(user_id);
CREATE INDEX IF NOT EXISTS idx_purchases_supplier_id ON purchases(supplier_id);

-- Display summary of inserted data
SELECT 'Categories:' as table_name, COUNT(*) as record_count FROM categories
UNION ALL
SELECT 'Articles:', COUNT(*) FROM articles  
UNION ALL
SELECT 'Suppliers:', COUNT(*) FROM suppliers
UNION ALL
SELECT 'Clients:', COUNT(*) FROM clients
UNION ALL
SELECT 'Sales:', COUNT(*) FROM sales
UNION ALL
SELECT 'Purchases:', COUNT(*) FROM purchases
UNION ALL
SELECT 'Stock Records:', COUNT(*) FROM stock;

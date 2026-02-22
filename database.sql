-- Database: shop_management
CREATE DATABASE IF NOT EXISTS shop_management;
USE shop_management;

-- 1. USERS & ACCESS
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'cashier') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. ARTICLES / PRODUCTS
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    parent_id INT NULL,
    FOREIGN KEY (parent_id) REFERENCES categories(id)
);

CREATE TABLE IF NOT EXISTS articles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    barcode VARCHAR(50) UNIQUE,
    reference VARCHAR(100),
    category_id INT,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    purchase_price DECIMAL(10,2) NOT NULL,
    percentage_of_sales_profit DECIMAL(5,2) DEFAULT 0,
    wholesale_percentage DECIMAL(5,2) DEFAULT 0,
    wholesale DECIMAL(10,2) DEFAULT 0,
    sale_price DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
    unit VARCHAR(20),
    stock_alert_level INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- 3. STOCK MANAGEMENT
CREATE TABLE IF NOT EXISTS stock (
    id INT PRIMARY KEY AUTO_INCREMENT,
    article_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    location VARCHAR(100),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id)
);

CREATE TABLE IF NOT EXISTS stock_movements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    article_id INT NOT NULL,
    type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    source ENUM('purchase', 'sale', 'return', 'manual') NOT NULL,
    reference_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id)
);

-- 4. SUPPLIERS
CREATE TABLE IF NOT EXISTS suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    balance DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. PURCHASES
CREATE TABLE IF NOT EXISTS purchases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_id INT NOT NULL,
    invoice_number VARCHAR(100),
    total_amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending', 'paid', 'partial') DEFAULT 'pending',
    type_of_payment ENUM('cash', 'check', 'transfer'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

CREATE TABLE IF NOT EXISTS purchase_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    purchase_id INT NOT NULL,
    article_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id),
    FOREIGN KEY (article_id) REFERENCES articles(id)
);

-- 6. CLIENTS
CREATE TABLE IF NOT EXISTS clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    balance DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. SALES
CREATE TABLE IF NOT EXISTS sales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT,
    user_id INT NOT NULL,
    document_type ENUM('sale', 'invoice', 'quote') NOT NULL,
    payment_mode_id INT,
    invoice_number VARCHAR(100),
    subtotal_amount DECIMAL(10,2) NOT NULL,
    discount_type ENUM('percent', 'fixed'),
    discount_value DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('draft', 'confirmed', 'paid', 'partial', 'cancelled') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS sale_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sale_id INT NOT NULL,
    article_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (article_id) REFERENCES articles(id)
);

-- 8. RETURNS
CREATE TABLE IF NOT EXISTS returns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    document_type ENUM('sale', 'invoice', 'purchase') NOT NULL,
    document_id INT NOT NULL,
    status ENUM('draft', 'confirmed') DEFAULT 'draft',
    total_amount DECIMAL(10,2) NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS return_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    return_id INT NOT NULL,
    article_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (return_id) REFERENCES returns(id),
    FOREIGN KEY (article_id) REFERENCES articles(id)
);

-- 9. PAYMENTS & PAYMENT MODES
CREATE TABLE IF NOT EXISTS payment_modes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entity_type ENUM('sale', 'invoice', 'purchase', 'charge') NOT NULL,
    entity_id INT NOT NULL,
    payment_mode_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_mode_id) REFERENCES payment_modes(id)
);

-- 10. PAYMENT SITUATIONS
CREATE TABLE IF NOT EXISTS payment_situations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entity_type ENUM('client', 'supplier') NOT NULL,
    entity_id INT NOT NULL,
    balance DECIMAL(10,2) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 11. DRAFT ORDERS
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
    FOREIGN KEY (client_id) REFERENCES clients(id)
);

-- 12. DRAFT ITEMS
CREATE TABLE IF NOT EXISTS draft_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    draft_id INT NOT NULL,
    article_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (draft_id) REFERENCES draft_orders(id),
    FOREIGN KEY (article_id) REFERENCES articles(id)
);

-- 11. EXPENSES / CHARGES
CREATE TABLE IF NOT EXISTS charges (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    category VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS charge_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    charge_id INT NOT NULL,
    payment_mode_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    FOREIGN KEY (charge_id) REFERENCES charges(id),
    FOREIGN KEY (payment_mode_id) REFERENCES payment_modes(id)
);

-- 12. MISC / DIVERS
CREATE TABLE IF NOT EXISTS favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    article_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (article_id) REFERENCES articles(id),
    UNIQUE KEY unique_favorite (user_id, article_id)
);

CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    key_name VARCHAR(100) UNIQUE NOT NULL,
    value TEXT,
    type VARCHAR(50) DEFAULT 'string'
);

-- 13. AUDIT / HISTORY
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    old_value TEXT,
    new_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert default payment modes if they don't exist
INSERT INTO payment_modes (name) 
SELECT * FROM (SELECT 'Cash') AS tmp WHERE NOT EXISTS (SELECT name FROM payment_modes WHERE name = 'Cash') LIMIT 1;
INSERT INTO payment_modes (name) 
SELECT * FROM (SELECT 'Check') AS tmp WHERE NOT EXISTS (SELECT name FROM payment_modes WHERE name = 'Check') LIMIT 1;
INSERT INTO payment_modes (name) 
SELECT * FROM (SELECT 'Bank Transfer') AS tmp WHERE NOT EXISTS (SELECT name FROM payment_modes WHERE name = 'Bank Transfer') LIMIT 1;
INSERT INTO payment_modes (name) 
SELECT * FROM (SELECT 'Credit Card') AS tmp WHERE NOT EXISTS (SELECT name FROM payment_modes WHERE name = 'Credit Card') LIMIT 1;

-- Insert default admin user (password: admin123)
INSERT INTO users (name, username, password_hash, role) 
SELECT 'Administrator', 'admin', '$2y$10$4.bb/1.bb/1.bb/1.bb/1.bb/1.bb/1.bb/1.bb/1.bb/1.bb/1.', 'admin' 
WHERE NOT EXISTS (SELECT username FROM users WHERE username = 'admin');

-- Insert default settings
INSERT INTO settings (key_name, value, type) 
SELECT * FROM (SELECT 'company_name', 'My Shop', 'string') AS tmp WHERE NOT EXISTS (SELECT key_name FROM settings WHERE key_name = 'company_name') LIMIT 1;
INSERT INTO settings (key_name, value, type) 
SELECT * FROM (SELECT 'tax_rate_default', '20', 'decimal') AS tmp WHERE NOT EXISTS (SELECT key_name FROM settings WHERE key_name = 'tax_rate_default') LIMIT 1;
INSERT INTO settings (key_name, value, type) 
SELECT * FROM (SELECT 'currency_symbol', '$', 'string') AS tmp WHERE NOT EXISTS (SELECT key_name FROM settings WHERE key_name = 'currency_symbol') LIMIT 1;
INSERT INTO settings (key_name, value, type) 
SELECT * FROM (SELECT 'receipt_header', 'Thank you for your purchase!', 'string') AS tmp WHERE NOT EXISTS (SELECT key_name FROM settings WHERE key_name = 'receipt_header') LIMIT 1;

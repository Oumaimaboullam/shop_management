-- Add purchase_payments table if it doesn't exist
CREATE TABLE IF NOT EXISTS purchase_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    purchase_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_type ENUM('cash', 'check', 'transfer', 'card') NOT NULL,
    payment_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id)
);

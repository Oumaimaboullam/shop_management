-- Add missing company settings for Moroccan invoice
USE shop_management;

-- Insert company information settings individually
INSERT IGNORE INTO settings (key_name, value, type) VALUES ('company_name', 'Société Maroc', 'string');
INSERT IGNORE INTO settings (key_name, value, type) VALUES ('company_address', '123 Rue Business, Casablanca, Maroc', 'string');
INSERT IGNORE INTO settings (key_name, value, type) VALUES ('company_phone', '+212 5XX XXX XXX', 'string');
INSERT IGNORE INTO settings (key_name, value, type) VALUES ('company_email', 'info@societe.ma', 'string');
INSERT IGNORE INTO settings (key_name, value, type) VALUES ('company_website', 'www.societe.ma', 'string');
INSERT IGNORE INTO settings (key_name, value, type) VALUES ('company_rc', 'RC: 123456', 'string');
INSERT IGNORE INTO settings (key_name, value, type) VALUES ('company_ice', 'ICE: 00123456789', 'string');
INSERT IGNORE INTO settings (key_name, value, type) VALUES ('company_cnss', 'CNSS: 1234567', 'string');
INSERT IGNORE INTO settings (key_name, value, type) VALUES ('company_bank', 'Banque: BMCE - RIB: 007 780 0001234567800001 18', 'string');
INSERT IGNORE INTO settings (key_name, value, type) VALUES ('company_logo', '', 'string');

-- Update existing currency symbol to DH for Moroccan currency
UPDATE settings SET value = 'DH' WHERE key_name = 'currency_symbol' AND value = '$';

-- Display confirmation
SELECT 'Company settings added successfully!' as message;

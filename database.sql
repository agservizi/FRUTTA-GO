-- database.sql - Schema e dati iniziali per Frutta Go

-- Crea database
CREATE DATABASE IF NOT EXISTS frutta_go CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE frutta_go;

-- Tabella utenti
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'operator') NOT NULL DEFAULT 'operator',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabella categorie
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0
);

-- Tabella prodotti
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category_id INT,
    unit_type ENUM('kg', 'pz', 'cassetta') NOT NULL DEFAULT 'kg',
    price_sale DECIMAL(10,2) NOT NULL,
    price_cost DECIMAL(10,2) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_favorite BOOLEAN DEFAULT FALSE,
    image_url VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Tabella movimenti magazzino
CREATE TABLE inventory_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    type ENUM('in', 'out') NOT NULL,
    qty DECIMAL(10,3) NOT NULL,
    unit_type ENUM('kg', 'pz', 'cassetta') NOT NULL,
    cost_total DECIMAL(10,2) NULL,
    reason ENUM('vendita', 'spreco', 'rettifica') NULL,
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabella vendite
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    total DECIMAL(10,2) NOT NULL,
    discount_type ENUM('percentage', 'fixed') NULL,
    discount_value DECIMAL(10,2) NULL,
    payment_method ENUM('cash', 'card', 'mixed') DEFAULT 'cash',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabella righe vendita
CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    qty DECIMAL(10,3) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Tabella fornitori
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(150) NULL,
    address VARCHAR(255) NULL,
    note TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabella acquisti
CREATE TABLE purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NULL,
    total DECIMAL(10,2) NOT NULL,
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT NOT NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabella righe acquisto
CREATE TABLE purchase_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    product_id INT NOT NULL,
    qty DECIMAL(10,3) NOT NULL,
    unit_type ENUM('kg', 'pz', 'cassetta') NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Tabella impostazioni applicazione
CREATE TABLE app_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    value_text TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Indici
CREATE INDEX idx_products_name ON products(name);
CREATE INDEX idx_sales_created_at ON sales(created_at);
CREATE INDEX idx_inventory_created_at_product ON inventory_movements(created_at, product_id);
CREATE INDEX idx_suppliers_name ON suppliers(name);
CREATE INDEX idx_purchases_created_at ON purchases(created_at);

-- Impostazioni iniziali
INSERT INTO app_settings (key_name, value_text) VALUES
('store_name', 'Frutta Go'),
('currency_symbol', '€'),
('vat_rate', '4'),
('low_stock_threshold', '5'),
('receipt_footer', 'Grazie per aver acquistato da Frutta Go!')
ON DUPLICATE KEY UPDATE value_text = VALUES(value_text);

-- Dati iniziali
INSERT INTO categories (name, sort_order) VALUES
('Frutta', 1),
('Verdura', 2),
('Altri', 3);

INSERT INTO products (name, category_id, unit_type, price_sale, price_cost, is_active, is_favorite) VALUES
('Mele', 1, 'kg', 2.50, 1.80, TRUE, TRUE),
('Banane', 1, 'kg', 1.80, 1.20, TRUE, TRUE),
('Pomodori', 2, 'kg', 2.20, 1.50, TRUE, TRUE),
('Zucchine', 2, 'kg', 1.90, 1.30, TRUE, FALSE),
('Arance', 1, 'kg', 2.00, 1.40, TRUE, FALSE),
('Carote', 2, 'kg', 1.50, 1.00, TRUE, FALSE);

-- Crea utente admin (password: admin123)
INSERT INTO users (name, email, password_hash, role) VALUES
('Admin', 'admin@fruttago.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
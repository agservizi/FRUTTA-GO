-- fruttago.session.sql
-- Checklist SQL multi-negozio (Hostinger) - Frutta Go
-- Eseguire in phpMyAdmin/Hostinger sul DB PRODUZIONE corretto

-- =============================
-- 0) PRE-CHECK DATABASE
-- =============================
SELECT DATABASE() AS current_database;

-- Se non vedi frutta_go (o il DB giusto), fermati qui e seleziona il DB corretto.
-- USE frutta_go;

-- =============================
-- 1) VERIFICA STATO ATTUALE
-- =============================
SELECT table_name
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN (
    'stores','store_settings','users','categories','products',
    'inventory_movements','sales','sale_items','suppliers','purchases','purchase_items'
  )
ORDER BY table_name;

SELECT table_name, column_name, column_type, is_nullable, column_default
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name IN (
    'users','categories','products','inventory_movements','sales',
    'sale_items','suppliers','purchases','purchase_items'
  )
  AND column_name = 'store_id'
ORDER BY table_name;

-- =============================
-- 2) TABELLE BASE MULTI-STORE
-- =============================
CREATE TABLE IF NOT EXISTS stores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  code VARCHAR(100) NULL,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_store_code (code)
);

CREATE TABLE IF NOT EXISTS store_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  store_id INT NOT NULL,
  key_name VARCHAR(100) NOT NULL,
  value_text TEXT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_store_key (store_id, key_name)
);

INSERT INTO stores (id, name, code, is_active)
VALUES (1, 'Frutta Go', 'main', TRUE)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  is_active = VALUES(is_active);

-- =============================
-- 3) ALLINEAMENTO store_id SU TABELLE BUSINESS
-- =============================
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'store_id') = 0,
  'ALTER TABLE users ADD COLUMN store_id INT NOT NULL DEFAULT 1',
  'SELECT "users.store_id già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE() AND table_name = 'categories' AND column_name = 'store_id') = 0,
  'ALTER TABLE categories ADD COLUMN store_id INT NOT NULL DEFAULT 1',
  'SELECT "categories.store_id già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'store_id') = 0,
  'ALTER TABLE products ADD COLUMN store_id INT NOT NULL DEFAULT 1',
  'SELECT "products.store_id già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE() AND table_name = 'inventory_movements' AND column_name = 'store_id') = 0,
  'ALTER TABLE inventory_movements ADD COLUMN store_id INT NOT NULL DEFAULT 1',
  'SELECT "inventory_movements.store_id già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE() AND table_name = 'sales' AND column_name = 'store_id') = 0,
  'ALTER TABLE sales ADD COLUMN store_id INT NOT NULL DEFAULT 1',
  'SELECT "sales.store_id già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE() AND table_name = 'sale_items' AND column_name = 'store_id') = 0,
  'ALTER TABLE sale_items ADD COLUMN store_id INT NOT NULL DEFAULT 1',
  'SELECT "sale_items.store_id già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE() AND table_name = 'suppliers' AND column_name = 'store_id') = 0,
  'ALTER TABLE suppliers ADD COLUMN store_id INT NOT NULL DEFAULT 1',
  'SELECT "suppliers.store_id già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE() AND table_name = 'purchases' AND column_name = 'store_id') = 0,
  'ALTER TABLE purchases ADD COLUMN store_id INT NOT NULL DEFAULT 1',
  'SELECT "purchases.store_id già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE() AND table_name = 'purchase_items' AND column_name = 'store_id') = 0,
  'ALTER TABLE purchase_items ADD COLUMN store_id INT NOT NULL DEFAULT 1',
  'SELECT "purchase_items.store_id già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Safety: assicura default store_id=1 su eventuali record legacy
UPDATE users SET store_id = 1 WHERE store_id IS NULL OR store_id = 0;
UPDATE categories SET store_id = 1 WHERE store_id IS NULL OR store_id = 0;
UPDATE products SET store_id = 1 WHERE store_id IS NULL OR store_id = 0;
UPDATE inventory_movements SET store_id = 1 WHERE store_id IS NULL OR store_id = 0;
UPDATE sales SET store_id = 1 WHERE store_id IS NULL OR store_id = 0;
UPDATE sale_items SET store_id = 1 WHERE store_id IS NULL OR store_id = 0;
UPDATE suppliers SET store_id = 1 WHERE store_id IS NULL OR store_id = 0;
UPDATE purchases SET store_id = 1 WHERE store_id IS NULL OR store_id = 0;
UPDATE purchase_items SET store_id = 1 WHERE store_id IS NULL OR store_id = 0;

-- Crea store placeholder per eventuali store_id legacy non presenti in stores
INSERT INTO stores (id, name, code, is_active)
SELECT t.store_id, CONCAT('Store #', t.store_id), CONCAT('legacy-', t.store_id), TRUE
FROM (
  SELECT DISTINCT store_id FROM users
  UNION SELECT DISTINCT store_id FROM categories
  UNION SELECT DISTINCT store_id FROM products
  UNION SELECT DISTINCT store_id FROM inventory_movements
  UNION SELECT DISTINCT store_id FROM sales
  UNION SELECT DISTINCT store_id FROM sale_items
  UNION SELECT DISTINCT store_id FROM suppliers
  UNION SELECT DISTINCT store_id FROM purchases
  UNION SELECT DISTINCT store_id FROM purchase_items
) t
LEFT JOIN stores s ON s.id = t.store_id
WHERE t.store_id IS NOT NULL
  AND t.store_id > 0
  AND s.id IS NULL;

-- =============================
-- 3B) HARDENING INTEGRITÀ (INDEX + FK)
-- =============================

-- INDEX store_id
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = DATABASE() AND table_name = 'users' AND index_name = 'idx_users_store_id') = 0,
  'ALTER TABLE users ADD INDEX idx_users_store_id (store_id)',
  'SELECT "idx_users_store_id già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = DATABASE() AND table_name = 'categories' AND index_name = 'idx_categories_store_id') = 0,
  'ALTER TABLE categories ADD INDEX idx_categories_store_id (store_id)',
  'SELECT "idx_categories_store_id già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = DATABASE() AND table_name = 'products' AND index_name = 'idx_products_store_id') = 0,
  'ALTER TABLE products ADD INDEX idx_products_store_id (store_id)',
  'SELECT "idx_products_store_id già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = DATABASE() AND table_name = 'inventory_movements' AND index_name = 'idx_inventory_movements_store_id') = 0,
  'ALTER TABLE inventory_movements ADD INDEX idx_inventory_movements_store_id (store_id)',
  'SELECT "idx_inventory_movements_store_id già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = DATABASE() AND table_name = 'sales' AND index_name = 'idx_sales_store_id') = 0,
  'ALTER TABLE sales ADD INDEX idx_sales_store_id (store_id)',
  'SELECT "idx_sales_store_id già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = DATABASE() AND table_name = 'sale_items' AND index_name = 'idx_sale_items_store_id') = 0,
  'ALTER TABLE sale_items ADD INDEX idx_sale_items_store_id (store_id)',
  'SELECT "idx_sale_items_store_id già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = DATABASE() AND table_name = 'suppliers' AND index_name = 'idx_suppliers_store_id') = 0,
  'ALTER TABLE suppliers ADD INDEX idx_suppliers_store_id (store_id)',
  'SELECT "idx_suppliers_store_id già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = DATABASE() AND table_name = 'purchases' AND index_name = 'idx_purchases_store_id') = 0,
  'ALTER TABLE purchases ADD INDEX idx_purchases_store_id (store_id)',
  'SELECT "idx_purchases_store_id già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = DATABASE() AND table_name = 'purchase_items' AND index_name = 'idx_purchase_items_store_id') = 0,
  'ALTER TABLE purchase_items ADD INDEX idx_purchase_items_store_id (store_id)',
  'SELECT "idx_purchase_items_store_id già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- FOREIGN KEY store_id -> stores(id)
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.table_constraints
   WHERE table_schema = DATABASE() AND table_name = 'users' AND constraint_name = 'fk_users_store') = 0,
  'ALTER TABLE users ADD CONSTRAINT fk_users_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE',
  'SELECT "fk_users_store già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.table_constraints
   WHERE table_schema = DATABASE() AND table_name = 'categories' AND constraint_name = 'fk_categories_store') = 0,
  'ALTER TABLE categories ADD CONSTRAINT fk_categories_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE',
  'SELECT "fk_categories_store già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.table_constraints
   WHERE table_schema = DATABASE() AND table_name = 'products' AND constraint_name = 'fk_products_store') = 0,
  'ALTER TABLE products ADD CONSTRAINT fk_products_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE',
  'SELECT "fk_products_store già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.table_constraints
   WHERE table_schema = DATABASE() AND table_name = 'inventory_movements' AND constraint_name = 'fk_inventory_movements_store') = 0,
  'ALTER TABLE inventory_movements ADD CONSTRAINT fk_inventory_movements_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE',
  'SELECT "fk_inventory_movements_store già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.table_constraints
   WHERE table_schema = DATABASE() AND table_name = 'sales' AND constraint_name = 'fk_sales_store') = 0,
  'ALTER TABLE sales ADD CONSTRAINT fk_sales_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE',
  'SELECT "fk_sales_store già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.table_constraints
   WHERE table_schema = DATABASE() AND table_name = 'sale_items' AND constraint_name = 'fk_sale_items_store') = 0,
  'ALTER TABLE sale_items ADD CONSTRAINT fk_sale_items_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE',
  'SELECT "fk_sale_items_store già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.table_constraints
   WHERE table_schema = DATABASE() AND table_name = 'suppliers' AND constraint_name = 'fk_suppliers_store') = 0,
  'ALTER TABLE suppliers ADD CONSTRAINT fk_suppliers_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE',
  'SELECT "fk_suppliers_store già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.table_constraints
   WHERE table_schema = DATABASE() AND table_name = 'purchases' AND constraint_name = 'fk_purchases_store') = 0,
  'ALTER TABLE purchases ADD CONSTRAINT fk_purchases_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE',
  'SELECT "fk_purchases_store già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.table_constraints
   WHERE table_schema = DATABASE() AND table_name = 'purchase_items' AND constraint_name = 'fk_purchase_items_store') = 0,
  'ALTER TABLE purchase_items ADD CONSTRAINT fk_purchase_items_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE',
  'SELECT "fk_purchase_items_store già presente"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================
-- 4) DEFAULT SETTINGS PER STORE MAIN
-- =============================
INSERT INTO store_settings (store_id, key_name, value_text)
VALUES
  (1, 'store_name', 'Frutta Go'),
  (1, 'currency_symbol', '€'),
  (1, 'vat_rate', '4'),
  (1, 'low_stock_threshold', '5'),
  (1, 'receipt_footer', 'Grazie per aver acquistato da Frutta Go!')
ON DUPLICATE KEY UPDATE value_text = VALUES(value_text);

-- =============================
-- 5) TEST RAPIDO: SECONDO NEGOZIO
-- =============================
INSERT INTO stores (name, code, is_active)
VALUES ('Frutta Go - Secondo Negozio', 'demo2', TRUE)
ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = VALUES(is_active);

-- Utente admin per demo2 (password: admin123)
INSERT INTO users (store_id, name, email, password_hash, role)
SELECT s.id, 'Admin Demo2', 'admin+demo2@fruttago.com',
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'
FROM stores s
WHERE s.code = 'demo2'
  AND NOT EXISTS (
    SELECT 1 FROM users u WHERE u.email = 'admin+demo2@fruttago.com'
  );

-- Categoria test per demo2
INSERT INTO categories (store_id, name, sort_order)
SELECT s.id, 'Categoria Test Demo2', 1
FROM stores s
WHERE s.code = 'demo2'
  AND NOT EXISTS (
    SELECT 1 FROM categories c
    WHERE c.store_id = s.id AND c.name = 'Categoria Test Demo2'
  );

-- Prodotto test per demo2
INSERT INTO products (store_id, name, category_id, unit_type, price_sale, price_cost, is_active, is_favorite)
SELECT s.id,
       'Prodotto Test Demo2',
       (SELECT c.id FROM categories c WHERE c.store_id = s.id AND c.name = 'Categoria Test Demo2' LIMIT 1),
       'kg', 9.90, 6.50, TRUE, FALSE
FROM stores s
WHERE s.code = 'demo2'
  AND NOT EXISTS (
    SELECT 1 FROM products p
    WHERE p.store_id = s.id AND p.name = 'Prodotto Test Demo2'
  );

INSERT INTO store_settings (store_id, key_name, value_text)
SELECT s.id, 'store_name', 'Frutta Go - Secondo Negozio'
FROM stores s
WHERE s.code = 'demo2'
ON DUPLICATE KEY UPDATE value_text = VALUES(value_text);

-- =============================
-- 6) VERIFICA ISOLAMENTO (OUTPUT CHECK)
-- =============================
SELECT id, name, code, is_active FROM stores ORDER BY id;

SELECT
  s.code,
  (SELECT COUNT(*) FROM users u WHERE u.store_id = s.id) AS users_count,
  (SELECT COUNT(*) FROM categories c WHERE c.store_id = s.id) AS categories_count,
  (SELECT COUNT(*) FROM products p WHERE p.store_id = s.id) AS products_count,
  (SELECT COUNT(*) FROM sales sa WHERE sa.store_id = s.id) AS sales_count
FROM stores s
ORDER BY s.id;

-- Credenziali test login:
-- store_code: main  | email: admin@fruttago.com        | password: admin123
-- store_code: demo2 | email: admin+demo2@fruttago.com  | password: admin123

-- =============================
-- 7) CLEANUP TEST (OPZIONALE)
-- =============================
-- Scommenta solo se vuoi rimuovere i dati demo2
-- DELETE FROM products WHERE store_id = (SELECT id FROM stores WHERE code='demo2') AND name='Prodotto Test Demo2';
-- DELETE FROM categories WHERE store_id = (SELECT id FROM stores WHERE code='demo2') AND name='Categoria Test Demo2';
-- DELETE FROM users WHERE email='admin+demo2@fruttago.com';
-- DELETE FROM store_settings WHERE store_id = (SELECT id FROM stores WHERE code='demo2') AND key_name='store_name';
-- DELETE FROM stores WHERE code='demo2';

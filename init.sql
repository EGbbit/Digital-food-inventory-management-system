CREATE DATABASE IF NOT EXISTS food_inventory;
USE food_inventory;

CREATE TABLE IF NOT EXISTS users (
	id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(100) NOT NULL,
	email VARCHAR(100) UNIQUE NOT NULL,
	password VARCHAR(255) NOT NULL,
	phone VARCHAR(20),
	role ENUM('admin', 'waiter', 'chef', 'manager') NOT NULL,
	is_active TINYINT(1) DEFAULT 1,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ingredients (
	id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(120) NOT NULL,
	category VARCHAR(80),
	unit VARCHAR(20) NOT NULL,
	current_stock DECIMAL(10,2) NOT NULL DEFAULT 0,
	reorder_level DECIMAL(10,2) NOT NULL DEFAULT 0,
	unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
	is_active TINYINT(1) DEFAULT 1,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS menu_items (
	id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(120) NOT NULL,
	category VARCHAR(80),
	selling_price DECIMAL(10,2) NOT NULL,
	is_available TINYINT(1) DEFAULT 1,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
	id INT AUTO_INCREMENT PRIMARY KEY,
	order_number VARCHAR(40) UNIQUE NOT NULL,
	waiter_id INT NOT NULL,
	table_number VARCHAR(20),
	status ENUM('pending', 'preparing', 'served', 'cancelled') DEFAULT 'pending',
	total_amount DECIMAL(10,2) DEFAULT 0,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (waiter_id) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS order_items (
	id INT AUTO_INCREMENT PRIMARY KEY,
	order_id INT NOT NULL,
	menu_item_id INT NOT NULL,
	quantity INT NOT NULL DEFAULT 1,
	unit_price DECIMAL(10,2) NOT NULL,
	line_total DECIMAL(10,2) NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
	FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS recipe_ingredients (
	id INT AUTO_INCREMENT PRIMARY KEY,
	menu_item_id INT NOT NULL,
	ingredient_id INT NOT NULL,
	quantity_required DECIMAL(10,2) NOT NULL,
	FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
	FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE RESTRICT,
	UNIQUE KEY unique_recipe_line (menu_item_id, ingredient_id)
);

CREATE TABLE IF NOT EXISTS stock_movements (
	id INT AUTO_INCREMENT PRIMARY KEY,
	ingredient_id INT NOT NULL,
	movement_type ENUM('stock_in', 'usage', 'adjustment', 'wastage') NOT NULL,
	quantity DECIMAL(10,2) NOT NULL,
	reference_type ENUM('purchase', 'order', 'manual', 'wastage') DEFAULT 'manual',
	reference_id INT NULL,
	notes VARCHAR(255),
	created_by INT NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE RESTRICT,
	FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS wastage_logs (
	id INT AUTO_INCREMENT PRIMARY KEY,
	ingredient_id INT NOT NULL,
	quantity DECIMAL(10,2) NOT NULL,
	reason VARCHAR(120) NOT NULL,
	logged_by INT NOT NULL,
	logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE RESTRICT,
	FOREIGN KEY (logged_by) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS alerts (
	id INT AUTO_INCREMENT PRIMARY KEY,
	ingredient_id INT NOT NULL,
	alert_type ENUM('low_stock', 'out_of_stock') NOT NULL,
	message VARCHAR(255) NOT NULL,
	is_resolved TINYINT(1) DEFAULT 0,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	resolved_at TIMESTAMP NULL,
	FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chef_stock_notes (
	id INT AUTO_INCREMENT PRIMARY KEY,
	ingredient_id INT NOT NULL,
	chef_id INT NOT NULL,
	observed_stock DECIMAL(10,2) NOT NULL,
	reorder_level_snapshot DECIMAL(10,2) NOT NULL DEFAULT 0,
	expected_expiry_date DATE NULL,
	shelf_life_days INT NULL,
	urgency ENUM('normal', 'watch', 'urgent') NOT NULL DEFAULT 'watch',
	comment VARCHAR(300) NOT NULL,
	is_acknowledged TINYINT(1) NOT NULL DEFAULT 0,
	acknowledged_by INT NULL,
	acknowledged_at TIMESTAMP NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_chef_stock_notes_created (created_at),
	INDEX idx_chef_stock_notes_ack (is_acknowledged),
	FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE,
	FOREIGN KEY (chef_id) REFERENCES users(id) ON DELETE RESTRICT,
	FOREIGN KEY (acknowledged_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS predictive_reports (
	id INT AUTO_INCREMENT PRIMARY KEY,
	report_month DATE NOT NULL UNIQUE,
	report_label VARCHAR(40) NOT NULL,
	report_body TEXT NOT NULL,
	generation_mode ENUM('auto', 'manual') NOT NULL DEFAULT 'auto',
	generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

SET @is_fresh_install = (SELECT CASE WHEN COUNT(*) = 0 THEN 1 ELSE 0 END FROM users);

INSERT INTO users (name, email, password, phone, role, is_active)
SELECT 'System Admin', 'admin@gmail.com', '$2y$10$A4VMPIE9/yhPJsWUldudoOYETb57EnzLmAmVMMGyFSRHPRwZORM9O', '555-1001', 'admin', 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE role = 'admin');

INSERT INTO users (name, email, password, phone, role, is_active)
SELECT 'Mia Waiter', 'waiter@gmail.com', '$2y$10$A4VMPIE9/yhPJsWUldudoOYETb57EnzLmAmVMMGyFSRHPRwZORM9O', '555-1002', 'waiter', 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE role = 'waiter');

INSERT INTO users (name, email, password, phone, role, is_active)
SELECT 'Liam Chef', 'chef@gmail.com', '$2y$10$A4VMPIE9/yhPJsWUldudoOYETb57EnzLmAmVMMGyFSRHPRwZORM9O', '555-1003', 'chef', 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE role = 'chef');

INSERT INTO users (name, email, password, phone, role, is_active)
SELECT 'Noah Manager', 'manager@gmail.com', '$2y$10$A4VMPIE9/yhPJsWUldudoOYETb57EnzLmAmVMMGyFSRHPRwZORM9O', '555-1004', 'manager', 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE role = 'manager');

INSERT INTO ingredients (name, category, unit, current_stock, reorder_level, unit_cost)
SELECT 'Rice', 'Dry Goods', 'kg', 60, 15, 2.40
WHERE @is_fresh_install = 1;

INSERT INTO ingredients (name, category, unit, current_stock, reorder_level, unit_cost)
SELECT 'Chicken Breast', 'Meat', 'kg', 25, 8, 8.90
WHERE @is_fresh_install = 1;

INSERT INTO ingredients (name, category, unit, current_stock, reorder_level, unit_cost)
SELECT 'Tomato', 'Vegetables', 'kg', 18, 7, 3.20
WHERE @is_fresh_install = 1;

INSERT INTO ingredients (name, category, unit, current_stock, reorder_level, unit_cost)
SELECT 'Cooking Oil', 'Pantry', 'liter', 30, 10, 4.50
WHERE @is_fresh_install = 1;

INSERT INTO menu_items (name, category, selling_price, is_available)
SELECT 'Chicken Fried Rice', 'Main Course', 18.00, 1
WHERE @is_fresh_install = 1;

INSERT INTO menu_items (name, category, selling_price, is_available)
SELECT 'Tomato Pasta', 'Main Course', 16.50, 1
WHERE @is_fresh_install = 1;

INSERT INTO orders (order_number, waiter_id, table_number, status, total_amount)
SELECT 'ORD-001', id, 'T1', 'served', 34.50
FROM users
WHERE role = 'waiter' AND @is_fresh_install = 1
ORDER BY id ASC
LIMIT 1;

INSERT INTO orders (order_number, waiter_id, table_number, status, total_amount)
SELECT 'ORD-002', id, 'T4', 'preparing', 18.00
FROM users
WHERE role = 'waiter' AND @is_fresh_install = 1
ORDER BY id ASC
LIMIT 1;

INSERT INTO alerts (ingredient_id, alert_type, message, is_resolved)
SELECT 2, 'low_stock', 'Chicken Breast is nearing reorder level.', 0
WHERE @is_fresh_install = 1;

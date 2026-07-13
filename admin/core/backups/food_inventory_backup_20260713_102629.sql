-- FoodFlow database backup
-- Generated at: 2026-07-13 10:26:29

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table: alerts
-- ----------------------------
DROP TABLE IF EXISTS `alerts`;
CREATE TABLE `alerts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ingredient_id` int NOT NULL,
  `alert_type` enum('low_stock','out_of_stock') NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_resolved` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ingredient_id` (`ingredient_id`),
  CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `alerts` (`id`, `ingredient_id`, `alert_type`, `message`, `is_resolved`, `created_at`, `resolved_at`) VALUES ('1', '2', 'low_stock', 'Chicken Breast is nearing reorder level.', '1', '2026-06-21 23:52:56', '2026-06-22 23:16:48');
INSERT INTO `alerts` (`id`, `ingredient_id`, `alert_type`, `message`, `is_resolved`, `created_at`, `resolved_at`) VALUES ('2', '6', 'low_stock', 'Salmon Fillet is below reorder level.', '1', '2026-06-24 14:42:44', '2026-06-30 17:30:32');
INSERT INTO `alerts` (`id`, `ingredient_id`, `alert_type`, `message`, `is_resolved`, `created_at`, `resolved_at`) VALUES ('3', '7', 'low_stock', 'Cheese stock nearing minimum threshold.', '1', '2026-06-24 14:42:44', '2026-06-30 17:30:42');
INSERT INTO `alerts` (`id`, `ingredient_id`, `alert_type`, `message`, `is_resolved`, `created_at`, `resolved_at`) VALUES ('4', '7', 'out_of_stock', 'Cheese is out of stock.', '1', '2026-07-01 23:31:13', '2026-07-02 17:12:29');

-- ----------------------------
-- Table: chef_stock_notes
-- ----------------------------
DROP TABLE IF EXISTS `chef_stock_notes`;
CREATE TABLE `chef_stock_notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ingredient_id` int NOT NULL,
  `chef_id` int NOT NULL,
  `observed_stock` decimal(10,2) NOT NULL,
  `reorder_level_snapshot` decimal(10,2) NOT NULL DEFAULT '0.00',
  `suggested_restock_amount` decimal(10,2) DEFAULT NULL,
  `expected_expiry_date` date DEFAULT NULL,
  `shelf_life_days` int DEFAULT NULL,
  `urgency` enum('normal','watch','urgent') NOT NULL DEFAULT 'watch',
  `comment` varchar(300) NOT NULL,
  `is_acknowledged` tinyint(1) NOT NULL DEFAULT '0',
  `acknowledged_by` int DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chef_stock_notes_created` (`created_at`),
  KEY `idx_chef_stock_notes_ack` (`is_acknowledged`),
  KEY `ingredient_id` (`ingredient_id`),
  KEY `chef_id` (`chef_id`),
  KEY `acknowledged_by` (`acknowledged_by`),
  CONSTRAINT `chef_stock_notes_ibfk_1` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chef_stock_notes_ibfk_2` FOREIGN KEY (`chef_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chef_stock_notes_ibfk_3` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('1', '6', '3', '0.15', '0.02', NULL, '2026-06-10', '60', 'watch', 'restock', '0', NULL, NULL, '2026-06-29 11:22:53');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('2', '8', '3', '0.01', '0.03', NULL, '2026-06-05', '20', 'urgent', 'restock', '0', NULL, NULL, '2026-06-29 11:45:09');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('3', '4', '3', '0.10', '0.04', NULL, '2026-06-06', '30', 'urgent', 'expiry risk', '0', NULL, NULL, '2026-06-29 13:10:07');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('4', '4', '3', '0.10', '0.04', NULL, '2026-06-06', '30', 'urgent', 'expiry risk', '0', NULL, NULL, '2026-06-29 13:10:22');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('5', '4', '3', '0.10', '0.04', NULL, '2026-06-06', '30', 'urgent', 'expiry risk', '0', NULL, NULL, '2026-06-29 13:10:37');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('6', '4', '3', '0.10', '0.04', NULL, '2026-06-06', '30', 'urgent', 'expiry risk', '0', NULL, NULL, '2026-06-29 13:10:52');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('7', '4', '3', '0.10', '0.04', NULL, '2026-06-06', '30', 'urgent', 'expiry risk', '0', NULL, NULL, '2026-06-29 13:11:07');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('8', '4', '3', '0.10', '0.04', NULL, '2026-06-06', '30', 'urgent', 'expiry risk', '0', NULL, NULL, '2026-06-29 13:11:22');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('9', '4', '3', '0.10', '0.04', NULL, '2026-06-06', '30', 'urgent', 'expiry risk', '0', NULL, NULL, '2026-06-29 13:11:37');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('10', '4', '3', '0.10', '0.04', NULL, '2026-06-06', '30', 'urgent', 'expiry risk', '0', NULL, NULL, '2026-06-29 13:11:52');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('11', '4', '3', '0.10', '0.04', NULL, '2026-06-06', '30', 'urgent', 'expiry risk', '0', NULL, NULL, '2026-06-29 13:12:07');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('12', '4', '3', '0.10', '0.04', NULL, '2026-06-06', '30', 'urgent', 'expiry risk', '0', NULL, NULL, '2026-06-29 13:12:22');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('13', '4', '3', '0.10', '0.04', NULL, '2026-06-06', '30', 'urgent', 'expiry risk', '1', '12', '2026-07-02 17:06:53', '2026-06-29 13:12:37');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('14', '2', '3', '0.02', '0.03', NULL, '2026-06-30', '30', 'urgent', 'reorder', '0', NULL, NULL, '2026-06-30 16:02:11');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('15', '2', '3', '0.02', '0.03', NULL, '2026-06-30', '30', 'urgent', 'reorder', '0', NULL, NULL, '2026-06-30 16:02:26');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('16', '2', '3', '0.02', '0.03', NULL, '2026-06-30', '30', 'urgent', 'reorder', '1', '4', '2026-06-30 19:33:03', '2026-06-30 16:02:41');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('17', '2', '3', '0.02', '0.03', NULL, '2026-06-30', '30', 'urgent', 'reorder', '0', NULL, NULL, '2026-06-30 16:02:56');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('18', '2', '3', '0.02', '0.03', NULL, '2026-06-30', '30', 'urgent', 'reorder', '0', NULL, NULL, '2026-06-30 16:03:11');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('19', '2', '3', '0.02', '0.03', NULL, '2026-06-30', '30', 'urgent', 'reorder', '0', NULL, NULL, '2026-06-30 16:18:41');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('20', '2', '3', '0.02', '0.03', NULL, '2026-06-30', '30', 'urgent', 'reorder', '1', '4', '2026-06-30 19:55:59', '2026-06-30 16:18:57');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('21', '2', '3', '0.02', '0.03', NULL, '2026-06-30', '30', 'urgent', 'reorder', '1', '12', '2026-07-05 09:26:09', '2026-06-30 16:19:12');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('22', '2', '3', '0.02', '0.03', NULL, '2026-06-30', '30', 'urgent', 'reorder', '1', '4', '2026-06-30 19:32:56', '2026-06-30 16:19:27');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('23', '2', '3', '0.02', '0.03', NULL, '2026-06-30', '30', 'urgent', 'reorder', '1', '4', '2026-06-30 19:33:35', '2026-06-30 16:19:42');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('24', '2', '3', '0.02', '0.03', NULL, '2026-06-30', '30', 'urgent', 'reorder', '1', '12', '2026-07-02 17:01:26', '2026-06-30 16:19:57');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('25', '2', '3', '0.02', '0.03', NULL, '2026-06-30', '30', 'urgent', 'reorder', '1', '4', '2026-06-30 19:32:39', '2026-06-30 16:20:12');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('26', '2', '3', '0.02', '0.03', NULL, '2026-06-30', '30', 'urgent', 'reorder', '1', '4', '2026-06-30 16:50:31', '2026-06-30 16:20:27');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('27', '9', '3', '30.00', '50.00', '5000.00', NULL, NULL, 'urgent', 'restock', '1', '12', '2026-07-03 14:11:50', '2026-07-02 16:54:57');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('28', '7', '3', '400.00', '500.00', '10000.00', NULL, NULL, 'urgent', 'restock', '0', NULL, NULL, '2026-07-02 17:33:54');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('29', '10', '3', '5000.00', '600.00', '10000.00', NULL, NULL, 'urgent', 'restock', '1', '12', '2026-07-05 10:08:07', '2026-07-05 10:05:41');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('30', '1', '3', '50.00', '100.00', '10000.00', NULL, NULL, 'urgent', 'restock', '0', NULL, NULL, '2026-07-05 19:39:36');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('31', '1', '3', '50.00', '100.00', '10000.00', NULL, NULL, 'urgent', 'restock', '0', NULL, NULL, '2026-07-05 19:39:51');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('32', '1', '3', '50.00', '100.00', '10000.00', NULL, NULL, 'urgent', 'restock', '0', NULL, NULL, '2026-07-05 19:40:06');
INSERT INTO `chef_stock_notes` (`id`, `ingredient_id`, `chef_id`, `observed_stock`, `reorder_level_snapshot`, `suggested_restock_amount`, `expected_expiry_date`, `shelf_life_days`, `urgency`, `comment`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `created_at`) VALUES ('33', '1', '3', '50.00', '100.00', '10000.00', NULL, NULL, 'urgent', 'restock', '0', NULL, NULL, '2026-07-05 19:45:24');

-- ----------------------------
-- Table: ingredients
-- ----------------------------
DROP TABLE IF EXISTS `ingredients`;
CREATE TABLE `ingredients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `category` varchar(80) DEFAULT NULL,
  `unit` varchar(20) NOT NULL,
  `current_stock` decimal(10,2) NOT NULL DEFAULT '0.00',
  `reorder_level` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unit_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `ingredients` (`id`, `name`, `category`, `unit`, `current_stock`, `reorder_level`, `unit_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('1', 'Rice', 'Dry Goods', 'kg', '30060.00', '100.00', '2.40', '1', '2026-06-21 23:52:56', '2026-07-08 09:16:52');
INSERT INTO `ingredients` (`id`, `name`, `category`, `unit`, `current_stock`, `reorder_level`, `unit_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('2', 'Chicken Breast', 'Meat', 'kg', '325.00', '0.03', '8.90', '1', '2026-06-21 23:52:56', '2026-06-30 22:57:15');
INSERT INTO `ingredients` (`id`, `name`, `category`, `unit`, `current_stock`, `reorder_level`, `unit_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('3', 'Tomato', 'Vegetables', 'kg', '17.92', '7.00', '3.20', '1', '2026-06-21 23:52:56', '2026-06-24 14:43:43');
INSERT INTO `ingredients` (`id`, `name`, `category`, `unit`, `current_stock`, `reorder_level`, `unit_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('4', 'Cooking Oil', 'Pantry', 'liter', '29.85', '10.00', '4.50', '1', '2026-06-21 23:52:56', '2026-07-02 12:22:00');
INSERT INTO `ingredients` (`id`, `name`, `category`, `unit`, `current_stock`, `reorder_level`, `unit_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('5', 'Onion', 'Vegetables', 'kg', '11.50', '8.00', '2.30', '1', '2026-06-24 14:42:44', '2026-07-02 12:22:00');
INSERT INTO `ingredients` (`id`, `name`, `category`, `unit`, `current_stock`, `reorder_level`, `unit_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('6', 'Salmon Fillet', 'Seafood', 'kg', '8.56', '6.00', '14.50', '1', '2026-06-24 14:42:44', '2026-06-24 14:43:43');
INSERT INTO `ingredients` (`id`, `name`, `category`, `unit`, `current_stock`, `reorder_level`, `unit_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('7', 'Cheese', 'Dairy', 'kg', '117000.00', '600.00', '9.20', '1', '2026-06-24 14:42:44', '2026-07-05 09:26:44');
INSERT INTO `ingredients` (`id`, `name`, `category`, `unit`, `current_stock`, `reorder_level`, `unit_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('8', 'Pasta', 'Dry Goods', 'kg', '20.00', '6.00', '3.80', '1', '2026-06-24 14:42:44', '2026-06-24 14:42:44');
INSERT INTO `ingredients` (`id`, `name`, `category`, `unit`, `current_stock`, `reorder_level`, `unit_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('9', 'eggs', 'Chef Note', 'crates', '21030.00', '100.00', '0.00', '1', '2026-07-02 16:54:57', '2026-07-02 17:11:00');
INSERT INTO `ingredients` (`id`, `name`, `category`, `unit`, `current_stock`, `reorder_level`, `unit_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('10', 'potatoes', 'General', 'kg', '10500.00', '1000.00', '0.00', '1', '2026-07-05 09:47:40', '2026-07-05 10:06:50');

-- ----------------------------
-- Table: menu_items
-- ----------------------------
DROP TABLE IF EXISTS `menu_items`;
CREATE TABLE `menu_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `category` varchar(80) DEFAULT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `is_available` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('1', 'Chicken Fried Rice', 'Main Course', '18.00', '1', '2026-06-21 23:52:56');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('2', 'Tomato Pasta', 'Main Course', '16.50', '1', '2026-06-21 23:52:56');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('3', 'Grilled Salmon', 'Main Course', '24.00', '1', '2026-06-24 14:42:44');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('4', 'Cheese Omelette', 'Breakfast', '11.00', '1', '2026-06-24 14:42:44');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('5', 'Beef Burger', 'Main', '550.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('6', 'Chicken Burger', 'Main', '520.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('7', 'Cheese Burger', 'Main', '580.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('8', 'Double Beef Burger', 'Main', '690.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('9', 'Chicken Wrap', 'Main', '480.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('10', 'Beef Wrap', 'Main', '520.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('11', 'Falafel Wrap', 'Main', '430.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('12', 'Grilled Chicken Plate', 'Main', '760.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('13', 'Grilled Fish Plate', 'Main', '790.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('14', 'Roast Beef Plate', 'Main', '840.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('16', 'Chicken Alfredo Pasta', 'Main', '710.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('17', 'Beef Bolognese Pasta', 'Main', '740.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('18', 'Penne Arrabbiata', 'Main', '590.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('19', 'Mushroom Risotto', 'Main', '680.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('20', 'Seafood Risotto', 'Main', '840.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('21', 'Beef Fried Rice', 'Main', '640.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('22', 'Vegetable Fried Rice', 'Main', '560.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('23', 'Chicken Biryani', 'Main', '720.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('24', 'Beef Biryani', 'Main', '760.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('25', 'Vegetable Biryani', 'Main', '630.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('26', 'Chapati and Stew', 'Main', '450.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('27', 'Ugali and Beef Stew', 'Main', '520.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('28', 'Ugali and Chicken Stew', 'Main', '500.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('29', 'Pilau Beef', 'Main', '610.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('30', 'Pilau Chicken', 'Main', '590.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('31', 'Nyama Choma Platter', 'Main', '960.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('32', 'Tilapia Wet Fry', 'Main', '820.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('33', 'Fish and Chips', 'Main', '700.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('34', 'Margherita Pizza', 'Pizza', '740.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('35', 'Pepperoni Pizza', 'Pizza', '860.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('36', 'BBQ Chicken Pizza', 'Pizza', '890.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('37', 'Veggie Pizza', 'Pizza', '780.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('38', 'Hawaiian Pizza', 'Pizza', '870.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('39', 'French Fries', 'Side', '250.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('40', 'Masala Fries', 'Side', '300.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('41', 'Potato Wedges', 'Side', '320.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('42', 'Onion Rings', 'Side', '310.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('43', 'Garlic Bread', 'Side', '280.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('44', 'Steamed Rice', 'Side', '220.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('45', 'Saute Vegetables', 'Side', '290.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('46', 'Coleslaw', 'Side', '180.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('47', 'Kachumbari', 'Side', '160.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('48', 'Caesar Salad', 'Starter', '390.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('49', 'Greek Salad', 'Starter', '370.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('50', 'Garden Salad', 'Starter', '340.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('51', 'Chicken Salad', 'Starter', '430.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('52', 'Tomato Soup', 'Starter', '320.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('53', 'Pumpkin Soup', 'Starter', '340.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('54', 'Mushroom Soup', 'Starter', '360.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('55', 'Chicken Wings', 'Starter', '520.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('56', 'BBQ Wings', 'Starter', '560.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('57', 'Samosa Beef', 'Starter', '120.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('58', 'Samosa Veg', 'Starter', '100.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('59', 'Spring Rolls', 'Starter', '280.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('60', 'Mozzarella Sticks', 'Starter', '410.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('61', 'Fresh Orange Juice', 'Drink', '220.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('62', 'Fresh Mango Juice', 'Drink', '240.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('63', 'Passion Juice', 'Drink', '230.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('64', 'Pineapple Juice', 'Drink', '230.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('65', 'Iced Tea', 'Drink', '180.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('66', 'Lemonade', 'Drink', '170.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('67', 'Milkshake Vanilla', 'Drink', '320.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('68', 'Milkshake Chocolate', 'Drink', '340.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('69', 'Milkshake Strawberry', 'Drink', '340.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('70', 'Soda', 'Drink', '120.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('71', 'Mineral Water Small', 'Drink', '100.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('72', 'Mineral Water Large', 'Drink', '160.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('73', 'Espresso', 'Drink', '180.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('74', 'Cappuccino', 'Drink', '240.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('75', 'Latte', 'Drink', '260.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('76', 'African Tea', 'Drink', '150.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('77', 'Black Tea', 'Drink', '130.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('78', 'Chocolate Cake Slice', 'Dessert', '280.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('79', 'Cheesecake Slice', 'Dessert', '320.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('80', 'Carrot Cake Slice', 'Dessert', '260.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('81', 'Fruit Salad', 'Dessert', '300.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('82', 'Ice Cream Vanilla', 'Dessert', '220.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('83', 'Ice Cream Chocolate', 'Dessert', '220.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('84', 'Brownie with Ice Cream', 'Dessert', '360.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('85', 'Pancakes with Honey', 'Dessert', '340.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('86', 'Waffles with Syrup', 'Dessert', '360.00', '1', '2026-06-24 17:12:25');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('87', 'Grilled Fish', 'Main', '760.00', '1', '2026-06-24 17:18:40');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('88', 'Fresh Juice', 'Drink', '220.00', '1', '2026-06-24 17:18:40');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('89', 'chapati beans', 'all', '40.52', '1', '2026-06-25 21:11:46');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('90', 'beef fried rice', 'all', '40.52', '1', '2026-06-25 21:56:50');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('91', 'Vegetable Pasta', 'Main', '620.00', '1', '2026-06-29 11:47:48');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('92', 'Fried chicken', 'all', '50.00', '1', '2026-06-29 11:48:27');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('93', 'chicken breast', 'lunch', '400.00', '1', '2026-06-30 19:32:07');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('94', 'eggs', 'all', '30.00', '1', '2026-07-02 16:56:54');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('95', 'Mashed potatoes', 'all', '2000.00', '1', '2026-07-05 10:09:48');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('96', 'chips mayai', 'breakfast', '60.00', '1', '2026-07-05 19:47:57');
INSERT INTO `menu_items` (`id`, `name`, `category`, `selling_price`, `is_available`, `created_at`) VALUES ('97', 'githeri', 'all', '50.00', '1', '2026-07-08 10:43:40');

-- ----------------------------
-- Table: order_alerts
-- ----------------------------
DROP TABLE IF EXISTS `order_alerts`;
CREATE TABLE `order_alerts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `table_number` varchar(20) NOT NULL,
  `waiter_id` int NOT NULL,
  `alert_status` enum('new','seen') NOT NULL DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_alert_status` (`alert_status`),
  KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('1', '10', 'ORD-20260624-140914-402', 'T4', '2', 'seen', '2026-06-24 17:09:14');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('2', '11', 'ORD-20260624-141323-728', 'T1', '2', 'seen', '2026-06-24 17:13:23');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('3', '12', 'ORD-20260625-072729-551', 'T5', '2', 'seen', '2026-06-25 10:27:29');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('4', '13', 'ORD-20260625-073318-771', 'T7', '2', 'seen', '2026-06-25 10:33:18');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('5', '14', 'ORD-20260625-073525-650', 'T7', '2', 'seen', '2026-06-25 10:35:25');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('6', '15', 'ORD-20260625-074006-549', 'T7', '2', 'seen', '2026-06-25 10:40:06');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('7', '16', 'ORD-20260625-074442-157', 'T7', '2', 'seen', '2026-06-25 10:44:42');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('8', '17', 'ORD-20260625-075026-985', 'T7', '2', 'seen', '2026-06-25 10:50:26');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('9', '18', 'ORD-20260625-080202-300', 'T7', '2', 'seen', '2026-06-25 11:02:02');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('10', '19', 'ORD-20260625-183147-347', 'T12', '2', 'seen', '2026-06-25 21:31:47');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('11', '20', 'ORD-20260629-064120-216', 'T7', '2', 'seen', '2026-06-29 09:41:20');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('12', '21', 'ORD-20260629-064154-222', 'T5', '2', 'seen', '2026-06-29 09:41:54');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('13', '22', 'ORD-20260629-075550-618', 'T6', '2', 'seen', '2026-06-29 10:55:50');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('14', '23', 'ORD-20260629-080641-411', 'T7', '2', 'seen', '2026-06-29 11:06:41');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('15', '24', 'ORD-20260629-082022-989', 'T5', '2', 'seen', '2026-06-29 11:20:22');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('16', '25', 'ORD-20260629-084255-675', 'T7', '2', 'seen', '2026-06-29 11:42:55');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('17', '26', 'ORD-20260629-100633-104', 'T18', '2', 'seen', '2026-06-29 13:06:33');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('18', '27', 'ORD-20260629-100713-193', 'T5', '2', 'seen', '2026-06-29 13:07:13');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('19', '28', 'ORD-20260629-100724-758', 'T5', '2', 'seen', '2026-06-29 13:07:24');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('20', '29', 'ORD-20260630-101921-880', 'T13', '2', 'seen', '2026-06-30 13:19:21');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('21', '30', 'ORD-20260630-103502-815', 'T13', '2', 'seen', '2026-06-30 13:35:02');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('22', '31', 'ORD-20260630-103644-580', 'T13', '2', 'seen', '2026-06-30 13:36:44');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('23', '32', 'ORD-20260701-212927-929', 'T21', '2', 'seen', '2026-07-02 00:29:27');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('24', '33', 'ORD-20260702-092200-251', 'T7', '2', 'seen', '2026-07-02 12:22:00');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('25', '34', 'ORD-20260702-124145-658', 'T15', '10', 'new', '2026-07-02 15:41:45');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('26', '35', 'ORD-20260705-063834-525', 'T14', '2', 'new', '2026-07-05 09:38:34');
INSERT INTO `order_alerts` (`id`, `order_id`, `order_number`, `table_number`, `waiter_id`, `alert_status`, `created_at`) VALUES ('27', '36', 'ORD-20260705-071233-422', 'T15', '2', 'new', '2026-07-05 10:12:33');

-- ----------------------------
-- Table: order_items
-- ----------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `menu_item_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL,
  `line_total` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `menu_item_id` (`menu_item_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('1', '3', '1', '1', '18.00', '18.00', '2026-06-22 00:36:13');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('2', '4', '1', '1', '18.00', '18.00', '2026-06-22 00:36:39');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('3', '5', '1', '1', '18.00', '18.00', '2026-06-23 12:39:27');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('4', '6', '1', '1', '18.00', '18.00', '2026-06-24 14:42:44');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('5', '6', '2', '1', '16.50', '16.50', '2026-06-24 14:42:44');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('6', '7', '3', '1', '24.00', '24.00', '2026-06-24 14:42:44');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('7', '8', '4', '1', '11.00', '11.00', '2026-06-24 14:42:44');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('8', '8', '1', '1', '18.00', '18.00', '2026-06-24 14:42:44');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('9', '9', '3', '2', '24.00', '48.00', '2026-06-24 14:43:43');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('10', '10', '4', '1', '11.00', '11.00', '2026-06-24 17:09:14');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('11', '11', '27', '1', '520.00', '520.00', '2026-06-24 17:13:23');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('12', '12', '76', '1', '150.00', '150.00', '2026-06-25 10:27:29');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('13', '13', '46', '1', '180.00', '180.00', '2026-06-25 10:33:18');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('14', '14', '46', '1', '180.00', '180.00', '2026-06-25 10:35:25');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('15', '15', '4', '1', '11.00', '11.00', '2026-06-25 10:40:06');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('16', '16', '4', '1', '11.00', '11.00', '2026-06-25 10:44:42');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('17', '17', '4', '1', '11.00', '11.00', '2026-06-25 10:50:26');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('18', '18', '4', '1', '11.00', '11.00', '2026-06-25 11:02:02');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('19', '19', '89', '1', '40.52', '40.52', '2026-06-25 21:31:47');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('20', '20', '24', '3', '760.00', '2280.00', '2026-06-29 09:41:20');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('21', '21', '36', '1', '890.00', '890.00', '2026-06-29 09:41:54');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('22', '22', '76', '3', '150.00', '450.00', '2026-06-29 10:55:50');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('23', '23', '36', '4', '890.00', '3560.00', '2026-06-29 11:06:41');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('24', '24', '36', '1', '890.00', '890.00', '2026-06-29 11:20:22');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('25', '25', '76', '1', '150.00', '150.00', '2026-06-29 11:42:55');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('26', '26', '36', '1', '890.00', '890.00', '2026-06-29 13:06:33');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('27', '27', '24', '1', '760.00', '760.00', '2026-06-29 13:07:13');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('28', '28', '24', '1', '760.00', '760.00', '2026-06-29 13:07:24');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('29', '29', '4', '5', '11.00', '55.00', '2026-06-30 13:19:21');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('30', '30', '90', '1', '40.52', '40.52', '2026-06-30 13:35:02');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('31', '31', '28', '3', '500.00', '1500.00', '2026-06-30 13:36:44');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('32', '32', '76', '2', '150.00', '300.00', '2026-07-02 00:29:27');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('33', '33', '4', '1', '11.00', '11.00', '2026-07-02 12:22:00');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('34', '34', '77', '1', '130.00', '130.00', '2026-07-02 15:41:45');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('35', '35', '90', '1', '40.52', '40.52', '2026-07-05 09:38:34');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `line_total`, `created_at`) VALUES ('36', '36', '95', '7', '2000.00', '14000.00', '2026-07-05 10:12:33');

-- ----------------------------
-- Table: orders
-- ----------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_number` varchar(40) NOT NULL,
  `waiter_id` int NOT NULL,
  `table_number` varchar(20) DEFAULT NULL,
  `status` enum('pending','preparing','served','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `waiter_id` (`waiter_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`waiter_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('1', 'ORD-001', '2', 'T1', 'served', '34.50', '2026-06-21 23:52:56', '2026-06-21 23:52:56');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('2', 'ORD-002', '2', 'T4', 'served', '18.00', '2026-06-21 23:52:56', '2026-06-22 22:06:51');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('3', 'ORD-20260621-213613-120', '2', '1', 'served', '18.00', '2026-06-22 00:36:13', '2026-06-25 21:47:18');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('4', 'ORD-20260621-213639-265', '2', '1', 'served', '18.00', '2026-06-22 00:36:39', '2026-06-23 12:40:27');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('5', 'ORD-20260623-093927-644', '2', '1', 'served', '18.00', '2026-06-23 12:39:27', '2026-06-24 12:41:24');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('6', 'ORD-20260616-001', '7', 'T1', 'served', '34.50', '2026-06-24 14:42:44', '2026-06-24 14:42:44');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('7', 'ORD-20260616-002', '7', 'T4', 'served', '24.00', '2026-06-24 14:42:44', '2026-06-25 21:50:41');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('8', 'ORD-20260616-003', '7', 'T7', 'served', '29.00', '2026-06-24 14:42:44', '2026-06-25 21:48:09');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('9', 'ORD-20260624-114343-338', '2', 'T7', 'served', '48.00', '2026-06-24 14:43:43', '2026-06-25 21:48:12');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('10', 'ORD-20260624-140914-402', '2', 'T4', 'served', '11.00', '2026-06-24 17:09:14', '2026-06-24 17:31:26');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('11', 'ORD-20260624-141323-728', '2', 'T1', 'served', '520.00', '2026-06-24 17:13:23', '2026-06-25 21:50:47');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('12', 'ORD-20260625-072729-551', '2', 'T5', 'served', '150.00', '2026-06-25 10:27:29', '2026-06-25 11:13:51');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('13', 'ORD-20260625-073318-771', '2', 'T7', 'served', '180.00', '2026-06-25 10:33:18', '2026-06-25 11:14:04');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('14', 'ORD-20260625-073525-650', '2', 'T7', 'served', '180.00', '2026-06-25 10:35:25', '2026-06-25 11:13:32');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('15', 'ORD-20260625-074006-549', '2', 'T7', 'served', '11.00', '2026-06-25 10:40:06', '2026-06-25 11:14:02');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('16', 'ORD-20260625-074442-157', '2', 'T7', 'served', '11.00', '2026-06-25 10:44:42', '2026-06-25 11:12:32');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('17', 'ORD-20260625-075026-985', '2', 'T7', 'served', '11.00', '2026-06-25 10:50:26', '2026-06-25 11:13:28');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('18', 'ORD-20260625-080202-300', '2', 'T7', 'served', '11.00', '2026-06-25 11:02:02', '2026-06-25 11:12:23');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('19', 'ORD-20260625-183147-347', '2', 'T12', 'served', '40.52', '2026-06-25 21:31:47', '2026-06-25 21:50:50');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('20', 'ORD-20260629-064120-216', '2', 'T7', 'served', '2280.00', '2026-06-29 09:41:20', '2026-06-29 09:55:22');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('21', 'ORD-20260629-064154-222', '2', 'T5', 'served', '890.00', '2026-06-29 09:41:54', '2026-06-29 10:57:09');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('22', 'ORD-20260629-075550-618', '2', 'T6', 'served', '450.00', '2026-06-29 10:55:50', '2026-06-29 11:21:43');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('23', 'ORD-20260629-080641-411', '2', 'T7', 'served', '3560.00', '2026-06-29 11:06:41', '2026-06-29 11:21:46');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('24', 'ORD-20260629-082022-989', '2', 'T5', 'served', '890.00', '2026-06-29 11:20:22', '2026-06-29 11:44:21');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('25', 'ORD-20260629-084255-675', '2', 'T7', 'served', '150.00', '2026-06-29 11:42:55', '2026-06-29 11:44:24');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('26', 'ORD-20260629-100633-104', '2', 'T18', 'served', '890.00', '2026-06-29 13:06:33', '2026-06-29 13:08:39');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('27', 'ORD-20260629-100713-193', '2', 'T5', 'served', '760.00', '2026-06-29 13:07:13', '2026-06-29 13:08:50');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('28', 'ORD-20260629-100724-758', '2', 'T5', 'served', '760.00', '2026-06-29 13:07:24', '2026-06-29 13:08:52');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('29', 'ORD-20260630-101921-880', '2', 'T13', 'served', '55.00', '2026-06-30 13:19:21', '2026-07-01 23:30:35');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('30', 'ORD-20260630-103502-815', '2', 'T13', 'served', '40.52', '2026-06-30 13:35:02', '2026-07-01 23:30:52');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('31', 'ORD-20260630-103644-580', '2', 'T13', 'served', '1500.00', '2026-06-30 13:36:44', '2026-07-01 23:30:57');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('32', 'ORD-20260701-212927-929', '2', 'T21', 'served', '300.00', '2026-07-02 00:29:27', '2026-07-02 12:22:35');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('33', 'ORD-20260702-092200-251', '2', 'T7', 'pending', '11.00', '2026-07-02 12:22:00', '2026-07-02 12:22:00');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('34', 'ORD-20260702-124145-658', '10', 'T15', 'pending', '130.00', '2026-07-02 15:41:45', '2026-07-02 15:41:45');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('35', 'ORD-20260705-063834-525', '2', 'T14', 'pending', '40.52', '2026-07-05 09:38:34', '2026-07-05 09:38:34');
INSERT INTO `orders` (`id`, `order_number`, `waiter_id`, `table_number`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES ('36', 'ORD-20260705-071233-422', '2', 'T15', 'pending', '14000.00', '2026-07-05 10:12:33', '2026-07-05 10:12:33');

-- ----------------------------
-- Table: predictive_reports
-- ----------------------------
DROP TABLE IF EXISTS `predictive_reports`;
CREATE TABLE `predictive_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `report_month` date NOT NULL,
  `report_label` varchar(40) NOT NULL,
  `report_body` text NOT NULL,
  `generation_mode` enum('auto','manual') NOT NULL DEFAULT 'auto',
  `generated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_month` (`report_month`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `predictive_reports` (`id`, `report_month`, `report_label`, `report_body`, `generation_mode`, `generated_at`) VALUES ('1', '2026-06-01', 'June 2026', 'African Tea sold 4.0x in week 5 compared to week 4 of June 2026.\nRecommendation: stock up around 4x for this dish ahead of similar demand periods.\nTop dishes this month:\n- Cheese Omelette: 11 sold\n- BBQ Chicken Pizza: 7 sold\n- Chicken Fried Rice: 5 sold\n- Beef Biryani: 5 sold\n- African Tea: 5 sold', 'manual', '2026-06-30 17:31:15');
INSERT INTO `predictive_reports` (`id`, `report_month`, `report_label`, `report_body`, `generation_mode`, `generated_at`) VALUES ('4', '2026-07-01', 'July 2026', 'No major weekly sales spike detected in July 2026.\nRecommendation: maintain a 30% buffer on top-selling dishes.\nTop dishes this month:\n- Mashed potatoes: 7 sold\n- African Tea: 2 sold\n- Cheese Omelette: 1 sold\n- Black Tea: 1 sold\n- beef fried rice: 1 sold\n\nShelf-life and Expiry Monitoring (from chef notes):\n- Pasta [URGENT] | Expired 30 day(s) ago | observed 0.01 vs reorder 0.03 | suggested restock 0.00 | note: restock\n- Cooking Oil [URGENT] | Expired 29 day(s) ago | observed 0.10 vs reorder 0.04 | suggested restock 0.00 | note: expiry risk\n- Cooking Oil [URGENT] | Expired 29 day(s) ago | observed 0.10 vs reorder 0.04 | suggested restock 0.00 | note: expiry risk\n- Cooking Oil [URGENT] | Expired 29 day(s) ago | observed 0.10 vs reorder 0.04 | suggested restock 0.00 | note: expiry risk\n- Cooking Oil [URGENT] | Expired 29 day(s) ago | observed 0.10 vs reorder 0.04 | suggested restock 0.00 | note: expiry risk\n- Cooking Oil [URGENT] | Expired 29 day(s) ago | observed 0.10 vs reorder 0.04 | suggested restock 0.00 | note: expiry risk\n- Cooking Oil [URGENT] | Expired 29 day(s) ago | observed 0.10 vs reorder 0.04 | suggested restock 0.00 | note: expiry risk\n- Cooking Oil [URGENT] | Expired 29 day(s) ago | observed 0.10 vs reorder 0.04 | suggested restock 0.00 | note: expiry risk\n- Cooking Oil [URGENT] | Expired 29 day(s) ago | observed 0.10 vs reorder 0.04 | suggested restock 0.00 | note: expiry risk\n- Cooking Oil [URGENT] | Expired 29 day(s) ago | observed 0.10 vs reorder 0.04 | suggested restock 0.00 | note: expiry risk\nLikely affected food items (based on recipes):\n- Cheese Omelette\n- Chicken Fried Rice\n- Grilled Salmon\n- Tomato Pasta\nRecommendation: prioritize procurement or temporary menu substitution for urgent/near-expiry ingredients.', 'manual', '2026-07-05 20:14:51');

-- ----------------------------
-- Table: recipe_ingredients
-- ----------------------------
DROP TABLE IF EXISTS `recipe_ingredients`;
CREATE TABLE `recipe_ingredients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `menu_item_id` int NOT NULL,
  `ingredient_id` int NOT NULL,
  `quantity_required` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_recipe_line` (`menu_item_id`,`ingredient_id`),
  KEY `ingredient_id` (`ingredient_id`),
  CONSTRAINT `recipe_ingredients_ibfk_1` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recipe_ingredients_ibfk_2` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `recipe_ingredients` (`id`, `menu_item_id`, `ingredient_id`, `quantity_required`) VALUES ('1', '1', '1', '0.25');
INSERT INTO `recipe_ingredients` (`id`, `menu_item_id`, `ingredient_id`, `quantity_required`) VALUES ('2', '1', '2', '0.18');
INSERT INTO `recipe_ingredients` (`id`, `menu_item_id`, `ingredient_id`, `quantity_required`) VALUES ('3', '1', '3', '0.05');
INSERT INTO `recipe_ingredients` (`id`, `menu_item_id`, `ingredient_id`, `quantity_required`) VALUES ('4', '1', '5', '0.05');
INSERT INTO `recipe_ingredients` (`id`, `menu_item_id`, `ingredient_id`, `quantity_required`) VALUES ('5', '1', '4', '0.03');
INSERT INTO `recipe_ingredients` (`id`, `menu_item_id`, `ingredient_id`, `quantity_required`) VALUES ('6', '2', '8', '0.20');
INSERT INTO `recipe_ingredients` (`id`, `menu_item_id`, `ingredient_id`, `quantity_required`) VALUES ('7', '2', '3', '0.10');
INSERT INTO `recipe_ingredients` (`id`, `menu_item_id`, `ingredient_id`, `quantity_required`) VALUES ('8', '2', '5', '0.05');
INSERT INTO `recipe_ingredients` (`id`, `menu_item_id`, `ingredient_id`, `quantity_required`) VALUES ('9', '2', '7', '0.04');
INSERT INTO `recipe_ingredients` (`id`, `menu_item_id`, `ingredient_id`, `quantity_required`) VALUES ('10', '3', '6', '0.22');
INSERT INTO `recipe_ingredients` (`id`, `menu_item_id`, `ingredient_id`, `quantity_required`) VALUES ('11', '3', '4', '0.02');
INSERT INTO `recipe_ingredients` (`id`, `menu_item_id`, `ingredient_id`, `quantity_required`) VALUES ('12', '3', '3', '0.04');
INSERT INTO `recipe_ingredients` (`id`, `menu_item_id`, `ingredient_id`, `quantity_required`) VALUES ('13', '4', '7', '0.06');
INSERT INTO `recipe_ingredients` (`id`, `menu_item_id`, `ingredient_id`, `quantity_required`) VALUES ('14', '4', '5', '0.03');
INSERT INTO `recipe_ingredients` (`id`, `menu_item_id`, `ingredient_id`, `quantity_required`) VALUES ('15', '4', '4', '0.01');

-- ----------------------------
-- Table: stock_movements
-- ----------------------------
DROP TABLE IF EXISTS `stock_movements`;
CREATE TABLE `stock_movements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ingredient_id` int NOT NULL,
  `movement_type` enum('stock_in','usage','adjustment','wastage') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `reference_type` enum('purchase','order','manual','wastage') DEFAULT 'manual',
  `reference_id` int DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ingredient_id` (`ingredient_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('1', '2', 'usage', '0.01', 'manual', NULL, 'great', '3', '2026-06-23 12:41:12');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('2', '1', 'stock_in', '20.00', 'purchase', NULL, 'Weekly restock', '6', '2026-06-24 14:42:44');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('3', '2', 'stock_in', '10.00', 'purchase', NULL, 'Morning delivery', '6', '2026-06-24 14:42:44');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('4', '6', 'wastage', '0.60', 'wastage', NULL, 'Damaged during prep', '8', '2026-06-24 14:42:44');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('5', '4', 'adjustment', '1.00', 'manual', NULL, 'Stock count correction', '6', '2026-06-24 14:42:44');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('6', '3', 'usage', '0.08', 'order', '9', 'Auto usage from order ORD-20260624-114343-338', '2', '2026-06-24 14:43:43');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('7', '4', 'usage', '0.04', 'order', '9', 'Auto usage from order ORD-20260624-114343-338', '2', '2026-06-24 14:43:43');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('8', '6', 'usage', '0.44', 'order', '9', 'Auto usage from order ORD-20260624-114343-338', '2', '2026-06-24 14:43:43');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('9', '4', 'usage', '0.01', 'order', '10', 'Auto usage from order ORD-20260624-140914-402', '2', '2026-06-24 17:09:14');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('10', '5', 'usage', '0.03', 'order', '10', 'Auto usage from order ORD-20260624-140914-402', '2', '2026-06-24 17:09:14');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('11', '7', 'usage', '0.06', 'order', '10', 'Auto usage from order ORD-20260624-140914-402', '2', '2026-06-24 17:09:14');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('12', '5', 'wastage', '0.14', 'manual', NULL, 'rotten', '3', '2026-06-24 17:25:47');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('13', '4', 'usage', '0.01', 'order', '15', 'Auto usage from order ORD-20260625-074006-549', '2', '2026-06-25 10:40:06');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('14', '5', 'usage', '0.03', 'order', '15', 'Auto usage from order ORD-20260625-074006-549', '2', '2026-06-25 10:40:06');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('15', '7', 'usage', '0.06', 'order', '15', 'Auto usage from order ORD-20260625-074006-549', '2', '2026-06-25 10:40:06');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('16', '4', 'usage', '0.01', 'order', '16', 'Auto usage from order ORD-20260625-074442-157', '2', '2026-06-25 10:44:42');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('17', '5', 'usage', '0.03', 'order', '16', 'Auto usage from order ORD-20260625-074442-157', '2', '2026-06-25 10:44:42');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('18', '7', 'usage', '0.06', 'order', '16', 'Auto usage from order ORD-20260625-074442-157', '2', '2026-06-25 10:44:42');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('19', '4', 'usage', '0.01', 'order', '17', 'Auto usage from order ORD-20260625-075026-985', '2', '2026-06-25 10:50:26');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('20', '5', 'usage', '0.03', 'order', '17', 'Auto usage from order ORD-20260625-075026-985', '2', '2026-06-25 10:50:26');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('21', '7', 'usage', '0.06', 'order', '17', 'Auto usage from order ORD-20260625-075026-985', '2', '2026-06-25 10:50:26');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('22', '4', 'usage', '0.01', 'order', '18', 'Auto usage from order ORD-20260625-080202-300', '2', '2026-06-25 11:02:02');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('23', '5', 'usage', '0.03', 'order', '18', 'Auto usage from order ORD-20260625-080202-300', '2', '2026-06-25 11:02:02');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('24', '7', 'usage', '0.06', 'order', '18', 'Auto usage from order ORD-20260625-080202-300', '2', '2026-06-25 11:02:02');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('25', '5', 'usage', '10.00', 'manual', NULL, 'low stock', '3', '2026-06-25 21:51:50');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('26', '7', 'wastage', '20.00', 'manual', NULL, 'poor management', '3', '2026-06-25 21:53:53');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('27', '7', 'wastage', '0.05', 'manual', NULL, 'poor storage', '3', '2026-06-29 11:45:55');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('28', '4', 'usage', '0.05', 'order', '29', 'Auto usage from order ORD-20260630-101921-880', '2', '2026-06-30 13:19:21');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('29', '5', 'usage', '0.15', 'order', '29', 'Auto usage from order ORD-20260630-101921-880', '2', '2026-06-30 13:19:21');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('30', '7', 'usage', '0.30', 'order', '29', 'Auto usage from order ORD-20260630-101921-880', '2', '2026-06-30 13:19:21');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('31', '5', 'wastage', '0.03', 'manual', NULL, 'poor storage', '3', '2026-06-30 17:26:59');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('32', '4', 'usage', '0.01', 'order', '33', 'Auto usage from order ORD-20260702-092200-251', '2', '2026-07-02 12:22:00');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('33', '5', 'usage', '0.03', 'order', '33', 'Auto usage from order ORD-20260702-092200-251', '2', '2026-07-02 12:22:00');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('34', '7', 'usage', '0.06', 'order', '33', 'Auto usage from order ORD-20260702-092200-251', '2', '2026-07-02 12:22:00');
INSERT INTO `stock_movements` (`id`, `ingredient_id`, `movement_type`, `quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES ('35', '10', 'usage', '500.00', 'manual', NULL, 'Add this item', '3', '2026-07-05 09:48:43');

-- ----------------------------
-- Table: unavailable_item_requests
-- ----------------------------
DROP TABLE IF EXISTS `unavailable_item_requests`;
CREATE TABLE `unavailable_item_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_query` varchar(120) NOT NULL,
  `request_date` date NOT NULL,
  `request_count` int NOT NULL DEFAULT '1',
  `last_waiter_id` int DEFAULT NULL,
  `is_acknowledged` tinyint(1) NOT NULL DEFAULT '0',
  `acknowledged_by` int DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `last_requested_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_item_query_date` (`item_query`,`request_date`),
  KEY `idx_request_date` (`request_date`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `unavailable_item_requests` (`id`, `item_query`, `request_date`, `request_count`, `last_waiter_id`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `last_requested_at`) VALUES ('1', 'beef fried rice', '2026-06-25', '1', '2', '0', NULL, NULL, '2026-06-25 21:18:10');
INSERT INTO `unavailable_item_requests` (`id`, `item_query`, `request_date`, `request_count`, `last_waiter_id`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `last_requested_at`) VALUES ('2', 'chicken', '2026-06-25', '1', '2', '0', NULL, NULL, '2026-06-25 21:18:39');
INSERT INTO `unavailable_item_requests` (`id`, `item_query`, `request_date`, `request_count`, `last_waiter_id`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `last_requested_at`) VALUES ('3', 'chapati beans', '2026-06-25', '1', '2', '0', NULL, NULL, '2026-06-25 21:21:55');
INSERT INTO `unavailable_item_requests` (`id`, `item_query`, `request_date`, `request_count`, `last_waiter_id`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `last_requested_at`) VALUES ('4', 'chicken', '2026-06-29', '2', '2', '0', NULL, NULL, '2026-06-29 13:06:55');
INSERT INTO `unavailable_item_requests` (`id`, `item_query`, `request_date`, `request_count`, `last_waiter_id`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `last_requested_at`) VALUES ('6', 'beef fried rice', '2026-06-30', '1', '2', '0', NULL, NULL, '2026-06-30 13:20:01');
INSERT INTO `unavailable_item_requests` (`id`, `item_query`, `request_date`, `request_count`, `last_waiter_id`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `last_requested_at`) VALUES ('7', 'nonexisting item xyz', '2026-07-01', '1', '2', '0', NULL, NULL, '2026-07-02 00:29:06');
INSERT INTO `unavailable_item_requests` (`id`, `item_query`, `request_date`, `request_count`, `last_waiter_id`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `last_requested_at`) VALUES ('8', 'fried beef rice', '2026-07-05', '1', '2', '1', '12', '2026-07-05 19:45:58', '2026-07-05 19:45:58');
INSERT INTO `unavailable_item_requests` (`id`, `item_query`, `request_date`, `request_count`, `last_waiter_id`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `last_requested_at`) VALUES ('9', 'mashed potatos', '2026-07-05', '1', '2', '1', '12', '2026-07-05 19:37:08', '2026-07-05 19:37:08');
INSERT INTO `unavailable_item_requests` (`id`, `item_query`, `request_date`, `request_count`, `last_waiter_id`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `last_requested_at`) VALUES ('10', 'githeri', '2026-07-08', '1', '2', '1', '12', '2026-07-08 10:44:19', '2026-07-08 10:44:19');

-- ----------------------------
-- Table: users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','waiter','chef','manager') NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('1', 'System Admin', 'admin@gmail.com', '$2y$12$y2RhLNCtRHS9Umwj0obYHOEvowgiKFUtIVFxcOZRYxiiVfu7RDt5u', '555-1001', 'admin', '1', '2026-06-21 23:52:56', '2026-06-30 17:12:06');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('2', 'Mia Waiter', 'waiter@gmail.com', '$2y$12$dImhv84uvjbl2Rn2Onh9vevst.QYatBCXRJ1FlOo8FRr0CTmCkBka', '555-1002', 'waiter', '1', '2026-06-21 23:52:56', '2026-07-05 09:15:18');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('3', 'Liam Chef', 'chef@gmail.com', '$2y$12$4hoqh/bMCbYwfMdsbW7byeFWXWsJtQTS13Zm4dEDacSPMCXIP2hxq', '555-1003', 'chef', '1', '2026-06-21 23:52:56', '2026-07-09 13:40:13');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('4', 'Noah Lika', 'manager@gmail.com', '$2y$12$8/SD./P5zCJoOSHid.gh/.Mu4Vn2JbZMU9vIoqlIQ9nOIjSGUzuGy', '555-1004', 'manager', '1', '2026-06-21 23:52:56', '2026-06-30 17:08:17');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('5', 'Larry madoa', 'larrymadoa@gmail.com', '$2y$12$1B5aD0RNuoG5lKx/vCgyZ.9G9WpToVc/mZQT6XyfDhf3h.aU.NSnW', '071000000', 'manager', '1', '2026-06-24 14:14:48', '2026-06-24 14:14:48');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('6', 'System Admin', 'admin@foodflow.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-1001', 'admin', '1', '2026-06-24 14:42:44', '2026-06-24 14:42:44');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('7', 'Mia Waiter', 'waiter@foodflow.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-1002', 'waiter', '1', '2026-06-24 14:42:44', '2026-06-24 14:42:44');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('8', 'Liam Chef', 'chef@foodflow.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-1003', 'chef', '1', '2026-06-24 14:42:44', '2026-06-24 14:42:44');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('10', 'Mariam misha', 'maria@gmail.com', '$2y$12$ukf7VHB/NU0XJHxzx47SfupqwFV9lbmdfwHVX9LmKwDIubT8oRzHS', '0710203040', 'waiter', '1', '2026-06-29 11:27:16', '2026-07-02 15:46:55');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('12', 'Carolyne', 'carolyn@gmail.com', '$2y$12$mrvXnkQqdZT.zqkzKT2V5.P7TT4XIiwWrCs3DHxxmGu3BhOxfbKH2', '0732101104', 'manager', '1', '2026-07-02 15:43:31', '2026-07-07 13:29:30');

-- ----------------------------
-- Table: wastage_logs
-- ----------------------------
DROP TABLE IF EXISTS `wastage_logs`;
CREATE TABLE `wastage_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ingredient_id` int NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `reason` varchar(120) NOT NULL,
  `logged_by` int NOT NULL,
  `logged_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ingredient_id` (`ingredient_id`),
  KEY `logged_by` (`logged_by`),
  CONSTRAINT `wastage_logs_ibfk_1` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `wastage_logs_ibfk_2` FOREIGN KEY (`logged_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `wastage_logs` (`id`, `ingredient_id`, `quantity`, `reason`, `logged_by`, `logged_at`) VALUES ('1', '6', '0.60', 'Damaged during cleaning', '8', '2026-06-24 14:42:44');
INSERT INTO `wastage_logs` (`id`, `ingredient_id`, `quantity`, `reason`, `logged_by`, `logged_at`) VALUES ('2', '3', '0.40', 'Spoilage', '8', '2026-06-24 14:42:44');
INSERT INTO `wastage_logs` (`id`, `ingredient_id`, `quantity`, `reason`, `logged_by`, `logged_at`) VALUES ('3', '5', '0.14', 'rotten', '3', '2026-06-24 17:25:47');
INSERT INTO `wastage_logs` (`id`, `ingredient_id`, `quantity`, `reason`, `logged_by`, `logged_at`) VALUES ('4', '7', '20.00', 'poor management', '3', '2026-06-25 21:53:53');
INSERT INTO `wastage_logs` (`id`, `ingredient_id`, `quantity`, `reason`, `logged_by`, `logged_at`) VALUES ('5', '7', '0.05', 'poor storage', '3', '2026-06-29 11:45:55');
INSERT INTO `wastage_logs` (`id`, `ingredient_id`, `quantity`, `reason`, `logged_by`, `logged_at`) VALUES ('6', '5', '0.03', 'poor storage', '3', '2026-06-30 17:26:59');

SET FOREIGN_KEY_CHECKS=1;

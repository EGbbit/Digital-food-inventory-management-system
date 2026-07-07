-- FoodFlow users table backup
-- Generated at: 2026-07-05 17:24:31

SET FOREIGN_KEY_CHECKS=0;

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
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('3', 'Liam Chef', 'chef@gmail.com', '$2y$12$8/SD./P5zCJoOSHid.gh/.Mu4Vn2JbZMU9vIoqlIQ9nOIjSGUzuGy', '555-1003', 'chef', '1', '2026-06-21 23:52:56', '2026-06-25 11:12:11');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('4', 'Noah Lika', 'manager@gmail.com', '$2y$12$8/SD./P5zCJoOSHid.gh/.Mu4Vn2JbZMU9vIoqlIQ9nOIjSGUzuGy', '555-1004', 'manager', '1', '2026-06-21 23:52:56', '2026-06-30 17:08:17');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('5', 'Larry madoa', 'larrymadoa@gmail.com', '$2y$12$1B5aD0RNuoG5lKx/vCgyZ.9G9WpToVc/mZQT6XyfDhf3h.aU.NSnW', '071000000', 'manager', '1', '2026-06-24 14:14:48', '2026-06-24 14:14:48');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('6', 'System Admin', 'admin@foodflow.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-1001', 'admin', '1', '2026-06-24 14:42:44', '2026-06-24 14:42:44');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('7', 'Mia Waiter', 'waiter@foodflow.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-1002', 'waiter', '1', '2026-06-24 14:42:44', '2026-06-24 14:42:44');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('8', 'Liam Chef', 'chef@foodflow.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-1003', 'chef', '1', '2026-06-24 14:42:44', '2026-06-24 14:42:44');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('10', 'Mariam misha', 'maria@gmail.com', '$2y$12$ukf7VHB/NU0XJHxzx47SfupqwFV9lbmdfwHVX9LmKwDIubT8oRzHS', '0710203040', 'waiter', '1', '2026-06-29 11:27:16', '2026-07-02 15:46:55');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `is_active`, `created_at`, `updated_at`) VALUES ('12', 'Carolyne', 'carolyn@gmail.com', '$2y$12$K2.sVg7BW0UJVskgO7uKEOIWDRtq5q1AXPISiMbd8JbB.1VRkUOm.', '0732101104', 'manager', '1', '2026-07-02 15:43:31', '2026-07-02 15:43:31');

SET FOREIGN_KEY_CHECKS=1;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 12, 2026 at 11:37 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `notun_alo`
--

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `agency_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `payment_type` enum('points','cash') NOT NULL,
  `status` enum('pending','confirmed','assigned','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `agency_id`, `product_id`, `payment_type`, `status`, `created_at`) VALUES
(1, 6, 3, 1, 'cash', 'assigned', '2026-05-12 19:14:20'),
(2, 6, 3, 1, 'cash', 'assigned', '2026-05-12 20:06:47');

-- --------------------------------------------------------

--
-- Table structure for table `pickups`
--

CREATE TABLE `pickups` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `agency_id` int(11) DEFAULT NULL,
  `category` enum('Paper','Plastic','Metal') NOT NULL,
  `estimated_weight` decimal(10,2) NOT NULL COMMENT 'Weight in KG',
  `status` enum('pending','assigned','completed') NOT NULL DEFAULT 'pending',
  `schedule_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `task_type` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pickups`
--

INSERT INTO `pickups` (`id`, `user_id`, `agency_id`, `category`, `estimated_weight`, `status`, `schedule_date`, `created_at`, `task_type`) VALUES
(1, 3, NULL, 'Paper', 5.50, 'completed', '2026-05-06', '2026-05-12 19:06:57', 'pickup'),
(2, 3, NULL, 'Plastic', 3.20, 'assigned', '2026-05-11', '2026-05-12 19:06:57', 'pickup'),
(3, 3, NULL, 'Metal', 8.00, 'pending', '2026-05-16', '2026-05-12 19:06:57', 'pickup'),
(4, 6, 3, 'Paper', 4.00, 'assigned', '2026-05-14', '2026-05-12 20:06:32', 'pickup'),
(5, 6, NULL, 'Plastic', 6.00, 'pending', '2026-05-16', '2026-05-12 20:14:01', 'pickup'),
(6, 6, 3, '', 0.00, 'assigned', '2026-05-13', '2026-05-12 21:00:33', 'delivery');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price_points` int(11) NOT NULL DEFAULT 0,
  `price_cash` decimal(10,2) NOT NULL DEFAULT 0.00,
  `image_url` varchar(500) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price_points`, `price_cash`, `image_url`, `stock`, `created_at`) VALUES
(1, 'Recycled Notebook', 'Handcrafted notebook from upcycled paper. 100 pages, eco-friendly cover.', 150, 120.00, 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=400', 23, '2026-05-12 19:06:57'),
(2, 'Upcycled Tote Bag', 'Durable tote bag made from recycled plastic bottles. Stylish and green!', 200, 180.00, 'https://images.unsplash.com/photo-1597484661643-2f5fef640dd1?w=400', 15, '2026-05-12 19:06:57'),
(3, 'Metal Pen Set', 'Set of 3 pens crafted from recycled metal scraps. Smooth writing experience.', 120, 95.00, 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=400', 40, '2026-05-12 19:06:57'),
(4, 'Eco Planter Pot', 'Small planter pot made from upcycled plastic. Perfect for desk plants.', 180, 150.00, 'https://images.unsplash.com/photo-1485955900006-10f4d324d411?w=400', 20, '2026-05-12 19:06:57'),
(5, 'Recycled Coaster Set', 'Set of 4 coasters made from compressed recycled materials. Heat resistant.', 100, 80.00, 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400', 50, '2026-05-12 19:06:57'),
(6, 'Upcycled Wall Art', 'Unique wall art piece created from reclaimed metal and paper materials.', 350, 320.00, 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?w=400', 8, '2026-05-12 19:06:57');

-- --------------------------------------------------------

--
-- Table structure for table `rewards`
--

CREATE TABLE `rewards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_points` int(11) NOT NULL DEFAULT 0,
  `lifetime_points` int(11) NOT NULL DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rewards`
--

INSERT INTO `rewards` (`id`, `user_id`, `total_points`, `lifetime_points`, `last_updated`) VALUES
(1, 3, 250, 250, '2026-05-12 19:06:57'),
(2, 6, 0, 0, '2026-05-12 19:13:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `picture_url` varchar(500) DEFAULT NULL,
  `role` enum('admin','user','agency') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `address`, `phone`, `picture_url`, `role`, `created_at`) VALUES
(1, 'Admin User', 'admin1@gmail.com', '$2y$10$GdxcQ9EsjOHRlzRfujyKFOUPkFIxv7POwZU9aM7MHhrbKxLHW9r2W', 'Dhaka, Bangladesh', '01700000000', NULL, 'admin', '2026-05-12 19:06:57'),
(2, 'Green Agency', 'agency@notunalo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Chittagong, Bangladesh', '01800000000', NULL, 'agency', '2026-05-12 19:06:57'),
(3, 'Agent One', 'agent1@gmail.com', '$2y$10$tpa5kjz6R8/IPVomT6l7weso07lwTyjWpt/BYRgVqozmYXV.bzuvy', 'Dhaka, Bangladesh', '01700000001', NULL, 'agency', '2026-05-12 19:06:57'),
(4, 'Agent Two', 'agent2@gmail.com', '$2y$10$tpa5kjz6R8/IPVomT6l7weso07lwTyjWpt/BYRgVqozmYXV.bzuvy', 'Dhaka, Bangladesh', '01700000002', NULL, 'agency', '2026-05-12 19:06:57'),
(5, 'Test User', 'user@notunalo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sylhet, Bangladesh', '01900000000', NULL, 'user', '2026-05-12 19:06:57'),
(6, 'Ayush Hassan', 'ahr_007@gmail.com', '$2y$10$di2rERKQs52AM3M/A6rA/O9o3STYZleqB6xuAkWRifUvrW9U5.T36', 'Bolbonah', '01xxxxxxxxx', 'uploads/profile_pictures/profile_6_1778613557.png', 'user', '2026-05-12 19:13:23');

-- --------------------------------------------------------

--
-- Table structure for table `user_ml_scores`
--

CREATE TABLE `user_ml_scores` (
  `user_id` int(11) NOT NULL,
  `churn_score` decimal(6,5) NOT NULL,
  `risk_label` enum('low','medium','high') NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `agency_id` (`agency_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `pickups`
--
ALTER TABLE `pickups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `agency_id` (`agency_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rewards`
--
ALTER TABLE `rewards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_ml_scores`
--
ALTER TABLE `user_ml_scores`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pickups`
--
ALTER TABLE `pickups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `rewards`
--
ALTER TABLE `rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`agency_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pickups`
--
ALTER TABLE `pickups`
  ADD CONSTRAINT `pickups_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pickups_ibfk_2` FOREIGN KEY (`agency_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rewards`
--
ALTER TABLE `rewards`
  ADD CONSTRAINT `rewards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_ml_scores`
--
ALTER TABLE `user_ml_scores`
  ADD CONSTRAINT `fk_user_ml_scores_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

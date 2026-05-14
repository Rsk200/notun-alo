-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2026 at 01:18 AM
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
-- Stand-in structure for view `category_averages`
-- (See below for the actual view)
--
CREATE TABLE `category_averages` (
`category` varchar(50)
,`avg_co2` decimal(7,4)
,`avg_water_liters_per_kg` decimal(9,2)
,`avg_energy_kwh_per_kg` decimal(7,4)
);

-- --------------------------------------------------------

--
-- Table structure for table `emission_factors`
--

CREATE TABLE `emission_factors` (
  `id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `subcategory` varchar(100) NOT NULL,
  `co2_sa_adjusted` decimal(6,4) NOT NULL,
  `water_liters_per_kg` decimal(8,2) NOT NULL,
  `energy_kwh_per_kg` decimal(6,4) NOT NULL,
  `co2_equivalent_label` varchar(200) DEFAULT NULL,
  `is_ewaste` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emission_factors`
--

INSERT INTO `emission_factors` (`id`, `category`, `subcategory`, `co2_sa_adjusted`, `water_liters_per_kg`, `energy_kwh_per_kg`, `co2_equivalent_label`, `is_ewaste`) VALUES
(1, 'Paper', 'Mixed paper', 2.6800, 26.40, 17.0000, '1kg Mixed paper recycled = 13 km car journey saved', 0),
(2, 'Paper', 'Newspaper', 2.4300, 22.10, 14.5000, '1kg Newspaper recycled = 12 km car journey saved', 0),
(3, 'Paper', 'Cardboard / OCC', 2.8200, 28.70, 18.2000, '1kg Cardboard / OCC recycled = 13 km car journey saved', 0),
(4, 'Paper', 'Office paper (HGP)', 3.4100, 33.20, 21.0000, '1kg Office paper (HGP) recycled = 16 km car journey saved', 0),
(5, 'Plastic', 'Mixed plastic', 1.3000, 5.80, 82.0000, '1kg Mixed plastic recycled = 6 km car journey saved', 0),
(6, 'Plastic', 'PET (#1 bottles)', 1.9000, 8.10, 84.0000, '1kg PET (#1 bottles) recycled = 9 km car journey saved', 0),
(7, 'Plastic', 'HDPE (#2 bottles)', 1.5500, 6.40, 78.0000, '1kg HDPE (#2 bottles) recycled = 7 km car journey saved', 0),
(8, 'Plastic', 'PP (#5)', 1.4700, 5.90, 76.0000, '1kg PP (#5) recycled = 7 km car journey saved', 0),
(9, 'Plastic', 'PVC (#3)', 0.8300, 4.20, 61.0000, '1kg PVC (#3) recycled = 4 km car journey saved', 0),
(10, 'Plastic', 'LDPE (#4 film)', 1.4000, 5.50, 80.0000, '1kg LDPE (#4 film) recycled = 7 km car journey saved', 0),
(11, 'Metal', 'Mixed metal', 3.5700, 8.10, 14.0000, '1kg Mixed metal recycled = 17 km car journey saved', 0),
(12, 'Metal', 'Aluminium cans', 7.7400, 14.30, 42.0000, '1kg Aluminium cans recycled = 37 km car journey saved', 0),
(13, 'Metal', 'Steel / Iron', 1.5100, 5.20, 8.5000, '1kg Steel / Iron recycled = 7 km car journey saved', 0),
(14, 'Metal', 'Copper wire', 3.2600, 9.80, 22.0000, '1kg Copper wire recycled = 16 km car journey saved', 0),
(15, 'Glass', 'Mixed glass', 0.2600, 2.10, 2.8000, '1kg Mixed glass recycled = 1 km car journey saved', 0),
(16, 'Glass', 'Clear glass', 0.2800, 2.30, 3.1000, '1kg Clear glass recycled = 1 km car journey saved', 0),
(17, 'Glass', 'Coloured glass', 0.2300, 1.90, 2.5000, '1kg Coloured glass recycled = 1 km car journey saved', 0),
(18, 'E-waste', 'Mixed WEEE', 2.7200, 180.00, 38.0000, '1kg Mixed WEEE recycled = 13 km car journey saved', 1),
(19, 'E-waste', 'Mobile phones', 37.4000, 910.00, 99.9999, '1kg Mobile phones recycled = 178 km car journey saved', 1),
(20, 'E-waste', 'Laptops / PCs', 23.8000, 580.00, 99.9999, '1kg Laptops / PCs recycled = 113 km car journey saved', 1),
(21, 'E-waste', 'Circuit boards (PCB)', 15.7000, 320.00, 95.0000, '1kg Circuit boards (PCB) recycled = 75 km car journey saved', 1),
(22, 'Organic', 'Food waste (compost)', 0.4900, 0.00, 0.5000, '1kg Food waste (compost) recycled = 2 km car journey saved', 0),
(23, 'Organic', 'Garden / yard waste', 0.1800, 0.00, 0.3000, '1kg Garden / yard waste recycled = 1 km car journey saved', 0),
(24, 'Textile', 'Mixed clothing', 3.4000, 35.00, 28.0000, '1kg Mixed clothing recycled = 16 km car journey saved', 0),
(25, 'Textile', 'Cotton', 3.2300, 38.00, 25.0000, '1kg Cotton recycled = 15 km car journey saved', 0),
(26, 'Rubber', 'Tyres / rubber', 1.0600, 3.50, 15.0000, '1kg Tyres / rubber recycled = 5 km car journey saved', 0),
(27, 'Wood', 'Dimensional lumber', 0.4600, 1.80, 4.5000, '1kg Dimensional lumber recycled = 2 km car journey saved', 0),
(28, 'Wood', 'Mixed wood / furniture', 0.3700, 1.40, 3.8000, '1kg Mixed wood / furniture recycled = 2 km car journey saved', 0);

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
(1, 4, 2, 1, 'cash', 'assigned', '2026-05-11 21:08:17'),
(2, 4, 5, 5, 'points', 'assigned', '2026-05-11 21:28:34'),
(3, 4, NULL, 1, 'points', 'confirmed', '2026-05-11 21:34:41'),
(4, 4, NULL, 1, 'points', 'confirmed', '2026-05-11 22:12:47');

-- --------------------------------------------------------

--
-- Table structure for table `pickups`
--

CREATE TABLE `pickups` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `agency_id` int(11) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `subcategory` varchar(100) DEFAULT NULL,
  `estimated_weight` decimal(10,2) NOT NULL COMMENT 'Weight in KG',
  `status` enum('pending','assigned','completed') NOT NULL DEFAULT 'pending',
  `schedule_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `task_type` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pickups`
--

INSERT INTO `pickups` (`id`, `user_id`, `agency_id`, `category`, `subcategory`, `estimated_weight`, `status`, `schedule_date`, `created_at`) VALUES
(1, 3, NULL, 'Paper', 'Mixed paper', 5.50, 'completed', '2026-05-05', '2026-05-11 19:59:04'),
(2, 3, NULL, 'Plastic', 'Mixed plastic', 3.20, 'assigned', '2026-05-10', '2026-05-11 19:59:04'),
(3, 3, NULL, 'Metal', 'Mixed metal', 8.00, 'pending', '2026-05-15', '2026-05-11 19:59:04'),
(4, 4, 5, 'Paper', 'Mixed paper', 2.00, 'completed', '2026-10-22', '2026-05-11 20:46:10'),
(5, 4, 5, 'Paper', 'Mixed paper', 20.00, 'completed', '2026-05-13', '2026-05-11 21:06:28'),
(6, 4, 5, 'Paper', 'Mixed paper', 20.00, 'completed', '2026-05-20', '2026-05-11 21:33:41'),
(7, 4, 5, 'Paper', 'Mixed paper', 5.10, 'assigned', '2026-05-20', '2026-05-11 21:34:07'),
(8, 9, NULL, 'Plastic', 'Mixed plastic', 5.00, 'pending', '2026-04-17', '2026-04-12 06:26:51'),
(9, 10, NULL, 'Metal', 'Mixed metal', 6.00, 'assigned', '2026-04-17', '2026-04-12 06:26:51'),
(10, 11, NULL, 'Paper', 'Mixed paper', 7.00, 'pending', '2026-04-17', '2026-04-12 06:26:51'),
(11, 12, NULL, 'Plastic', 'Mixed plastic', 8.00, 'assigned', '2026-04-17', '2026-04-12 06:26:51'),
(12, 13, NULL, 'Metal', 'Mixed metal', 4.00, 'pending', '2026-04-17', '2026-04-12 06:26:51'),
(13, 14, NULL, 'Paper', 'Mixed paper', 5.00, 'assigned', '2026-04-17', '2026-04-12 06:26:51'),
(14, 15, NULL, 'Plastic', 'Mixed plastic', 6.00, 'pending', '2026-04-17', '2026-04-12 06:26:51'),
(15, 16, NULL, 'Metal', 'Mixed metal', 7.00, 'assigned', '2026-04-17', '2026-04-12 06:26:51'),
(16, 17, NULL, 'Paper', 'Mixed paper', 8.00, 'pending', '2026-04-17', '2026-04-12 06:26:51'),
(17, 18, NULL, 'Plastic', 'Mixed plastic', 4.00, 'assigned', '2026-04-17', '2026-04-12 06:26:51'),
(18, 19, NULL, 'Metal', 'Mixed metal', 7.50, 'completed', '2026-03-07', '2026-03-07 06:26:51'),
(19, 20, NULL, 'Paper', 'Mixed paper', 2.50, 'completed', '2026-03-06', '2026-03-06 06:26:51'),
(20, 20, NULL, 'Paper', 'Mixed paper', 6.00, 'assigned', '2026-04-17', '2026-04-12 06:26:51'),
(21, 21, NULL, 'Plastic', 'Mixed plastic', 3.50, 'completed', '2026-03-05', '2026-03-05 06:26:51'),
(22, 22, NULL, 'Metal', 'Mixed metal', 4.50, 'completed', '2026-03-04', '2026-03-04 06:26:51'),
(23, 22, NULL, 'Metal', 'Mixed metal', 8.00, 'assigned', '2026-04-17', '2026-04-12 06:26:51'),
(24, 23, NULL, 'Paper', 'Mixed paper', 5.50, 'completed', '2026-03-03', '2026-03-03 06:26:51'),
(25, 24, NULL, 'Plastic', 'Mixed plastic', 6.50, 'completed', '2026-03-02', '2026-03-02 06:26:51'),
(26, 24, NULL, 'Plastic', 'Mixed plastic', 5.00, 'assigned', '2026-04-17', '2026-04-12 06:26:51'),
(27, 25, NULL, 'Metal', 'Mixed metal', 7.50, 'completed', '2026-03-01', '2026-03-01 06:26:51'),
(28, 26, NULL, 'Paper', 'Mixed paper', 2.50, 'completed', '2026-02-28', '2026-02-28 06:26:51'),
(29, 26, NULL, 'Paper', 'Mixed paper', 7.00, 'assigned', '2026-04-17', '2026-04-12 06:26:51'),
(30, 27, NULL, 'Plastic', 'Mixed plastic', 3.50, 'completed', '2026-02-27', '2026-02-27 06:26:51'),
(31, 28, NULL, 'Metal', 'Mixed metal', 4.50, 'completed', '2026-02-26', '2026-02-26 06:26:51'),
(32, 28, NULL, 'Metal', 'Mixed metal', 4.00, 'assigned', '2026-04-17', '2026-04-12 06:26:51'),
(33, 29, NULL, 'Paper', 'Mixed paper', 5.50, 'completed', '2026-05-02', '2026-05-02 06:26:51'),
(34, 29, NULL, 'Plastic', 'Mixed plastic', 6.50, 'completed', '2026-04-20', '2026-04-20 06:26:51'),
(35, 29, NULL, 'Metal', 'Mixed metal', 7.50, 'completed', '2026-04-08', '2026-04-08 06:26:51'),
(36, 29, NULL, 'Paper', 'Mixed paper', 2.50, 'completed', '2026-03-27', '2026-03-27 06:26:51'),
(37, 29, NULL, 'Paper', 'Mixed paper', 4.00, 'pending', '2026-05-17', '2026-05-10 06:26:51'),
(38, 30, NULL, 'Plastic', 'Mixed plastic', 6.50, 'completed', '2026-05-01', '2026-05-01 06:26:51'),
(39, 30, NULL, 'Metal', 'Mixed metal', 7.50, 'completed', '2026-04-19', '2026-04-19 06:26:51'),
(40, 30, NULL, 'Paper', 'Mixed paper', 2.50, 'completed', '2026-04-07', '2026-04-07 06:26:51'),
(41, 30, NULL, 'Plastic', 'Mixed plastic', 3.50, 'completed', '2026-03-26', '2026-03-26 06:26:51'),
(42, 30, NULL, 'Plastic', 'Mixed plastic', 5.00, 'pending', '2026-05-17', '2026-05-10 06:26:51'),
(43, 31, NULL, 'Metal', 'Mixed metal', 7.50, 'completed', '2026-04-30', '2026-04-30 06:26:51'),
(44, 31, NULL, 'Paper', 'Mixed paper', 2.50, 'completed', '2026-04-18', '2026-04-18 06:26:51'),
(45, 31, NULL, 'Plastic', 'Mixed plastic', 3.50, 'completed', '2026-04-06', '2026-04-06 06:26:51'),
(46, 31, NULL, 'Metal', 'Mixed metal', 4.50, 'completed', '2026-03-25', '2026-03-25 06:26:51'),
(47, 31, NULL, 'Metal', 'Mixed metal', 6.00, 'pending', '2026-05-17', '2026-05-10 06:26:51'),
(48, 32, NULL, 'Paper', 'Mixed paper', 2.50, 'completed', '2026-05-07', '2026-05-07 06:26:51'),
(49, 32, NULL, 'Plastic', 'Mixed plastic', 3.50, 'completed', '2026-04-25', '2026-04-25 06:26:51'),
(50, 32, NULL, 'Metal', 'Mixed metal', 4.50, 'completed', '2026-04-13', '2026-04-13 06:26:51'),
(51, 32, NULL, 'Paper', 'Mixed paper', 5.50, 'completed', '2026-04-01', '2026-04-01 06:26:51'),
(52, 32, NULL, 'Paper', 'Mixed paper', 3.00, 'pending', '2026-05-17', '2026-05-10 06:26:51'),
(53, 33, NULL, 'Plastic', 'Mixed plastic', 3.50, 'completed', '2026-05-06', '2026-05-06 06:26:51'),
(54, 33, NULL, 'Metal', 'Mixed metal', 4.50, 'completed', '2026-04-24', '2026-04-24 06:26:51'),
(55, 33, NULL, 'Paper', 'Mixed paper', 5.50, 'completed', '2026-04-12', '2026-04-12 06:26:51'),
(56, 33, NULL, 'Plastic', 'Mixed plastic', 6.50, 'completed', '2026-03-31', '2026-03-31 06:26:51'),
(57, 33, NULL, 'Plastic', 'Mixed plastic', 4.00, 'pending', '2026-05-17', '2026-05-10 06:26:51'),
(58, 34, NULL, 'Metal', 'Mixed metal', 4.50, 'completed', '2026-05-05', '2026-05-05 06:26:51'),
(59, 34, NULL, 'Paper', 'Mixed paper', 5.50, 'completed', '2026-04-23', '2026-04-23 06:26:51'),
(60, 34, NULL, 'Plastic', 'Mixed plastic', 6.50, 'completed', '2026-04-11', '2026-04-11 06:26:51'),
(61, 34, NULL, 'Metal', 'Mixed metal', 7.50, 'completed', '2026-03-30', '2026-03-30 06:26:51'),
(62, 34, NULL, 'Metal', 'Mixed metal', 5.00, 'pending', '2026-05-17', '2026-05-10 06:26:51'),
(63, 35, NULL, 'Paper', 'Mixed paper', 5.50, 'completed', '2026-05-04', '2026-05-04 06:26:51'),
(64, 35, NULL, 'Plastic', 'Mixed plastic', 6.50, 'completed', '2026-04-22', '2026-04-22 06:26:51'),
(65, 35, NULL, 'Metal', 'Mixed metal', 7.50, 'completed', '2026-04-10', '2026-04-10 06:26:51'),
(66, 35, NULL, 'Paper', 'Mixed paper', 2.50, 'completed', '2026-03-29', '2026-03-29 06:26:51'),
(67, 35, NULL, 'Paper', 'Mixed paper', 6.00, 'pending', '2026-05-17', '2026-05-10 06:26:51'),
(68, 36, NULL, 'Plastic', 'Mixed plastic', 6.50, 'completed', '2026-05-03', '2026-05-03 06:26:51'),
(69, 36, NULL, 'Metal', 'Mixed metal', 7.50, 'completed', '2026-04-21', '2026-04-21 06:26:51'),
(70, 36, NULL, 'Paper', 'Mixed paper', 2.50, 'completed', '2026-04-09', '2026-04-09 06:26:51'),
(71, 36, NULL, 'Plastic', 'Mixed plastic', 3.50, 'completed', '2026-03-28', '2026-03-28 06:26:51'),
(72, 36, NULL, 'Plastic', 'Mixed plastic', 3.00, 'pending', '2026-05-17', '2026-05-10 06:26:51'),
(73, 37, NULL, 'Metal', 'Mixed metal', 7.50, 'completed', '2026-05-02', '2026-05-02 06:26:51'),
(74, 37, NULL, 'Paper', 'Mixed paper', 2.50, 'completed', '2026-04-20', '2026-04-20 06:26:51'),
(75, 37, NULL, 'Plastic', 'Mixed plastic', 3.50, 'completed', '2026-04-08', '2026-04-08 06:26:51'),
(76, 37, NULL, 'Metal', 'Mixed metal', 4.50, 'completed', '2026-03-27', '2026-03-27 06:26:51'),
(77, 37, NULL, 'Metal', 'Mixed metal', 4.00, 'pending', '2026-05-17', '2026-05-10 06:26:51'),
(78, 38, NULL, 'Paper', 'Mixed paper', 2.50, 'completed', '2026-05-01', '2026-05-01 06:26:51'),
(79, 38, NULL, 'Plastic', 'Mixed plastic', 3.50, 'completed', '2026-04-19', '2026-04-19 06:26:51'),
(80, 38, NULL, 'Metal', 'Mixed metal', 4.50, 'completed', '2026-04-07', '2026-04-07 06:26:51'),
(81, 38, NULL, 'Paper', 'Mixed paper', 5.50, 'completed', '2026-03-26', '2026-03-26 06:26:51'),
(82, 38, NULL, 'Paper', 'Mixed paper', 5.00, 'pending', '2026-05-17', '2026-05-10 06:26:51'),
(83, 9, NULL, 'Plastic', 'Mixed plastic', 4.50, 'completed', '2026-05-11', '2026-05-11 06:29:25'),
(84, 10, NULL, 'Metal', 'Mixed metal', 5.50, 'completed', '2026-05-11', '2026-05-11 06:29:25'),
(85, 11, NULL, 'Paper', 'Mixed paper', 6.50, 'completed', '2026-05-11', '2026-05-11 06:29:25'),
(86, 12, NULL, 'Plastic', 'Mixed plastic', 3.50, 'completed', '2026-05-11', '2026-05-11 06:29:25'),
(87, 13, NULL, 'Metal', 'Mixed metal', 4.50, 'completed', '2026-05-11', '2026-05-11 06:29:25'),
(88, 14, NULL, 'Paper', 'Mixed paper', 5.50, 'completed', '2026-05-11', '2026-05-11 06:29:25'),
(89, 15, NULL, 'Plastic', 'Mixed plastic', 6.50, 'completed', '2026-05-11', '2026-05-11 06:29:25'),
(90, 16, NULL, 'Metal', 'Mixed metal', 3.50, 'completed', '2026-05-11', '2026-05-11 06:29:25'),
(91, 17, NULL, 'Paper', 'Mixed paper', 4.50, 'completed', '2026-05-11', '2026-05-11 06:29:25'),
(92, 18, NULL, 'Plastic', 'Mixed plastic', 5.50, 'completed', '2026-05-11', '2026-05-11 06:29:25'),
(93, 101, NULL, 'Paper', 'Mixed paper', 2.20, 'completed', '2025-12-08', '2025-12-07 21:46:26'),
(94, 101, NULL, 'Paper', 'Newspaper', 2.80, 'completed', '2026-01-07', '2026-01-06 21:46:26'),
(95, 101, NULL, 'Paper', 'Cardboard / OCC', 3.40, 'completed', '2026-02-06', '2026-02-05 21:46:26'),
(96, 101, NULL, 'Paper', 'Office paper (HGP)', 4.00, 'completed', '2026-03-08', '2026-03-07 21:46:26'),
(97, 101, NULL, 'Paper', 'Mixed paper', 4.80, 'completed', '2026-04-07', '2026-04-06 21:46:26'),
(98, 101, NULL, 'Paper', 'Cardboard / OCC', 5.50, 'completed', '2026-05-07', '2026-05-06 21:46:26'),
(99, 102, NULL, 'Plastic', 'PET (#1 bottles)', 3.00, 'completed', '2025-12-08', '2025-12-07 21:46:26'),
(100, 102, NULL, 'Metal', 'Aluminium cans', 1.50, 'completed', '2026-01-07', '2026-01-06 21:46:26'),
(101, 102, NULL, 'Glass', 'Mixed glass', 4.00, 'completed', '2026-02-06', '2026-02-05 21:46:26'),
(102, 102, NULL, 'Textile', 'Mixed clothing', 2.10, 'completed', '2026-03-08', '2026-03-07 21:46:26'),
(103, 102, NULL, 'Plastic', 'HDPE (#2 bottles)', 3.50, 'completed', '2026-04-07', '2026-04-06 21:46:26'),
(104, 102, NULL, 'Metal', 'Copper wire', 1.20, 'completed', '2026-05-07', '2026-05-06 21:46:26'),
(105, 103, NULL, 'Paper', 'Mixed paper', 2.00, 'completed', '2025-12-08', '2025-12-07 21:46:26'),
(106, 103, NULL, 'E-waste', 'Mixed WEEE', 1.00, 'completed', '2026-01-07', '2026-01-06 21:46:26'),
(107, 103, NULL, 'E-waste', 'Laptops / PCs', 0.70, 'completed', '2026-02-06', '2026-02-05 21:46:26'),
(108, 103, NULL, 'E-waste', 'Mobile phones', 1.40, 'completed', '2026-03-08', '2026-03-07 21:46:26'),
(109, 103, NULL, 'E-waste', 'Circuit boards (PCB)', 0.80, 'completed', '2026-04-07', '2026-04-06 21:46:26'),
(110, 103, NULL, 'Plastic', 'Mixed plastic', 2.50, 'completed', '2026-05-07', '2026-05-06 21:46:26'),
(124, 4, NULL, 'Paper', 'Mixed paper', 4.20, 'completed', '2025-12-13', '2025-12-12 21:48:14'),
(125, 4, NULL, 'Plastic', 'PET (#1 bottles)', 5.10, 'completed', '2026-01-12', '2026-01-11 21:48:14'),
(126, 4, NULL, 'Metal', 'Aluminium cans', 3.30, 'completed', '2026-02-11', '2026-02-10 21:48:14'),
(127, 4, NULL, 'Paper', 'Cardboard / OCC', 6.00, 'completed', '2026-03-13', '2026-03-12 21:48:14'),
(128, 4, NULL, 'Plastic', 'HDPE (#2 bottles)', 5.70, 'completed', '2026-04-12', '2026-04-11 21:48:14'),
(129, 3, NULL, 'Paper', 'Mixed paper', 2.30, 'completed', '2025-12-13', '2025-12-12 21:48:14'),
(130, 3, NULL, 'Plastic', 'PET (#1 bottles)', 2.80, 'completed', '2026-01-12', '2026-01-11 21:48:14'),
(131, 3, NULL, 'Metal', 'Aluminium cans', 1.70, 'completed', '2026-02-11', '2026-02-10 21:48:14'),
(132, 3, NULL, 'Paper', 'Cardboard / OCC', 3.20, 'completed', '2026-03-13', '2026-03-12 21:48:14'),
(133, 3, NULL, 'Plastic', 'HDPE (#2 bottles)', 3.40, 'completed', '2026-04-12', '2026-04-11 21:48:14');

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
(1, 'Recycled Notebook', 'Handcrafted notebook from upcycled paper. 100 pages, eco-friendly cover.', 150, 120.00, 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=400', 22, '2026-05-11 19:59:04'),
(2, 'Upcycled Tote Bag', 'Durable tote bag made from recycled plastic bottles. Stylish and green!', 200, 180.00, 'https://images.unsplash.com/photo-1597484661643-2f5fef640dd1?w=400', 15, '2026-05-11 19:59:04'),
(3, 'Metal Pen Set', 'Set of 3 pens crafted from recycled metal scraps. Smooth writing experience.', 120, 95.00, 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=400', 40, '2026-05-11 19:59:04'),
(4, 'Eco Planter Pot', 'Small planter pot made from upcycled plastic. Perfect for desk plants.', 180, 150.00, 'https://images.unsplash.com/photo-1485955900006-10f4d324d411?w=400', 20, '2026-05-11 19:59:04'),
(5, 'Recycled Coaster Set', 'Set of 4 coasters made from compressed recycled materials. Heat resistant.', 100, 80.00, 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400', 49, '2026-05-11 19:59:04'),
(7, 'Eco vass', 'OKK', 200, 120.00, 'https://cdn.shopify.com/s/files/1/0638/7721/8543/files/HB_FranceCh_000.jpg?v=1721225233', 20, '2026-05-11 20:44:40');

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
(1, 3, 250, 250, '2026-05-11 21:48:48'),
(2, 4, 50, 200, '2026-05-11 22:12:47'),
(12, 7, 0, 0, '2026-05-12 01:00:11'),
(13, 9, 130, 130, '2026-05-12 10:29:25'),
(14, 10, 140, 140, '2026-05-12 10:29:25'),
(15, 11, 120, 120, '2026-05-12 10:29:25'),
(16, 12, 130, 130, '2026-05-12 10:29:25'),
(17, 13, 140, 140, '2026-05-12 10:29:25'),
(18, 14, 120, 120, '2026-05-12 10:29:25'),
(19, 15, 130, 130, '2026-05-12 10:29:25'),
(20, 16, 140, 140, '2026-05-12 10:29:25'),
(21, 17, 120, 120, '2026-05-12 10:29:25'),
(22, 18, 130, 130, '2026-05-12 10:29:25'),
(23, 19, 68, 68, '2026-05-12 10:26:51'),
(24, 20, 71, 71, '2026-05-12 10:26:51'),
(25, 21, 74, 74, '2026-05-12 10:26:51'),
(26, 22, 77, 77, '2026-05-12 10:26:51'),
(27, 23, 80, 80, '2026-05-12 10:26:51'),
(28, 24, 83, 83, '2026-05-12 10:26:51'),
(29, 25, 86, 86, '2026-05-12 10:26:51'),
(30, 26, 89, 89, '2026-05-12 10:26:51'),
(31, 27, 92, 92, '2026-05-12 10:26:51'),
(32, 28, 95, 95, '2026-05-12 10:26:51'),
(33, 29, 472, 472, '2026-05-12 10:26:51'),
(34, 30, 484, 484, '2026-05-12 10:26:51'),
(35, 31, 496, 496, '2026-05-12 10:26:51'),
(36, 32, 508, 508, '2026-05-12 10:26:51'),
(37, 33, 520, 520, '2026-05-12 10:26:51'),
(38, 34, 532, 532, '2026-05-12 10:26:51'),
(39, 35, 544, 544, '2026-05-12 10:26:51'),
(40, 36, 556, 556, '2026-05-12 10:26:51'),
(41, 37, 568, 568, '2026-05-12 10:26:51'),
(42, 38, 580, 580, '2026-05-12 10:26:51'),
(103, 101, 650, 650, '2026-05-12 21:46:26'),
(104, 102, 720, 720, '2026-05-12 21:46:26'),
(105, 103, 900, 900, '2026-05-12 21:46:26');

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
(1, 'Admin User', 'admin1@gmail.com', '$2y$10$GdxcQ9EsjOHRlzRfujyKFOUPkFIxv7POwZU9aM7MHhrbKxLHW9r2W', 'Dhaka, Bangladesh', '01700000000', NULL, 'admin', '2026-05-11 19:59:04'),
(2, 'Green Agency', 'agency@notunalo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Chittagong, Bangladesh', '01800000000', NULL, 'agency', '2026-05-11 19:59:04'),
(3, 'Test User', 'user@notunalo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sylhet, Bangladesh', '01900000000', NULL, 'user', '2026-05-11 19:59:04'),
(4, 'Rabbi Sadnan Khan', 'rabbisadnankhan@gmail.com', '$2y$10$kLzExuVOvraqdrbZ7HhVt.81zBRXcdKrI2K9lDH0q.56o0lL6ed16', 'Baiterek Tower, Left Bank', '', 'https://cdn.shopify.com/s/files/1/0638/7721/8543/files/HB_FranceCh_000.jpg?v=1721225233', 'user', '2026-05-11 20:07:34'),
(5, 'pathao', 'agent1@gmail.com', '$2y$10$tpa5kjz6R8/IPVomT6l7weso07lwTyjWpt/BYRgVqozmYXV.bzuvy', 'Dhaka, Bangladesh', '01700000001', NULL, 'agency', '2026-05-11 20:40:19'),
(6, 'speedx', 'agent2@gmail.com', '$2y$10$tpa5kjz6R8/IPVomT6l7weso07lwTyjWpt/BYRgVqozmYXV.bzuvy', 'Dhaka, Bangladesh', '01700000002', NULL, 'agency', '2026-05-11 20:40:19'),
(7, 'Shariar Islam', 'yaaa@gmail.com', '$2y$10$mHfUmmDLD/UfN2ZIebdvE.srIf8O/ks7pPH9gzO/Ho123EEeLp4P6', 'sdd', '01711360963', NULL, 'user', '2026-05-12 01:00:11'),
(8, 'AI Demo Admin', 'aiadmin@notunalo.test', '$2y$10$3XGNdhEA35f/V.iZn8aDkuN6271Jw6rGcXJ0d23IhRlbcbs803QlW', 'Dhaka, Bangladesh', '01799000000', NULL, 'admin', '2026-05-12 10:26:51'),
(9, 'Churn Demo User 01', 'churn001@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Dhaka, Bangladesh', '01777000001', NULL, 'user', '2026-05-03 10:26:51'),
(10, 'Churn Demo User 02', 'churn002@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Chattogram, Bangladesh', '01777000002', NULL, 'user', '2026-05-02 10:26:51'),
(11, 'Churn Demo User 03', 'churn003@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Sylhet, Bangladesh', '01777000003', NULL, 'user', '2026-05-01 10:26:51'),
(12, 'Churn Demo User 04', 'churn004@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Rajshahi, Bangladesh', '01777000004', NULL, 'user', '2026-04-30 10:26:51'),
(13, 'Churn Demo User 05', 'churn005@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Khulna, Bangladesh', '01777000005', NULL, 'user', '2026-04-29 10:26:51'),
(14, 'Churn Demo User 06', 'churn006@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Narayanganj, Bangladesh', '01777000006', NULL, 'user', '2026-04-28 10:26:51'),
(15, 'Churn Demo User 07', 'churn007@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Gazipur, Bangladesh', '01777000007', NULL, 'user', '2026-04-27 10:26:51'),
(16, 'Churn Demo User 08', 'churn008@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Dhaka, Bangladesh', '01777000008', NULL, 'user', '2026-04-26 10:26:51'),
(17, 'Churn Demo User 09', 'churn009@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Chattogram, Bangladesh', '01777000009', NULL, 'user', '2026-04-25 10:26:51'),
(18, 'Churn Demo User 10', 'churn010@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Sylhet, Bangladesh', '01777000010', NULL, 'user', '2026-04-24 10:26:51'),
(19, 'Churn Demo User 11', 'churn011@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Rajshahi, Bangladesh', '01777000011', NULL, 'user', '2026-03-17 10:26:51'),
(20, 'Churn Demo User 12', 'churn012@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Khulna, Bangladesh', '01777000012', NULL, 'user', '2026-03-16 10:26:51'),
(21, 'Churn Demo User 13', 'churn013@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Narayanganj, Bangladesh', '01777000013', NULL, 'user', '2026-03-15 10:26:51'),
(22, 'Churn Demo User 14', 'churn014@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Gazipur, Bangladesh', '01777000014', NULL, 'user', '2026-03-14 10:26:51'),
(23, 'Churn Demo User 15', 'churn015@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Dhaka, Bangladesh', '01777000015', NULL, 'user', '2026-03-13 10:26:51'),
(24, 'Churn Demo User 16', 'churn016@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Chattogram, Bangladesh', '01777000016', NULL, 'user', '2026-03-12 10:26:51'),
(25, 'Churn Demo User 17', 'churn017@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Sylhet, Bangladesh', '01777000017', NULL, 'user', '2026-03-11 10:26:51'),
(26, 'Churn Demo User 18', 'churn018@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Rajshahi, Bangladesh', '01777000018', NULL, 'user', '2026-03-10 10:26:51'),
(27, 'Churn Demo User 19', 'churn019@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Khulna, Bangladesh', '01777000019', NULL, 'user', '2026-03-09 10:26:51'),
(28, 'Churn Demo User 20', 'churn020@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Narayanganj, Bangladesh', '01777000020', NULL, 'user', '2026-03-08 10:26:51'),
(29, 'Churn Demo User 21', 'churn021@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Gazipur, Bangladesh', '01777000021', NULL, 'user', '2025-09-10 10:26:51'),
(30, 'Churn Demo User 22', 'churn022@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Dhaka, Bangladesh', '01777000022', NULL, 'user', '2025-09-06 10:26:51'),
(31, 'Churn Demo User 23', 'churn023@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Chattogram, Bangladesh', '01777000023', NULL, 'user', '2025-09-02 10:26:51'),
(32, 'Churn Demo User 24', 'churn024@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Sylhet, Bangladesh', '01777000024', NULL, 'user', '2025-08-29 10:26:51'),
(33, 'Churn Demo User 25', 'churn025@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Rajshahi, Bangladesh', '01777000025', NULL, 'user', '2025-08-25 10:26:51'),
(34, 'Churn Demo User 26', 'churn026@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Khulna, Bangladesh', '01777000026', NULL, 'user', '2025-08-21 10:26:51'),
(35, 'Churn Demo User 27', 'churn027@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Narayanganj, Bangladesh', '01777000027', NULL, 'user', '2025-08-17 10:26:51'),
(36, 'Churn Demo User 28', 'churn028@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Gazipur, Bangladesh', '01777000028', NULL, 'user', '2025-08-13 10:26:51'),
(37, 'Churn Demo User 29', 'churn029@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Dhaka, Bangladesh', '01777000029', NULL, 'user', '2025-08-09 10:26:51'),
(38, 'Churn Demo User 30', 'churn030@notunalo.test', '$2y$10$0BwDr.OrVDpsrGnHn3EC0uqutoyYWFDE9XTBn1Q84W/VwOCJUQe36', 'Chattogram, Bangladesh', '01777000030', NULL, 'user', '2025-08-05 10:26:51'),
(101, 'Impact Demo Paper', 'impact001@notunalo.test', '$2y$10$P0wlTfdbhB7ZlGaWqNt7IO.gHKqkeErSPo8mFYtMwFHVYhb5r3Xn.', 'Dhaka, Bangladesh', '019880001', NULL, 'user', '2025-11-13 21:46:26'),
(102, 'Impact Demo Mixed', 'impact002@notunalo.test', '$2y$10$P0wlTfdbhB7ZlGaWqNt7IO.gHKqkeErSPo8mFYtMwFHVYhb5r3Xn.', 'Chattogram, Bangladesh', '019880002', NULL, 'user', '2025-11-13 21:46:26'),
(103, 'Impact Demo E-waste', 'impact003@notunalo.test', '$2y$10$P0wlTfdbhB7ZlGaWqNt7IO.gHKqkeErSPo8mFYtMwFHVYhb5r3Xn.', 'Sylhet, Bangladesh', '019880003', NULL, 'user', '2025-11-13 21:46:26');

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
-- Dumping data for table `user_ml_scores`
--

INSERT INTO `user_ml_scores` (`user_id`, `churn_score`, `risk_label`, `updated_at`) VALUES
(3, 0.53000, 'medium', '2026-05-12 10:29:40'),
(4, 0.34000, 'low', '2026-05-12 10:29:40'),
(7, 0.16000, 'low', '2026-05-12 10:29:40'),
(9, 0.76000, 'high', '2026-05-12 10:29:40'),
(10, 0.73000, 'high', '2026-05-12 10:29:40'),
(11, 0.49000, 'medium', '2026-05-12 10:29:40'),
(12, 0.79000, 'high', '2026-05-12 10:29:40'),
(13, 0.76000, 'high', '2026-05-12 10:29:40'),
(14, 0.49000, 'medium', '2026-05-12 10:29:40'),
(15, 0.79000, 'high', '2026-05-12 10:29:40'),
(16, 0.73000, 'high', '2026-05-12 10:29:40'),
(17, 0.48000, 'medium', '2026-05-12 10:29:40'),
(18, 0.79000, 'high', '2026-05-12 10:29:40'),
(19, 0.34000, 'low', '2026-05-12 10:29:40'),
(20, 0.45000, 'medium', '2026-05-12 10:29:40'),
(21, 0.34000, 'low', '2026-05-12 10:29:40'),
(22, 0.61000, 'medium', '2026-05-12 10:29:40'),
(23, 0.25000, 'low', '2026-05-12 10:29:40'),
(24, 0.62000, 'medium', '2026-05-12 10:29:40'),
(25, 0.30000, 'low', '2026-05-12 10:29:40'),
(26, 0.28000, 'low', '2026-05-12 10:29:40'),
(27, 0.30000, 'low', '2026-05-12 10:29:40'),
(28, 0.52000, 'medium', '2026-05-12 10:29:40'),
(29, 0.05000, 'low', '2026-05-12 10:29:40'),
(30, 0.13000, 'low', '2026-05-12 10:29:40'),
(31, 0.13000, 'low', '2026-05-12 10:29:40'),
(32, 0.09000, 'low', '2026-05-12 10:29:40'),
(33, 0.13000, 'low', '2026-05-12 10:29:40'),
(34, 0.13000, 'low', '2026-05-12 10:29:40'),
(35, 0.06000, 'low', '2026-05-12 10:29:40'),
(36, 0.11000, 'low', '2026-05-12 10:29:40'),
(37, 0.13000, 'low', '2026-05-12 10:29:40'),
(38, 0.06000, 'low', '2026-05-12 10:29:40');

-- --------------------------------------------------------

--
-- Structure for view `category_averages`
--
DROP TABLE IF EXISTS `category_averages`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `category_averages`  AS SELECT `emission_factors`.`category` AS `category`, round(avg(`emission_factors`.`co2_sa_adjusted`),4) AS `avg_co2`, round(avg(`emission_factors`.`water_liters_per_kg`),2) AS `avg_water_liters_per_kg`, round(avg(`emission_factors`.`energy_kwh_per_kg`),4) AS `avg_energy_kwh_per_kg` FROM `emission_factors` GROUP BY `emission_factors`.`category` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `emission_factors`
--
ALTER TABLE `emission_factors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_subcategory` (`subcategory`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `fk_orders_agency` (`agency_id`);

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
-- AUTO_INCREMENT for table `emission_factors`
--
ALTER TABLE `emission_factors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pickups`
--
ALTER TABLE `pickups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `rewards`
--
ALTER TABLE `rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_agency` FOREIGN KEY (`agency_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

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

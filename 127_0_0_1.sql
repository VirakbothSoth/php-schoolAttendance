-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 25, 2026 at 11:27 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `exam`
--
CREATE DATABASE IF NOT EXISTS `exam` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `exam`;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('attended','absent') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `attendance_date`, `status`) VALUES
(1, 3, '2026-09-24', 'attended'),
(3, 4, '2026-09-24', 'absent'),
(4, 3, '2026-09-25', 'attended'),
(5, 4, '2026-09-25', 'attended'),
(6, 3, '2026-09-01', 'attended'),
(7, 4, '2026-09-01', 'attended'),
(8, 3, '2026-09-02', 'attended'),
(9, 4, '2026-09-02', 'attended'),
(10, 3, '2026-09-03', 'attended'),
(11, 4, '2026-09-03', 'attended'),
(12, 3, '2026-09-04', 'attended'),
(13, 4, '2026-09-04', 'attended'),
(14, 3, '2026-09-08', 'attended'),
(15, 4, '2026-09-08', 'attended'),
(16, 3, '2026-09-09', 'attended'),
(17, 4, '2026-09-09', 'attended'),
(18, 3, '2026-09-10', 'attended'),
(19, 4, '2026-09-10', 'attended'),
(20, 3, '2026-09-11', 'attended'),
(21, 4, '2026-09-11', 'attended'),
(22, 3, '2026-09-15', 'attended'),
(23, 4, '2026-09-15', 'attended'),
(24, 3, '2026-09-16', 'attended'),
(25, 4, '2026-09-16', 'attended'),
(26, 3, '2026-09-17', 'attended'),
(27, 4, '2026-09-17', 'attended'),
(28, 3, '2026-09-18', 'attended'),
(29, 4, '2026-09-18', 'attended'),
(30, 3, '2026-09-22', 'attended'),
(31, 4, '2026-09-22', 'attended'),
(32, 3, '2026-09-23', 'attended'),
(33, 4, '2026-09-23', 'attended');

-- --------------------------------------------------------

--
-- Table structure for table `student_subject_scores`
--

CREATE TABLE `student_subject_scores` (
  `student_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `score` decimal(7,2) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_subject_scores`
--

INSERT INTO `student_subject_scores` (`student_id`, `subject_id`, `score`) VALUES
(3, 1, 10.00),
(3, 2, 10.00),
(4, 1, 9.00),
(4, 2, 12.00);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `default_score` decimal(7,2) UNSIGNED NOT NULL,
  `max_score` decimal(7,2) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `default_score`, `max_score`) VALUES
(1, 'PHP', 10.00, 12.00),
(2, 'Web Fundamentals', 10.00, 12.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `username` varchar(80) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','staff') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `username`, `password`, `role`) VALUES
(1, 'sanc', 'sanc', '$2y$10$vbK0C73V6b0LcCYZjZ.WWO5VHKM7CsTV6xvF83BIitHRymum7XgNe', 'staff'),
(2, 'Sanctuary Katsunaga', 'teacher', '$2y$10$5vW90LyLHBWKT7NNhtl..eOhcWNc5o3tJzOlqBuHXgwE/qnKWjhHO', 'staff'),
(3, 'Virakboth Soth', 'virakbothsoth', '$2y$10$LKo.sjEnyMSVNHiCqOSKU.U1STFZvthhtEwqOjq09T9NF9SwgYSrC', 'student'),
(4, 'Bong Bopha', 'bopha', '$2y$10$vbK0C73V6b0LcCYZjZ.WWO5VHKM7CsTV6xvF83BIitHRymum7XgNe', 'student');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attendance_student_date_unique` (`student_id`,`attendance_date`);

--
-- Indexes for table `student_subject_scores`
--
ALTER TABLE `student_subject_scores`
  ADD PRIMARY KEY (`student_id`,`subject_id`),
  ADD KEY `student_subject_scores_subject_fk` (`subject_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subjects_name_unique` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_username_unique` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_student_fk` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_subject_scores`
--
ALTER TABLE `student_subject_scores`
  ADD CONSTRAINT `student_subject_scores_student_fk` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_subject_scores_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

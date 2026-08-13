-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 11, 2026 at 02:59 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dms`
--
CREATE DATABASE IF NOT EXISTS `dms` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `dms`;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `postId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `commentText` text NOT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `postId`, `userId`, `commentText`, `createdAt`) VALUES
(1, 15, 1, 'Yeah I Agree with that', '2026-08-11 12:13:10'),
(2, 15, 24, 'What can we Do for this', '2026-08-11 12:13:47'),
(3, 15, 1, 'Agree', '2026-08-11 12:38:33');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `postId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `location` varchar(128) DEFAULT 'No',
  `status` enum('pending','approved','resolved') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`postId`, `userId`, `title`, `description`, `location`, `status`, `created_at`) VALUES
(15, 1, 'Climate emergencies photos of 2026 so far', '2026 has been quite an eventful year… and it’s only been three months! By March, we have seen a huge range of climate disasters in almost every part of the Earth. People around the world feel the effects, especially in areas vulnerable to the climate crisis. From forest fires in Chile to snowstorms in Japan, from cyclone in Sri Lanka to flooding in Brazil, France, and Kenya.\r\n\r\nScientists have warned us for years about the link between erratic weather, extreme heat, and heavy rainfall. These are clear signs of a climate emergency. Severe climate change shows in the polar vortex, flash flooding, and extreme weather. This isn’t a coincidence; it’s a choice made by politicians and a focus for the economy.\r\n\r\nSince February 2026, we have seen billions of dollars being poured into the war against Iran. If only we had this kind of commitment and budgets promised at UNFCCC meetings or in securing the future of the communities living under the threat of climate change. Fighting climate change means pushing for strong public policies. It also involves stopping fossil fuel expansion and investing in cities that can protect lives. Governments and companies should look beyond profits. They need to think about the millions of people impacted. We need to start rethinking our priorities and focus on what’s really urgent. NOW\r\n\r\nThis is a short visual reminder of a few climate emergencies this year.\r\n\r\n🇨🇱 Chile: In the 2025-2026 wildfire season alone, more than three thousand wildfires have already been recorded. Nationwide, the burned area is 193% higher than that of the previous season (2024-2025). The Ñuble, Biobío, and La Araucanía regions, among the hardest hit in southern Chile.', 'No', 'approved', '2026-08-11 12:10:08');

-- --------------------------------------------------------

--
-- Table structure for table `userData`
--

CREATE TABLE `userData` (
  `profileId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `fullName` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `university` varchar(128) DEFAULT 'No_University',
  `studentId` varchar(32) DEFAULT 'No_StudentID',
  `contactNumber` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `userData`
--

INSERT INTO `userData` (`profileId`, `userId`, `fullName`, `email`, `university`, `studentId`, `contactNumber`) VALUES
(9, 24, 'RASHMIKA VIHANGA', 'xrashmikavihanga@gmail.com', 'UOC', '2025is103', 778063588),
(10, 26, 'Dr.Samantha', 'samantha@ucsc.ac.lk', 'UOC', '', 767210359),
(11, 27, 'MR.Kottu', 'kottutakatka@gmail.com', 'UOC', '', NULL),
(12, 28, 'php Samantha', 'samanthaPettiAracchi@gmail.com', 'UOC', '', 767210359);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userId` int(11) NOT NULL,
  `userName` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','ordinary') DEFAULT 'ordinary'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userId`, `userName`, `password`, `role`) VALUES
(1, 'admin', '$2y$10$h5hHup2pdVSh48Z7hv7kGOg499ckjHV2BUOlogEcWt74HT9gi.bUS', 'admin'),
(24, 'vihanga', '$2y$10$wv9Jcg9vIgowO6WAz5eOn.LArVBInVWjZCCBkm693jcIGVFKtBnhG', 'ordinary'),
(26, 'samantha', '$2y$10$D43k.z6Iej3wtCk1bAKBQOCxGotmDEamMQHU/I3CD8YKIjwuUpnL.', 'ordinary'),
(27, 'kottuwa', '$2y$10$yC6xaOcE3uqbCccekG2R/ujE3TBftJiHtF0Bki5EU8lccOVgN3Lz2', 'ordinary'),
(28, 'sama', '$2y$10$jr1XGYIv.VO./4FXc.ZtL.NckoVX8W1ZaBowH5FffIUduuXENA33.', 'ordinary');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `postId` (`postId`),
  ADD KEY `userId` (`userId`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`postId`),
  ADD KEY `userId` (`userId`);

--
-- Indexes for table `userData`
--
ALTER TABLE `userData`
  ADD PRIMARY KEY (`profileId`),
  ADD UNIQUE KEY `userId` (`userId`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userId`),
  ADD UNIQUE KEY `userName` (`userName`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `postId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `userData`
--
ALTER TABLE `userData`
  MODIFY `profileId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`postId`) REFERENCES `posts` (`postId`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`userId`) REFERENCES `users` (`userId`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `users` (`userId`) ON DELETE CASCADE;

--
-- Constraints for table `userData`
--
ALTER TABLE `userData`
  ADD CONSTRAINT `userdata_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `users` (`userId`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

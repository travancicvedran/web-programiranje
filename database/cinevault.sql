-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 25, 2026 at 03:57 PM
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
-- Database: `cinevault`
--

-- --------------------------------------------------------

--
-- Table structure for table `films`
--

CREATE TABLE `films` (
  `Naslov` varchar(255) NOT NULL,
  `Zanr` varchar(100) NOT NULL,
  `Godina` int(11) NOT NULL,
  `Trajanje_min` int(11) NOT NULL,
  `Ocjena` decimal(2,1) DEFAULT NULL,
  `Rezisery` varchar(255) NOT NULL,
  `Zemlja_porijekla` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `films`
--

INSERT INTO `films` (`Naslov`, `Zanr`, `Godina`, `Trajanje_min`, `Ocjena`, `Rezisery`, `Zemlja_porijekla`) VALUES
('12 Angry Men', 'Crime, Drama', 1957, 96, 9.0, 'Sidney Lumet', 'USA'),
('Back to the Future', 'Adventure, Comedy', 1985, 116, 8.5, 'Robert Zemeckis', 'USA'),
('City of God', 'Crime, Drama', 2002, 130, 8.6, 'Fernando Meirelles', 'Brazil'),
('Fight Club', 'Drama', 1999, 139, 8.8, 'David Fincher', 'USA'),
('Gladiator', 'Action, Adventure', 2000, 155, 8.5, 'Ridley Scott', 'USA/UK'),
('Goodfellas', 'Biography, Crime', 1990, 145, 8.7, 'Martin Scorsese', 'USA'),
('Il Buono, il Brutto, il Cattivo', 'Western', 1966, 161, 8.8, 'Sergio Leone', 'Italy'),
('Inception', 'Action, Adventure', 2010, 148, 8.8, 'Christopher Nolan', 'USA/UK'),
('Interstellar', 'Adventure, Drama', 2014, 169, 8.7, 'Christopher Nolan', 'USA/UK'),
('Life Is Beautiful', 'Comedy, Drama', 1997, 116, 8.6, 'Roberto Benigni', 'Italy'),
('Naslov', 'Zanr', 0, 0, 0.0, 'Rezisery', 'Zemlja_porijekla'),
('One Flew Over the Cuckoo\'s Nest', 'Drama', 1975, 133, 8.7, 'Milos Forman', 'USA'),
('Parasite', 'Drama, Thriller', 2019, 132, 8.5, 'Bong Joon Ho', 'South Korea'),
('Psycho', 'Horror, Mystery', 1960, 109, 8.5, 'Alfred Hitchcock', 'USA'),
('Pulp Fiction', 'Crime, Drama', 1994, 154, 8.9, 'Quentin Tarantino', 'USA'),
('Saving Private Ryan', 'Drama, War', 1998, 169, 8.6, 'Steven Spielberg', 'USA'),
('Schindler\'s List', 'Biography, Drama', 1993, 195, 9.0, 'Steven Spielberg', 'USA'),
('Se7en', 'Crime, Drama', 1995, 127, 8.6, 'David Fincher', 'USA'),
('Seven Samurai', 'Action, Drama', 1954, 207, 8.6, 'Akira Kurosawa', 'Japan'),
('Star Wars: Episode IV - A New Hope', 'Action, Adventure', 1977, 121, 8.6, 'George Lucas', 'USA'),
('Terminator 2: Judgment Day', 'Action, Sci-Fi', 1991, 137, 8.6, 'James Cameron', 'USA'),
('The Dark Knight', 'Action, Crime', 2008, 152, 9.0, 'Christopher Nolan', 'UK/USA'),
('The Departed', 'Crime, Drama', 2006, 151, 8.5, 'Martin Scorsese', 'USA'),
('The Godfather', 'Crime, Drama', 1972, 175, 9.2, 'Francis Ford Coppola', 'USA'),
('The Green Mile', 'Crime, Drama', 1999, 189, 8.6, 'Frank Darabont', 'USA'),
('The Lion King', 'Animation, Adventure', 1994, 88, 8.5, 'Roger Allers', 'USA'),
('The Lord of the Rings: The Return of the King', 'Action, Adventure', 2003, 201, 9.0, 'Peter Jackson', 'NZ/USA'),
('The Matrix', 'Action, Sci-Fi', 1999, 136, 8.7, 'Lana Wachowski', 'USA'),
('The Pianist', 'Biography, Drama', 2002, 150, 8.5, 'Roman Polanski', 'France/Poland'),
('The Shawshank Redemption', 'Drama', 1994, 142, 9.3, 'Frank Darabont', 'USA'),
('The Silence of the Lambs', 'Crime, Drama', 1991, 118, 8.6, 'Jonathan Demme', 'USA');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `films`
--
ALTER TABLE `films`
  ADD PRIMARY KEY (`Naslov`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

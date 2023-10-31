-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 31, 2023 at 08:36 AM
-- Server version: 10.4.25-MariaDB
-- PHP Version: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pathologylab_final`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `CalculateTestPrice` (IN `patient_age` INT, IN `test_type` VARCHAR(255), OUT `test_price` DECIMAL(8,2))   BEGIN
    SELECT
        CASE
            WHEN test_type = 'bloodtest' THEN
                (SELECT blood_test_price FROM price WHERE patient_age >= min_age AND patient_age <= max_age LIMIT 1)
            WHEN test_type = 'urinetest' THEN
                (SELECT urine_test_price FROM price WHERE patient_age >= min_age AND patient_age <= max_age LIMIT 1)
            WHEN test_type = 'radiologytest' THEN
                (SELECT radiology_test_price FROM price WHERE patient_age >= min_age AND patient_age <= max_age LIMIT 1)
            ELSE 0.00  -- Default price for unknown test type
        END
    INTO test_price;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `appointment`
--

CREATE TABLE `appointment` (
  `AppointmentID` int(11) NOT NULL,
  `PatientID` int(11) DEFAULT NULL,
  `AppointmentDate` date DEFAULT NULL,
  `AppointmentTime` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `appointment`
--

INSERT INTO `appointment` (`AppointmentID`, `PatientID`, `AppointmentDate`, `AppointmentTime`) VALUES
(21, 23, '2023-10-28', '14:00:00'),
(23, 25, '2023-10-31', '13:00:00'),
(24, 24, '2023-10-31', '10:00:00'),
(25, 24, '2023-11-01', '10:00:00'),
(26, 24, '2023-10-31', '14:00:00'),
(27, 28, '2023-11-03', '16:00:00'),
(28, 29, '2023-11-01', '15:00:00'),
(29, 30, '2023-11-01', '13:00:00');

--
-- Triggers `appointment`
--
DELIMITER $$
CREATE TRIGGER `after_appointment_delete` AFTER DELETE ON `appointment` FOR EACH ROW BEGIN
    INSERT INTO appointment_log (appointment_id, patient_id, deletion_time)
    VALUES (OLD.AppointmentID, OLD.PatientID, NOW());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `appointments_for_date`
-- (See below for the actual view)
--
CREATE TABLE `appointments_for_date` (
`AppointmentID` int(11)
,`Name` varchar(255)
,`AppointmentDate` date
,`AppointmentTime` time
);

-- --------------------------------------------------------

--
-- Table structure for table `appointment_log`
--

CREATE TABLE `appointment_log` (
  `log_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `deletion_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `bloodtest`
--

CREATE TABLE `bloodtest` (
  `TestID` int(11) NOT NULL,
  `BloodType` varchar(255) DEFAULT NULL,
  `HaemoglobinLevel` decimal(10,2) DEFAULT NULL,
  `WBCount` decimal(10,2) DEFAULT NULL,
  `RBCCount` decimal(10,2) DEFAULT NULL,
  `PlateletCount` decimal(10,2) DEFAULT NULL,
  `AppointmentID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `bloodtest`
--

INSERT INTO `bloodtest` (`TestID`, `BloodType`, `HaemoglobinLevel`, `WBCount`, `RBCCount`, `PlateletCount`, `AppointmentID`) VALUES
(7, 'a+', '1.00', '2.00', '111.00', '1.00', 21),
(9, NULL, NULL, NULL, NULL, NULL, 24),
(10, NULL, NULL, NULL, NULL, NULL, 26),
(11, 'o+', '69.00', '77.00', '12.00', '0.00', 27),
(12, 'B+', '231.00', '213213.00', '12321321.00', '231321.00', 28);

-- --------------------------------------------------------

--
-- Table structure for table `pathologist`
--

CREATE TABLE `pathologist` (
  `PathologistID` int(11) NOT NULL,
  `LabID` int(11) DEFAULT NULL,
  `Name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `pathologist`
--

INSERT INTO `pathologist` (`PathologistID`, `LabID`, `Name`) VALUES
(1, 1, 'Dr. Smith'),
(2, 2, 'Dr. Johnson'),
(3, 3, 'Dr. Brown');

-- --------------------------------------------------------

--
-- Table structure for table `pathologylab`
--

CREATE TABLE `pathologylab` (
  `LabID` int(11) NOT NULL,
  `PatientID` int(11) DEFAULT NULL,
  `LabName` varchar(255) DEFAULT NULL,
  `Location` varchar(255) DEFAULT NULL,
  `Final_Report_Status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `pathologylab`
--

INSERT INTO `pathologylab` (`LabID`, `PatientID`, `LabName`, `Location`, `Final_Report_Status`) VALUES
(1, 1, 'Lab A', '123 Lab St', 'Complete'),
(2, 2, 'Lab B', '456 Lab St', 'Pending'),
(3, 3, 'Lab C', '789 Lab St', 'Complete');

-- --------------------------------------------------------

--
-- Table structure for table `patient`
--

CREATE TABLE `patient` (
  `PatientID` int(11) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `DOB` date DEFAULT NULL,
  `Gender` varchar(10) DEFAULT NULL,
  `Contact` varchar(255) DEFAULT NULL,
  `Address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `patient`
--

INSERT INTO `patient` (`PatientID`, `Name`, `Email`, `DOB`, `Gender`, `Contact`, `Address`) VALUES
(23, 'Sachit', 'sachitdesai11@gmail.com', '2003-05-11', 'Male', '1111111111', 'Pune'),
(24, 'bob', 'bob@bob.com', '2023-10-03', 'Male', '0123456789', 'pune'),
(28, 'anon', 'anon@gmail.com', '2023-06-07', 'Male', '1111111111', 'baner pune'),
(29, 'Saahil ', 'saahil.shaikh.btech2021@sitpune.edu.in', '2003-12-30', 'Male', '9720331701', 'Pune'),
(30, 'Rahul Gandhi', 'rogitiw473@ilusale.com', '1950-10-29', 'Male', '6453423121', '3-Sharda Vihar\r\nNalapani Chowk, Adhoiwala\r\nSahastradhara Road');

-- --------------------------------------------------------

--
-- Table structure for table `patient_deleted_log`
--

CREATE TABLE `patient_deleted_log` (
  `LogID` int(11) NOT NULL,
  `PatientID` int(11) NOT NULL,
  `DeletedTimestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `patient_deleted_log`
--

INSERT INTO `patient_deleted_log` (`LogID`, `PatientID`, `DeletedTimestamp`) VALUES
(1, 25, '2023-10-28 17:40:45'),
(2, 27, '2023-10-30 07:58:00');

-- --------------------------------------------------------

--
-- Table structure for table `price`
--

CREATE TABLE `price` (
  `price_id` int(11) NOT NULL,
  `min_age` int(11) DEFAULT NULL,
  `max_age` int(11) DEFAULT NULL,
  `blood_test_price` decimal(8,2) DEFAULT NULL,
  `urine_test_price` decimal(8,2) DEFAULT NULL,
  `radiology_test_price` decimal(8,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `price`
--

INSERT INTO `price` (`price_id`, `min_age`, `max_age`, `blood_test_price`, `urine_test_price`, `radiology_test_price`) VALUES
(1, 0, 12, '20.00', '25.00', '50.00'),
(2, 13, 59, '30.00', '35.00', '60.00'),
(3, 60, 200, '25.00', '30.00', '55.00');

-- --------------------------------------------------------

--
-- Table structure for table `radiologytest`
--

CREATE TABLE `radiologytest` (
  `TestID` int(11) NOT NULL,
  `ScanType` varchar(255) DEFAULT NULL,
  `ScanDate` date DEFAULT NULL,
  `AppointmentID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `radiologytest`
--

INSERT INTO `radiologytest` (`TestID`, `ScanType`, `ScanDate`, `AppointmentID`) VALUES
(6, 'x ray', '2023-10-27', 23),
(7, 'X-Ray', '2023-10-27', 29);

-- --------------------------------------------------------

--
-- Table structure for table `scan`
--

CREATE TABLE `scan` (
  `SampleID` int(11) NOT NULL,
  `ScanType` varchar(255) DEFAULT NULL,
  `ScanDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `scan`
--

INSERT INTO `scan` (`SampleID`, `ScanType`, `ScanDate`) VALUES
(3, 'X-Ray', '2023-10-17');

-- --------------------------------------------------------

--
-- Table structure for table `urinetest`
--

CREATE TABLE `urinetest` (
  `TestID` int(11) NOT NULL,
  `UrineColor` varchar(255) DEFAULT NULL,
  `UrineAppearance` varchar(255) DEFAULT NULL,
  `pHLevel` decimal(5,2) DEFAULT NULL,
  `SpecificGravity` decimal(5,2) DEFAULT NULL,
  `ProteinPresence` varchar(255) DEFAULT NULL,
  `GlucoseLevel` decimal(10,2) DEFAULT NULL,
  `KetoneLevel` decimal(10,2) DEFAULT NULL,
  `AppointmentID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `urinetest`
--

INSERT INTO `urinetest` (`TestID`, `UrineColor`, `UrineAppearance`, `pHLevel`, `SpecificGravity`, `ProteinPresence`, `GlucoseLevel`, `KetoneLevel`, `AppointmentID`) VALUES
(7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 25);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','pathologist') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'adminuser', 'adminpassword', 'admin'),
(2, 'pathologistuser', 'pathologistpassword', 'pathologist');

-- --------------------------------------------------------

--
-- Structure for view `appointments_for_date`
--
DROP TABLE IF EXISTS `appointments_for_date`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `appointments_for_date`  AS SELECT `appointment`.`AppointmentID` AS `AppointmentID`, `patient`.`Name` AS `Name`, `appointment`.`AppointmentDate` AS `AppointmentDate`, `appointment`.`AppointmentTime` AS `AppointmentTime` FROM (`appointment` join `patient` on(`appointment`.`PatientID` = `patient`.`PatientID`))  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointment`
--
ALTER TABLE `appointment`
  ADD PRIMARY KEY (`AppointmentID`),
  ADD KEY `PatientID` (`PatientID`);

--
-- Indexes for table `appointment_log`
--
ALTER TABLE `appointment_log`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `bloodtest`
--
ALTER TABLE `bloodtest`
  ADD PRIMARY KEY (`TestID`),
  ADD KEY `AppointmentID` (`AppointmentID`);

--
-- Indexes for table `pathologist`
--
ALTER TABLE `pathologist`
  ADD PRIMARY KEY (`PathologistID`),
  ADD KEY `LabID` (`LabID`);

--
-- Indexes for table `pathologylab`
--
ALTER TABLE `pathologylab`
  ADD PRIMARY KEY (`LabID`),
  ADD KEY `PatientID` (`PatientID`);

--
-- Indexes for table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`PatientID`),
  ADD UNIQUE KEY `Name` (`Name`);

--
-- Indexes for table `patient_deleted_log`
--
ALTER TABLE `patient_deleted_log`
  ADD PRIMARY KEY (`LogID`);

--
-- Indexes for table `price`
--
ALTER TABLE `price`
  ADD PRIMARY KEY (`price_id`);

--
-- Indexes for table `radiologytest`
--
ALTER TABLE `radiologytest`
  ADD PRIMARY KEY (`TestID`),
  ADD KEY `AppointmentID` (`AppointmentID`);

--
-- Indexes for table `scan`
--
ALTER TABLE `scan`
  ADD PRIMARY KEY (`SampleID`);

--
-- Indexes for table `urinetest`
--
ALTER TABLE `urinetest`
  ADD PRIMARY KEY (`TestID`),
  ADD KEY `AppointmentID` (`AppointmentID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointment`
--
ALTER TABLE `appointment`
  MODIFY `AppointmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `appointment_log`
--
ALTER TABLE `appointment_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bloodtest`
--
ALTER TABLE `bloodtest`
  MODIFY `TestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `pathologist`
--
ALTER TABLE `pathologist`
  MODIFY `PathologistID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pathologylab`
--
ALTER TABLE `pathologylab`
  MODIFY `LabID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `patient`
--
ALTER TABLE `patient`
  MODIFY `PatientID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `patient_deleted_log`
--
ALTER TABLE `patient_deleted_log`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `price`
--
ALTER TABLE `price`
  MODIFY `price_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `radiologytest`
--
ALTER TABLE `radiologytest`
  MODIFY `TestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `urinetest`
--
ALTER TABLE `urinetest`
  MODIFY `TestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bloodtest`
--
ALTER TABLE `bloodtest`
  ADD CONSTRAINT `fk_foreign_key_name` FOREIGN KEY (`AppointmentID`) REFERENCES `appointment` (`AppointmentID`);

--
-- Constraints for table `radiologytest`
--
ALTER TABLE `radiologytest`
  ADD CONSTRAINT `fk` FOREIGN KEY (`AppointmentID`) REFERENCES `appointment` (`AppointmentID`);

--
-- Constraints for table `urinetest`
--
ALTER TABLE `urinetest`
  ADD CONSTRAINT `foreignkey` FOREIGN KEY (`AppointmentID`) REFERENCES `appointment` (`AppointmentID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

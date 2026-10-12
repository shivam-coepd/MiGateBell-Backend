CREATE TABLE IF NOT EXISTS `guard_vehicle_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_type` varchar(100) NOT NULL,
  `vehicle_number` varchar(50) NOT NULL,
  `driver_name` varchar(100) NOT NULL,
  `driver_phone` varchar(20) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `guard_id` int(11) NOT NULL,
  `society_id` int(11) NOT NULL,
  `status` enum('inside','exited') NOT NULL DEFAULT 'inside',
  `entry_time` datetime NOT NULL,
  `exit_time` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

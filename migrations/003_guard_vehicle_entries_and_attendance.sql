-- Guard Vehicle Entries Table
-- Tracks utility/service vehicles entering and exiting the society gate
CREATE TABLE IF NOT EXISTS `guard_vehicle_entries` (
  `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `vehicle_type`   VARCHAR(100)     NOT NULL,
  `vehicle_number` VARCHAR(20)      NOT NULL,
  `driver_name`    VARCHAR(150)     NOT NULL,
  `driver_phone`   VARCHAR(15)      NOT NULL,
  `purpose`        VARCHAR(255)     NOT NULL,
  `resident_id`    INT UNSIGNED     NULL,
  `guard_id`       INT UNSIGNED     NOT NULL,
  `society_id`     INT UNSIGNED     NOT NULL,
  `status`         ENUM('inside','exited') NOT NULL DEFAULT 'inside',
  `entry_time`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `exit_time`      DATETIME         NULL,
  `created_at`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_society_status`  (`society_id`, `status`),
  INDEX `idx_guard_id`        (`guard_id`),
  INDEX `idx_vehicle_number`  (`vehicle_number`),
  CONSTRAINT `fk_gve_guard`    FOREIGN KEY (`guard_id`)   REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gve_society`  FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gve_resident` FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Guard Attendance Table
-- Tracks daily check-in / check-out for guards
CREATE TABLE IF NOT EXISTS `guard_attendance` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `guard_id`    INT UNSIGNED  NOT NULL,
  `society_id`  INT UNSIGNED  NOT NULL,
  `date`        DATE          NOT NULL,
  `in_time`     DATETIME      NULL,
  `out_time`    DATETIME      NULL,
  `status`      ENUM('present','absent','half_day','off') NOT NULL DEFAULT 'present',
  `notes`       VARCHAR(500)  NULL,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_guard_date`  (`guard_id`, `date`),
  INDEX `idx_society_date`    (`society_id`, `date`),
  CONSTRAINT `fk_ga_guard`    FOREIGN KEY (`guard_id`)   REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ga_society`  FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

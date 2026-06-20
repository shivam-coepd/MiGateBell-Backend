CREATE TABLE `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `society_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `category` VARCHAR(100) DEFAULT 'Event',
  `event_date` DATE NOT NULL,
  `event_time` VARCHAR(100) DEFAULT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `organizer` VARCHAR(255) DEFAULT NULL,
  `price` VARCHAR(100) DEFAULT 'Free',
  `cover_image` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `attendees` INT DEFAULT 0,
  `rating` DECIMAL(3, 1) DEFAULT 0.0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`society_id`) REFERENCES `societies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE `event_attendees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `status` ENUM('going', 'maybe', 'not_going') DEFAULT 'going',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_attendee` (`event_id`, `user_id`)
);

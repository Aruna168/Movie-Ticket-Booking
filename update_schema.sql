-- Update movies table to store more detailed information
ALTER TABLE `movies` 
ADD COLUMN IF NOT EXISTS `genre` VARCHAR(100) NULL AFTER `description`,
ADD COLUMN IF NOT EXISTS `director` VARCHAR(100) NULL AFTER `genre`,
ADD COLUMN IF NOT EXISTS `cast` TEXT NULL AFTER `director`,
ADD COLUMN IF NOT EXISTS `release_date` DATE NULL AFTER `cast`,
ADD COLUMN IF NOT EXISTS `language` VARCHAR(50) NULL AFTER `release_date`,
ADD COLUMN IF NOT EXISTS `duration` INT NULL COMMENT 'Duration in minutes' AFTER `language`,
ADD COLUMN IF NOT EXISTS `trailer_url` VARCHAR(255) NULL AFTER `duration`,
ADD COLUMN IF NOT EXISTS `imdb_id` VARCHAR(20) NULL AFTER `trailer_url`;

-- Update theaters table to store more detailed location information
ALTER TABLE `theaters` 
ADD COLUMN IF NOT EXISTS `address` TEXT NULL AFTER `location`,
ADD COLUMN IF NOT EXISTS `city` VARCHAR(100) NULL AFTER `address`,
ADD COLUMN IF NOT EXISTS `state` VARCHAR(100) NULL AFTER `city`,
ADD COLUMN IF NOT EXISTS `pincode` VARCHAR(20) NULL AFTER `state`,
ADD COLUMN IF NOT EXISTS `latitude` DECIMAL(10,8) NULL AFTER `pincode`,
ADD COLUMN IF NOT EXISTS `longitude` DECIMAL(11,8) NULL AFTER `latitude`,
ADD COLUMN IF NOT EXISTS `contact_phone` VARCHAR(20) NULL AFTER `longitude`,
ADD COLUMN IF NOT EXISTS `contact_email` VARCHAR(100) NULL AFTER `contact_phone`,
ADD COLUMN IF NOT EXISTS `facilities` TEXT NULL COMMENT 'JSON array of available facilities' AFTER `contact_email`,
ADD COLUMN IF NOT EXISTS `image` VARCHAR(255) NULL AFTER `facilities`;

-- Create table for theater screens
CREATE TABLE IF NOT EXISTS `screens` (
  `screen_id` INT NOT NULL AUTO_INCREMENT,
  `theater_id` INT NOT NULL,
  `screen_name` VARCHAR(50) NOT NULL,
  `total_seats` INT NOT NULL DEFAULT 0,
  `screen_type` VARCHAR(50) NULL COMMENT 'e.g. 2D, 3D, IMAX, etc.',
  `seating_arrangement` TEXT NULL COMMENT 'JSON representation of seating layout',
  PRIMARY KEY (`screen_id`),
  INDEX `fk_screens_theaters_idx` (`theater_id`),
  CONSTRAINT `fk_screens_theaters` FOREIGN KEY (`theater_id`)
    REFERENCES `theaters` (`theater_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update showtimes table to reference screens instead of theaters directly
ALTER TABLE `showtimes` 
ADD COLUMN IF NOT EXISTS `screen_id` INT NULL AFTER `theater_id`,
ADD INDEX IF NOT EXISTS `fk_showtimes_screens_idx` (`screen_id`),
ADD CONSTRAINT IF NOT EXISTS `fk_showtimes_screens` FOREIGN KEY (`screen_id`)
    REFERENCES `screens` (`screen_id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Create table for movie reviews and ratings
CREATE TABLE IF NOT EXISTS `movie_reviews` (
  `review_id` INT NOT NULL AUTO_INCREMENT,
  `movie_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `rating` DECIMAL(3,1) NOT NULL,
  `review_text` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  INDEX `fk_reviews_movies_idx` (`movie_id`),
  INDEX `fk_reviews_users_idx` (`user_id`),
  CONSTRAINT `fk_reviews_movies` FOREIGN KEY (`movie_id`)
    REFERENCES `movies` (`movie_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reviews_users` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create table for theater reviews and ratings
CREATE TABLE IF NOT EXISTS `theater_reviews` (
  `review_id` INT NOT NULL AUTO_INCREMENT,
  `theater_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `rating` DECIMAL(3,1) NOT NULL,
  `review_text` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  INDEX `fk_reviews_theaters_idx` (`theater_id`),
  INDEX `fk_theater_reviews_users_idx` (`user_id`),
  CONSTRAINT `fk_reviews_theaters` FOREIGN KEY (`theater_id`)
    REFERENCES `theaters` (`theater_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_theater_reviews_users` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create table for movie tags (for improved search functionality)
CREATE TABLE IF NOT EXISTS `movie_tags` (
  `tag_id` INT NOT NULL AUTO_INCREMENT,
  `movie_id` INT NOT NULL,
  `tag_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`tag_id`),
  INDEX `fk_tags_movies_idx` (`movie_id`),
  CONSTRAINT `fk_tags_movies` FOREIGN KEY (`movie_id`)
    REFERENCES `movies` (`movie_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
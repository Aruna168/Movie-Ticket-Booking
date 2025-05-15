-- Update movies table to store more detailed information
ALTER TABLE `movies` 
ADD COLUMN IF NOT EXISTS `genre` VARCHAR(100) NULL AFTER `description`,
ADD COLUMN IF NOT EXISTS `director` VARCHAR(100) NULL AFTER `genre`,
ADD COLUMN IF NOT EXISTS `cast` TEXT NULL AFTER `director`,
ADD COLUMN IF NOT EXISTS `release_date` DATE NULL AFTER `cast`,
ADD COLUMN IF NOT EXISTS `language` VARCHAR(50) NULL AFTER `release_date`,
ADD COLUMN IF NOT EXISTS `duration` INT NULL COMMENT 'Duration in minutes' AFTER `language`,
ADD COLUMN IF NOT EXISTS `trailer_url` VARCHAR(255) NULL AFTER `duration`;
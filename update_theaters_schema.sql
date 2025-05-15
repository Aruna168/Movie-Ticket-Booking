-- Update theaters table to store more detailed location information
ALTER TABLE `theaters` 
ADD COLUMN IF NOT EXISTS `address` TEXT NULL AFTER `location`,
ADD COLUMN IF NOT EXISTS `city` VARCHAR(100) NULL AFTER `address`,
ADD COLUMN IF NOT EXISTS `state` VARCHAR(100) NULL AFTER `state`,
ADD COLUMN IF NOT EXISTS `pincode` VARCHAR(20) NULL AFTER `state`,
ADD COLUMN IF NOT EXISTS `latitude` DECIMAL(10,8) NULL AFTER `pincode`,
ADD COLUMN IF NOT EXISTS `longitude` DECIMAL(11,8) NULL AFTER `latitude`,
ADD COLUMN IF NOT EXISTS `contact_phone` VARCHAR(20) NULL AFTER `longitude`,
ADD COLUMN IF NOT EXISTS `contact_email` VARCHAR(100) NULL AFTER `contact_phone`,
ADD COLUMN IF NOT EXISTS `image` VARCHAR(255) NULL AFTER `contact_email`;
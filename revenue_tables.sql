-- Create revenue_distribution table to track payment splits
CREATE TABLE IF NOT EXISTS `revenue_distribution` (
  `distribution_id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `theater_id` int(11) NOT NULL,
  `distributor_id` int(11) DEFAULT NULL,
  `platform_amount` decimal(10,2) NOT NULL COMMENT 'Platform fee',
  `theater_amount` decimal(10,2) NOT NULL COMMENT 'Theater share',
  `distributor_amount` decimal(10,2) NOT NULL COMMENT 'Movie distributor share',
  `tax_amount` decimal(10,2) NOT NULL COMMENT 'Tax amount',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`distribution_id`),
  KEY `payment_id` (`payment_id`),
  KEY `theater_id` (`theater_id`),
  CONSTRAINT `revenue_distribution_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE CASCADE,
  CONSTRAINT `revenue_distribution_ibfk_2` FOREIGN KEY (`theater_id`) REFERENCES `theaters` (`theater_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create theater_payouts table to track payments to theaters
CREATE TABLE IF NOT EXISTS `theater_payouts` (
  `payout_id` int(11) NOT NULL AUTO_INCREMENT,
  `theater_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('Pending','Processing','Completed','Failed') NOT NULL DEFAULT 'Pending',
  `payout_method` varchar(50) NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `payout_date` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payout_id`),
  KEY `theater_id` (`theater_id`),
  CONSTRAINT `theater_payouts_ibfk_1` FOREIGN KEY (`theater_id`) REFERENCES `theaters` (`theater_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create theater_bank_details table to store theater payment information
CREATE TABLE IF NOT EXISTS `theater_bank_details` (
  `detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `theater_id` int(11) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `ifsc_code` varchar(20) NOT NULL,
  `upi_id` varchar(50) DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `pan` varchar(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`detail_id`),
  UNIQUE KEY `theater_id` (`theater_id`),
  CONSTRAINT `theater_bank_details_ibfk_1` FOREIGN KEY (`theater_id`) REFERENCES `theaters` (`theater_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add revenue_share column to theaters table if it doesn't exist
ALTER TABLE `theaters` 
ADD COLUMN IF NOT EXISTS `revenue_share` decimal(5,2) NOT NULL DEFAULT 70.00 COMMENT 'Percentage of ticket price that goes to theater' AFTER `location`;

-- Add distributor_share column to movies table if it doesn't exist
ALTER TABLE `movies` 
ADD COLUMN IF NOT EXISTS `distributor_share` decimal(5,2) NOT NULL DEFAULT 20.00 COMMENT 'Percentage of ticket price that goes to distributor' AFTER `duration`;

-- Add platform_fee column to movie_pricing table if it doesn't exist
ALTER TABLE `movie_pricing` 
ADD COLUMN IF NOT EXISTS `platform_fee_percentage` decimal(5,2) NOT NULL DEFAULT 5.00 COMMENT 'Platform fee percentage' AFTER `convenience_fee`;
-- Add FirstTimeLogin column to staff table
ALTER TABLE `staff` ADD `FirstTimeLogin` TINYINT(1) NOT NULL DEFAULT '1' AFTER `CreatedAt`;

-- Update existing staff records to mark them as completed setup
UPDATE `staff` SET `FirstTimeLogin` = 0 WHERE `StaffName` IS NOT NULL; 
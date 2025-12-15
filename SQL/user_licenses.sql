-- =========================================================
-- 📄 Table des permis de conduire
-- =========================================================
CREATE TABLE IF NOT EXISTS `user_licenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `UserID` INT NOT NULL UNIQUE,
    `permit_number` VARCHAR(50),
    `date_obtained` DATE,
    `date_expiration` DATE,
    `document_file` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_user_id` (`UserID`),
    INDEX `idx_permit_number` (`permit_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

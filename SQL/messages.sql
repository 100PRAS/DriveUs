-- Table pour le système de messagerie
CREATE TABLE IF NOT EXISTS `messages` (
    `MessageID` INT AUTO_INCREMENT PRIMARY KEY,
    `sender` VARCHAR(255) NOT NULL,
    `receiver` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `date_envoi` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `lu` TINYINT(1) DEFAULT 0,
    INDEX `idx_sender` (`sender`),
    INDEX `idx_receiver` (`receiver`),
    INDEX `idx_date` (`date_envoi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exemple de données pour tester
-- INSERT INTO messages (sender, receiver, message) VALUES 
-- ('user1@example.com', 'user2@example.com', 'Bonjour, est-ce que le trajet est toujours disponible ?'),
-- ('user2@example.com', 'user1@example.com', 'Oui, il reste des places !');

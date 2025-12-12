-- Mise à jour de la table messages pour supporter les conversations de groupe
USE `bdd`;

-- Ajouter une colonne TrajetID pour lier les messages aux trajets
ALTER TABLE `messages` 
ADD COLUMN `TrajetID` INT NULL AFTER `receiver`,
ADD COLUMN `is_group` TINYINT(1) DEFAULT 0 AFTER `TrajetID`,
ADD INDEX `idx_trajet` (`TrajetID`);

-- Table pour gérer les participants aux conversations de groupe
CREATE TABLE IF NOT EXISTS `conversation_participants` (
    `ConversationID` INT AUTO_INCREMENT PRIMARY KEY,
    `TrajetID` INT NOT NULL,
    `UserEmail` VARCHAR(255) NOT NULL,
    `date_ajout` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`TrajetID`) REFERENCES `trajet`(`TrajetID`) ON DELETE CASCADE,
    UNIQUE KEY `unique_participant` (`TrajetID`, `UserEmail`),
    INDEX `idx_trajet_conv` (`TrajetID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exemples de requêtes utiles :

-- Récupérer tous les participants d'un trajet
-- SELECT cp.UserEmail, u.Prenom, u.PhotoProfil 
-- FROM conversation_participants cp
-- LEFT JOIN user u ON u.Mail = cp.UserEmail
-- WHERE cp.TrajetID = ?;

-- Récupérer tous les messages d'une conversation de groupe
-- SELECT m.*, u.Prenom, u.PhotoProfil
-- FROM messages m
-- LEFT JOIN user u ON u.Mail = m.sender
-- WHERE m.TrajetID = ? AND m.is_group = 1
-- ORDER BY m.date_envoi ASC;

-- ========================================
-- SYSTÈME DE MESSAGERIE - GUIDE D'INSTALLATION
-- ========================================

-- 1. CRÉER LA TABLE MESSAGES
-- Exécutez ce script dans phpMyAdmin ou via la console MySQL
-- (Base de données: driveus)

USE driveus;

CREATE TABLE IF NOT EXISTS `messages` (
    `MessageID` INT AUTO_INCREMENT PRIMARY KEY,
    `sender` VARCHAR(255) NOT NULL COMMENT 'Email de l\'expéditeur',
    `receiver` VARCHAR(255) NOT NULL COMMENT 'Email du destinataire',
    `message` TEXT NOT NULL COMMENT 'Contenu du message',
    `date_envoi` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Date et heure d\'envoi',
    `lu` TINYINT(1) DEFAULT 0 COMMENT 'Message lu (0=non, 1=oui)',
    INDEX `idx_sender` (`sender`),
    INDEX `idx_receiver` (`receiver`),
    INDEX `idx_date` (`date_envoi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ========================================
-- 2. INSÉRER DES DONNÉES DE TEST (Optionnel)
-- ========================================

-- Remplacez les emails par des vrais utilisateurs de votre base
INSERT INTO messages (sender, receiver, message, date_envoi) VALUES 
('user1@test.com', 'user2@test.com', 'Bonjour, est-ce que le trajet Paris-Lyon est toujours disponible ?', NOW()),
('user2@test.com', 'user1@test.com', 'Oui, il reste 2 places ! Départ à 14h.', NOW()),
('user1@test.com', 'user2@test.com', 'Parfait ! Je réserve une place.', NOW());


-- ========================================
-- 3. FONCTIONNALITÉS DISPONIBLES
-- ========================================

-- ✅ Envoi de messages entre utilisateurs
-- ✅ Chargement automatique des conversations
-- ✅ Rafraîchissement automatique toutes les 3 secondes
-- ✅ Assistant DriveUs intégré (réponses automatiques)
-- ✅ Envoi de fichiers (pièces jointes)
-- ✅ Formulaire "Nous contacter"
-- ✅ Interface responsive


-- ========================================
-- 4. FICHIERS DU SYSTÈME
-- ========================================

-- Frontend:
-- - Messagerie.php : Interface principale
-- - CSS/Messagerie1.css : Styles de base
-- - CSS/Sombre_Messagerie.css : Mode sombre

-- Backend:
-- - Outils/send_message.php : Envoi de messages
-- - Outils/get_conversation.php : Liste des conversations
-- - Outils/get_message.php : Chargement des messages d'une conversation
-- - Outils/Assistant.php : FAQ interactive


-- ========================================
-- 5. REQUÊTES UTILES
-- ========================================

-- Voir tous les messages
SELECT * FROM messages ORDER BY date_envoi DESC;

-- Messages d'un utilisateur spécifique
SELECT * FROM messages 
WHERE sender = 'user@example.com' OR receiver = 'user@example.com'
ORDER BY date_envoi ASC;

-- Conversations d'un utilisateur
SELECT DISTINCT 
    CASE 
        WHEN sender = 'user@example.com' THEN receiver
        ELSE sender
    END AS contact
FROM messages
WHERE sender = 'user@example.com' OR receiver = 'user@example.com'
ORDER BY contact ASC;

-- Messages non lus
SELECT * FROM messages WHERE lu = 0 AND receiver = 'user@example.com';

-- Marquer un message comme lu
UPDATE messages SET lu = 1 WHERE MessageID = 1;

-- Supprimer une conversation
DELETE FROM messages 
WHERE (sender = 'user1@example.com' AND receiver = 'user2@example.com')
   OR (sender = 'user2@example.com' AND receiver = 'user1@example.com');


-- ========================================
-- 6. AMÉLIORATIONS FUTURES POSSIBLES
-- ========================================

-- 📌 Notifications en temps réel (WebSocket)
-- 📌 Marquage des messages lus/non lus
-- 📌 Suppression de messages
-- 📌 Envoi d'images
-- 📌 Messagerie de groupe
-- 📌 Recherche dans les messages
-- 📌 Archivage de conversations

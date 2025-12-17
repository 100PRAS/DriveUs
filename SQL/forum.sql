-- Table pour les sujets du forum
CREATE TABLE IF NOT EXISTS `forum_topics` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `author_email` VARCHAR(255) NOT NULL,
    `author_name` VARCHAR(100) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_created` (`created_at`),
    INDEX `idx_author` (`author_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les réponses du forum
CREATE TABLE IF NOT EXISTS `forum_replies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `topic_id` INT NOT NULL,
    `content` TEXT NOT NULL,
    `author_email` VARCHAR(255) NOT NULL,
    `author_name` VARCHAR(100) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_topic` (`topic_id`),
    INDEX `idx_created` (`created_at`),
    FOREIGN KEY (`topic_id`) REFERENCES `forum_topics`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données d'exemple
INSERT INTO forum_topics (title, content, author_email, author_name) VALUES 
('Conseils pour un covoiturage sécurisé', 'Salut ! Avez-vous des astuces pour que le covoiturage soit safe ?', 'alice@example.com', 'Alice'),
('Trajets Lyon → Paris : vos expériences', 'Je fais souvent cet aller-retour, comment vous le gérez ?', 'julien@example.com', 'Julien');

INSERT INTO forum_replies (topic_id, content, author_email, author_name) VALUES 
(1, 'Toujours vérifier les avis avant de réserver.', 'karim@example.com', 'Karim'),
(1, 'Prévenir quelqu\'un de ton trajet, ça rassure !', 'sarah@example.com', 'Sarah'),
(2, 'Toujours partir tôt pour éviter les bouchons.', 'antoine@example.com', 'Antoine');

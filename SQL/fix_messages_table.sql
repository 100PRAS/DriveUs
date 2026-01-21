-- ============================================
-- Script de migration pour la table messages
-- Ajoute les colonnes manquantes et unifie la structure
-- Date: 21 janvier 2026
-- ============================================

USE dbs15148242;

-- 1. Vérifier et ajouter la colonne 'lu' pour le statut de lecture
ALTER TABLE messages 
ADD COLUMN IF NOT EXISTS lu TINYINT(1) DEFAULT 0 COMMENT 'Statut de lecture du message (0=non lu, 1=lu)';

-- 2. Ajouter la colonne 'date_envoi' comme alias de 'created_at' pour compatibilité
-- Nous gardons created_at comme source de vérité et utilisons date_envoi pour la compatibilité
ALTER TABLE messages 
ADD COLUMN IF NOT EXISTS date_envoi DATETIME DEFAULT NULL COMMENT 'Date d\'envoi du message (alias de created_at)';

-- 3. Copier les valeurs de created_at vers date_envoi pour les enregistrements existants
UPDATE messages 
SET date_envoi = created_at 
WHERE date_envoi IS NULL AND created_at IS NOT NULL;

-- 4. Créer un trigger pour synchroniser created_at et date_envoi lors des insertions
DROP TRIGGER IF EXISTS messages_before_insert;

DELIMITER $$
CREATE TRIGGER messages_before_insert 
BEFORE INSERT ON messages
FOR EACH ROW
BEGIN
    -- Si created_at est défini mais pas date_envoi, copier created_at vers date_envoi
    IF NEW.created_at IS NOT NULL AND NEW.date_envoi IS NULL THEN
        SET NEW.date_envoi = NEW.created_at;
    END IF;
    
    -- Si date_envoi est défini mais pas created_at, copier date_envoi vers created_at
    IF NEW.date_envoi IS NOT NULL AND NEW.created_at IS NULL THEN
        SET NEW.created_at = NEW.date_envoi;
    END IF;
    
    -- Si aucun n'est défini, utiliser NOW()
    IF NEW.created_at IS NULL AND NEW.date_envoi IS NULL THEN
        SET NEW.created_at = NOW();
        SET NEW.date_envoi = NOW();
    END IF;
END$$
DELIMITER ;

-- 5. Afficher la nouvelle structure de la table
SHOW COLUMNS FROM messages;

-- 6. Compter les enregistrements
SELECT COUNT(*) AS total_messages FROM messages;

-- Note: La colonne 'id' est conservée comme clé primaire
-- Les fichiers PHP qui utilisent 'MessageID' seront mis à jour pour utiliser 'id'

-- Script SQL pour ajouter les colonnes de réinitialisation de mot de passe
-- Exécuter ce script pour activer la fonctionnalité de récupération de mot de passe

-- Ajouter les colonnes reset_token et reset_token_expiry à la table user
-- Syntaxe MSSQL/MySQL compatible
ALTER TABLE [user]
ADD reset_token VARCHAR(64) NULL,
    reset_token_expiry DATETIME NULL;

-- Créer un index sur reset_token pour accélérer les recherches
CREATE INDEX idx_reset_token ON [user](reset_token);

-- Note: Les valeurs NULL permettent de marquer qu'aucune réinitialisation n'est en cours
-- Le token est généré avec bin2hex(random_bytes(32)) = 64 caractères
-- L'expiration est fixée à 1 heure après la génération

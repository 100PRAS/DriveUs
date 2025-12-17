USE bdd;

-- Ajouter la colonne last_activity si absente
ALTER TABLE user
  ADD COLUMN last_activity DATETIME NULL;

-- Index pour accélérer les requêtes de présence
CREATE INDEX idx_user_last_activity ON user (last_activity);

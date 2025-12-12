-- Ajouter la colonne stops si elle n'existe pas
ALTER TABLE `trajet` ADD COLUMN `stops` LONGTEXT NULL DEFAULT NULL AFTER `VilleArrivee`;

-- Cette colonne stockera les arrêts intermédiaires en JSON
-- Exemple: ["Paris", "Lyon", "Grenoble"]

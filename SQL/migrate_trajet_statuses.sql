-- Migration pour ajouter les statuts manquants et les colonnes de confirmation

-- Modifier l'enum du statut pour inclure les nouveaux statuts
ALTER TABLE `trajet` 
MODIFY COLUMN `statut` enum('brouillon','publie','publié','en cours','terminée','supprime','supprimé') NOT NULL DEFAULT 'brouillon';

-- Ajouter les colonnes pour tracker les confirmations du conducteur et du passager
ALTER TABLE `trajet`
ADD COLUMN `conductor_started` TINYINT(1) DEFAULT 0 COMMENT 'Le conducteur a confirmé le départ du trajet',
ADD COLUMN `passenger_started` TINYINT(1) DEFAULT 0 COMMENT 'Le passager a confirmé le départ du trajet';

-- Créer une table pour tracker les confirmations par passager (chaque passager peut avoir une réservation)
-- Cela permettra de vérifier que TOUS les passagers ont confirmé avant que le trajet commence vraiment
CREATE TABLE IF NOT EXISTS `reservations_started` (
  `ReservationID` int(11) NOT NULL,
  `passenger_confirmed` TINYINT(1) DEFAULT 0 COMMENT 'Le passager a confirmé le départ',
  `confirmed_at` TIMESTAMP NULL,
  PRIMARY KEY (`ReservationID`),
  FOREIGN KEY (`ReservationID`) REFERENCES `reservations`(`ReservationID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des réservations
CREATE TABLE IF NOT EXISTS `reservations` (
  `ReservationID` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `TrajetID` int NOT NULL,
  `PassagerID` int NOT NULL,
  `statut` varchar(50) DEFAULT 'confirmée',
  `nombre_places` int DEFAULT 1,
  `date_reservation` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`TrajetID`) REFERENCES `trajet`(`TrajetID`),
  FOREIGN KEY (`PassagerID`) REFERENCES `user`(`UserID`),
  UNIQUE KEY `unique_booking` (`TrajetID`, `PassagerID`)
);

-- Index pour les performances
CREATE INDEX idx_passager ON reservations(PassagerID);
CREATE INDEX idx_trajet ON reservations(TrajetID);

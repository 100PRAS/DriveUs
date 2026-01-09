<?php
session_start();

// Vérifier que c'est un administrateur (optionnel)
require __DIR__ . '/../config/config.php';

if (!$pdo) {
    die("Erreur de connexion à la base de données");
}

try {
    $sql = "CREATE TABLE IF NOT EXISTS `reservations` (
        `ReservationID` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `TrajetID` int NOT NULL,
        `PassagerID` int NOT NULL,
        `statut` varchar(50) DEFAULT 'confirmée',
        `nombre_places` int DEFAULT 1,
        `date_reservation` timestamp DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`TrajetID`) REFERENCES `trajet`(`TrajetID`),
        FOREIGN KEY (`PassagerID`) REFERENCES `user`(`UserID`),
        UNIQUE KEY `unique_booking` (`TrajetID`, `PassagerID`),
        KEY `idx_passager` (`PassagerID`),
        KEY `idx_trajet` (`TrajetID`)
    )";
    
    $pdo->exec($sql);
    
    echo "✓ Table 'reservations' créée avec succès !";
    
} catch (PDOException $e) {
    echo "✗ Erreur : " . $e->getMessage();
}
?>

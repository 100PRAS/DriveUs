<?php
require __DIR__ . '/../config/config.php';

$userId = 34;

echo "=== DEBUG USER 34 ===\n\n";

// Trajets du conducteur
echo "1. TRAJETS DU CONDUCTEUR 34:\n";
$stmt = $conn->prepare("SELECT TrajetID, VilleDepart, VilleArrivee, DateDepart, nombre_places FROM trajet WHERE ConducteurID = ? AND statut = 'publie'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$tripCount = 0;
$tripIds = [];
while ($row = $result->fetch_assoc()) {
    $tripIds[] = $row['TrajetID'];
    echo "   - Trip {$row['TrajetID']}: {$row['VilleDepart']} → {$row['VilleArrivee']} ({$row['nombre_places']} places)\n";
    $tripCount++;
}
$stmt->close();
echo "   Total: $tripCount trajets\n\n";

// Réservations sur ces trajets
echo "2. RÉSERVATIONS SUR CES TRAJETS:\n";
if (!empty($tripIds)) {
    $placeholders = implode(',', $tripIds);
    $stmt = $conn->prepare("
        SELECT r.ReservationID, r.TrajetID, r.PassagerID, r.nombre_places, r.statut, 
               u.Prenom, t.VilleDepart, t.VilleArrivee
        FROM reservation r
        JOIN trajet t ON r.TrajetID = t.TrajetID
        JOIN user u ON r.PassagerID = u.UserID
        WHERE t.ConducteurID = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $resCount = 0;
    while ($row = $result->fetch_assoc()) {
        echo "   - Résa {$row['ReservationID']}: {$row['Prenom']} réserve {$row['nombre_places']} place(s) ({$row['statut']})\n";
        $resCount++;
    }
    $stmt->close();
    echo "   Total: $resCount réservations\n";
} else {
    echo "   Aucun trajet publié.\n";
}

$conn->close();
?>

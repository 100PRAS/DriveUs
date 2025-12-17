<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

require __DIR__ . '/../config/config.php';

$userId = $_SESSION['UserID'] ?? null;

// Debug info
$debug = [
    'session_userid' => $userId,
    'user_trips' => [],
    'user_reservations_as_conductor' => [],
    'all_reservations' => []
];

if (!$userId) {
    echo json_encode(['error' => 'Not logged in'] + $debug);
    exit;
}

// 1. Vérifier les trajets de cet utilisateur
$stmt = $conn->prepare("SELECT TrajetID, VilleDepart, VilleArrivee, DateDepart FROM trajet WHERE ConducteurID = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $debug['user_trips'][] = $row;
}
$stmt->close();

// 2. Vérifier les réservations sur les trajets de cet utilisateur
$stmt = $conn->prepare("
    SELECT r.ReservationID, r.TrajetID, r.PassagerID, r.nombre_places, r.statut, r.date_reservation,
           u.Prenom, t.VilleDepart, t.VilleArrivee
    FROM reservations r
    JOIN trajet t ON r.TrajetID = t.TrajetID
    JOIN user u ON r.PassagerID = u.UserID
    WHERE t.ConducteurID = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $debug['user_reservations_as_conductor'][] = $row;
}
$stmt->close();

// 3. Toutes les réservations (pour voir la structure)
$result = $conn->query("SELECT * FROM reservations LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $debug['all_reservations'][] = $row;
}

echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>

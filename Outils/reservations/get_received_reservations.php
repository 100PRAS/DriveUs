<?php
session_start();
header("Content-Type: application/json");

// Vérification utilisateur connecté
if (!isset($_SESSION['UserID'])) {
    echo json_encode(["error" => "Not logged"]);
    exit;
}

require __DIR__ . '/../config/config.php';

$userId = $_SESSION['UserID'];

// Récupérer l'email utilisateur (conducteur)
$stmtUser = $conn->prepare("SELECT Mail FROM user WHERE UserID = ?");
$stmtUser->bind_param("i", $userId);
$stmtUser->execute();
$stmtUser->bind_result($userEmail);
$stmtUser->fetch();
$stmtUser->close();

if (!$userId) {
    echo json_encode(["error" => "User not found"]);
    exit;
}

// Récupérer les réservations des trajets du conducteur
$query = "
    SELECT 
        r.ReservationID,
        r.TrajetID,
        r.nombre_places,
        r.statut,
        r.date_reservation,
        t.VilleDepart,
        t.VilleArrivee,
        t.DateDepart,
        u.Prenom as PassengerName
    FROM reservations r
    JOIN trajet t ON r.TrajetID = t.TrajetID
    JOIN user u ON r.PassagerID = u.UserID
    WHERE t.ConducteurID = ?
    ORDER BY r.date_reservation DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$reservations = [];
while ($row = $result->fetch_assoc()) {
    $reservations[] = [
        'id' => $row['ReservationID'],
        'tripId' => $row['TrajetID'],
        'from' => $row['VilleDepart'],
        'to' => $row['VilleArrivee'],
        'date' => $row['DateDepart'],
        'seats' => $row['nombre_places'],
        'status' => $row['statut'],
        'passenger' => $row['PassengerName'],
        'bookingDate' => $row['date_reservation']
    ];
}

$stmt->close();
$conn->close();

echo json_encode($reservations);
?>

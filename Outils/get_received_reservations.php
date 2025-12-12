<?php
session_start();
header("Content-Type: application/json");

// Vérification utilisateur connecté
if (!isset($_SESSION['user_mail'])) {
    echo json_encode(["error" => "Not logged"]);
    exit;
}

require "config.php";

$userEmail = $_SESSION['user_mail'];

// Récupérer l'ID utilisateur (conducteur)
$stmtUser = $conn->prepare("SELECT UserID FROM user WHERE Mail = ?");
$stmtUser->bind_param("s", $userEmail);
$stmtUser->execute();
$stmtUser->bind_result($userId);
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

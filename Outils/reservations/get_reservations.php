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

// Récupérer l'email utilisateur
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

// Récupérer les réservations de l'utilisateur
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
        t.heure,
        t.Prix,
        u.Prenom as ConductorName,
        u.Mail as ConductorEmail,
        u.PhotoProfil as ConductorPhoto
    FROM reservation r
    JOIN trajet t ON r.TrajetID = t.TrajetID
    JOIN user u ON t.ConducteurID = u.UserID
    WHERE r.PassagerID = ?
    ORDER BY r.date_reservation DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$reservations = [];
while ($row = $result->fetch_assoc()) {
    $photo = !empty($row['ConductorPhoto']) ? '/DriveUs/Image_Profil/' . $row['ConductorPhoto'] : '/DriveUs/Image_Profil/default.png';
    $reservations[] = [
        'id' => $row['ReservationID'],
        'tripId' => $row['TrajetID'],
        'from' => $row['VilleDepart'],
        'to' => $row['VilleArrivee'],
        'date' => $row['DateDepart'],
        'time' => $row['heure'],
        'price' => $row['Prix'],
        'seats' => $row['nombre_places'],
        'status' => $row['statut'],
        'driver' => $row['ConductorName'],
        'driverEmail' => $row['ConductorEmail'],
        'driverPhoto' => $photo,
        'bookingDate' => $row['date_reservation']
    ];
}

$stmt->close();
$conn->close();

echo json_encode($reservations);
?>

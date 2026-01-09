<?php
session_start();
header("Content-Type: application/json");

// Vérification utilisateur connecté
if (!isset($_SESSION['UserID'])) {
    echo json_encode(["error" => "Not logged"]);
    exit;
}

require __DIR__ . '/../config/config.php';

if (!$pdo) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$userId = $_SESSION['UserID'];

// Récupérer l'email utilisateur
$stmtUser = $pdo->prepare("SELECT Mail FROM user WHERE UserID = ?");
$stmtUser->execute([$userId]);
$userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$userRow) {
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
    FROM reservations r
    JOIN trajet t ON r.TrajetID = t.TrajetID
    JOIN user u ON t.ConducteurID = u.UserID
    WHERE r.PassagerID = ?
    ORDER BY r.date_reservation DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute([$userId]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$reservations = [];
foreach ($results as $row) {
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

echo json_encode($reservations);
?>

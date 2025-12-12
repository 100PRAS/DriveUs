<?php
session_start();
header("Content-Type: application/json");

// Vérification utilisateur connecté
if (!isset($_SESSION['user_mail'])) {
    echo json_encode(["error" => "Not logged", "message" => "Utilisateur non connecté"]);
    exit;
}

require "config.php";

$data = json_decode(file_get_contents("php://input"), true);
$reservationId = $data["reservationId"] ?? null;
$userEmail = $_SESSION['user_mail'];

if (!$reservationId) {
    echo json_encode(["error" => "Invalid data"]);
    exit;
}

// Récupérer l'ID utilisateur
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

// Vérifier que la réservation appartient à l'utilisateur
$stmtCheck = $conn->prepare("SELECT TrajetID, nombre_places FROM reservations WHERE ReservationID = ? AND PassagerID = ?");
$stmtCheck->bind_param("ii", $reservationId, $userId);
$stmtCheck->execute();
$stmtCheck->bind_result($tripId, $seatsBooked);
$stmtCheck->fetch();
$stmtCheck->close();

if (!$tripId) {
    echo json_encode(["error" => "Reservation not found"]);
    exit;
}

// Annuler la réservation
$status = "annulée";
$stmtCancel = $conn->prepare("UPDATE reservations SET statut = ? WHERE ReservationID = ?");
$stmtCancel->bind_param("si", $status, $reservationId);

if ($stmtCancel->execute()) {
    // Restaurer les places disponibles
    $stmtUpdate = $conn->prepare("UPDATE trajet SET nombre_places = nombre_places + ? WHERE TrajetID = ?");
    $stmtUpdate->bind_param("ii", $seatsBooked, $tripId);
    $stmtUpdate->execute();
    $stmtUpdate->close();

    echo json_encode(["success" => true, "message" => "Réservation annulée"]);
} else {
    echo json_encode(["error" => "Database error"]);
}

$stmtCancel->close();
$conn->close();
?>

<?php
session_start();
header("Content-Type: application/json");

// Vérification utilisateur connecté
if (!isset($_SESSION['user_mail'])) {
    echo json_encode(["error" => "Not logged", "message" => "Utilisateur non connecté"]);
    exit;
}

require "config.php";

// Récupérer les données POST
$data = json_decode(file_get_contents("php://input"), true);

$tripId = $data["tripId"] ?? null;
$numberOfSeats = isset($data["numberOfSeats"]) ? (int)$data["numberOfSeats"] : 1;
$userEmail = $_SESSION['user_mail'];

if (!$tripId || $numberOfSeats < 1) {
    echo json_encode(["error" => "Invalid data", "message" => "Données invalides"]);
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
    echo json_encode(["error" => "User not found", "message" => "Utilisateur introuvable"]);
    exit;
}

// Vérifier que le trajet existe et que des places sont disponibles
$stmtTrip = $conn->prepare("SELECT nombre_places, ConducteurID FROM trajet WHERE TrajetID = ? AND statut = 'publie'");
$stmtTrip->bind_param("i", $tripId);
$stmtTrip->execute();
$stmtTrip->bind_result($seatsAvailable, $conductorId);
$stmtTrip->fetch();
$stmtTrip->close();

if (!$seatsAvailable) {
    echo json_encode(["error" => "Trip not found", "message" => "Trajet introuvable ou fermé"]);
    exit;
}

// Vérifier qu'on ne réserve pas son propre trajet
if ($userId == $conductorId) {
    echo json_encode(["error" => "Cannot book own trip", "message" => "Vous ne pouvez pas réserver votre propre trajet"]);
    exit;
}

// Vérifier les places disponibles
if ($seatsAvailable < $numberOfSeats) {
    echo json_encode(["error" => "Not enough seats", "message" => "Pas assez de places disponibles"]);
    exit;
}

// Vérifier si l'utilisateur a déjà réservé ce trajet
$stmtCheck = $conn->prepare("SELECT ReservationID FROM reservations WHERE TrajetID = ? AND PassagerID = ?");
$stmtCheck->bind_param("ii", $tripId, $userId);
$stmtCheck->execute();
$stmtCheck->store_result();
if ($stmtCheck->num_rows > 0) {
    echo json_encode(["error" => "Already booked", "message" => "Vous avez déjà réservé ce trajet"]);
    exit;
}
$stmtCheck->close();

// Créer la réservation
$status = "confirmée";
$stmtReserve = $conn->prepare("
    INSERT INTO reservations (TrajetID, PassagerID, statut, nombre_places, date_reservation)
    VALUES (?, ?, ?, ?, NOW())
");
$stmtReserve->bind_param("iisi", $tripId, $userId, $status, $numberOfSeats);

if ($stmtReserve->execute()) {
    // Mettre à jour les places disponibles du trajet
    $newSeats = $seatsAvailable - $numberOfSeats;
    $stmtUpdate = $conn->prepare("UPDATE trajet SET nombre_places = ? WHERE TrajetID = ?");
    $stmtUpdate->bind_param("ii", $newSeats, $tripId);
    $stmtUpdate->execute();
    $stmtUpdate->close();

    echo json_encode([
        "success" => true,
        "message" => "Réservation confirmée",
        "reservationId" => $stmtReserve->insert_id,
        "seatsRemaining" => $newSeats
    ]);
} else {
    echo json_encode(["error" => "Database error", "message" => "Erreur lors de la réservation"]);
}

$stmtReserve->close();
$conn->close();
?>

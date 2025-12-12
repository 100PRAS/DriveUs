<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_mail'])) {
    echo json_encode(["error" => "Not logged", "message" => "Utilisateur non connecté"]);
    exit;
}

require "config.php";

// Récupérer l'ID du trajet
$tripId = $_GET['trip_id'] ?? null;

if (!$tripId) {
    echo json_encode(["error" => "Missing trip_id"]);
    exit;
}

// Récupérer le conducteur du trajet
$stmt = $conn->prepare("SELECT user_id FROM trajet WHERE id = ?");
$stmt->bind_param("i", $tripId);
$stmt->execute();
$result = $stmt->get_result();
$trip = $result->fetch_assoc();

if (!$trip) {
    echo json_encode(["error" => "Trip not found"]);
    exit;
}

// Récupérer l'email du conducteur
$driverId = $trip['user_id'];
$stmt = $conn->prepare("SELECT Mail FROM user WHERE id = ?");
$stmt->bind_param("i", $driverId);
$stmt->execute();
$result = $stmt->get_result();
$driver = $result->fetch_assoc();

if (!$driver) {
    echo json_encode(["error" => "Driver not found"]);
    exit;
}

echo json_encode([
    "success" => true,
    "driver_email" => $driver['Mail'],
    "trip_id" => $tripId
]);
?>

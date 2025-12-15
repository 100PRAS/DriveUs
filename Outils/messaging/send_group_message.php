<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['UserID'])) {
    echo json_encode(["error" => "Not logged"]);
    exit;
}

require __DIR__ . '/../config/config.php';

$data = json_decode(file_get_contents("php://input"), true);

// Récupérer l'email du sender
$stmt = $conn->prepare("SELECT Mail FROM user WHERE UserID = ?");
$stmt->bind_param("i", $_SESSION['UserID']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$sender = $row['Mail'] ?? null;

$trajetId = $data["trajet_id"] ?? null;
$message = $data["message"] ?? "";

if (!$trajetId || $message === "") {
    echo json_encode(["error" => "Missing data"]);
    exit;
}

// Vérifier que l'utilisateur fait partie du trajet (conducteur ou passager)
$stmt = $conn->prepare("
    SELECT t.ConducteurId, u.Mail as ConducteurMail
    FROM trajet t
    LEFT JOIN user u ON u.UserID = t.ConducteurId
    WHERE t.TrajetID = ?
");
$stmt->bind_param("i", $trajetId);
$stmt->execute();
$result = $stmt->get_result();
$trajet = $result->fetch_assoc();
$stmt->close();

if (!$trajet) {
    echo json_encode(["error" => "Trajet not found"]);
    exit;
}

$isConducteur = ($trajet['ConducteurMail'] === $sender);

// Si pas conducteur, vérifier si c'est un passager
if (!$isConducteur) {
    $stmt = $conn->prepare("
        SELECT r.ReservationID
        FROM reservations r
        LEFT JOIN user u ON u.UserID = r.PassagerID
        WHERE r.TrajetID = ? AND u.Mail = ? AND r.statut = 'confirmée'
    ");
    $stmt->bind_param("is", $trajetId, $sender);
    $stmt->execute();
    $result = $stmt->get_result();
    $isPassager = $result->num_rows > 0;
    $stmt->close();
    
    if (!$isPassager) {
        echo json_encode(["error" => "Not authorized", "message" => "Vous ne faites pas partie de ce trajet"]);
        exit;
    }
}

// Insérer le message de groupe
$stmt = $conn->prepare("
    INSERT INTO messages (sender, receiver, message, TrajetID, is_group, date_envoi)
    VALUES (?, 'group', ?, ?, 1, NOW())
");
$stmt->bind_param("ssi", $sender, $message, $trajetId);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Message envoyé au groupe"]);
} else {
    echo json_encode(["error" => "Database error"]);
}

$stmt->close();
$conn->close();

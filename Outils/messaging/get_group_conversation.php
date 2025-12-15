<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['UserID'])) {
    echo json_encode(["error" => "Not logged"]);
    exit;
}

require __DIR__ . '/../config/config.php';

$trajetId = $_GET['trajet_id'] ?? null;

// Récupérer l'email de l'utilisateur
$stmt = $conn->prepare("SELECT Mail FROM user WHERE UserID = ?");
$stmt->bind_param("i", $_SESSION['UserID']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$userEmail = $row['Mail'] ?? null;

if (!$trajetId) {
    echo json_encode(["error" => "Missing trajet_id"]);
    exit;
}

// Vérifier si l'utilisateur est le conducteur ou un passager du trajet
$stmt = $conn->prepare("
    SELECT t.ConducteurId, u.Mail as ConducteurMail, u.Prenom as ConducteurPrenom, u.PhotoProfil as ConducteurPhoto
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

// Récupérer tous les passagers du trajet
$stmt = $conn->prepare("
    SELECT DISTINCT u.Mail, u.Prenom, u.PhotoProfil
    FROM reservations r
    LEFT JOIN user u ON u.UserID = r.PassagerID
    WHERE r.TrajetID = ? AND r.statut = 'confirmée'
");
$stmt->bind_param("i", $trajetId);
$stmt->execute();
$result = $stmt->get_result();

$participants = [];
// Ajouter le conducteur
$participants[] = [
    "email" => $trajet['ConducteurMail'],
    "prenom" => $trajet['ConducteurPrenom'] ?? $trajet['ConducteurMail'],
    "photo" => !empty($trajet['ConducteurPhoto']) ? "/DriveUs/Image_Profil/" . $trajet['ConducteurPhoto'] : "/DriveUs/Image/default.png",
    "role" => "conducteur"
];

// Ajouter les passagers
while ($row = $result->fetch_assoc()) {
    $participants[] = [
        "email" => $row['Mail'],
        "prenom" => $row['Prenom'] ?? $row['Mail'],
        "photo" => !empty($row['PhotoProfil']) ? "/DriveUs/Image_Profil/" . $row['PhotoProfil'] : "/DriveUs/Image/default.png",
        "role" => "passager"
    ];
}
$stmt->close();

// Récupérer les messages de groupe du trajet
$stmt = $conn->prepare("
    SELECT m.*, u.Prenom, u.PhotoProfil
    FROM messages m
    LEFT JOIN user u ON u.Mail = m.sender
    WHERE m.TrajetID = ? AND m.is_group = 1
    ORDER BY m.date_envoi ASC
");
$stmt->bind_param("i", $trajetId);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        "id" => $row['MessageID'],
        "sender" => $row['sender'],
        "prenom" => $row['Prenom'] ?? $row['sender'],
        "photo" => !empty($row['PhotoProfil']) ? "/DriveUs/Image_Profil/" . $row['PhotoProfil'] : "/DriveUs/Image/default.png",
        "message" => $row['message'],
        "date" => $row['date_envoi'],
        "lu" => $row['lu']
    ];
}
$stmt->close();

echo json_encode([
    "success" => true,
    "participants" => $participants,
    "messages" => $messages,
    "trajetId" => $trajetId
]);

$conn->close();

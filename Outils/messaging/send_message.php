<?php
session_start();
header("Content-Type: application/json");

// Vérification user connecté
if (!isset($_SESSION['UserID'])) {
    echo json_encode(["error" => "Not logged", "message" => "Utilisateur non connecté"]);
    exit;
}

require __DIR__ . '/../config/config.php'; // connexion $conn

$data = json_decode(file_get_contents("php://input"), true);

// Récupérer l'email du sender
$stmt = $conn->prepare("SELECT Mail FROM user WHERE UserID = ?");
$stmt->bind_param("i", $_SESSION['UserID']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$sender = $row['Mail'] ?? null;

$receiver = $data["receiver"] ?? "";
$message = $data["message"] ?? "";

if ($receiver === "" || $message === "") {
    echo json_encode(["error" => "Missing data", "message" => "Données manquantes"]);
    exit;
}

// Ne pas envoyer de message à l'assistant
if ($receiver === "Assistant DriveUs (24h/24)") {
    echo json_encode(["success" => true, "message" => "Message envoyé à l'assistant"]);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO messages (sender, receiver, message, created_at)
    VALUES (?, ?, ?, NOW())
");
    INSERT INTO messages (sender, receiver, message, created_at)
    VALUES (?, ?, ?, NOW())
");
$stmt->bind_param("sss", $sender, $receiver, $message);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Message envoyé avec succès"]);
} else {
    echo json_encode(["error" => "Database error", "message" => "Erreur lors de l'envoi"]);
}

$stmt->close();
$conn->close();
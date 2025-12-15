<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['UserID'])) {
    echo json_encode([]);
    exit;
}

require __DIR__ . '/../config/config.php';

// Récupérer l'email de l'utilisateur
$stmt = $conn->prepare("SELECT Mail FROM user WHERE UserID = ?");
$stmt->bind_param("i", $_SESSION['UserID']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$user = $row['Mail'] ?? null;

$contact = $_GET["contact"] ?? "";

if ($contact === "") {
    echo json_encode([]);
    exit;
}

$sql = "
SELECT sender, receiver, message, date_envoi
FROM messages
WHERE 
    (sender = ? AND receiver = ?)
    OR
    (sender = ? AND receiver = ?)
ORDER BY date_envoi ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $user, $contact, $contact, $user);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode($messages);

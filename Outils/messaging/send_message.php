<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['UserID'])) {
    echo json_encode(['error' => 'Not logged', 'message' => 'Utilisateur non connecté']);
    exit;
}

require __DIR__ . '/../config/config.php';
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'no_pdo_connection']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Récupérer l'email du sender
$stmt = $pdo->prepare('SELECT Mail FROM user WHERE UserID = ?');
$stmt->execute([$_SESSION['UserID']]);
$sender = $stmt->fetchColumn();

$receiver = trim($data['receiver'] ?? '');
$message  = trim($data['message'] ?? '');

if ($receiver === '' || $message === '') {
    echo json_encode(['error' => 'Missing data', 'message' => 'Données manquantes']);
    exit;
}

// Ne pas envoyer de message à l'assistant
if ($receiver === 'Assistant DriveUs (24h/24)') {
    echo json_encode(['success' => true, 'message' => "Message envoyé à l'assistant"]);
    exit;
}

$stmt = $pdo->prepare('INSERT INTO messages (sender, receiver, message, created_at) VALUES (:s, :r, :m, NOW())');
$ok = $stmt->execute(['s' => $sender, 'r' => $receiver, 'm' => $message]);

if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Message envoyé avec succès']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => "Erreur lors de l'envoi"]);
}
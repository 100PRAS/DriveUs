<?php
session_start();
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Non authentifié']);
    exit;
}

$userEmail = $_SESSION['email'];
$messageId = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;

if ($messageId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Identifiant invalide']);
    exit;
}

try {
    $sql = "DELETE FROM messages WHERE id = ? AND sender = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $messageId, $userEmail);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Suppression impossible']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erreur serveur']);
}

$conn->close();
?>

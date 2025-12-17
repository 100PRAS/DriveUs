<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/config.php';

$userId = $_SESSION['UserID'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

// S'assurer que la colonne last_activity existe
try {
    $conn->query("ALTER TABLE user ADD COLUMN IF NOT EXISTS last_activity DATETIME NULL");
} catch (Exception $e) {
    // Pour MySQL < 8.0 sans IF NOT EXISTS, vérifier puis ajouter
    $check = $conn->query("SHOW COLUMNS FROM user LIKE 'last_activity'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE user ADD COLUMN last_activity DATETIME NULL");
    }
}

// Mettre à jour la dernière activité à NOW()
$stmt = $conn->prepare("UPDATE user SET last_activity = NOW() WHERE UserID = ?");
$stmt->bind_param("i", $userId);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Échec mise à jour']);
}
$stmt->close();
?>
<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';

$userId = $_SESSION['UserID'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

if (!$pdo instanceof PDO) {
    echo json_encode(['success' => false, 'message' => 'Connexion non disponible']);
    exit;
}

// S'assurer que la colonne last_activity existe
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM user LIKE 'last_activity'");
    if ($colCheck->rowCount() === 0) {
        $pdo->exec("ALTER TABLE user ADD COLUMN last_activity DATETIME NULL");
    }
} catch (Exception $e) {
    // Colonner existe déjà, continuer
}

// Mettre à jour la dernière activité à NOW()
try {
    $stmt = $pdo->prepare("UPDATE user SET last_activity = NOW() WHERE UserID = ?");
    $stmt->execute([$userId]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
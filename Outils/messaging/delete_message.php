<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// Vérifier l'authentification via UserID en session
if (!isset($_SESSION['UserID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Non authentifié']);
    exit;
}

$userID = $_SESSION['UserID'];

// Récupérer l'email de l'utilisateur
try {
    $userStmt = $pdo->prepare("SELECT Mail FROM user WHERE UserID = ?");
    $userStmt->execute([$userID]);
    $userRow = $userStmt->fetch();
    if (!$userRow) {
        echo json_encode(['status' => 'error', 'message' => 'Utilisateur non trouvé']);
        exit;
    }
    $userEmail = $userRow['Mail'];
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erreur authentification']);
    exit;
}
$messageId = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;

if ($messageId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Identifiant invalide']);
    exit;
}

try {
    // Vérifier que le message appartient à l'utilisateur avant de le supprimer
    $checkStmt = $pdo->prepare("SELECT id FROM messages WHERE id = ? AND sender = ?");
    $checkStmt->execute([$messageId, $userEmail]);
    
    if (!$checkStmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Message non trouvé ou accès refusé']);
        exit;
    }
    
    // Supprimer le message
    $deleteStmt = $pdo->prepare("DELETE FROM messages WHERE id = ? AND sender = ?");
    $result = $deleteStmt->execute([$messageId, $userEmail]);
    
    if ($result && $deleteStmt->rowCount() > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Suppression impossible']);
    }
} catch (PDOException $e) {
    error_log("Erreur suppression message: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>

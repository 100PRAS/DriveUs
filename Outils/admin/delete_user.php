<?php
// Handler pour supprimer un utilisateur (Admin uniquement)
session_start();

require_once __DIR__ . '/../config/config.php';

// Vérifier que l'utilisateur est admin
$stmt = $pdo->prepare("SELECT niveau FROM user WHERE UserID = ? LIMIT 1");
$stmt->execute([$_SESSION['UserID'] ?? 0]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$is_admin = ($user && ((int)$user['niveau'] == 1 || (int)$user['niveau'] == 2));

if (!$is_admin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

// Récupérer l'ID de l'utilisateur
$user_id = (int)($_POST['user_id'] ?? 0);

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant']);
    exit;
}

// Empêcher de supprimer l'admin lui-même
if ($user_id === (int)$_SESSION['UserID']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas vous supprimer vous-même']);
    exit;
}

try {
    // Commencer une transaction
    $pdo->beginTransaction();
    
    // Supprimer les messages envoyés
    $stmt = $pdo->prepare("DELETE FROM messages WHERE SenderID = ? OR ReceiverID = ?");
    $stmt->execute([$user_id, $user_id]);
    
    // Supprimer les réservations
    $stmt = $pdo->prepare("DELETE FROM reservations WHERE PassagerID = ?");
    $stmt->execute([$user_id]);
    
    // Supprimer les trajets
    $stmt = $pdo->prepare("DELETE FROM trajet WHERE ConducteurID = ?");
    $stmt->execute([$user_id]);
    
    // Supprimer l'utilisateur
    $stmt = $pdo->prepare("DELETE FROM user WHERE UserID = ?");
    $stmt->execute([$user_id]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>

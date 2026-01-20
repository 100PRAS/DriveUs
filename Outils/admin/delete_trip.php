<?php
// Handler pour supprimer un trajet (Admin uniquement)
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

// Récupérer l'ID du trajet
$trajet_id = (int)($_POST['trajet_id'] ?? 0);

if (!$trajet_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID trajet manquant']);
    exit;
}

try {
    // Commencer une transaction
    $pdo->beginTransaction();
    
    // Supprimer les réservations associées
    $stmt = $pdo->prepare("DELETE FROM reservations WHERE TrajetID = ?");
    $stmt->execute([$trajet_id]);
    
    // Supprimer le trajet
    $stmt = $pdo->prepare("DELETE FROM trajet WHERE TrajetID = ?");
    $stmt->execute([$trajet_id]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Trajet supprimé avec succès']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>

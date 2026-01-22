<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']['UserID'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

require __DIR__ . '/../config/config.php';

$reservationId = $_GET['reservation_id'] ?? null;
$userId = $_SESSION['user']['UserID'];

if (!$reservationId) {
    echo json_encode(['success' => false, 'message' => 'ID réservation manquant']);
    exit;
}

try {
    // Récupérer l'avis s'il existe
    $stmt = $pdo->prepare("
        SELECT AvisID, note, commentaire, date_creation
        FROM avis
        WHERE ReservationID = ? AND evaluateur_id = ?
        LIMIT 1
    ");
    $stmt->execute([$reservationId, $userId]);
    $avis = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'avis' => $avis ?: null
    ]);
} catch (Exception $e) {
    error_log('Erreur récupération avis: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>

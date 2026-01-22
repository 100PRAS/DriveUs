<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']['UserID'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

require __DIR__ . '/../config/config.php';

$data = json_decode(file_get_contents('php://input'), true);
$reservationId = $data['reservation_id'] ?? null;
$note = (int)($data['note'] ?? 0);
$commentaire = trim($data['commentaire'] ?? '');
$evaluateur_id = $_SESSION['user']['UserID'];

// Validation
if (!$reservationId || $note < 1 || $note > 5) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    // Vérifier que la réservation existe et appartient à l'utilisateur
    $stmt = $pdo->prepare("
        SELECT r.ReservationID, r.TrajetID, r.PassagerID, t.ConducteurID
        FROM reservations r
        JOIN trajet t ON r.TrajetID = t.TrajetID
        WHERE r.ReservationID = ?
    ");
    $stmt->execute([$reservationId]);
    $reservation = $stmt->fetch();

    if (!$reservation) {
        echo json_encode(['success' => false, 'message' => 'Réservation non trouvée']);
        exit;
    }

    // Le conducteur évalue le passager
    $evaluated_user_id = $reservation['PassagerID'];

    // Vérifier si un avis existe déjà
    $check_stmt = $pdo->prepare("
        SELECT AvisID FROM avis
        WHERE ReservationID = ? AND evaluateur_id = ? AND evaluated_user_id = ?
    ");
    $check_stmt->execute([$reservationId, $evaluateur_id, $evaluated_user_id]);

    if ($check_stmt->fetch()) {
        // Mettre à jour l'avis existant
        $update_stmt = $pdo->prepare("
            UPDATE avis
            SET note = ?, commentaire = ?, date_creation = NOW()
            WHERE ReservationID = ? AND evaluateur_id = ? AND evaluated_user_id = ?
        ");
        $update_stmt->execute([$note, $commentaire, $reservationId, $evaluateur_id, $evaluated_user_id]);
        echo json_encode(['success' => true, 'message' => 'Avis mis à jour']);
    } else {
        // Créer un nouvel avis
        $insert_stmt = $pdo->prepare("
            INSERT INTO avis (ReservationID, evaluateur_id, evaluated_user_id, note, commentaire, date_creation)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $insert_stmt->execute([$reservationId, $evaluateur_id, $evaluated_user_id, $note, $commentaire]);
        echo json_encode(['success' => true, 'message' => 'Avis créé']);
    }
} catch (Exception $e) {
    error_log('Erreur création avis: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>

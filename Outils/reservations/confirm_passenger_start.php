<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['UserID'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Non authentifié']));
}

require_once __DIR__ . '/../config/config.php';

$userId = $_SESSION['UserID'];
$tripId = (int)($_POST['trip_id'] ?? 0);
$reservationId = (int)($_POST['reservation_id'] ?? 0);

if (!$tripId || !$reservationId) {
    http_response_code(400);
    die(json_encode(['error' => 'Paramètres manquants']));
}

// Vérifier que la réservation existe et appartient à l'utilisateur
$stmt = $pdo->prepare("
    SELECT r.ReservationID, r.TrajetID 
    FROM reservations r 
    WHERE r.ReservationID = ? AND r.PassagerID = ? AND r.TrajetID = ?
");
$stmt->execute([$reservationId, $userId, $tripId]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    http_response_code(403);
    die(json_encode(['error' => 'Réservation non trouvée ou accès refusé']));
}

// Marquer que le passager a confirmé son départ
$stmt = $pdo->prepare("
    INSERT INTO reservations_started (ReservationID, passenger_confirmed, confirmed_at)
    VALUES (?, 1, NOW())
    ON DUPLICATE KEY UPDATE passenger_confirmed = 1, confirmed_at = NOW()
");
$result = $stmt->execute([$reservationId]);

if ($result) {
    // Vérifier si TOUS les passagers ont confirmé
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT r.ReservationID) as total_passengers,
            COUNT(DISTINCT CASE WHEN rs.passenger_confirmed = 1 THEN r.ReservationID END) as confirmed_passengers
        FROM reservations r
        LEFT JOIN reservations_started rs ON r.ReservationID = rs.ReservationID
        WHERE r.TrajetID = ? AND r.statut = 'confirmée'
    ");
    $stmt->execute([$tripId]);
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si tous les passagers ont confirmé ET le conducteur a aussi confirmé
    $stmt = $pdo->prepare("SELECT conductor_started FROM trajet WHERE TrajetID = ?");
    $stmt->execute([$tripId]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $allConfirmed = $counts['total_passengers'] > 0 && 
                   $counts['total_passengers'] == $counts['confirmed_passengers'] && 
                   $trip['conductor_started'] == 1;
    
    http_response_code(200);
    die(json_encode([
        'success' => true,
        'message' => 'Départ confirmé par le passager',
        'all_confirmed' => $allConfirmed,
        'total_passengers' => $counts['total_passengers'],
        'confirmed_passengers' => $counts['confirmed_passengers']
    ]));
} else {
    http_response_code(500);
    die(json_encode(['error' => 'Erreur lors de la mise à jour']));
}

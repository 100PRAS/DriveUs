<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header("Content-Type: application/json; charset=utf-8");

function fail($message, $code = 400) {
    http_response_code($code);
    echo json_encode(["success" => false, "message" => $message]);
    exit;
}

if (!isset($_SESSION['UserID'])) {
    fail("Utilisateur non connecte", 401);
}

require __DIR__ . '/../config/config.php';

if (!$pdo instanceof PDO) {
    fail("Database connection failed", 500);
}

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$reservationId = isset($data["reservationId"]) ? (int)$data["reservationId"] : 0;
$userId = (int)$_SESSION['UserID'];

if ($reservationId <= 0) {
    fail("Donnees invalides (identifiant de reservation manquant)");
}

try {
    $pdo->beginTransaction();
    
    // Verifier que la reservation appartient a l'utilisateur
    $stmtCheck = $pdo->prepare("SELECT TrajetID, nombre_places, statut FROM reservations WHERE ReservationID = ? AND PassagerID = ?");
    $stmtCheck->execute([$reservationId, $userId]);
    $reservationData = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$reservationData) {
        fail("Reservation introuvable ou non autorisee", 404);
    }

    $tripId = $reservationData['TrajetID'];
    $seatsBooked = $reservationData['nombre_places'];
    $currentStatus = $reservationData['statut'];

    // Verifier qu'elle n'est pas deja annulee
    if ($currentStatus === "annulee") {
        fail("Cette reservation est deja annulee");
    }

    // Marquer comme annulee
    $stmtCancel = $pdo->prepare("UPDATE reservations SET statut = ? WHERE ReservationID = ?");
    if (!$stmtCancel->execute(["annulee", $reservationId])) {
        throw new Exception("Erreur lors de l'annulation");
    }

    // Restaurer les places disponibles
    $stmtUpdate = $pdo->prepare("UPDATE trajet SET nombre_places = nombre_places + ? WHERE TrajetID = ?");
    if (!$stmtUpdate->execute([$seatsBooked, $tripId])) {
        throw new Exception("Erreur lors de la restauration des places");
    }

    $pdo->commit();
    echo json_encode([
        "success" => true,
        "message" => "Reservation annulee",
        "seatsRestored" => $seatsBooked
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    fail($e->getMessage(), 500);
}

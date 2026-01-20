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
    fail("Utilisateur non connecté", 401);
}

require __DIR__ . '/../config/config.php';

if (!$pdo instanceof PDO) {
    fail("Erreur de connexion à la base de données", 500);
}
$data = json_decode(file_get_contents("php://input"), true) ?? [];
$reservationId = isset($data["reservationId"]) ? (int)$data["reservationId"] : 0;
$userId = (int)$_SESSION['UserID'];

if ($reservationId <= 0) {
    fail("Identifiant de réservation invalide");
}

try {
    $pdo->beginTransaction();

    $table = 'reservations';
    $tables = $pdo->query("SHOW TABLES LIKE 'reservations'")->fetchAll();
    if (count($tables) === 0) {
        $table = 'reservation';
    }

    $stmtCheck = $pdo->prepare("SELECT r.statut, r.PassagerID, t.ConducteurID FROM {$table} r JOIN trajet t ON r.TrajetID = t.TrajetID WHERE r.ReservationID = ? FOR UPDATE");
    $stmtCheck->execute([$reservationId]);
    $reservation = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        fail("Réservation introuvable ou non autorisée", 404);
    }

    $isPassenger = $userId === (int)$reservation['PassagerID'];
    $isDriver = $userId === (int)$reservation['ConducteurID'];

    if (!$isPassenger && !$isDriver) {
        fail("Action non autorisée pour cet utilisateur", 403);
    }

    $current = strtolower($reservation['statut'] ?? '');
    if ($current === 'annulee') {
        fail("La réservation est déjà annulée");
    }
    if ($current === 'terminee') {
        fail("La réservation est déjà terminée");
    }

    $nextStatus = null;
    $baseStatuses = ['en cours', 'confirmée', 'confirmee'];

    if ($isPassenger) {
        if ($current === 'attente_passager') {
            $nextStatus = 'terminee';
        } elseif (in_array($current, $baseStatuses, true) || $current === 'attente_conducteur') {
            $nextStatus = 'attente_conducteur';
        }
    } else { // conducteur
        if ($current === 'attente_conducteur') {
            $nextStatus = 'terminee';
        } elseif (in_array($current, $baseStatuses, true) || $current === 'attente_passager') {
            $nextStatus = 'attente_passager';
        }
    }

    if ($nextStatus === null) {
        fail("Statut actuel incompatible avec une terminaison");
    }

    $stmtUpdate = $pdo->prepare("UPDATE {$table} SET statut = ? WHERE ReservationID = ?");
    if (!$stmtUpdate->execute([$nextStatus, $reservationId])) {
        throw new Exception("Impossible de mettre à jour la réservation");
    }

    $pdo->commit();
    echo json_encode([
        "success" => true,
        "message" => $nextStatus === 'terminee' ? "Réservation terminée" : "En attente de l'autre partie",
        "statut" => $nextStatus
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    fail($e->getMessage(), 500);
}

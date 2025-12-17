<?php
session_start();
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

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$reservationId = isset($data["reservationId"]) ? (int)$data["reservationId"] : 0;
$userId = (int)$_SESSION['UserID'];

if ($reservationId <= 0) {
    fail("Donnees invalides (identifiant de reservation manquant)");
}

$conn->begin_transaction();
try {
    // Verifier que la reservation appartient a l'utilisateur
    $stmtCheck = $conn->prepare("SELECT TrajetID, nombre_places, statut FROM reservation WHERE ReservationID = ? AND PassagerID = ?");
    $stmtCheck->bind_param("ii", $reservationId, $userId);
    $stmtCheck->execute();
    $stmtCheck->bind_result($tripId, $seatsBooked, $currentStatus);
    $found = $stmtCheck->fetch();
    $stmtCheck->close();

    if (!$found) {
        fail("Reservation introuvable ou non autorisee", 404);
    }

    // Verifier qu'elle n'est pas deja annulee
    if ($currentStatus === "annulee") {
        fail("Cette reservation est deja annulee");
    }

    // Marquer comme annulee
    $status = "annulee";
    $stmtCancel = $conn->prepare("UPDATE reservation SET statut = ? WHERE ReservationID = ?");
    $stmtCancel->bind_param("si", $status, $reservationId);
    if (!$stmtCancel->execute()) {
        $stmtCancel->close();
        throw new Exception("Erreur lors de l'annulation");
    }
    $stmtCancel->close();

    // Restaurer les places disponibles
    $stmtUpdate = $conn->prepare("UPDATE trajet SET nombre_places = nombre_places + ? WHERE TrajetID = ?");
    $stmtUpdate->bind_param("ii", $seatsBooked, $tripId);
    if (!$stmtUpdate->execute()) {
        $stmtUpdate->close();
        throw new Exception("Erreur lors de la restauration des places");
    }
    $stmtUpdate->close();

    $conn->commit();
    echo json_encode([
        "success" => true,
        "message" => "Reservation annulee",
        "seatsRestored" => $seatsBooked
    ]);
} catch (Exception $e) {
    $conn->rollback();
    fail($e->getMessage(), 500);
}

$conn->close();
?>

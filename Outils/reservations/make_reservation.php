<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . '/../mail/GmailSender.php';

function fail($message, $code = 400) {
    http_response_code($code);
    echo json_encode(["success" => false, "message" => $message]);
    exit;
}

if (!isset($_SESSION['UserID'])) {
    fail("Utilisateur non connecté", 401);
}

require __DIR__ . '/../config/config.php';

if (!$pdo) {
    fail("Erreur de connexion à la base de données", 500);
}

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$tripId = isset($data["tripId"]) ? (int)$data["tripId"] : 0;
$numberOfSeats = isset($data["numberOfSeats"]) ? max(1, (int)$data["numberOfSeats"]) : 1;
$userId = (int)$_SESSION['UserID'];

try {
    // Démarrer une transaction
    $pdo->beginTransaction();
    // Recharger l'utilisateur depuis la session (Mail facultatif)
    $stmt = $pdo->prepare("SELECT Mail, Prenom FROM user WHERE UserID = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $userEmail = $row['Mail'] ?? null;
    $userFirstName = $row['Prenom'] ?? '';

    if ($tripId <= 0) {
        fail("Données invalides (identifiant du trajet manquant)");
    }

    // Vérifier que le trajet existe et est publié
    $stmtTrip = $pdo->prepare("SELECT nombre_places, ConducteurID, VilleDepart, VilleArrivee, DateDepart, heure, Prix FROM trajet WHERE TrajetID = ? AND statut = 'publie'");
    $stmtTrip->execute([$tripId]);
    $tripData = $stmtTrip->fetch(PDO::FETCH_ASSOC);

    if (!$tripData) {
        fail("Trajet introuvable ou fermé", 404);
    }

    $seatsAvailable = (int)$tripData['nombre_places'];
    $conductorId = (int)$tripData['ConducteurID'];
    $fromCity = $tripData['VilleDepart'] ?? '';
    $toCity = $tripData['VilleArrivee'] ?? '';
    $tripDate = $tripData['DateDepart'] ?? '';
    $tripTime = $tripData['heure'] ?? '';
    $tripPrice = $tripData['Prix'] ?? '';

    // Empêcher de réserver son propre trajet
    if ($userId === $conductorId) {
        fail("Vous ne pouvez pas réserver votre propre trajet");
    }

    // Places disponibles suffisantes ?
    if ($seatsAvailable < $numberOfSeats) {
        fail("Pas assez de places disponibles");
    }

    // Déjà réservé ?
    $stmtCheck = $pdo->prepare("SELECT ReservationID FROM reservations WHERE TrajetID = ? AND PassagerID = ?");
    $stmtCheck->execute([$tripId, $userId]);
    if ($stmtCheck->rowCount() > 0) {
        fail("Vous avez déjà réservé ce trajet");
    }

    // Insérer la réservation
    $status = "confirmée";
    $now = date('Y-m-d H:i:s');
    $stmtReserve = $pdo->prepare("INSERT INTO reservations (TrajetID, PassagerID, statut, nombre_places, date_reservation) VALUES (?, ?, ?, ?, ?)");
    $stmtReserve->execute([$tripId, $userId, $status, $numberOfSeats, $now]);
    $reservationId = $pdo->lastInsertId();

    // Mettre à jour le nombre de places et marquer complet si nécessaire
    $newSeats = $seatsAvailable - $numberOfSeats;
    $stmtUpdate = $pdo->prepare("
        UPDATE trajet 
        SET nombre_places = ?,
            statut = CASE WHEN ? <= 0 THEN 'complet' ELSE statut END
        WHERE TrajetID = ?
    ");
    $stmtUpdate->execute([$newSeats, $newSeats, $tripId]);

    // Valider la transaction
    $pdo->commit();

    // Notifications email (non bloquantes)
    try {
        $gmail = new GmailSender();

        // Email passager
        if (!empty($userEmail)) {
            $subject = 'Votre réservation est confirmée';
            $html = "<html><body style='font-family:Arial,sans-serif;'>"
                . "<h3 style='color:#4c51bf;'>Réservation confirmée</h3>"
                . "<p>Bonjour " . htmlspecialchars($userFirstName) . ",</p>"
                . "<p>Votre réservation pour le trajet " . htmlspecialchars($fromCity) . " → " . htmlspecialchars($toCity) . " le " . htmlspecialchars($tripDate) . " à " . htmlspecialchars($tripTime) . " est confirmée.</p>"
                . "<p>Places réservées: " . (int)$numberOfSeats . "</p>"
                . "</body></html>";
            $gmail->send($userEmail, $subject, $html);
        }

        // Email conducteur
        $stmtDriver = $pdo->prepare("SELECT Mail, Prenom FROM user WHERE UserID = ?");
        $stmtDriver->execute([$conductorId]);
        $driver = $stmtDriver->fetch(PDO::FETCH_ASSOC);
        if ($driver && !empty($driver['Mail'])) {
            $subjectDriver = 'Nouvelle réservation sur votre trajet';
            $htmlDriver = "<html><body style='font-family:Arial,sans-serif;'>"
                . "<h3 style='color:#4c51bf;'>Nouveau passager</h3>"
                . "<p>Bonjour " . htmlspecialchars($driver['Prenom'] ?? '') . ",</p>"
                . "<p>Un passager a réservé votre trajet " . htmlspecialchars($fromCity) . " → " . htmlspecialchars($toCity) . " le " . htmlspecialchars($tripDate) . " à " . htmlspecialchars($tripTime) . ".</p>"
                . "<p>Places réservées: " . (int)$numberOfSeats . "</p>"
                . "</body></html>";
            $gmail->send($driver['Mail'], $subjectDriver, $htmlDriver);
        }
    } catch (Exception $e) {
        error_log('Notification reservation email échouée: ' . $e->getMessage());
    }

    echo json_encode([
        "success" => true,
        "message" => "Réservation confirmée",
        "reservationId" => $reservationId,
        "seatsRemaining" => $newSeats
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    fail("Erreur lors de la réservation: " . $e->getMessage(), 500);
} catch (Exception $e) {
    $pdo->rollBack();
    fail("Erreur: " . $e->getMessage(), 500);
}
?>

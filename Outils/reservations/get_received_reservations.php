<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header("Content-Type: application/json");

// Vérification utilisateur connecté
if (!isset($_SESSION['UserID'])) {
    echo json_encode(["error" => "Not logged"]);
    exit;
}

require __DIR__ . '/../config/config.php';

if (!$pdo instanceof PDO) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$userId = (int)$_SESSION['UserID'];

try {
    // Récupérer les réservations des trajets du conducteur
    $table = 'reservations';
    $tables = $pdo->query("SHOW TABLES LIKE 'reservations'")->fetchAll();
    if (count($tables) === 0) {
        $table = 'reservation';
    }

    $query = "
            SELECT 
                r.ReservationID,
                r.TrajetID,
                r.nombre_places AS nombre_places,
                r.statut AS statut,
                r.date_reservation AS date_reservation,
                t.VilleDepart,
                t.VilleArrivee,
                t.DateDepart,
                u.Prenom as PassengerName,
                u.Mail as PassengerEmail
            FROM {$table} r
            JOIN trajet t ON r.TrajetID = t.TrajetID
            JOIN user u ON r.PassagerID = u.UserID
            WHERE t.ConducteurID = ?
            ORDER BY r.date_reservation DESC
        ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $reservations = [];
    foreach ($results as $row) {
        $reservations[] = [
            'id' => $row['ReservationID'],
            'tripId' => $row['TrajetID'],
            'from' => $row['VilleDepart'],
            'to' => $row['VilleArrivee'],
            'date' => $row['DateDepart'],
            'seats' => $row['nombre_places'],
            'status' => $row['statut'],
            'passenger' => $row['PassengerName'],
            'passengerEmail' => $row['PassengerEmail'],
            'bookingDate' => $row['date_reservation']
        ];
    }

    echo json_encode($reservations);
} catch (Throwable $e) {
    error_log('[get_received_reservations] ' . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}

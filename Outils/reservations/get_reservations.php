<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Vérification utilisateur connecté
if (!isset($_SESSION['UserID'])) {
    echo json_encode(['error' => 'Not logged']);
    exit;
}

require __DIR__ . '/../config/config.php';

if (!$pdo instanceof PDO) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$userId = (int)$_SESSION['UserID'];

try {
    // Détecte la table réservations ou reservation
    $table = 'reservations';
    $tables = $pdo->query("SHOW TABLES LIKE 'reservations'")->fetchAll();
    if (count($tables) === 0) {
        $table = 'reservation';
    }

    // Récupère les réservations avec compatibilité des colonnes
    $sql = "
        SELECT 
            r.ReservationID,
            r.TrajetID,
            r.nombre_places AS seats,
            r.statut AS statut,
            r.date_reservation AS date_reservation,
            t.VilleDepart,
            t.VilleArrivee,
            t.DateDepart,
            t.heure,
            t.Prix,
            u.Prenom as ConductorName,
            u.Nom as ConductorLast,
            u.Mail as ConductorEmail,
            u.PhotoProfil as ConductorPhoto
        FROM {$table} r
        JOIN trajet t ON r.TrajetID = t.TrajetID
        JOIN user u ON t.ConducteurID = u.UserID
        WHERE r.PassagerID = ?
        ORDER BY r.date_reservation DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $reservations = [];
    foreach ($results as $row) {
        $photo = !empty($row['ConductorPhoto']) 
            ? '/Image_Profil/' . htmlspecialchars($row['ConductorPhoto']) 
            : '/Image_Profil/default.png';
        $reservations[] = [
            'id' => $row['ReservationID'],
            'tripId' => $row['TrajetID'],
            'from' => $row['VilleDepart'],
            'to' => $row['VilleArrivee'],
            'date' => $row['DateDepart'],
            'time' => $row['heure'] ?? 'N/A',
            'price' => $row['Prix'],
            'seats' => $row['seats'],
            'status' => $row['statut'],
            'driver' => trim(($row['ConductorName'] ?? '') . ' ' . ($row['ConductorLast'] ?? '')),
            'driverEmail' => $row['ConductorEmail'],
            'driverPhoto' => $photo,
            'bookingDate' => $row['date_reservation']
        ];
    }

    echo json_encode($reservations);
} catch (Throwable $e) {
    error_log('[get_reservations] ' . $e->getMessage());
    echo json_encode(['error' => 'Query failed', 'details' => $e->getMessage()]);
}
?>

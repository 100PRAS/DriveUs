<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header("Content-Type: application/json");

if (!isset($_SESSION['UserID'])) {
    echo json_encode(["error" => "Not logged"]);
    exit;
}

require __DIR__ . '/../config/config.php';

if (!$pdo instanceof PDO) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

try {
    $data = json_decode(file_get_contents("php://input"), true);

    // Récupérer l'email du sender
    $stmt = $pdo->prepare("SELECT Mail FROM user WHERE UserID = ?");
    $stmt->execute([$_SESSION['UserID']]);
    $sender = $stmt->fetchColumn();

    $trajetId = $data["trajet_id"] ?? null;
    $message = $data["message"] ?? "";

    if (!$trajetId || $message === "") {
        echo json_encode(["error" => "Missing data"]);
        exit;
    }

    // Vérifier que l'utilisateur fait partie du trajet (conducteur ou passager)
    $stmt = $pdo->prepare("
        SELECT t.ConducteurID, u.Mail as ConducteurMail
        FROM trajet t
        LEFT JOIN user u ON u.UserID = t.ConducteurID
        WHERE t.TrajetID = ?
    ");
    $stmt->execute([$trajetId]);
    $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trajet) {
        echo json_encode(["error" => "Trajet not found"]);
        exit;
    }

    $isConducteur = ($trajet['ConducteurMail'] === $sender);

    // Si pas conducteur, vérifier si c'est un passager
    if (!$isConducteur) {
        $stmt = $pdo->prepare("
            SELECT r.ReservationID
            FROM reservations r
            LEFT JOIN user u ON u.UserID = r.PassagerID
            WHERE r.TrajetID = ? AND u.Mail = ? AND r.statut = 'confirmée'
        ");
        $stmt->execute([$trajetId, $sender]);
        $isPassager = $stmt->rowCount() > 0;
        
        if (!$isPassager) {
            echo json_encode(["error" => "Not authorized", "message" => "Vous ne faites pas partie de ce trajet"]);
            exit;
        }
    }

    // Insérer le message de groupe
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender, receiver, message, TrajetID, is_group, created_at)
        VALUES (?, 'group', ?, ?, 1, NOW())
    ");

    if ($stmt->execute([$sender, $message, $trajetId])) {
        echo json_encode(["success" => true, "message" => "Message envoyé au groupe"]);
    } else {
        echo json_encode(["error" => "Database error"]);
    }
} catch (Throwable $e) {
    error_log('[send_group_message] ' . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
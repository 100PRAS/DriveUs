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
    $trajetId = $_GET['trajet_id'] ?? null;

    if (!$trajetId) {
        echo json_encode(["error" => "Missing trajet_id"]);
        exit;
    }

    // Récupérer l'email de l'utilisateur
    $stmt = $pdo->prepare("SELECT Mail FROM user WHERE UserID = ?");
    $stmt->execute([$_SESSION['UserID']]);
    $userEmail = $stmt->fetchColumn();

    // Vérifier si l'utilisateur est le conducteur ou un passager du trajet
    $stmt = $pdo->prepare("
        SELECT t.ConducteurID, u.Mail as ConducteurMail, u.Prenom as ConducteurPrenom, u.PhotoProfil as ConducteurPhoto
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

    // Récupérer tous les passagers du trajet
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.Mail, u.Prenom, u.PhotoProfil
        FROM reservations r
        LEFT JOIN user u ON u.UserID = r.PassagerID
        WHERE r.TrajetID = ? AND r.statut = 'confirmée'
    ");
    $stmt->execute([$trajetId]);
    $passengers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $photoBasePath = getPhotoBasePath();
    $participants = [];
    // Ajouter le conducteur
    $participants[] = [
        "email" => $trajet['ConducteurMail'],
        "prenom" => $trajet['ConducteurPrenom'] ?? $trajet['ConducteurMail'],
        "photo" => !empty($trajet['ConducteurPhoto']) ? $photoBasePath . $trajet['ConducteurPhoto'] : $photoBasePath . "default.png",
        "role" => "conducteur"
    ];

    // Ajouter les passagers
    foreach ($passengers as $row) {
        $participants[] = [
            "email" => $row['Mail'],
            "prenom" => $row['Prenom'] ?? $row['Mail'],
            "photo" => !empty($row['PhotoProfil']) ? $photoBasePath . $row['PhotoProfil'] : $photoBasePath . "default.png",
            "role" => "passager"
        ];
    }

    // Récupérer les messages de groupe du trajet
    $stmt = $pdo->prepare("
        SELECT m.*, u.Prenom, u.PhotoProfil
        FROM messages m
        LEFT JOIN user u ON u.Mail = m.sender
        WHERE m.TrajetID = ? AND m.is_group = 1
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$trajetId]);
    $messageResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $messages = [];
    foreach ($messageResults as $row) {
        $messages[] = [
            "id" => $row['id'],
            "sender" => $row['sender'],
            "prenom" => $row['Prenom'] ?? $row['sender'],
            "photo" => !empty($row['PhotoProfil']) ? $photoBasePath . $row['PhotoProfil'] : $photoBasePath . "default.png",
            "message" => $row['message'],
            "date" => $row['created_at'] ?? $row['date_envoi'],
            "lu" => $row['lu'] ?? 0
        ];
    }

    echo json_encode([
        "success" => true,
        "participants" => $participants,
        "messages" => $messages,
        "trajetId" => $trajetId
    ]);
} catch (Throwable $e) {
    error_log('[get_group_conversation] ' . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
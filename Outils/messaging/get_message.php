<?php
session_start();
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['UserID'])) {
        echo json_encode([]);
        exit;
    }

    require_once __DIR__ . '/../config/config.php';
    if (!$pdo) {
        http_response_code(500);
        echo json_encode(['error' => 'no_pdo_connection']);
        exit;
    }

    $contact = trim($_GET['contact'] ?? '');
    if ($contact === '') {
        echo json_encode([]);
        exit;
    }

    // Email utilisateur connecté
    $stmt = $pdo->prepare('SELECT Mail FROM user WHERE UserID = ?');
    $stmt->execute([$_SESSION['UserID']]);
    $user = $stmt->fetchColumn();

    if (!$user) {
        echo json_encode([]);
        exit;
    }

    // Messages bilatéraux
    $sql = 'SELECT id, sender, receiver, message, created_at
            FROM messages
            WHERE (sender = :me AND receiver = :contact) OR (sender = :contact AND receiver = :me)
            ORDER BY created_at ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['me' => $user, 'contact' => $contact]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($messages);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'exception',
        'message' => $e->getMessage(),
        'contact' => $_GET['contact'] ?? null
    ]);
}

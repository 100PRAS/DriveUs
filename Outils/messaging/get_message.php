<?php
session_start();
header("Content-Type: application/json");

try {
    if (!isset($_SESSION['UserID'])) {
        echo json_encode([]);
        exit;
    }

    require_once __DIR__ . '/../config/config.php';

    $contact = $_GET["contact"] ?? "";
    if ($contact === "") {
        echo json_encode([]);
        exit;
    }

    // Récupérer l'email de l'utilisateur connecté
    $stmt = $conn->prepare("SELECT Mail FROM user WHERE UserID = ?");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'prepare_user_failed', 'message' => $conn->error]);
        exit;
    }
    
    $stmt->bind_param("i", $_SESSION['UserID']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $user = $row['Mail'] ?? null;
    $stmt->close();

    if (!$user) {
        echo json_encode([]);
        exit;
    }

    // Récupérer les messages
    $sql = "SELECT id, sender, receiver, message, created_at 
            FROM messages 
            WHERE (sender = ? AND receiver = ?) OR (sender = ? AND receiver = ?)
            ORDER BY created_at ASC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            'error' => 'prepare_messages_failed',
            'message' => $conn->error,
            'sql' => $sql,
            'user' => $user,
            'contact' => $contact
        ]);
        exit;
    }

    $stmt->bind_param("ssss", $user, $contact, $contact, $user);
    
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode([
            'error' => 'execute_failed',
            'message' => $stmt->error,
            'sql' => $sql,
            'user' => $user,
            'contact' => $contact
        ]);
        exit;
    }

    $result = $stmt->get_result();
    $messages = [];
    
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    $stmt->close();
    $conn->close();

    echo json_encode($messages);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'exception',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'contact' => $_GET["contact"] ?? null
    ]);
}

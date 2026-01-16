<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if (!isset($_SESSION['UserID'])) {
    echo json_encode(['error' => 'Not logged', 'message' => 'Utilisateur non connecté']);
    exit;
}

require __DIR__ . '/../config/config.php';
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'no_pdo_connection']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Récupérer l'email du sender
$stmt = $pdo->prepare('SELECT Mail FROM user WHERE UserID = ?');
$stmt->execute([$_SESSION['UserID']]);
$sender = $stmt->fetchColumn();

$receiver = trim($data['receiver'] ?? '');
$message  = trim($data['message'] ?? '');
$trajet   = trim($data['trajet'] ?? '');

if ($receiver === '' || $message === '') {
    echo json_encode(['error' => 'Missing data', 'message' => 'Données manquantes']);
    exit;
}

// Ne pas envoyer de message à l'assistant
if ($receiver === 'Assistant DriveUs (24h/24)') {
    echo json_encode(['success' => true, 'message' => "Message envoyé à l'assistant"]);
    exit;
}

// Enregistrer le message avec le trajet si disponible
$stmt = $pdo->prepare('INSERT INTO messages (sender, receiver, message, created_at) VALUES (:s, :r, :m, NOW())');
$ok = $stmt->execute(['s' => $sender, 'r' => $receiver, 'm' => $message]);

// Si un trajet est associé, enregistrer l'association conversation-trajet
if ($ok && !empty($trajet)) {
    try {
        // Vérifier si la table conversation_trajet existe, sinon la créer
        $pdo->exec("CREATE TABLE IF NOT EXISTS conversation_trajet (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user1 VARCHAR(255) NOT NULL,
            user2 VARCHAR(255) NOT NULL,
            trajet TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_conv (user1, user2)
        )");
        
        // Normaliser l'ordre des emails (alphabétique)
        $users = [$sender, $receiver];
        sort($users);
        
        // Insérer ou mettre à jour le trajet de la conversation
        $stmt = $pdo->prepare(
            'INSERT INTO conversation_trajet (user1, user2, trajet) VALUES (:u1, :u2, :t) 
             ON DUPLICATE KEY UPDATE trajet = :t, updated_at = NOW()'
        );
        $stmt->execute(['u1' => $users[0], 'u2' => $users[1], 't' => $trajet]);
    } catch (Exception $e) {
        // Ignorer les erreurs de création de table en silence
        error_log('Erreur conversation_trajet: ' . $e->getMessage());
    }
}

$ok = true;

if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Message envoyé avec succès']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => "Erreur lors de l'envoi"]);
}
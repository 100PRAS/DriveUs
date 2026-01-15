<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header("Content-Type: application/json");

try {
    if (!isset($_SESSION['UserID'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Non connecté']);
        exit;
    }

    require_once __DIR__ . '/../config/config.php';

    $data = json_decode(file_get_contents("php://input"), true);

    $topicId = intval($data['topic_id'] ?? 0);
    $content = trim($data['content'] ?? '');

    if (!$topicId || !$content) {
        echo json_encode(['error' => 'Données manquantes']);
        exit;
    }

    // Récupérer email et prénom de l'utilisateur
    $stmt = $pdo->prepare("SELECT Mail, Prenom, Nom FROM user WHERE UserID = ?");
    $stmt->execute([$_SESSION['UserID']]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['error' => 'Utilisateur non trouvé']);
        exit;
    }

    $authorName = $user['Prenom'] . ' ' . substr($user['Nom'], 0, 1) . '.';

    // Insérer la réponse
    $stmt = $pdo->prepare("INSERT INTO forum_replies (topic_id, content, author_email, author_name) VALUES (?, ?, ?, ?)");
    $stmt->execute([$topicId, $content, $user['Mail'], $authorName]);
    $replyId = $pdo->lastInsertId();

    echo json_encode(['success' => true, 'id' => $replyId]);

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

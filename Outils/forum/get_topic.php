<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header("Content-Type: application/json");

try {
    require_once __DIR__ . '/../config/config.php';

    $topicId = $_GET['id'] ?? 0;

    if (!$topicId) {
        echo json_encode(['error' => 'ID manquant']);
        exit;
    }

    // Récupérer le sujet
    $stmt = $pdo->prepare("SELECT id, title, content, author_name, created_at FROM forum_topics WHERE id = ?");
    $stmt->execute([$topicId]);
    $topic = $stmt->fetch();

    if (!$topic) {
        echo json_encode(['error' => 'Sujet non trouvé']);
        exit;
    }

    // Récupérer les réponses
    $stmt = $pdo->prepare("SELECT id, content, author_name, created_at FROM forum_replies WHERE topic_id = ? ORDER BY created_at ASC");
    $stmt->execute([$topicId]);
    $replies = $stmt->fetchAll();

    $topic['replies'] = $replies;

    echo json_encode($topic);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

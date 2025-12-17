<?php
session_start();
header("Content-Type: application/json");

try {
    require_once __DIR__ . '/../config/config.php';

    $topicId = $_GET['id'] ?? 0;

    if (!$topicId) {
        echo json_encode(['error' => 'ID manquant']);
        exit;
    }

    // Récupérer le sujet
    $stmt = $conn->prepare("SELECT id, title, content, author_name, created_at FROM forum_topics WHERE id = ?");
    $stmt->bind_param("i", $topicId);
    $stmt->execute();
    $result = $stmt->get_result();
    $topic = $result->fetch_assoc();
    $stmt->close();

    if (!$topic) {
        echo json_encode(['error' => 'Sujet non trouvé']);
        exit;
    }

    // Récupérer les réponses
    $stmt = $conn->prepare("SELECT id, content, author_name, created_at FROM forum_replies WHERE topic_id = ? ORDER BY created_at ASC");
    $stmt->bind_param("i", $topicId);
    $stmt->execute();
    $result = $stmt->get_result();

    $replies = [];
    while ($row = $result->fetch_assoc()) {
        $replies[] = $row;
    }

    $topic['replies'] = $replies;

    echo json_encode($topic);

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

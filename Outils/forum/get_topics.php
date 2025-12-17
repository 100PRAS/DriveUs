<?php
session_start();
header("Content-Type: application/json");

try {
    require_once __DIR__ . '/../config/config.php';

    $search = $_GET['search'] ?? '';

    if ($search) {
        $sql = "SELECT t.id, t.title, t.author_name, t.created_at, COUNT(r.id) as reply_count
                FROM forum_topics t
                LEFT JOIN forum_replies r ON t.id = r.topic_id
                WHERE t.title LIKE ? OR t.content LIKE ?
                GROUP BY t.id
                ORDER BY t.created_at DESC";
        $stmt = $conn->prepare($sql);
        $searchTerm = "%$search%";
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
    } else {
        $sql = "SELECT t.id, t.title, t.author_name, t.created_at, COUNT(r.id) as reply_count
                FROM forum_topics t
                LEFT JOIN forum_replies r ON t.id = r.topic_id
                GROUP BY t.id
                ORDER BY t.created_at DESC";
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $topics = [];
    while ($row = $result->fetch_assoc()) {
        $topics[] = $row;
    }

    echo json_encode($topics);

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

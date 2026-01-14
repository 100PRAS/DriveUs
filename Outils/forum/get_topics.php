<?php
header('Content-Type: application/json');
error_reporting(0); // Masque les warnings PHP
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

$search = $_GET['search'] ?? '';
$topics = [];

try {
    if (!$pdo) {
        throw new Exception('Connexion à la base de données indisponible');
    }

    if ($search) {
        $sql = "SELECT t.id, t.title, t.author_name, t.created_at, COUNT(r.id) AS reply_count
                FROM forum_topics t
                LEFT JOIN forum_replies r ON t.id = r.topic_id
                WHERE t.title LIKE :term OR t.content LIKE :term
                GROUP BY t.id
                ORDER BY t.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $term = "%{$search}%";
        $stmt->bindParam(':term', $term, PDO::PARAM_STR);
    } else {
        $sql = "SELECT t.id, t.title, t.author_name, t.created_at, COUNT(r.id) AS reply_count
                FROM forum_topics t
                LEFT JOIN forum_replies r ON t.id = r.topic_id
                GROUP BY t.id
                ORDER BY t.created_at DESC";
        $stmt = $pdo->prepare($sql);
    }

    $stmt->execute();
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($topics);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

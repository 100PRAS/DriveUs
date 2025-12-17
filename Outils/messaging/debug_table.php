<?php
session_start();
header("Content-Type: application/json");

require_once __DIR__ . '/../config/config.php';

// Afficher la structure de la table messages
$result = $conn->query("DESCRIBE messages");

$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row;
}

echo json_encode(['columns' => $columns], JSON_PRETTY_PRINT);

$conn->close();
?>

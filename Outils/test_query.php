<?php
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/config.php';

// Requête simple sans paramètres
$sql = "SELECT * FROM trajet WHERE statut = 'publie' LIMIT 5";

$result = $conn->query($sql);

if(!$result) {
    die(json_encode(['error' => 'Query failed: ' . $conn->error]));
}

$rows = [];
while($r = $result->fetch_assoc()) {
    $rows[] = $r;
}

echo json_encode([
    'count' => count($rows),
    'data' => $rows
], JSON_UNESCAPED_UNICODE);

<?php
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/config.php';

// Vérifier tous les statuts
$result = $conn->query("SELECT statut, COUNT(*) as cnt FROM trajet GROUP BY statut");

$statuts = [];
while($r = $result->fetch_assoc()) {
    $statuts[] = $r;
}

echo json_encode([
    'message' => 'Statuts des trajets',
    'data' => $statuts
], JSON_UNESCAPED_UNICODE);

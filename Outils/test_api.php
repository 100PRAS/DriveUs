<?php
// Ultra simple test
header('Content-Type: application/json; charset=utf-8');

echo json_encode(['step' => '1 - Démarrage']);

require __DIR__ . '/config.php';

echo json_encode(['step' => '2 - Config inclus', 'conn' => ($conn ? 'OK' : 'FAIL')]);

$sql = "SELECT * FROM trajet WHERE statut = 'publie' LIMIT 2";

echo json_encode(['step' => '3 - SQL: ' . $sql]);

$result = $conn->query($sql);

echo json_encode(['step' => '4 - Query result: ' . ($result ? 'OK' : $conn->error)]);

if($result) {
    $rows = [];
    while($r = $result->fetch_assoc()) {
        $rows[] = $r;
    }
    echo json_encode(['step' => '5 - Rows', 'count' => count($rows)], JSON_UNESCAPED_UNICODE);
}


// Compter les trajets
$result = $conn->query("SELECT COUNT(*) as count FROM trajet");
$row = $result->fetch_assoc();
echo "✓ Nombre de trajets: " . $row['count'] . "\n\n";

// Afficher les colonnes
echo "Colonnes de la table 'trajet':\n";
$result = $conn->query("DESCRIBE trajet");
while($col = $result->fetch_assoc()) {
    echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
}

// Test simple de la requête
echo "\n\nTest requête simple:\n";
$sql = "SELECT * FROM trajet LIMIT 1";
$result = $conn->query($sql);
if($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "Premier trajet:\n";
    foreach($row as $key => $val) {
        echo "  $key => " . substr($val, 0, 50) . "\n";
    }
} else {
    echo "❌ Aucun trajet trouvé\n";
}

$conn->close();

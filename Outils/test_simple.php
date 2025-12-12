<?php
header('Content-Type: application/json; charset=utf-8');

// Test ultra simple
echo json_encode(['test' => 'Démarrage du script']);

// Test inclusion config
if(file_exists(__DIR__ . '/config.php')) {
    echo "\n" . json_encode(['config' => 'Fichier trouvé']);
    require __DIR__ . '/config.php';
    echo "\n" . json_encode(['connexion' => ($conn ? 'OK' : 'FAILED')]);
    
    if($conn) {
        // Test requête simple
        $result = $conn->query("SELECT COUNT(*) as cnt FROM trajet");
        if($result) {
            $row = $result->fetch_assoc();
            echo "\n" . json_encode(['trajet_count' => $row['cnt']]);
        } else {
            echo "\n" . json_encode(['query_error' => $conn->error]);
        }
    }
} else {
    echo "\n" . json_encode(['config' => 'Fichier NOT FOUND']);
}

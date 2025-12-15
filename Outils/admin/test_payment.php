<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== TEST payment_handler ===\n\n";

$_SESSION['UserID'] = 34; // Assumez que vous êtes connecté

// Test exactement ce que payment_handler fait
$userId = $_SESSION['UserID'];

echo "UserID: $userId\n";
echo "Connexion DB: " . ($conn ? "OK" : "FAIL") . "\n\n";

echo "Schéma de payment_methods:\n";
$result = $conn->query("DESCRIBE payment_methods");
while ($col = $result->fetch_assoc()) {
    echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
}

echo "\n=== Test requête ligne 22 ===\n";
$query = "SELECT id, card_brand, last4, exp_month, exp_year, is_default, created_at FROM payment_methods WHERE UserID = ? ORDER BY is_default DESC, created_at DESC";
echo "Requête: $query\n\n";

try {
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo "ERREUR prepare: " . $conn->error . "\n";
    } else {
        echo "✅ Prepare OK\n";
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        echo "✅ Execute OK\n";
        $result = $stmt->get_result();
        echo "Résultats: " . $result->num_rows . " cartes trouvées\n";
    }
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
}

?>

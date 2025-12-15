<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Diagnostic Base de Données</h2>";

// Vérifier connexion
if ($conn->connect_error) {
    die("Erreur connexion: " . $conn->connect_error);
}

echo "<h3>Vérification table payment_methods</h3>";

// Vérifier si la table existe
$result = $conn->query("SHOW TABLES LIKE 'payment_methods'");
if ($result->num_rows == 0) {
    echo "<p style='color:red'>❌ Table payment_methods n'existe pas</p>";
} else {
    echo "<p style='color:green'>✅ Table payment_methods existe</p>";
    
    // Afficher la structure
    echo "<h4>Structure de la table:</h4>";
    $columns = $conn->query("DESCRIBE payment_methods");
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($col = $columns->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>Vérification table user_vehicles</h3>";
$result = $conn->query("SHOW TABLES LIKE 'user_vehicles'");
if ($result->num_rows == 0) {
    echo "<p style='color:red'>❌ Table user_vehicles n'existe pas</p>";
} else {
    echo "<p style='color:green'>✅ Table user_vehicles existe</p>";
    $columns = $conn->query("DESCRIBE user_vehicles");
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th></tr>";
    while ($col = $columns->fetch_assoc()) {
        echo "<tr><td>" . htmlspecialchars($col['Field']) . "</td><td>" . htmlspecialchars($col['Type']) . "</td></tr>";
    }
    echo "</table>";
}

?>

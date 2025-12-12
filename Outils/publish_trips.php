<?php
require 'config.php';

// Mettre à jour les trajets en statut 'publie'
$sql = "UPDATE trajet SET statut = 'publie' WHERE statut = 'brouillon'";
$result = $conn->query($sql);

if($result) {
    echo "✓ " . $conn->affected_rows . " trajets sont maintenant publiés\n";
} else {
    echo "❌ Erreur: " . $conn->error;
}

$conn->close();

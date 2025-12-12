<?php
// Test direct de l'API avec paramètres
header('Content-Type: text/plain; charset=utf-8');

require 'config.php';

echo "=== TEST DE L'API get_trips.php ===\n\n";

// Simuler les paramètres GET
$_GET['from'] = '';
$_GET['to'] = '';
$_GET['date'] = '';
$_GET['priceMax'] = '9999';
$_GET['seatsMin'] = '0';
$_GET['timeBand'] = 'all';
$_GET['minRating'] = '0';
$_GET['sort'] = 'relevance';

echo "Paramètres: " . json_encode($_GET) . "\n\n";

// Inclure et exécuter le script
echo "Résultat de l'API:\n";
ob_start();
include 'get_trips.php';
$output = ob_get_clean();

// Afficher la sortie brute
echo "RAW OUTPUT (" . strlen($output) . " bytes):\n";
echo $output;
echo "\n\nHEX DUMP (premiers 200 chars):\n";
echo bin2hex(substr($output, 0, 200));

// Vérifier si c'est du JSON valide
echo "\n\n=== Vérification JSON ===\n";
$decoded = json_decode($output, true);
if(json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ JSON invalide: " . json_last_error_msg() . "\n";
    echo "Erreur code: " . json_last_error() . "\n";
} else {
    echo "✓ JSON valide\n";
    echo "Nombre de trajets: " . count($decoded) . "\n";
    if(count($decoded) > 0) {
        echo "Premier trajet:\n";
        print_r($decoded[0]);
    }
}

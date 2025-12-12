<?php
// Test direct de l'API avec paramètres
header('Content-Type: text/plain; charset=utf-8');

// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TEST DE L'API get_trips.php ===\n\n";

echo "Tentative d'inclusion de config.php...\n";
if(!file_exists(__DIR__ . '/config.php')) {
    echo "❌ config.php NOT FOUND!\n";
    exit;
}

require __DIR__ . '/config.php';
echo "✓ config.php inclus\n";
echo "Connexion mysqli: " . ($conn ? "OK" : "FAILED") . "\n\n";

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

echo "Résultat de l'API:\n";
echo "---START---\n";

// Capturer les erreurs et la sortie
ob_start();
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "PHP ERROR [$errno]: $errstr in $errfile:$errline\n";
});

try {
    include 'get_trips.php';
} catch(Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}

restore_error_handler();
$output = ob_get_clean();

echo $output;
echo "---END---\n";

echo "\nOutput length: " . strlen($output) . " bytes\n";

// Vérifier si c'est du JSON valide
echo "\n=== Vérification JSON ===\n";
if(empty($output)) {
    echo "❌ SORTIE VIDE - L'API ne produit rien!\n";
} else {
    $decoded = json_decode($output, true);
    if(json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ JSON invalide: " . json_last_error_msg() . "\n";
        echo "Erreur code: " . json_last_error() . "\n";
        echo "Contenu: " . substr($output, 0, 500) . "\n";
    } else {
        echo "✓ JSON valide\n";
        echo "Nombre de trajets: " . count($decoded) . "\n";
        if(count($decoded) > 0) {
            echo "Premier trajet:\n";
            print_r($decoded[0]);
        }
    }
}

<?php

// Charger les variables d'environnement depuis .env
$env_file = __DIR__ . '/../../.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

$config_prod = [
    'host' => $_ENV['DB_PROD_HOST'] ?? 'db5019347583.hosting-data.io',
    'port' => $_ENV['DB_PROD_PORT'] ?? 3306,
    'db'   => $_ENV['DB_PROD_DB'] ?? 'dbs15148242',
    'user' => $_ENV['DB_PROD_USER'] ?? 'dbu150815',
    'pass' => $_ENV['DB_PROD_PASS'] ?? '',
    'env' => 'prod'
];

// Utiliser la config prod en priorité
$config = $config_prod;

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};port={$config['port']};dbname={$config['db']};charset=utf8mb4",
        $config['user'],
        $config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    // Essayer la config prod si local échoue
    if ($config['env'] === 'local') {
        try {
            $config = $config_prod;
            $pdo = new PDO(
                "mysql:host={$config['host']};port={$config['port']};dbname={$config['db']};charset=utf8mb4",
                $config['user'],
                $config['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e2) {
            $pdo = null;
            error_log("PDO Connection Error (both local and prod failed): " . $e2->getMessage());
        }
    } else {
        $pdo = null;
        error_log("PDO Connection Error: " . $e->getMessage());
    }
}

/**
 * Détecte le chemin correct des photos selon l'environnement (local XAMPP vs Ionos)
 * @return string Chemin absolu vers le dossier Image_Profil
 */
function getPhotoBasePath() {
    static $photoPath = null;
    
    if ($photoPath !== null) {
        return $photoPath;
    }
    
    // Déterminer automatiquement le chemin
    $localPath = '/Image_Profil/';
    $ionosPath = '/DriveUs/DriveUs/Image_Profil/';
    
    // Vérifier l'environnement par le HOST
    $host = $_SERVER['HTTP_HOST'] ?? '';
    
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        // XAMPP local
        $photoPath = $localPath;
    } elseif (strpos($host, 'ionos') !== false || strpos($host, '.de') !== false) {
        // Ionos hosting
        $photoPath = $ionosPath;
    } else {
        // Autre serveur: tenter de déterminer en vérifiant la structure
        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if (strpos($documentRoot, '/DriveUs') !== false) {
            // Structure Ionos détectée
            $photoPath = $ionosPath;
        } else {
            // Par défaut, chemin local
            $photoPath = $localPath;
        }
    }
    
    return $photoPath;
}

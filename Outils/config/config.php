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

// Configuration - Essayer localement d'abord, puis Clever Cloud
$config_local = [
    'host' => $_ENV['DB_LOCAL_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_LOCAL_PORT'] ?? 3306,
    'db'   => $_ENV['DB_LOCAL_DB'] ?? 'driveus',
    'user' => $_ENV['DB_LOCAL_USER'] ?? 'root',
    'pass' => $_ENV['DB_LOCAL_PASS'] ?? '',
    'env' => 'local'
];

$config_prod = [
    'host' => $_ENV['DB_PROD_HOST'] ?? 'db5019347583.hosting-data.io',
    'port' => $_ENV['DB_PROD_PORT'] ?? 3306,
    'db'   => $_ENV['DB_PROD_DB'] ?? 'dbs15148242',
    'user' => $_ENV['DB_PROD_USER'] ?? 'dbu150815',
    'pass' => $_ENV['DB_PROD_PASS'] ?? '',
    'env' => 'prod'
];

// Utiliser la config locale en priorité
$config = $config_local;

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

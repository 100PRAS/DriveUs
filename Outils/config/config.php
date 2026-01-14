<?php


// Configuration - Essayer localement d'abord, puis Clever Cloud
$config_local = [
    'host' => 'localhost',
    'port' => 3306,
    'db'   => 'driveus',
    'user' => 'root',
    'pass' => '',
    'env' => 'local'
];

$config_prod = [
    'host' => "db5019347583.hosting-data.io",
    'port' => 3306,
    'db'   => "dbs15148242",
    'user' => "dbu150815",
    'pass' => "XPSjwJggX!aWCL3r",
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

<?php



// Configuration Clever Cloud uniquement
$host = "bdt14vr8flfkjapzigkf-mysql.services.clever-cloud.com";
$port = 3306;
$db   = "bdt14vr8flfkjapzigkf";
$user = "ui3ho6jb7fpuxbcb";
$pass = "IgPsBU73UiDTtiBz2RNH";
$env = 'prod';


try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    $pdo = null;
    // Ne pas afficher d'erreur HTML - laissez les handlers gérer les erreurs
    if (php_sapi_name() !== 'cli' && !headers_sent()) {
        // Si c'est une requête web et qu'aucun header n'a été envoyé, c'est une page classique
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/handlers/') === false && strpos($_SERVER['REQUEST_URI'] ?? '', '/Outils/') === false) {
            // Afficher l'erreur HTML seulement pour les pages classiques, pas les handlers
            echo '<h2 style="color:red">Erreur PDO : ' . htmlspecialchars($e->getMessage()) . '</h2>';
        }
    }
}

<?php
$host = "bdt14vr8flfkjapzigkf-mysql.services.clever-cloud.com";
$port = 3306;
$db   = "bdt14vr8flfkjapzigkf";
$user = "ui3ho6jb7fpuxbcb";
$pass = "IgPsBU73UiDTtiBz2RNH";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // echo "Connexion MySQL Clever Cloud OK";
} catch (PDOException $e) {
    die("Erreur MySQL (PDO) : " . $e->getMessage());
}

// Connexion MySQLi
$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Connexion MySQLi échouée: " . $conn->connect_error);
}

// echo "Connexion MySQLi OK";
?>

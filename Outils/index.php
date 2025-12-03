<?php
require_once __DIR__ . "/config.php";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h2>Connexion BDD OK ✅</h2>";

} catch (Exception $e) {
    echo "<h2>Erreur BDD ❌</h2>";
    echo $e->getMessage();
}
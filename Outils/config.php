<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Ne pas afficher en HTML
ini_set('log_errors', 1);

$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "bdd";

$conn = new mysqli($servername, $username, $password, $dbname);

// Ne pas utiliser die() - juste retourner $conn
// Le script appelant doit vérifier $conn
if ($conn->connect_error) {
    // Logger l'erreur mais ne pas bloquer
    error_log("DB Connection Error: " . $conn->connect_error);
}

// Définir charset UTF-8
if($conn && !$conn->connect_error) {
    $conn->set_charset("utf8mb4");
}
?>

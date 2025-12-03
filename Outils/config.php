<?php
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "bdd"; // nom de ta base réelle

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}
?>

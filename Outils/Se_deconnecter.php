<?php
session_start();

// Détruire toutes les variables de session
$_SESSION = [];

// Supprimer le cookie "remember me"
if (isset($_COOKIE['user_mail'])) {
    setcookie('user_mail', '', time() - 3600, "/");
}

// Détruire la session côté serveur
session_destroy();

// Rediriger vers la page de connexion
header("Location: Se_connecter.php");
exit;
?>

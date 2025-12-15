<?php
session_start();
session_unset();
session_destroy();
setcookie('user_id', '', time() - 3600, "/"); // Supprime le cookie
header("Location: Se_connecter.php");
exit;
?>

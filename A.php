<?php
require 'Outils/config/config.php';
if ($pdo) {
    echo "✅ Connexion BDD OK";
} else {
    echo "❌ Connexion BDD échouée";
}
?>
<?php

/**
 * Script de promotion en administrateur - À exécuter via CLI (command line)
 * Utilisation: php Outils/admin/promote_to_admin.php <email_utilisateur>
 */

// Sécurité: vérifier que c'est bien via CLI
if (php_sapi_name() !== 'cli') {
    die("❌ Ce script ne peut être exécuté que via la ligne de commande (CLI)\n");
}

require_once __DIR__ . '/../config/config.php';

if ($argc < 2) {
    echo "❌ Utilisation: php promote_to_admin.php <email_utilisateur>\n";
    echo "\nExemple: php promote_to_admin.php utilisateur@example.com\n";
    exit(1);
}

$email = trim($argv[1]);

if (empty($email)) {
    echo "❌ Email vide fourni.\n";
    exit(1);
}

try {
    // Vérifier si l'utilisateur existe
    $stmt = $pdo->prepare("SELECT UserID, Nom, Prénom, Mail, role FROM user WHERE Mail = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ Utilisateur non trouvé avec l'email: " . htmlspecialchars($email) . "\n";
        exit(1);
    }
    
    // Vérifier s'il est déjà admin
    if ($user['niveau'] === '1') {
        echo "⚠️  L'utilisateur " . htmlspecialchars($user['Prénom'] . ' ' . $user['Nom']) . " est déjà administrateur.\n";
        exit(0);
    }
    
    // Promouvoir en admin
    $stmt = $pdo->prepare("UPDATE user SET niveau = '1' WHERE UserID = ?");
    $stmt->execute([$user['UserID']]);
    
    // Enregistrer dans les logs (avec AdminID = 0 pour indiquer que c'est un action système)
    if ($pdo->exec("SELECT 1 FROM admin_logs LIMIT 1")) {
        $stmt = $pdo->prepare("INSERT INTO admin_logs (AdminID, Action, Description, TargetUserID) VALUES (?, ?, ?, ?)");
        $stmt->execute([0, 'CLI_PROMOTE_ADMIN', 'Promotion via CLI', $user['UserID']]);
    }
    
    echo "✅ Succès! L'utilisateur " . htmlspecialchars($user['Prénom'] . ' ' . $user['Nom']) . " (" . htmlspecialchars($email) . ") est maintenant administrateur.\n";
    exit(0);
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

<?php
session_start();
require_once 'Outils/config.php';

echo "<h2>Test du système de réinitialisation de mot de passe</h2>";

// Test 1: Vérifier la connexion à la base de données
echo "<h3>1. Test de connexion à la base de données</h3>";
if ($conn) {
    echo "✅ Connexion réussie<br>";
} else {
    echo "❌ Erreur de connexion: " . mysqli_connect_error() . "<br>";
    die();
}

// Test 2: Vérifier la structure de la table user
echo "<h3>2. Vérification de la table user</h3>";
$result = $conn->query("DESCRIBE user");
if ($result) {
    echo "✅ Table 'user' existe<br>";
    echo "<table border='1'><tr><th>Champ</th><th>Type</th><th>Null</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "❌ Erreur: " . $conn->error . "<br>";
}

// Test 3: Vérifier si les colonnes reset_token existent
echo "<h3>3. Vérification des colonnes reset_token</h3>";
$result = $conn->query("SHOW COLUMNS FROM user LIKE 'reset_token%'");
if ($result && $result->num_rows > 0) {
    echo "✅ Colonnes reset_token trouvées:<br>";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']} ({$row['Type']})<br>";
    }
} else {
    echo "❌ Les colonnes reset_token n'existent pas. Exécutez le script SQL:<br>";
    echo "<pre>ALTER TABLE user 
ADD reset_token VARCHAR(64) NULL,
ADD reset_token_expiry DATETIME NULL;</pre>";
}

// Test 4: Tester la fonction mail()
echo "<h3>4. Test de la fonction mail()</h3>";
if (function_exists('mail')) {
    echo "✅ Fonction mail() disponible<br>";
    echo "⚠️ Note: La fonction existe mais l'envoi peut échouer si SMTP n'est pas configuré<br>";
} else {
    echo "❌ Fonction mail() non disponible<br>";
}

// Test 5: Tester la génération de token
echo "<h3>5. Test de génération de token</h3>";
try {
    $token = bin2hex(random_bytes(32));
    echo "✅ Token généré: " . substr($token, 0, 20) . "...<br>";
    echo "Longueur: " . strlen($token) . " caractères<br>";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "<br>";
}

// Test 6: Vérifier un utilisateur test
echo "<h3>6. Test d'un utilisateur</h3>";
$result = $conn->query("SELECT Mail FROM user LIMIT 1");
if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "✅ Utilisateur trouvé: " . htmlspecialchars($user['Mail']) . "<br>";
} else {
    echo "⚠️ Aucun utilisateur dans la table<br>";
}

// Test 7: Formulaire de test
echo "<h3>7. Formulaire de test</h3>";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['test_email'])) {
    $email = $_POST['test_email'];
    echo "Email entré: " . htmlspecialchars($email) . "<br>";
    
    // Vérifier si l'email existe
    $stmt = $conn->prepare("SELECT Mail FROM user WHERE Mail = ?");
    if (!$stmt) {
        echo "❌ Erreur prepare: " . $conn->error . "<br>";
    } else {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "✅ Email trouvé dans la base<br>";
            
            // Générer et enregistrer le token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $updateStmt = $conn->prepare("UPDATE user SET reset_token = ?, reset_token_expiry = ? WHERE Mail = ?");
            if (!$updateStmt) {
                echo "❌ Erreur prepare UPDATE: " . $conn->error . "<br>";
            } else {
                $updateStmt->bind_param("sss", $token, $expiry, $email);
                if ($updateStmt->execute()) {
                    echo "✅ Token enregistré dans la base<br>";
                    echo "Token: " . substr($token, 0, 20) . "...<br>";
                    echo "Expiration: " . $expiry . "<br>";
                    echo "<br><a href='Reinitialiser_mot_de_passe.php?token=" . $token . "' target='_blank'>🔗 Tester le lien de réinitialisation</a><br>";
                } else {
                    echo "❌ Erreur UPDATE: " . $updateStmt->error . "<br>";
                }
            }
        } else {
            echo "❌ Email non trouvé dans la base<br>";
        }
    }
}
?>

<form method="POST">
    <label>Tester avec un email:</label><br>
    <input type="email" name="test_email" required>
    <button type="submit">Tester</button>
</form>

<hr>
<p><a href="Se_connecter.php">← Retour à la connexion</a></p>

<style>
    body { font-family: 'Poppins', Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
    h2 { color: #667eea; }
    h3 { color: #333; margin-top: 20px; }
    table { border-collapse: collapse; margin: 10px 0; }
    table td, table th { padding: 8px; text-align: left; }
    pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
    form { margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; }
    input[type="email"] { padding: 8px; width: 300px; border: 1px solid #ddd; border-radius: 4px; }
    button { padding: 8px 20px; background: #667eea; color: white; border: none; border-radius: 4px; cursor: pointer; }
    button:hover { background: #764ba2; }
</style>

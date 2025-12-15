<!DOCTYPE html>
<?php
session_start();

// Système de langue unifié
require_once 'Outils/config/langue.php';
require_once 'Outils/config/config.php';

$message = "";
$messageType = "";
$tokenValid = false;
$token = $_GET['token'] ?? '';

// Vérifier le token
if ($token) {
    $stmt = $conn->prepare("SELECT Mail, reset_token_expiry FROM user WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $expiry = strtotime($user['reset_token_expiry']);
        
        if ($expiry > time()) {
            $tokenValid = true;
            $userEmail = $user['Mail'];
        } else {
            $message = "Ce lien de réinitialisation a expiré.";
            $messageType = "error";
        }
    } else {
        $message = "Lien de réinitialisation invalide.";
        $messageType = "error";
    }
}

// Traiter la réinitialisation du mot de passe
if ($_SERVER["REQUEST_METHOD"] == "POST" && $tokenValid) {
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if ($newPassword === $confirmPassword) {
        if (strlen($newPassword) >= 8) {
            // Hasher le nouveau mot de passe
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Mettre à jour le mot de passe et supprimer le token
            $stmt = $conn->prepare("UPDATE user SET MotDePasseH = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?");
            $stmt->bind_param("ss", $hashedPassword, $token);
            
            if ($stmt->execute()) {
                $message = "Votre mot de passe a été réinitialisé avec succès !";
                $messageType = "success";
                $tokenValid = false;
                
                // Rediriger vers la page de connexion après 3 secondes
                header("refresh:3;url=/DriveUs/Se_connecter.php");
            } else {
                $message = "Erreur lors de la mise à jour du mot de passe.";
                $messageType = "error";
            }
        } else {
            $message = "Le mot de passe doit contenir au moins 8 caractères.";
            $messageType = "error";
        }
    } else {
        $message = "Les mots de passe ne correspondent pas.";
        $messageType = "error";
    }
}
?>

<html lang="<?= getLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Réinitialiser le mot de passe - DriveUs</title>
    <link rel="stylesheet" href="CSS/Outils/layout-global.css">
    <link rel="stylesheet" href="CSS/Reinitialiser.css">
    <link rel="stylesheet" href="CSS/Sombre/Sombre_Connexion1.css">
    <script src="/DriveUs/JS/Sombre.js"></script>
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #764ba2;
            --bg: white;
            --text: #333;
            --border: #e0e0e0;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        html.dark {
            --bg: #1a1a1a;
            --text: #e0e0e0;
            --border: #404040;
        }

        main {
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .reset-container {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 3rem;
            max-width: 450px;
            width: 100%;
            box-shadow: var(--shadow);
        }

        .reset-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .reset-header h1 {
            font-size: 1.8rem;
            color: var(--text);
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .reset-header p {
            color: #666;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            background: var(--bg);
            color: var(--text);
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input[type="password"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .password-requirements {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.5rem;
        }

        .btn-submit {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .reset-container {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'Outils/views/header.php'; ?>

    <main>
        <div class="reset-container">
            <div class="reset-header">
                <h1>🔐 Nouveau mot de passe</h1>
                <p>Choisissez un nouveau mot de passe sécurisé</p>
            </div>

            <?php if ($message): ?>
                <div class="message <?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($tokenValid): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="password">Nouveau mot de passe</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="••••••••" 
                            required
                            minlength="8"
                        >
                        <div class="password-requirements">
                            Minimum 8 caractères
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirmer le mot de passe</label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            placeholder="••••••••" 
                            required
                            minlength="8"
                        >
                    </div>

                    <button type="submit" class="btn-submit">
                        ✓ Réinitialiser le mot de passe
                    </button>
                </form>
            <?php elseif (!$message): ?>
                <div class="message error">
                    Lien de réinitialisation invalide ou expiré.
                </div>
            <?php endif; ?>

            <div class="back-link">
                <a href="/DriveUs/Se_connecter.php">← Retour à la connexion</a>
            </div>
        </div>
    </main>

    <?php include 'Outils/views/footer.php'; ?>

    <script>
        // Vérifier que les mots de passe correspondent
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');

        confirmPassword?.addEventListener('input', function() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Les mots de passe ne correspondent pas');
            } else {
                confirmPassword.setCustomValidity('');
            }
        });
    </script>
</body>
</html>

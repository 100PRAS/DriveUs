<!DOCTYPE html>
<?php

// Système de langue unifié
require_once 'Outils/config/langue.php';
require_once 'Outils/config/config.php';
require_once 'Outils/mail/GmailSender.php';

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    
    // Vérifier si l'email existe
    $stmt = $conn->prepare("SELECT Mail, Prenom FROM user WHERE Mail = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Générer un token unique
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Stocker le token dans la base de données
        $stmt = $conn->prepare("UPDATE user SET reset_token = ?, reset_token_expiry = ? WHERE Mail = ?");
        $stmt->bind_param("sss", $token, $expiry, $email);
        $stmt->execute();
        
        // Créer le lien de réinitialisation
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/DriveUs/Reinitialiser_mot_de_passe.php?token=" . $token;
        
        // Préparer l'email HTML
        $subject = "Réinitialisation de votre mot de passe - DriveUs";
        $htmlMessage = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Poppins', Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; color: #333; }
                .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 20px 0; }
                .footer { background: #f8f8f8; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 Réinitialisation de mot de passe</h1>
                </div>
                <div class='content'>
                    <p>Bonjour " . htmlspecialchars($user['Prenom']) . ",</p>
                    <p>Vous avez demandé à réinitialiser votre mot de passe sur <strong>DriveUs</strong>.</p>
                    <p>Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe :</p>
                    <p style='text-align: center;'>
                        <a href='{$resetLink}' class='button'>Réinitialiser mon mot de passe</a>
                    </p>
                    <p style='font-size: 14px; color: #666;'>Ce lien est valide pendant 1 heure.</p>
                    <p style='font-size: 14px; color: #666;'>Si vous n'avez pas demandé cette réinitialisation, ignorez simplement cet email.</p>
                </div>
                <div class='footer'>
                    <p>© 2026 DriveUs - Covoiturage intelligent</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Envoyer l'email via GmailSender (Ionos SMTP)
        $gmail = new GmailSender();
        $result = $gmail->send($email, $subject, $htmlMessage);
        
        if ($result['success']) {
            $message = "✅ Un email de réinitialisation a été envoyé à votre adresse.";
            $messageType = "success";
        } else if (isset($result['direct_link']) && $result['direct_link']) {
            // Mode dev : afficher le lien direct si pas de mot de passe configuré
            $message = "Lien de réinitialisation généré !<br><br>
                        <a href='{$resetLink}' style='color: #667eea; font-weight: 600;'>
                            Cliquez ici pour réinitialiser votre mot de passe
                        </a><br><br>
                        <small style='color: #666;'>Ce lien est valide pendant 1 heure.</small><br><br>
                        <small style='color: #999;'>💡 Pour envoyer un vrai email, configurez le mot de passe SMTP dans GmailSender.php</small>";
            $messageType = "success";
        } else {
            $message = "Erreur d'envoi: " . ($result['error'] ?? 'Échec SMTP') . "<br><br>
                        Lien direct: <a href='{$resetLink}' style='color: #667eea;'>Cliquez ici</a>";
            $messageType = "error";
        }
    } else {
        // Pour des raisons de sécurité, on affiche le même message même si l'email n'existe pas
        $message = "Si cet email existe, un lien de réinitialisation a été envoyé.";
        $messageType = "success";
    }
}
?>

<html lang="<?= getLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mot de passe oublié - DriveUs</title>
    <link rel="stylesheet" href="CSS/layout-global.css">
    <link rel="stylesheet" href="CSS/Outils/Header.css">
    <link rel="stylesheet" href="CSS/Outils/responsive.css">
    <script src="JS/Sombre.js"></script>
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

        input[type="email"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            background: var(--bg);
            color: var(--text);
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input[type="email"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
                <h1>🔑 Mot de passe oublié</h1>
                <p>Entrez votre adresse email pour recevoir un lien de réinitialisation</p>
            </div>

            <?php if ($message): ?>
                <div class="message <?= $messageType ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Adresse email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="votre@email.com" 
                        required
                        autocomplete="email"
                    >
                </div>

                <button type="submit" class="btn-submit">
                    📧 Envoyer le lien de réinitialisation
                </button>
            </form>

            <div class="back-link">
                <a href="Se_connecter.php">← Retour à la connexion</a>
            </div>
        </div>
    </main>

    <?php include 'Outils/views/footer.php'; ?>
</body>
</html>

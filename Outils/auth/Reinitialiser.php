<!DOCTYPE html>
<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../mail/GmailSender.php';

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    
    // Générer un token de réinitialisation
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Vérifier si l'email existe
    $stmt = $conn->prepare("SELECT Mail FROM user WHERE Mail = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Mettre à jour le token dans la base de données
        $updateStmt = $conn->prepare("UPDATE user SET reset_token = ?, reset_token_expiry = ? WHERE Mail = ?");
        $updateStmt->bind_param("sss", $token, $expiry, $email);
        $updateStmt->execute();
        
        // Envoyer l'email
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/DriveUs/Reinitialiser_mot_de_passe.php?token=" . $token;
        
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
                    <p>Bonjour,</p>
                    <p>Vous avez demandé à réinitialiser votre mot de passe sur <strong>DriveUs</strong>.</p>
                    <p>Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe :</p>
                    <p style='text-align: center;'>
                        <a href='{$resetLink}' class='button'>Réinitialiser mon mot de passe</a>
                    </p>
                    <p style='font-size: 14px; color: #666;'>Ce lien est valide pendant 1 heure.</p>
                    <p style='font-size: 14px; color: #666;'>Si vous n'avez pas demandé cette réinitialisation, ignorez simplement cet email.</p>
                </div>
                <div class='footer'>
                    <p>© 2025 DriveUs - Covoiturage intelligent</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
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
                    <p>Bonjour,</p>
                    <p>Vous avez demandé à réinitialiser votre mot de passe sur <strong>DriveUs</strong>.</p>
                    <p>Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe :</p>
                    <p style='text-align: center;'>
                        <a href='{$resetLink}' class='button'>Réinitialiser mon mot de passe</a>
                    </p>
                    <p style='font-size: 14px; color: #666;'>Ce lien est valide pendant 1 heure.</p>
                    <p style='font-size: 14px; color: #666;'>Si vous n'avez pas demandé cette réinitialisation, ignorez simplement cet email.</p>
                </div>
                <div class='footer'>
                    <p>© 2025 DriveUs - Covoiturage intelligent</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Tenter d'envoyer via Gmail
        $gmail = new GmailSender();
        // Le mot de passe doit être configuré dans GmailSender.php
        $result = $gmail->send($email, $subject, $htmlMessage);
        
        if ($result['success']) {
            $message = "✅ Un email de réinitialisation a été envoyé à votre adresse.";
            $messageType = "success";
        } else if (isset($result['direct_link']) && $result['direct_link']) {
            // Si pas de mot de passe Gmail configuré, afficher le lien
            $message = "Lien de réinitialisation généré ! <br><br>
                        <a href='{$resetLink}' target='_parent' style='color: #667eea; font-weight: 600;'>
                            Cliquez ici pour réinitialiser votre mot de passe
                        </a><br><br>
                        <small style='color: #666;'>Ce lien est valide pendant 1 heure.</small><br><br>
                        <small style='color: #999;'>💡 Pour envoyer un vrai email, configurez le mot de passe Gmail dans GmailSender.php</small>";
            $messageType = "success";
        } else {
            $message = "Erreur: " . ($result['error'] ?? 'Échec de l\'envoi') . "<br><br>
                        Lien direct: <a href='{$resetLink}' target='_parent' style='color: #667eea;'>Cliquez ici</a>";
            $messageType = "error";
        }
    } else {
        // Message identique pour ne pas révéler si l'email existe
        $message = "Un email de réinitialisation a été envoyé à votre adresse.";
        $messageType = "success";
    }
}
?>

<html lang="fr">
<head>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <title>Réinitialiser le mot de passe - DriveUs</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f8f9fa;
            padding: 2rem 1rem;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        h1 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 0.5rem;
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        p {
            text-align: center;
            color: #666;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        input[type="email"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        button {
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

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
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
    </style>
</head>

<body>
    <div class="container">
        <h1>🔐 Mot de passe oublié ?</h1>
        <p>Entrez votre adresse email pour recevoir un lien de réinitialisation</p>

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
                    placeholder="votre.email@exemple.com" 
                    required
                />
            </div>
            <button type="submit">📧 Envoyer le lien de réinitialisation</button>
        </form>
    </div>
</body>
</html>
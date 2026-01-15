<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/GmailSender.php';

try {
    // Récupérer les données POST
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = isset($data['name']) ? trim($data['name']) : '';
    $email = isset($data['email']) ? trim($data['email']) : '';
    $message = isset($data['message']) ? trim($data['message']) : '';
    
    // Validation
    if (empty($name) || empty($message)) {
        echo json_encode([
            'success' => false,
            'message' => 'Le nom et le message sont obligatoires.'
        ]);
        exit;
    }
    
    // Préparer le contenu de l'email
    $subject = "DriveUs - Nouveau message de contact";
    
    $htmlBody = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #1e73d9 0%, #0d47a1 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
            .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
            .info-row { margin: 10px 0; padding: 10px; background: white; border-left: 4px solid #1e73d9; }
            .label { font-weight: bold; color: #1e73d9; }
            .message-box { background: white; padding: 15px; margin-top: 15px; border-radius: 5px; border: 1px solid #ddd; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2 style='margin: 0;'>📧 Nouveau message de contact</h2>
            </div>
            <div class='content'>
                <div class='info-row'>
                    <span class='label'>Nom :</span> " . htmlspecialchars($name) . "
                </div>
                <div class='info-row'>
                    <span class='label'>Email :</span> " . htmlspecialchars($email ?: 'Non renseigné') . "
                </div>
                <div class='message-box'>
                    <p class='label'>Message :</p>
                    <p>" . nl2br(htmlspecialchars($message)) . "</p>
                </div>
                <p style='margin-top: 20px; color: #666; font-size: 0.9em;'>
                    Ce message a été envoyé depuis le formulaire de contact du site DriveUs.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Envoyer l'email
    $mailer = new GmailSender();
    $sent = $mailer->sendEmail(
        'contact@driveus.eu',
        $subject,
        $htmlBody,
        $email ?: 'noreply@driveus.eu', // Reply-to
        $name
    );
    
    if ($sent) {
        echo json_encode([
            'success' => true,
            'message' => 'Votre message a été envoyé avec succès.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de l\'envoi de l\'email.'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur : ' . $e->getMessage()
    ]);
}

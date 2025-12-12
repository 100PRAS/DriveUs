<?php
/**
 * Configuration Gmail pour l'envoi d'emails
 * Utilisez un mot de passe d'application Gmail (pas votre mot de passe normal)
 * https://myaccount.google.com/apppasswords
 */

define('GMAIL_USER', 'driveus.team@gmail.com');
define('GMAIL_PASSWORD', 'VOTRE_MOT_DE_PASSE_APPLICATION'); // À remplacer

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');

/**
 * Envoie un email via Gmail SMTP
 */
function sendGmailEmail($to, $toName, $subject, $htmlBody) {
    // Configuration de l'email
    $from = GMAIL_USER;
    $fromName = "DriveUs";
    
    // Headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Configuration SMTP via ini_set (méthode simple)
    ini_set('SMTP', SMTP_HOST);
    ini_set('smtp_port', SMTP_PORT);
    ini_set('sendmail_from', $from);
    
    // Tentative d'envoi
    $result = mail($to, $subject, $htmlBody, $headers);
    
    return $result;
}

/**
 * Envoie un email via Gmail avec authentification (méthode avancée)
 */
function sendGmailWithAuth($to, $toName, $subject, $htmlBody) {
    $from = GMAIL_USER;
    $password = GMAIL_PASSWORD;
    
    // Créer le message
    $boundary = uniqid('np');
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: DriveUs <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    
    // Utiliser fsockopen pour se connecter à Gmail SMTP
    $smtp = fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 30);
    if (!$smtp) {
        return false;
    }
    
    // Lire la réponse du serveur
    $response = fgets($smtp, 515);
    
    // Commencer la conversation SMTP
    fputs($smtp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
    $response = fgets($smtp, 515);
    
    // STARTTLS
    fputs($smtp, "STARTTLS\r\n");
    $response = fgets($smtp, 515);
    
    // Activer le cryptage
    stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    
    // EHLO à nouveau après TLS
    fputs($smtp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
    $response = fgets($smtp, 515);
    
    // Authentification
    fputs($smtp, "AUTH LOGIN\r\n");
    $response = fgets($smtp, 515);
    
    fputs($smtp, base64_encode($from) . "\r\n");
    $response = fgets($smtp, 515);
    
    fputs($smtp, base64_encode($password) . "\r\n");
    $response = fgets($smtp, 515);
    
    if (strpos($response, '235') === false) {
        fclose($smtp);
        return false;
    }
    
    // Envoyer l'email
    fputs($smtp, "MAIL FROM: <{$from}>\r\n");
    $response = fgets($smtp, 515);
    
    fputs($smtp, "RCPT TO: <{$to}>\r\n");
    $response = fgets($smtp, 515);
    
    fputs($smtp, "DATA\r\n");
    $response = fgets($smtp, 515);
    
    $message = "Subject: {$subject}\r\n";
    $message .= $headers . "\r\n";
    $message .= $htmlBody . "\r\n.\r\n";
    
    fputs($smtp, $message);
    $response = fgets($smtp, 515);
    
    // Fermer la connexion
    fputs($smtp, "QUIT\r\n");
    fclose($smtp);
    
    return true;
}
?>

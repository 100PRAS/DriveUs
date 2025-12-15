<?php
/**
 * Envoi d'email via Gmail SMTP avec socket
 * Configuration simplifiée pour DriveUs
 * 
 * IMPORTANT: Activez l'accès aux applications moins sécurisées
 * OU utilisez un service SMTP externe gratuit comme:
 * - Mailtrap (pour tests)
 * - SendGrid (100 emails/jour gratuits)
 * - Brevo/Sendinblue (300 emails/jour gratuits)
 */

class GmailSender {
    private $username = 'driveus.team@gmail.com';
    private $password = ''; // Laissez vide pour le mode développement (affichage du lien)
    private $host = 'smtp.gmail.com';
    private $port = 587;
    
    public function setPassword($password) {
        $this->password = $password;
    }
    
    public function send($to, $subject, $htmlBody) {
        // Si pas de mot de passe configuré, retourner le lien directement
        if (empty($this->password)) {
            return ['success' => false, 'direct_link' => true];
        }
        
        try {
            // Connexion au serveur SMTP
            $socket = fsockopen($this->host, $this->port, $errno, $errstr, 30);
            if (!$socket) {
                return ['success' => false, 'error' => $errstr];
            }
            
            $this->getResponse($socket);
            
            // EHLO
            fputs($socket, "EHLO localhost\r\n");
            $this->getResponse($socket);
            
            // STARTTLS
            fputs($socket, "STARTTLS\r\n");
            $this->getResponse($socket);
            
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            // EHLO à nouveau
            fputs($socket, "EHLO localhost\r\n");
            $this->getResponse($socket);
            
            // AUTH LOGIN
            fputs($socket, "AUTH LOGIN\r\n");
            $this->getResponse($socket);
            
            fputs($socket, base64_encode($this->username) . "\r\n");
            $this->getResponse($socket);
            
            fputs($socket, base64_encode($this->password) . "\r\n");
            $response = $this->getResponse($socket);
            
            if (strpos($response, '235') === false) {
                fclose($socket);
                return ['success' => false, 'error' => 'Authentification échouée'];
            }
            
            // MAIL FROM
            fputs($socket, "MAIL FROM: <{$this->username}>\r\n");
            $this->getResponse($socket);
            
            // RCPT TO
            fputs($socket, "RCPT TO: <{$to}>\r\n");
            $this->getResponse($socket);
            
            // DATA
            fputs($socket, "DATA\r\n");
            $this->getResponse($socket);
            
            // Message
            $message = "From: DriveUs <{$this->username}>\r\n";
            $message .= "To: <{$to}>\r\n";
            $message .= "Subject: {$subject}\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "\r\n";
            $message .= $htmlBody;
            $message .= "\r\n.\r\n";
            
            fputs($socket, $message);
            $this->getResponse($socket);
            
            // QUIT
            fputs($socket, "QUIT\r\n");
            fclose($socket);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function getResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
        return $response;
    }
}
?>

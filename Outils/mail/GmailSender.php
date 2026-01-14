<?php
/**
 * Envoi SMTP (Ionos/Autre) via socket TLS
 * Par défaut configuré pour Ionos, surcharge possible via variables d'environnement:
 * MAIL_HOST, MAIL_PORT, MAIL_USER, MAIL_PASS, MAIL_FROM_NAME
 */

class GmailSender {
    private $username;
    private $password;
    private $host;
    private $port;
    private $fromName;
    private $debug;

    public function __construct() {
        // Valeurs par défaut adaptées à Ionos
        $this->username = getenv('MAIL_USER') ?: 'noreply@driveus.eu';
        // Ne jamais utiliser de mot de passe par défaut en dur
        $this->password = getenv('MAIL_PASS') ?: '';
        $this->host = getenv('MAIL_HOST') ?: 'smtp.ionos.fr';
        $this->port = (int)(getenv('MAIL_PORT') ?: 587); // STARTTLS
        $this->fromName = getenv('MAIL_FROM_NAME') ?: 'DriveUs';
        $this->debug = (bool)(getenv('MAIL_DEBUG') ?: true); // activer le debug par défaut en test

        // Fallback: charger Outils/config/mail.ini si les env ne sont pas présents
        if (empty(getenv('MAIL_PASS'))) {
            $iniPath = __DIR__ . '/../config/mail.ini';
            if (file_exists($iniPath)) {
                // Compatibilité PHP: éviter INI_SCANNER_TYPED si non disponible
                $cfg = @parse_ini_file($iniPath, false);
                if (is_array($cfg)) {
                    $this->username = isset($cfg['MAIL_USER']) ? $cfg['MAIL_USER'] : $this->username;
                    $this->password = isset($cfg['MAIL_PASS']) ? $cfg['MAIL_PASS'] : $this->password;
                    $this->host     = isset($cfg['MAIL_HOST']) ? $cfg['MAIL_HOST'] : $this->host;
                    $this->port     = isset($cfg['MAIL_PORT']) ? (int)$cfg['MAIL_PORT'] : $this->port;
                    $this->fromName = isset($cfg['MAIL_FROM_NAME']) ? $cfg['MAIL_FROM_NAME'] : $this->fromName;
                    $this->debug    = isset($cfg['MAIL_DEBUG']) ? (bool)$cfg['MAIL_DEBUG'] : $this->debug;
                } else {
                    $this->log('Impossible de lire mail.ini');
                }
            }
        }
    }

    public function setPassword($password) {
        $this->password = $password;
    }
    
    public function send($to, $subject, $htmlBody) {
        $this->log("=== Nouvelle tentative d'envoi ===");
        $this->log("Destinataire: $to");
        $this->log("Sujet: $subject");
        
        // Si pas de mot de passe configuré, retourner le lien directement
        if (empty($this->password)) {
            $this->log("ERREUR: Mot de passe SMTP vide");
            return ['success' => false, 'direct_link' => true];
        }
        
        // CRITIQUE: Vérifier que FROM = USER authentifié (requis par Ionos)
        if ($this->username !== $to && strpos($to, '@') !== false) {
            $toDomain = substr(strrchr($to, "@"), 1);
            $userDomain = substr(strrchr($this->username, "@"), 1);
            if ($toDomain === $userDomain) {
                $this->log("ATTENTION: Envoi vers le même domaine que l'expéditeur");
            }
        }
        
        try {
            // Connexion au serveur SMTP (SSL implicite si port 465)
            $connectHost = ($this->port === 465) ? ('ssl://' . $this->host) : $this->host;
            $socket = fsockopen($connectHost, $this->port, $errno, $errstr, 30);
            if (!$socket) {
                return ['success' => false, 'error' => $errstr];
            }
            
            $this->log('Connected');
            $this->getResponse($socket);
            
            // EHLO
            $ehloHost = $this->getEhloHost();
            $this->sendCommand($socket, "EHLO {$ehloHost}\r\n", ['250']);
            
            // STARTTLS si port 587
            if ($this->port === 587) {
                $this->sendCommand($socket, "STARTTLS\r\n", ['220']);
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                // EHLO à nouveau après STARTTLS
                $this->sendCommand($socket, "EHLO {$ehloHost}\r\n", ['250']);
            }
            
            // AUTH LOGIN
            $this->sendCommand($socket, "AUTH LOGIN\r\n", ['334']);
            $this->sendCommand($socket, base64_encode($this->username) . "\r\n", ['334']);
            $authResp = $this->sendCommand($socket, base64_encode($this->password) . "\r\n", ['235']);
            
            // MAIL FROM
            $this->sendCommand($socket, "MAIL FROM: <{$this->username}>\r\n", ['250']);
            
            // RCPT TO
            $this->sendCommand($socket, "RCPT TO: <{$to}>\r\n", ['250']);
            
            // DATA
            $this->sendCommand($socket, "DATA\r\n", ['354']);
            
            // Message
            $message = "From: {$this->fromName} <{$this->username}>\r\n";
            $message .= "To: <{$to}>\r\n";
            $message .= "Subject: {$subject}\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 8bit\r\n";
            $message .= "Reply-To: {$this->username}\r\n";
            $message .= "Return-Path: {$this->username}\r\n";
            $message .= "X-Mailer: DriveUs SMTP Socket\r\n";
            $message .= "X-Priority: 3\r\n";
            $message .= "Precedence: bulk\r\n"; // Aide SPF/DKIM
            $message .= "Date: " . date('D, d M Y H:i:s O') . "\r\n";
            $message .= "Message-ID: <" . uniqid('', true) . '@' . $this->getEhloHost() . ">\r\n";
            $message .= "In-Reply-To: <no-reply@" . $this->getEhloHost() . ">\r\n";
            $message .= "\r\n";
            $message .= $htmlBody;
            $message .= "\r\n.\r\n";
            
            fputs($socket, $message);
            $final = $this->getResponse($socket);
            $this->log('Final response: ' . trim($final));
            if (strpos($final, '250') === false) {
                $this->sendCommand($socket, "QUIT\r\n", ['221']);
                fclose($socket);
                return ['success' => false, 'error' => 'Le serveur SMTP a refusé le message'];
            }
            
            // QUIT
            fputs($socket, "QUIT\r\n");
            fclose($socket);
            
            $this->log("=== Email envoyé avec succès ===");
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
        $this->log('SMTP: ' . trim($response));
        return $response;
    }

    private function sendCommand($socket, $command, array $expectCodes) {
        fputs($socket, $command);
        $resp = $this->getResponse($socket);
        foreach ($expectCodes as $code) {
            if (strpos($resp, $code) !== false) {
                return $resp;
            }
        }
        throw new Exception('Commande SMTP échouée: ' . trim($resp));
    }

    private function getEhloHost() {
        // Utilise le domaine de l'adresse email si possible
        if (strpos($this->username, '@') !== false) {
            return explode('@', $this->username)[1];
        }
        return 'localhost';
    }

    private function log($msg) {
        if ($this->debug) {
            $file = __DIR__ . '/smtp_debug.log';
            $entry = '[' . date('Y-m-d H:i:s') . "] " . $msg . "\n";
            file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
        }
    }
}
?>

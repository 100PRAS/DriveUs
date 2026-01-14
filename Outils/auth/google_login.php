<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/google_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['credential'])) {
    try {
        $jwt = trim($_POST['credential']);

        // Vérification du JWT via Google
        $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($jwt);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => GOOGLE_API_TIMEOUT
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new Exception("Erreur de communication avec Google. Veuillez réessayer.");
        }
        
        $data = json_decode($response, true);
        if (!isset($data['email'])) {
            throw new Exception("Token invalide ou expiré. Veuillez vous reconnecter.");
        }

        // Validation du Client ID pour plus de sécurité
        if (!isset($data['aud']) || $data['aud'] !== GOOGLE_OAUTH_CLIENT_ID) {
            throw new Exception("Client ID invalide");
        }

        $mail = $data['email'];
        $nom = $data['family_name'] ?? 'Utilisateur';
        $prenom = $data['given_name'] ?? 'Google';
        $photo = $data['picture'] ?? null;

        // Vérifier si l'utilisateur existe déjà
        $stmt = $pdo->prepare("SELECT UserID, role FROM user WHERE Mail = ?");
        $stmt->execute([$mail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $userId = null;
        
        if (!$user) {
            // Ajouter nouvel utilisateur
            $stmt2 = $pdo->prepare("INSERT INTO user (Nom, Prenom, Mail, PhotoProfil, role) VALUES (?, ?, ?, ?, ?)");
            $stmt2->execute([$nom, $prenom, $mail, $photo, GOOGLE_DEFAULT_ROLE]);
            $userId = $pdo->lastInsertId();
        } else {
            $userId = $user['UserID'];
        }

        if (!$userId) {
            throw new Exception("Impossible de récupérer l'ID utilisateur");
        }

        // Connexion réussie
        $_SESSION['UserID'] = $userId;
        $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
        
        http_response_code(200);
        echo json_encode([
            'success' => true, 
            'message' => 'Connexion réussie',
            'userId' => $userId,
            'redirect' => GOOGLE_LOGIN_REDIRECT
        ]);
        
    } catch (Exception $e) {
        error_log("Google Login Error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
    exit;
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Requête invalide']);
    exit;
}
?>

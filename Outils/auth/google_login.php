<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['credential'])) {
    try {
        $jwt = trim($_POST['credential']);

        // Vérification du JWT via Google
        $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($jwt);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 5
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new Exception("Erreur de communication avec Google");
        }
        
        $data = json_decode($response, true);
        if (!isset($data['email'])) {
            throw new Exception("Token invalide ou expiré");
        }

        $mail = $data['email'];
        $nom = $data['family_name'] ?? 'Utilisateur';
        $prenom = $data['given_name'] ?? 'Google';
        $photo = $data['picture'] ?? null;

        // Vérifier si l'utilisateur existe déjà
        $stmt = $conn->prepare("SELECT UserID FROM user WHERE Mail=?");
        if (!$stmt) {
            throw new Exception("Erreur DB: " . $conn->error);
        }
        $stmt->bind_param("s", $mail);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        $userId = null;
        
        if (!$user) {
            // Ajouter nouvel utilisateur
            $stmt2 = $conn->prepare("INSERT INTO user (Nom, Prenom, Mail, PhotoProfil) VALUES (?, ?, ?, ?)");
            if (!$stmt2) {
                throw new Exception("Erreur insertion: " . $conn->error);
            }
            $stmt2->bind_param("ssss", $nom, $prenom, $mail, $photo);
            if (!$stmt2->execute()) {
                throw new Exception("Erreur exécution: " . $stmt2->error);
            }
            $userId = $conn->insert_id;
            $stmt2->close();
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
        echo json_encode(['success' => true, 'message' => 'Connexion réussie']);
        
    } catch (Exception $e) {
        error_log("Google Login Error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Requête invalide']);
}
?>

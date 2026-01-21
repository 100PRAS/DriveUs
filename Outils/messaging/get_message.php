<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['UserID'])) {
        echo json_encode([]);
        exit;
    }

    require_once __DIR__ . '/../config/config.php';
    if (!$pdo) {
        http_response_code(500);
        echo json_encode(['error' => 'no_pdo_connection']);
        exit;
    }

    $contact = trim($_GET['contact'] ?? '');
    if ($contact === '') {
        echo json_encode([]);
        exit;
    }

    // Email utilisateur connecté
    $stmt = $pdo->prepare('SELECT Mail FROM user WHERE UserID = ?');
    $stmt->execute([$_SESSION['UserID']]);
    $user = $stmt->fetchColumn();

    if (!$user) {
        echo json_encode([]);
        exit;
    }

    // SÉCURITÉ: Vérifier que le contact existe bien dans la base
    $stmt = $pdo->prepare('SELECT Mail FROM user WHERE Mail = ?');
    $stmt->execute([$contact]);
    if (!$stmt->fetchColumn()) {
        echo json_encode(['error' => 'Contact invalide']);
        exit;
    }

    // SÉCURITÉ: Empêcher de voir les conversations d'autres utilisateurs
    // On ne retourne QUE les messages où l'utilisateur connecté est sender OU receiver
    // Messages bilatéraux avec infos profil
    $sql = 'SELECT m.id, m.sender, m.receiver, m.message, m.created_at,
                   u.Prenom, u.PhotoProfil
            FROM messages m
            LEFT JOIN user u ON u.Mail = m.sender
            WHERE (m.sender = :me AND m.receiver = :contact) OR (m.sender = :contact AND m.receiver = :me)
            ORDER BY m.created_at ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['me' => $user, 'contact' => $contact]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Résoudre les chemins photo
    $photoBasePath = getPhotoBasePath();
    foreach ($messages as &$msg) {
        $photoPath = $photoBasePath . 'default.png';
        if (!empty($msg['PhotoProfil'])) {
            $photoFile = $msg['PhotoProfil'];
            if (preg_match('~^https?://~i', $photoFile)) {
                $photoPath = $photoFile;
            } else {
                $candidates = [
                    $photoBasePath . ltrim($photoFile, '/') // UNIFIÉ: chemin unique
                ];
                foreach ($candidates as $candidate) {
                    $absolute = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $candidate;
                    if (file_exists($absolute)) {
                        $photoPath = $candidate;
                        break;
                    }
                }
            }
        }
        $msg['PhotoProfil'] = $photoPath;
    }

    echo json_encode($messages);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'exception',
        'message' => $e->getMessage(),
        'contact' => $_GET['contact'] ?? null
    ]);
}

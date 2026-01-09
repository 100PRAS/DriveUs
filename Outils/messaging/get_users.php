<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['UserID'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

require __DIR__ . '/../config/config.php';
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'no_pdo_connection']);
    exit;
}

// Email utilisateur courant
$stmt = $pdo->prepare('SELECT Mail FROM user WHERE UserID = ?');
$stmt->execute([$_SESSION['UserID']]);
$currentUser = $stmt->fetchColumn();

$search = trim($_GET['search'] ?? '');

// Base query
$sql = 'SELECT Mail, Prenom, Nom, PhotoProfil FROM user WHERE Mail != :current';
$params = ['current' => $currentUser];

if ($search !== '') {
    $sql .= ' AND (Prenom LIKE :term OR Nom LIKE :term OR Mail LIKE :term)';
    $params['term'] = "%{$search}%";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$users = [];
foreach ($rows as $row) {
    $photoPath = '/DriveUs/Image_Profil/default.png';
    if (!empty($row['PhotoProfil'])) {
        $photoFile = $row['PhotoProfil'];
        $candidates = [];

        if (preg_match('~^https?://~i', $photoFile)) {
            $candidates[] = $photoFile;
        } else {
            $relative = '/' . ltrim($photoFile, '/');
            $candidates[] = $relative;
            $candidates[] = '/DriveUs/Image_Profil/' . ltrim($photoFile, '/');
            $candidates[] = '/DriveUs/Outils/handlers/Image_Profil/' . ltrim($photoFile, '/');
        }

        foreach ($candidates as $candidate) {
            $absolute = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $candidate;
            if (file_exists($absolute)) {
                $photoPath = $candidate;
                break;
            }
        }
    }

    $users[] = [
        'email' => $row['Mail'],
        'prenom' => $row['Prenom'] ?? '',
        'nom' => $row['Nom'] ?? '',
        'displayName' => trim(($row['Prenom'] ?? '') . ' ' . ($row['Nom'] ?? '')) ?: $row['Mail'],
        'photo' => $photoPath
    ];
}

echo json_encode($users);

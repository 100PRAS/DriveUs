<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

// Vérifier la présence de la colonne last_activity (pour statut en ligne)
$hasLastActivity = false;
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM user LIKE 'last_activity'");
    $hasLastActivity = $colCheck && $colCheck->rowCount() > 0;
} catch (Throwable $e) {
    $hasLastActivity = false;
}

// Base query
$selectCols = $hasLastActivity
    ? 'Mail, Prenom, Nom, PhotoProfil, last_activity'
    : 'Mail, Prenom, Nom, PhotoProfil';
$sql = "SELECT $selectCols FROM user WHERE Mail != :current";
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
    // Calculer le statut en ligne si possible (last_activity < 120s)
    $lastActivity = $hasLastActivity ? ($row['last_activity'] ?? null) : null;
    $online = false;
    if ($lastActivity) {
        $lastTs = strtotime($lastActivity);
        $online = ($lastTs !== false) && (time() - $lastTs < 120);
    }

    $photoPath = '/Image_Profil/default.png';
    if (!empty($row['PhotoProfil'])) {
        $photoFile = $row['PhotoProfil'];
        $candidates = [];

        if (preg_match('~^https?://~i', $photoFile)) {
            $candidates[] = $photoFile;
        } else {
            $relative = '/' . ltrim($photoFile, '/');
            $candidates[] = $relative;
            $candidates[] = '/Image_Profil/' . ltrim($photoFile, '/');
            $candidates[] = '/Image_Profil/' . ltrim($photoFile, '/');
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
        'photo' => $photoPath,
        'last_activity' => $lastActivity,
        'online' => $online
    ];
}

echo json_encode($users);

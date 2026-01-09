<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION['UserID'])) {
    echo json_encode([]);
    exit;
}


require __DIR__ . '/../config/config.php';

$userId = $_SESSION['UserID'];

// Récupérer l'email de l'utilisateur
$stmt = $pdo->prepare("SELECT Mail FROM user WHERE UserID = ?");
$stmt->execute([$userId]);
$row = $stmt->fetch();
$currentEmail = $row['Mail'] ?? '';

if (!$currentEmail) {
    echo json_encode([]);
    exit;
}

// Vérifier si la colonne last_activity existe (PDO only)
$hasLastActivity = false;
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM user LIKE 'last_activity'");
    $hasLastActivity = $colCheck && $colCheck->rowCount() > 0;
} catch (Throwable $e) {
    $hasLastActivity = false;
}

// Récupérer les contacts distincts (sender/receiver) pour l'utilisateur courant
$sql = "
    SELECT DISTINCT contact_email FROM (
        SELECT receiver AS contact_email FROM messages WHERE sender = :mail
        UNION
        SELECT sender AS contact_email FROM messages WHERE receiver = :mail
    ) t
    WHERE contact_email IS NOT NULL AND contact_email <> ''
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['mail' => $currentEmail]);
$contactRows = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

// Fallback: si aucune conversation, proposer quelques utilisateurs (hors soi)
if (empty($contactRows)) {
    $selectCols = $hasLastActivity
        ? 'Mail, Prenom, PhotoProfil, last_activity'
        : 'Mail, Prenom, PhotoProfil';
    $fallbackSql = "SELECT $selectCols FROM user WHERE Mail <> :mail ORDER BY userID DESC LIMIT 20";
    $stmt = $pdo->prepare($fallbackSql);
    $stmt->execute(['mail' => $currentEmail]);
    $contactRows = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}

if (empty($contactRows)) {
    echo json_encode([]);
    exit;
}

// Charger les infos des contacts en un seul SELECT
$placeholders = implode(',', array_fill(0, count($contactRows), '?'));
$selectCols = $hasLastActivity
    ? 'Mail, Prenom, PhotoProfil, last_activity'
    : 'Mail, Prenom, PhotoProfil';
$infoSql = "SELECT $selectCols FROM user WHERE Mail IN ($placeholders)";
$stmt = $pdo->prepare($infoSql);
$stmt->execute($contactRows);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Indexer par Mail pour accès rapide
$byMail = [];
foreach ($rows as $u) {
    $byMail[$u['Mail']] = $u;
}

$contacts = [];
foreach ($contactRows as $email) {
    $u = $byMail[$email] ?? [];
    $lastActivity = $hasLastActivity ? ($u['last_activity'] ?? null) : null;
    $online = false;
    if ($lastActivity) {
        $lastTs = strtotime($lastActivity);
        $online = ($lastTs !== false) && (time() - $lastTs < 120);
    }

    $photoPath = '/DriveUs/Image_Profil/default.png';
    if (!empty($u['PhotoProfil'])) {
        $photoFile = $u['PhotoProfil'];
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

    $contacts[] = [
        'email' => $email,
        'name' => $u['Prenom'] ?? $email,
        'photo' => $photoPath,
        'last_activity' => $lastActivity,
        'online' => $online
    ];
}

echo json_encode($contacts);

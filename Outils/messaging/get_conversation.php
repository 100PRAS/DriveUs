<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

// SÉCURITÉ: Ne jamais afficher des utilisateurs avec qui on n'a pas de conversation
// Si aucune conversation n'existe, retourner un tableau vide
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

// Vérifier si la table conversation_trajet existe et récupérer les trajets
$trajets = [];
try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'conversation_trajet'");
    if ($tableCheck && $tableCheck->rowCount() > 0) {
        $trajetSql = "SELECT user1, user2, trajet FROM conversation_trajet";
        $trajetStmt = $pdo->query($trajetSql);
        while ($row = $trajetStmt->fetch(PDO::FETCH_ASSOC)) {
            $users = [$row['user1'], $row['user2']];
            sort($users);
            $key = $users[0] . '|' . $users[1];
            $trajets[$key] = $row['trajet'];
        }
    }
} catch (Exception $e) {
    // Table n'existe pas encore, ignorer
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

    $photoPath = '/Image_Profil/default.png';
    if (!empty($u['PhotoProfil'])) {
        $photoFile = $u['PhotoProfil'];
        $candidates = [];

        if (preg_match('~^https?://~i', $photoFile)) {
            $candidates[] = $photoFile;
        } else {
            $relative = '/' . ltrim($photoFile, '/');
            $candidates[] = $relative;
            $candidates[] = '/Image_Profil/' . ltrim($photoFile, '/'); // UNIFIÉ: chemin unique
        }

        foreach ($candidates as $candidate) {
            $absolute = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $candidate;
            if (file_exists($absolute)) {
                $photoPath = $candidate;
                break;
            }
        }
    }

    // Récupérer le trajet de cette conversation
    $users = [$currentEmail, $email];
    sort($users);
    $convKey = $users[0] . '|' . $users[1];
    $trajet = $trajets[$convKey] ?? '';

    $contacts[] = [
        'email' => $email,
        'name' => $u['Prenom'] ?? $email,
        'photo' => $photoPath,
        'last_activity' => $lastActivity,
        'online' => $online,
        'trajet' => $trajet
    ];
}

echo json_encode($contacts);

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
$stmt = $conn->prepare("SELECT Mail FROM user WHERE UserID = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$currentEmail = $row['Mail'] ?? '';
$stmt->close();

if (!$currentEmail) {
    echo json_encode([]);
    exit;
}

// Trouver les destinataires uniques depuis la table messages
$sql = "
    SELECT DISTINCT contact_email FROM (
        SELECT receiver AS contact_email FROM messages WHERE sender = ?
        UNION
        SELECT sender AS contact_email FROM messages WHERE receiver = ?
    ) t
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $currentEmail, $currentEmail);
$stmt->execute();
$contactsRes = $stmt->get_result();
$contacts = [];

// Vérifier si la colonne last_activity existe
$hasLastActivity = false;
$colCheck = $conn->query("SHOW COLUMNS FROM user LIKE 'last_activity'");
if ($colCheck && $colCheck->num_rows > 0) {
    $hasLastActivity = true;
}

while ($c = $contactsRes->fetch_assoc()) {
    $email = $c['contact_email'];
    if (!$email) continue;
    
    // Récupérer nom + photo (+ dernière activité si dispo)
    if ($hasLastActivity) {
        $stmt2 = $conn->prepare("SELECT Prenom, PhotoProfil, last_activity FROM user WHERE Mail = ?");
    } else {
        $stmt2 = $conn->prepare("SELECT Prenom, PhotoProfil FROM user WHERE Mail = ?");
    }
    $stmt2->bind_param("s", $email);
    $stmt2->execute();
    $uRes = $stmt2->get_result();
    $u = $uRes->fetch_assoc();
    $stmt2->close();

    $lastActivity = $hasLastActivity ? ($u['last_activity'] ?? null) : null;
    $online = false;
    if ($lastActivity) {
        // Considérer en ligne si dernière activité < 2 minutes
        $lastTs = strtotime($lastActivity);
        $online = ($lastTs !== false) && (time() - $lastTs < 120);
    }

    $contacts[] = [
        'email' => $email,
        'name' => $u['Prenom'] ?? $email,
        'photo' => !empty($u['PhotoProfil']) ? ('Image_Profil/' . $u['PhotoProfil']) : 'Image_Profil/default.png',
        'last_activity' => $lastActivity,
        'online' => $online
    ];
}

// Fallback: si aucune conversation trouvée, proposer une liste d'utilisateurs (hors soi)
if (count($contacts) === 0) {
    if ($hasLastActivity) {
        $stmt3 = $conn->prepare("SELECT Mail, Prenom, PhotoProfil, last_activity FROM user WHERE Mail <> ? ORDER BY (last_activity IS NOT NULL) DESC, last_activity DESC LIMIT 20");
    } else {
        $stmt3 = $conn->prepare("SELECT Mail, Prenom, PhotoProfil FROM user WHERE Mail <> ? LIMIT 20");
    }
    $stmt3->bind_param("s", $currentEmail);
    $stmt3->execute();
    $uList = $stmt3->get_result();
    while ($u = $uList->fetch_assoc()) {
        $lastActivity = $hasLastActivity ? ($u['last_activity'] ?? null) : null;
        $online = false;
        if ($lastActivity) {
            $lastTs = strtotime($lastActivity);
            $online = ($lastTs !== false) && (time() - $lastTs < 120);
        }
        $contacts[] = [
            'email' => $u['Mail'],
            'name' => $u['Prenom'] ?? $u['Mail'],
            'photo' => !empty($u['PhotoProfil']) ? ('Image_Profil/' . $u['PhotoProfil']) : 'Image_Profil/default.png',
            'last_activity' => $lastActivity,
            'online' => $online
        ];
    }
    $stmt3->close();
}

echo json_encode($contacts);
?>

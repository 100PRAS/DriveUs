<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header("Content-Type: application/json");

if (!isset($_SESSION['UserID'])) {
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

require __DIR__ . '/../config/config.php';

if (!$pdo instanceof PDO) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

try {
    // Récupérer l'email de l'utilisateur actuel
    $stmt = $pdo->prepare("SELECT Mail FROM user WHERE UserID = ?");
    $stmt->execute([$_SESSION['UserID']]);
    $currentUser = $stmt->fetchColumn();

    $search = $_GET['search'] ?? '';

    // Récupérer tous les utilisateurs sauf l'utilisateur actuel
    $sql = "SELECT Mail, Prenom, Nom, PhotoProfil FROM user WHERE Mail != ?";

    if ($search !== '') {
        $sql .= " AND (Prenom LIKE ? OR Nom LIKE ? OR Mail LIKE ?)";
        $searchParam = "%$search%";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currentUser, $searchParam, $searchParam, $searchParam]);
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currentUser]);
    }

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $users = [];
    foreach ($results as $row) {
        $users[] = [
            "email" => $row['Mail'],
            "prenom" => $row['Prenom'] ?? '',
            "nom" => $row['Nom'] ?? '',
            "displayName" => trim(($row['Prenom'] ?? '') . ' ' . ($row['Nom'] ?? '')) ?: $row['Mail'],
            "photo" => !empty($row['PhotoProfil']) ? "Image_Profil/" . $row['PhotoProfil'] : "Image_Profil/default.png"
        ];
    }

    echo json_encode($users);
} catch (Throwable $e) {
    error_log('[get_users] ' . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
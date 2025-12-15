<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['UserID'])) {
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

require __DIR__ . '/../config/config.php';

// Récupérer l'email de l'utilisateur actuel
$stmt = $conn->prepare("SELECT Mail FROM user WHERE UserID = ?");
$stmt->bind_param("i", $_SESSION['UserID']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$currentUser = $row['Mail'] ?? null;

$search = $_GET['search'] ?? '';

// Récupérer tous les utilisateurs sauf l'utilisateur actuel
$sql = "SELECT Mail, Prenom, Nom, PhotoProfil FROM user WHERE Mail != ?";

if ($search !== '') {
    $sql .= " AND (Prenom LIKE ? OR Nom LIKE ? OR Mail LIKE ?)";
    $searchParam = "%$search%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $currentUser, $searchParam, $searchParam, $searchParam);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $currentUser);
}

$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = [
        "email" => $row['Mail'],
        "prenom" => $row['Prenom'] ?? '',
        "nom" => $row['Nom'] ?? '',
        "displayName" => trim(($row['Prenom'] ?? '') . ' ' . ($row['Nom'] ?? '')) ?: $row['Mail'],
        "photo" => !empty($row['PhotoProfil']) ? "/DriveUs/Image_Profil/" . $row['PhotoProfil'] : "/DriveUs/Image/default.png"
    ];
}

echo json_encode($users);
$stmt->close();
$conn->close();

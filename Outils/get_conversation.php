<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_mail'])) {
    echo json_encode([]);
    exit;
}

require "config.php";

$user = $_SESSION['user_mail'];

$sql = "
SELECT DISTINCT 
    CASE 
        WHEN sender = ? THEN receiver
        ELSE sender
    END AS contact
FROM messages
WHERE sender = ? OR receiver = ?
ORDER BY contact ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $user, $user, $user);
$stmt->execute();
$result = $stmt->get_result();

$contacts = [];
while ($row = $result->fetch_assoc()) {
    $contact = $row["contact"];
    
    // Récupérer le prénom et la photo du contact s'il existe dans la BDD
    $userStmt = $conn->prepare("SELECT Prenom, PhotoProfil FROM user WHERE Mail = ?");
    $userStmt->bind_param("s", $contact);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userData = $userResult->fetch_assoc();
    
    $displayName = $contact;
    $photo = "https://randomuser.me/api/portraits/lego/" . (rand(0, 10)) . ".jpg";
    
    if ($userData) {
        if (!empty($userData['Prenom'])) {
            $displayName = $userData['Prenom'];
        }
        if (!empty($userData['PhotoProfil'])) {
            $photo = "Image_Profil/" . $userData['PhotoProfil'];
        }
    }
    
    $contacts[] = [
        "email" => $contact,
        "name" => $displayName,
        "photo" => $photo
    ];
    $userStmt->close();
}

echo json_encode($contacts);
?>

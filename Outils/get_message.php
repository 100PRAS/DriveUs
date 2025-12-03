<?php
session_start();
include("config.php");

if (!isset($_SESSION['user_mail'])) {
    http_response_code(401);
    die("Non connecté");
}

$user = $_SESSION['user_mail'];
$contact = $_GET["contact"];

$stmt = $conn->prepare("SELECT sender, receiver, message, created_at 
                        FROM messages 
                        WHERE (sender=? AND receiver=?) 
                           OR (sender=? AND receiver=?)
                        ORDER BY created_at ASC");

$stmt->bind_param("ssss", $user, $contact, $contact, $user);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode($messages);

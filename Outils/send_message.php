<?php
session_start();
include __DIR__ . "/config.php"; // chemin garanti

header('Content-Type: application/json; charset=utf-8');

// Vérifier session
if (!isset($_SESSION['user_mail'])) {
    http_response_code(401);
    echo json_encode(["error" => "Non connecté"]);
    exit;
}

// Récupère JSON envoyé
$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(["error" => "Aucune donnée JSON reçue"]);
    exit;
}

$sender   = $_SESSION['user_mail'];
$receiver = trim($input["receiver"] ?? "");
$message  = trim($input["message"]  ?? "");

if ($receiver === "" || $message === "") {
    http_response_code(422);
    echo json_encode(["error" => "receiver ou message manquant"]);
    exit;
}

// Prépare la requête
$sql = "INSERT INTO messages (sender, receiver, message) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    // Préparation échouée — donne l'erreur MySQL pour debug
    http_response_code(500);
    echo json_encode(["error" => "Erreur prepare()", "mysql_error" => $conn->error]);
    exit;
}

if (!$stmt->bind_param("sss", $sender, $receiver, $message)) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur bind_param()", "stmt_error" => $stmt->error]);
    exit;
}

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur execute()", "stmt_error" => $stmt->error]);
    exit;
}

// Succès
echo json_encode(["status" => "ok"]);
$stmt->close();
$conn->close();

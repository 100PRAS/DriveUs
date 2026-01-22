<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json");

if (!isset($_SESSION['UserID'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Non authentifié']));
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/cagnotte_helper.php';

$userId = $_SESSION['UserID'];
$action = $_POST['action'] ?? $_GET['action'] ?? null;

if ($action === 'get-balance') {
    $balance = getCagnotteBalance($pdo, $userId);
    http_response_code(200);
    die(json_encode(['balance' => $balance]));
}

if ($action === 'withdraw' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $montant = (float)($_POST['montant'] ?? $_GET['montant'] ?? 0);
    
    if ($montant <= 0) {
        http_response_code(400);
        die(json_encode(['error' => 'Montant invalide']));
    }
    
    $balance = getCagnotteBalance($pdo, $userId);
    if ($montant > $balance) {
        http_response_code(400);
        die(json_encode(['error' => 'Solde insuffisant']));
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO cagnotte (UserID, Valeur, TrajetID)
            VALUES (?, ?, NULL)
        ");
        $stmt->execute([$userId, -$montant]);
        
        http_response_code(200);
        die(json_encode([
            'success' => true,
            'message' => 'Retrait effectué',
            'new_balance' => $balance - $montant
        ]));
    } catch (PDOException $e) {
        http_response_code(500);
        die(json_encode(['error' => 'Erreur lors du retrait']));
    }
}

if ($action === 'get-history') {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.CagnotteID,
                c.Valeur,
                c.TrajetID,
                t.VilleDepart,
                t.VilleArrivee,
                t.DateDepart,
                t.heure,
                u.Prenom as ConductorName
            FROM cagnotte c
            LEFT JOIN trajet t ON c.TrajetID = t.TrajetID
            LEFT JOIN user u ON t.ConducteurID = u.UserID
            WHERE c.UserID = ?
            ORDER BY c.CagnotteID DESC
            LIMIT 100
        ");
        $stmt->execute([$userId]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        die(json_encode(['history' => $history]));
    } catch (PDOException $e) {
        http_response_code(500);
        die(json_encode(['error' => 'Erreur lors de la récupération de l\'historique']));
    }
}

http_response_code(400);
die(json_encode(['error' => 'Action non reconnue']));

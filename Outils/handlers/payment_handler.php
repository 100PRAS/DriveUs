<?php
// Force du rechargement (contournement cache opcode)
if (function_exists('opcache_reset')) opcache_reset();

// =========================================================
// 💳 Gestionnaire de méthodes de paiement
// =========================================================
session_start();
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['UserID'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$userId = $_SESSION['UserID'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_cards':
        getCards($conn, $userId);
        break;
    case 'add_card':
        addCard($conn, $userId, $_POST);
        break;
    case 'delete_card':
        deleteCard($conn, $userId, $_POST['card_id'] ?? 0);
        break;
    case 'set_default':
        setDefaultCard($conn, $userId, $_POST['card_id'] ?? 0);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action invalide']);
}

// =========================================================
// 🔐 Fonctions
// =========================================================
function getCards($conn, $userId) {
    $query = "SELECT id, card_brand, last4, exp_month, exp_year, is_default, created_at 
              FROM payment_methods 
              WHERE `UserID` = ? 
              ORDER BY is_default DESC, created_at DESC";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        error_log('[payment_handler] Prepare failed: ' . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Erreur préparation requête paiement']);
        exit;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $cards = [];
    while ($row = $result->fetch_assoc()) {
        $cards[] = $row;
    }

    echo json_encode(['success' => true, 'cards' => $cards]);
}

function addCard($conn, $userId, $post) {
    $cardNumber = preg_replace('/[\s-]/', '', $post['card_number'] ?? '');
    $expMonth = (int)($post['exp_month'] ?? 0);
    $expYear = (int)($post['exp_year'] ?? 0);
    $cvv = preg_replace('/\D/', '', $post['cvv'] ?? '');

    if (empty($cardNumber) || $expMonth < 1 || $expMonth > 12 || $expYear < date('Y')) {
        echo json_encode(['success' => false, 'message' => 'Données de carte invalides']);
        exit;
    }

    if (strlen($cvv) < 3 || strlen($cvv) > 4) {
        echo json_encode(['success' => false, 'message' => 'CVV invalide (3 ou 4 chiffres requis)']);
        exit;
    }

    if (!validateLuhn($cardNumber)) {
        echo json_encode(['success' => false, 'message' => 'Numéro de carte invalide']);
        exit;
    }

    $cardBrand = detectCardBrand($cardNumber);
    $last4 = substr($cardNumber, -4);
    $providerToken = 'tok_' . bin2hex(random_bytes(16)); // Token simulé
    $provider = 'stripe';

    // Vérifier si c’est la première carte
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM payment_methods WHERE UserID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $isDefault = $count == 0 ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO payment_methods 
        (UserID, provider, provider_token, card_brand, last4, exp_month, exp_year, is_default) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssiii", $userId, $provider, $providerToken, $cardBrand, $last4, $expMonth, $expYear, $isDefault);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Carte ajoutée avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout de la carte']);
    }
}

function deleteCard($conn, $userId, $cardId) {
    $cardId = (int)$cardId;
    if (!$cardId) { echo json_encode(['success'=>false,'message'=>'Carte invalide']); exit; }

    $stmt = $conn->prepare("DELETE FROM payment_methods WHERE id = ? AND UserID = ?");
    $stmt->bind_param("ii", $cardId, $userId);
    $stmt->execute();

    // Vérifier si une carte par défaut existe
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM payment_methods WHERE UserID = ? AND is_default = 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'] ?? 0;

    if ($count == 0) {
        // Définir la carte la plus récente comme défaut
        $stmt = $conn->prepare("
            UPDATE payment_methods 
            SET is_default = 1 
            WHERE id = (
                SELECT id FROM (
                    SELECT id FROM payment_methods 
                    WHERE UserID = ? ORDER BY created_at DESC LIMIT 1
                ) t
            )
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }

    echo json_encode(['success' => true, 'message' => 'Carte supprimée']);
}

function setDefaultCard($conn, $userId, $cardId) {
    $cardId = (int)$cardId;
    if (!$cardId) { echo json_encode(['success'=>false,'message'=>'Carte invalide']); exit; }

    $stmt = $conn->prepare("UPDATE payment_methods SET is_default = 0 WHERE UserID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE payment_methods SET is_default = 1 WHERE id = ? AND UserID = ?");
    $stmt->bind_param("ii", $cardId, $userId);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Carte définie par défaut']);
}

// ===================== Utilitaires =====================
function validateLuhn($number) {
    $sum = 0;
    $length = strlen($number);
    $parity = $length % 2;

    for ($i = 0; $i < $length; $i++) {
        $digit = (int)$number[$i];
        if ($i % 2 === $parity) {
            $digit *= 2;
            if ($digit > 9) $digit -= 9;
        }
        $sum += $digit;
    }

    return ($sum % 10 === 0);
}

function detectCardBrand($number) {
    if (preg_match('/^4/', $number)) return 'Visa';
    if (preg_match('/^5[1-5]/', $number)) return 'MasterCard';
    if (preg_match('/^3[47]/', $number)) return 'Amex';
    if (preg_match('/^6(?:011|5)/', $number)) return 'Discover';
    return 'Unknown';
}
?>

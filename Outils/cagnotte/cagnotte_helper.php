<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

// Fonction pour ajouter un crédit de cagnotte au conducteur
function addCagnotteCredit($pdo, $conductorUserId, $tripId, $amount) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO cagnotte (UserID, TrajetID, Valeur)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$conductorUserId, $tripId, $amount]);
    } catch (PDOException $e) {
        error_log("Error adding cagnotte credit: " . $e->getMessage());
        return false;
    }
}

// Fonction pour ajouter un crédit au passager (remboursement partiel)
function addPassengerCredit($pdo, $passengerUserId, $tripId, $amount) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO cagnotte (UserID, TrajetID, Valeur)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$passengerUserId, $tripId, $amount]);
    } catch (PDOException $e) {
        error_log("Error adding passenger credit: " . $e->getMessage());
        return false;
    }
}

// Fonction pour obtenir le solde de cagnotte d'un utilisateur
function getCagnotteBalance($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(Valeur), 0) as balance 
            FROM cagnotte 
            WHERE UserID = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['balance'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error getting cagnotte balance: " . $e->getMessage());
        return 0;
    }
}

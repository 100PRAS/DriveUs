<?php
// =========================================================
// 🚗 Gestionnaire de véhicules
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

// Répertoire de stockage des fiches techniques
$vehicleSpecsDir = dirname(__FILE__) . '/../Permis/vehicles/';
if (!is_dir($vehicleSpecsDir)) {
    mkdir($vehicleSpecsDir, 0755, true);
}

// =========================================================
// 📋 Récupérer tous les véhicules de l'utilisateur
// =========================================================
if ($action === 'get_vehicles') {
    $stmt = $conn->prepare("SELECT id, model, plate, year, seats, fuel_type, spec_file, created_at FROM user_vehicles WHERE UserID = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $vehicles = [];
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = $row;
    }
    
    echo json_encode(['success' => true, 'vehicles' => $vehicles]);
    exit;
}

// =========================================================
// ➕ Ajouter un nouveau véhicule
// =========================================================
if ($action === 'add_vehicle') {
    $model = trim($_POST['model'] ?? '');
    $plate = trim($_POST['plate'] ?? '');
    $year = (int)($_POST['year'] ?? 0);
    $seats = (int)($_POST['seats'] ?? 4);
    $fuelType = trim($_POST['fuel_type'] ?? '');
    $specFile = null;
    
    // Validation
    if (empty($model) || empty($plate) || empty($fuelType)) {
        echo json_encode(['success' => false, 'message' => 'Données véhicule invalides']);
        exit;
    }
    
    if ($seats < 1 || $seats > 9) {
        echo json_encode(['success' => false, 'message' => 'Nombre de places invalide (1-9)']);
        exit;
    }
    
    $validFuels = ['Essence', 'Diesel', 'Hybride', 'Électrique', 'GPL'];
    if (!in_array($fuelType, $validFuels)) {
        echo json_encode(['success' => false, 'message' => 'Type de carburant invalide']);
        exit;
    }
    
    // Gestion du fichier de fiche technique
    if (!empty($_FILES['spec_file']['name'])) {
        $file = $_FILES['spec_file'];
        
        // Validations
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'Le fichier dépasse 5MB']);
            exit;
        }
        
        if (!in_array($file['type'], $allowedMimes)) {
            echo json_encode(['success' => false, 'message' => 'Format de fichier non accepté (PDF, JPG, PNG uniquement)']);
            exit;
        }
        
        // Générer un nom de fichier unique
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $specFile = 'vehicle_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $filePath = $vehicleSpecsDir . $specFile;
        
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload du fichier']);
            exit;
        }
    }
    
    // Insérer le véhicule
    $stmt = $conn->prepare("INSERT INTO user_vehicles (UserID, model, plate, year, seats, fuel_type, spec_file) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issiiis", $userId, $model, $plate, $year, $seats, $fuelType, $specFile);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Véhicule ajouté avec succès']);
    } else {
        // Supprimer le fichier en cas d'erreur d'insertion
        if ($specFile && file_exists($filePath)) {
            unlink($filePath);
        }
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du véhicule']);
    }
    exit;
}

// =========================================================
// 🗑️ Supprimer un véhicule
// =========================================================
if ($action === 'delete_vehicle') {
    $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
    
    // Récupérer le fichier de fiche technique avant suppression
    $stmt = $conn->prepare("SELECT spec_file FROM user_vehicles WHERE id = ? AND UserID = ?");
    $stmt->bind_param("ii", $vehicleId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $vehicle = $result->fetch_assoc();
    
    if (!$vehicle) {
        echo json_encode(['success' => false, 'message' => 'Véhicule non trouvé']);
        exit;
    }
    
    // Supprimer le fichier de fiche technique
    if ($vehicle['spec_file']) {
        $filePath = $vehicleSpecsDir . $vehicle['spec_file'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    // Supprimer le véhicule de la base de données
    $stmt = $conn->prepare("DELETE FROM user_vehicles WHERE id = ? AND UserID = ?");
    $stmt->bind_param("ii", $vehicleId, $userId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Véhicule supprimé']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action invalide']);

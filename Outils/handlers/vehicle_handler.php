<?php
// =========================================================
// 🚗 Gestionnaire de véhicules
// =========================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// Créer la table user_vehicles si elle n'existe pas
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_vehicles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            UserID INT NOT NULL,
            model VARCHAR(100) NOT NULL,
            plate VARCHAR(20) NOT NULL,
            year INT,
            seats TINYINT DEFAULT 4,
            fuel_type VARCHAR(50),
            photo VARCHAR(255),
            spec_file VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (UserID) REFERENCES user(UserID) ON DELETE CASCADE
        )
    ");
    
    // Ajouter la colonne photo si elle n'existe pas déjà
    try {
        $pdo->exec("ALTER TABLE user_vehicles ADD COLUMN photo VARCHAR(255) AFTER fuel_type");
    } catch (PDOException $e) {
        // Colonne existe déjà, ignorer
    }
} catch (PDOException $e) {
    // Table existe déjà ou erreur, continuer quand même
}

// Vérifier que $pdo est valide
if (!isset($pdo) || $pdo === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur connexion base de données']);
    exit;
}

if (!isset($_SESSION['UserID'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$userId = $_SESSION['UserID'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Répertoires de stockage
$vehicleSpecsDir = dirname(__FILE__) . '/../Permis/vehicles/';
if (!is_dir($vehicleSpecsDir)) {
    mkdir($vehicleSpecsDir, 0755, true);
}

$vehiclePhotosDir = dirname(__FILE__) . '/../Permis/vehicles/photos/';
if (!is_dir($vehiclePhotosDir)) {
    mkdir($vehiclePhotosDir, 0755, true);
}

// =========================================================
// 📋 Récupérer tous les véhicules de l'utilisateur
// =========================================================
if ($action === 'get_vehicles') {
    $stmt = $pdo->prepare("SELECT id, model, plate, year, seats, fuel_type, photo, spec_file, created_at FROM user_vehicles WHERE UserID = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    $photoFile = null;
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
    
    $validFuels = ['Essence', 'Diesel', 'Hybride', 'Electrique', 'Électrique', 'GPL'];
    if (!in_array($fuelType, $validFuels)) {
        echo json_encode(['success' => false, 'message' => 'Type de carburant invalide']);
        exit;
    }
    
    // Gestion de la photo du véhicule
    if (!empty($_FILES['photo']['name'])) {
        $file = $_FILES['photo'];
        
        // Validations
        $allowedMimes = ['image/jpeg', 'image/png'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'La photo dépasse 5MB']);
            exit;
        }
        
        if (!in_array($file['type'], $allowedMimes)) {
            echo json_encode(['success' => false, 'message' => 'Format de photo non accepté (JPG, PNG uniquement)']);
            exit;
        }
        
        // Générer un nom de fichier unique
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $photoFile = 'photo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $photoPath = $vehiclePhotosDir . $photoFile;
        
        if (!move_uploaded_file($file['tmp_name'], $photoPath)) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload de la photo']);
            exit;
        }
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
    try {
        $stmt = $pdo->prepare("INSERT INTO user_vehicles (UserID, model, plate, year, seats, fuel_type, photo, spec_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $model, $plate, $year, $seats, $fuelType, $photoFile, $specFile]);
        echo json_encode(['success' => true, 'message' => 'Véhicule ajouté avec succès']);
    } catch (PDOException $e) {
        // Supprimer les fichiers en cas d'erreur d'insertion
        if ($photoFile && isset($photoPath) && file_exists($photoPath)) {
            unlink($photoPath);
        }
        if ($specFile && isset($filePath) && file_exists($filePath)) {
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
    
    try {
        // Récupérer les fichiers avant suppression
        $stmt = $pdo->prepare("SELECT photo, spec_file FROM user_vehicles WHERE id = ? AND UserID = ?");
        $stmt->execute([$vehicleId, $userId]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('[vehicle_handler] fetch vehicle failed: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération du véhicule']);
        exit;
    }
    
    if (!$vehicle) {
        echo json_encode(['success' => false, 'message' => 'Véhicule non trouvé']);
        exit;
    }
    
    // Supprimer la photo
    if ($vehicle['photo']) {
        $photoPath = $vehiclePhotosDir . $vehicle['photo'];
        if (file_exists($photoPath)) {
            unlink($photoPath);
        }
    }
    
    // Supprimer le fichier de fiche technique
    if ($vehicle['spec_file']) {
        $filePath = $vehicleSpecsDir . $vehicle['spec_file'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    // Supprimer le véhicule de la base de données
    try {
        $stmt = $pdo->prepare("DELETE FROM user_vehicles WHERE id = ? AND UserID = ?");
        $stmt->execute([$vehicleId, $userId]);
        echo json_encode(['success' => true, 'message' => 'Véhicule supprimé']);
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('[vehicle_handler] delete failed: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action invalide']);

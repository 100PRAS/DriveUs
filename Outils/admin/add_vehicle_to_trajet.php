<?php
// Script pour ajouter la colonne Vid à la table trajet
require_once __DIR__ . '/../config/config.php';

try {
    echo "<h2>Diagnostic et migration</h2>";
    
    // 1. Vérifier la structure de user_vehicles
    echo "<h3>1. Structure de user_vehicles</h3>";
    $columns = $pdo->query("SHOW COLUMNS FROM user_vehicles")->fetchAll(PDO::FETCH_ASSOC);
    $hasIdColumn = false;
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']}) {$col['Key']}<br>";
        if ($col['Field'] === 'id') {
            $hasIdColumn = true;
        }
    }
    
    // 2. Si la colonne id n'existe pas, la créer
    if (!$hasIdColumn) {
        echo "<br><strong>⚠️ Colonne 'id' manquante dans user_vehicles. Création...</strong><br>";
        
        // Vérifier s'il y a des données existantes
        $count = $pdo->query("SELECT COUNT(*) FROM user_vehicles")->fetchColumn();
        echo "Nombre de véhicules existants: $count<br>";
        
        if ($count > 0) {
            // Si données existantes, ajouter id et migrer
            $pdo->exec("ALTER TABLE user_vehicles ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST");
            echo "✅ Colonne 'id' ajoutée à user_vehicles<br>";
        } else {
            // Recréer la table vide avec id
            $pdo->exec("DROP TABLE IF EXISTS user_vehicles");
            $pdo->exec("
                CREATE TABLE user_vehicles (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    UserID INT NOT NULL,
                    model VARCHAR(100) NOT NULL,
                    plate VARCHAR(20) NOT NULL,
                    year INT,
                    seats TINYINT DEFAULT 4,
                    fuel_type VARCHAR(50),
                    spec_file VARCHAR(255),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (UserID) REFERENCES user(UserID) ON DELETE CASCADE
                )
            ");
            $pdo->exec("CREATE INDEX idx_user_id ON user_vehicles(UserID)");
            echo "✅ Table user_vehicles recréée avec colonne id<br>";
        }
    } else {
        echo "<br>✅ La colonne 'id' existe déjà dans user_vehicles<br>";
    }
    
    // 3. Vérifier et ajouter la colonne Vid dans trajet
    echo "<br><h3>2. Ajout de Vid dans trajet</h3>";
    $check = $pdo->query("SHOW COLUMNS FROM trajet LIKE 'Vid'");
    if ($check->rowCount() > 0) {
        echo "✅ La colonne Vid existe déjà dans la table trajet<br>";
    } else {
        $pdo->exec("ALTER TABLE trajet ADD COLUMN Vid INT NULL");
        echo "✅ Colonne Vid ajoutée à la table trajet<br>";
        
        $pdo->exec("ALTER TABLE trajet ADD KEY idx_vehicle (Vid)");
        echo "✅ Index idx_vehicle créé<br>";
    }
    
    // 4. Ajouter la contrainte de clé étrangère
    echo "<br><h3>3. Création de la clé étrangère</h3>";
    
    // Vérifier si la contrainte existe déjà
    $constraints = $pdo->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'trajet' 
        AND CONSTRAINT_NAME = 'fk_trajet_vehicle'
    ")->fetchAll();
    
    if (count($constraints) > 0) {
        echo "✅ La contrainte fk_trajet_vehicle existe déjà<br>";
    } else {
        $pdo->exec("
            ALTER TABLE trajet 
            ADD CONSTRAINT fk_trajet_vehicle 
            FOREIGN KEY (Vid) 
            REFERENCES user_vehicles(id) 
            ON DELETE SET NULL 
            ON UPDATE CASCADE
        ");
        echo "✅ Contrainte de clé étrangère fk_trajet_vehicle créée<br>";
    }
    
    echo "<br><h3>✅ Migration terminée avec succès!</h3>";
    echo "<p><a href='../../Publier_un_trajet.php'>Retour à Publier un trajet</a></p>";
    
} catch (PDOException $e) {
    echo "<br>❌ Erreur: " . $e->getMessage();
    echo "<br><br>Code d'erreur: " . $e->getCode();
}
?>

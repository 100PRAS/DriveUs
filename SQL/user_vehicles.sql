-- Table pour stocker les véhicules des utilisateurs
CREATE TABLE IF NOT EXISTS user_vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,                 -- Référence à user.UserID
    model VARCHAR(100) NOT NULL,         -- Ex: Toyota Prius
    plate VARCHAR(20) NOT NULL,          -- Plaque d'immatriculation
    year INT,                            -- Année de fabrication
    seats TINYINT DEFAULT 4,             -- Nombre de places (+ conducteur)
    fuel_type VARCHAR(50),               -- Essence, Diesel, Hybride, Électrique, GPL
    spec_file VARCHAR(255),              -- Fiche technique (PDF, image)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES user(UserID) ON DELETE CASCADE
);

-- Index pour améliorer les performances
CREATE INDEX idx_user_id ON user_vehicles(UserID);

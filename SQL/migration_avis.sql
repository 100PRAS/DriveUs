-- Migration: Créer la table des avis
-- Date: 2026-01-22

CREATE TABLE IF NOT EXISTS avis (
    AvisID INT PRIMARY KEY AUTO_INCREMENT,
    ReservationID INT NOT NULL,
    evaluateur_id INT NOT NULL,
    evaluated_user_id INT NOT NULL,
    note INT NOT NULL CHECK (note >= 1 AND note <= 5),
    commentaire TEXT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ReservationID) REFERENCES reservations(ReservationID) ON DELETE CASCADE,
    FOREIGN KEY (evaluateur_id) REFERENCES user(UserID) ON DELETE CASCADE,
    FOREIGN KEY (evaluated_user_id) REFERENCES user(UserID) ON DELETE CASCADE,
    UNIQUE KEY unique_avis (ReservationID, evaluateur_id, evaluated_user_id),
    INDEX idx_evaluated_user (evaluated_user_id),
    INDEX idx_date (date_creation)
);

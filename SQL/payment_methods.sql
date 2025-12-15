-- Table pour stocker les méthodes de paiement de manière sécurisée
CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_mail VARCHAR(255) NOT NULL,  -- Utiliser Mail comme référence utilisateur
    provider VARCHAR(50),              -- Stripe, PayPal, etc.
    provider_token VARCHAR(255),       -- token sécurisé du provider
    card_brand VARCHAR(20),            -- Visa, Mastercard, Amex, Discover
    last4 CHAR(4),                     -- 4 derniers chiffres (ex: 1234)
    exp_month TINYINT,                 -- Mois d'expiration (1-12)
    exp_year SMALLINT,                 -- Année d'expiration (ex: 2025)
    is_default BOOLEAN DEFAULT 0,      -- Carte par défaut
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_mail) REFERENCES user(Mail) ON DELETE CASCADE
);

-- Index pour améliorer les performances
CREATE INDEX idx_user_mail ON payment_methods(user_mail);
CREATE INDEX idx_is_default ON payment_methods(is_default);

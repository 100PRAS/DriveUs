# Guide d'installation du système de messagerie de groupe

## Étapes d'installation

### 1. Exécuter le script SQL de mise à jour

Ouvrez phpMyAdmin ou votre client MySQL et exécutez les commandes suivantes :

```sql
-- Mise à jour de la table messages
ALTER TABLE `messages` 
ADD COLUMN `TrajetID` INT NULL AFTER `receiver`,
ADD COLUMN `is_group` TINYINT(1) DEFAULT 0 AFTER `TrajetID`,
ADD INDEX `idx_trajet` (`TrajetID`);

-- Table pour gérer les participants aux conversations de groupe
CREATE TABLE IF NOT EXISTS `conversation_participants` (
    `ConversationID` INT AUTO_INCREMENT PRIMARY KEY,
    `TrajetID` INT NOT NULL,
    `UserEmail` VARCHAR(255) NOT NULL,
    `date_ajout` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`TrajetID`) REFERENCES `trajet`(`TrajetID`) ON DELETE CASCADE,
    UNIQUE KEY `unique_participant` (`TrajetID`, `UserEmail`),
    INDEX `idx_trajet_conv` (`TrajetID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Fonctionnalités implémentées

✅ **Messagerie privée (1-to-1)**
- Envoi et réception de messages entre deux utilisateurs
- Fichier: `Messagerie.php`
- API: `send_message.php`, `get_message.php`

✅ **Messagerie de groupe (trajet)**
- Conversation de groupe liée à un trajet spécifique
- Tous les passagers et le conducteur peuvent échanger
- Fichier: `Messagerie_groupe.php`
- API: `send_group_message.php`, `get_group_conversation.php`

✅ **Bouton de contact depuis Mes trajets**
- Bouton "💬 Groupe" dans `Mes_trajets.php`
- Ouvre automatiquement la conversation de groupe du trajet
- Seuls les participants (conducteur + passagers confirmés) peuvent accéder

### 3. Utilisation

#### Pour le conducteur :
1. Aller sur "Mes trajets"
2. Cliquer sur "💬 Groupe" pour un trajet
3. Communiquer avec tous les passagers en même temps

#### Pour les passagers :
1. Après avoir réservé un trajet, accéder à la conversation via leur liste de réservations
2. Voir tous les messages du groupe (conducteur + autres passagers)

### 4. Sécurité

- ✅ Vérification que l'utilisateur est bien participant du trajet
- ✅ Messages liés à un trajet spécifique (TrajetID)
- ✅ Authentification requise pour tous les endpoints

### 5. Améliorations futures possibles

- Notifications en temps réel (WebSocket)
- Historique des conversations archivées
- Possibilité de quitter une conversation
- Indicateurs de messages non lus
- Upload de fichiers/images dans les conversations

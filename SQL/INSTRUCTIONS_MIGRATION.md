# 📋 Instructions de Migration - Système de Messagerie

## ✅ Corrections appliquées

### 1. Script SQL créé
**Fichier:** `SQL/fix_messages_table.sql`

Ce script ajoute les colonnes manquantes à la table `messages` :
- ✅ `lu` (TINYINT) - Statut de lecture des messages
- ✅ `date_envoi` (DATETIME) - Alias de `created_at` pour compatibilité
- ✅ Trigger automatique pour synchroniser `created_at` et `date_envoi`

### 2. Fichiers PHP corrigés

#### **get_group_conversation.php**
- ❌ Avant : utilisait `MessageID` et `date_envoi`
- ✅ Après : utilise `id` et `created_at` (avec fallback sur `date_envoi`)

#### **send_group_message.php**
- ❌ Avant : utilisait `date_envoi`
- ✅ Après : utilise `created_at`

## 🚀 Pour appliquer les modifications

### Étape 1 : Exécuter le script SQL

**Option A - Via phpMyAdmin :**
1. Ouvrez http://localhost/phpmyadmin
2. Sélectionnez la base `dbs15148242`
3. Cliquez sur "SQL"
4. Copiez-collez le contenu de `SQL/fix_messages_table.sql`
5. Cliquez sur "Exécuter"

**Option B - Via MySQL en ligne de commande :**
```bash
cd C:\xampp\mysql\bin
.\mysql.exe -u root -e "SOURCE C:/xampp/htdocs/DriveUs/SQL/fix_messages_table.sql"
```

**Option C - Via PowerShell (si MySQL dans PATH) :**
```powershell
cd C:\xampp\htdocs\DriveUs
Get-Content .\SQL\fix_messages_table.sql | C:\xampp\mysql\bin\mysql.exe -u root dbs15148242
```

### Étape 2 : Vérifier la structure

Exécutez cette requête pour vérifier que les colonnes sont bien ajoutées :
```sql
SHOW COLUMNS FROM messages;
```

Vous devriez voir :
- `id` (INT, PRIMARY KEY)
- `sender` (VARCHAR)
- `receiver` (VARCHAR)
- `TrajetID` (INT)
- `is_group` (TINYINT)
- `message` (TEXT)
- `file_path` (VARCHAR)
- `created_at` (DATETIME)
- ✨ **`lu`** (TINYINT) - NOUVEAU
- ✨ **`date_envoi`** (DATETIME) - NOUVEAU

### Étape 3 : Tester le système

1. **Messagerie individuelle** : Accédez à `Messagerie.php`
2. **Messagerie de groupe** : Accédez à `Messagerie_groupe.php?trajet_id=X`
3. Envoyez des messages et vérifiez qu'ils s'affichent correctement

## 🔍 Vérifications post-migration

### Test 1 : Messages individuels
```sql
SELECT id, sender, receiver, message, created_at, lu 
FROM messages 
WHERE is_group = 0 
LIMIT 5;
```

### Test 2 : Messages de groupe
```sql
SELECT id, sender, message, TrajetID, created_at, date_envoi, lu 
FROM messages 
WHERE is_group = 1 
LIMIT 5;
```

### Test 3 : Trigger de synchronisation
```sql
-- Insérer un test
INSERT INTO messages (sender, receiver, message) 
VALUES ('test@test.com', 'test2@test.com', 'Test de synchronisation');

-- Vérifier que created_at et date_envoi sont identiques
SELECT id, created_at, date_envoi 
FROM messages 
WHERE sender = 'test@test.com' 
ORDER BY id DESC 
LIMIT 1;

-- Nettoyer
DELETE FROM messages WHERE sender = 'test@test.com';
```

## 📝 Résumé des changements

| Fichier | Changement | Statut |
|---------|-----------|--------|
| `SQL/fix_messages_table.sql` | Création du script de migration | ✅ Créé |
| `Outils/messaging/get_group_conversation.php` | `MessageID` → `id`, `date_envoi` → `created_at` | ✅ Corrigé |
| `Outils/messaging/send_group_message.php` | `date_envoi` → `created_at` | ✅ Corrigé |

## ⚠️ Notes importantes

1. **Compatibilité** : Le trigger garantit que `created_at` et `date_envoi` restent synchronisés
2. **Rétrocompatibilité** : Le fallback `created_at ?? date_envoi` dans le code PHP assure la compatibilité
3. **Pas de perte de données** : Les données existantes sont préservées
4. **Colonne `lu`** : Permet de développer la fonctionnalité "messages non lus" ultérieurement

## 🐛 En cas de problème

Si vous rencontrez des erreurs :
1. Vérifiez que vous utilisez bien la base `dbs15148242`
2. Vérifiez que l'utilisateur MySQL a les droits `ALTER TABLE` et `CREATE TRIGGER`
3. Consultez les logs PHP : `C:\xampp\apache\logs\error.log`
4. Consultez les logs MySQL : `C:\xampp\mysql\data\*.err`

# Configuration Email pour DriveUs

## ⚠️ Gmail et les mots de passe d'application

**Note importante** : Depuis 2024, Google a restreint l'accès aux mots de passe d'application pour les comptes personnels. Cette fonctionnalité n'est disponible que pour les comptes Google Workspace (payants).

## 🎯 Solutions alternatives recommandées

### Option 1 : Mode développement (actuel) ✅
**Le système affiche directement le lien de réinitialisation dans l'interface.**
- ✅ Aucune configuration requise
- ✅ Idéal pour le développement local
- ✅ Fonctionne immédiatement

### Option 2 : Services SMTP gratuits

#### A) **Brevo (ex-Sendinblue)** - RECOMMANDÉ
- 🎁 300 emails/jour GRATUITS
- 📝 Inscription : https://www.brevo.com/
- Configuration simple dans `GmailSender.php` :
```php
private $host = 'smtp-relay.brevo.com';
private $port = 587;
private $username = 'votre_email';
private $password = 'votre_clé_api_brevo';
```

#### B) **Mailtrap** - Pour tests
- 🎁 100% gratuit pour le développement
- 📝 Inscription : https://mailtrap.io/
- Les emails n'arrivent pas vraiment (boîte test)

#### C) **SendGrid**
- 🎁 100 emails/jour gratuits
- 📝 Inscription : https://sendgrid.com/

### Option 3 : Configuration XAMPP locale

Modifier `C:\xampp\php\php.ini` :
```ini
[mail function]
SMTP=smtp.gmail.com
smtp_port=587
sendmail_from=driveus.team@gmail.com
```

⚠️ Nécessite toujours une authentification Gmail valide

## 🚀 Configuration rapide avec Brevo (RECOMMANDÉ)

### Étape 1 : Créer un compte Brevo
1. Allez sur https://www.brevo.com/
2. Créez un compte gratuit (300 emails/jour)
3. Vérifiez votre email

### Étape 2 : Obtenir votre clé SMTP
1. Connectez-vous à Brevo
2. Allez dans **Paramètres** → **SMTP & API**
3. Cliquez sur **Clés SMTP**
4. Créez une nouvelle clé ou copiez celle existante

### Étape 3 : Configurer dans GmailSender.php
Ouvrez `Outils/GmailSender.php` et modifiez :

```php
private $username = 'votre_email@gmail.com';  // Votre email Brevo
private $password = 'votre_clé_smtp_brevo';    // La clé SMTP copiée
private $host = 'smtp-relay.brevo.com';
private $port = 587;
```

### Étape 4 : Tester
1. Rechargez la page de connexion
2. Cliquez sur "Mot de passe oublié ?"
3. Entrez votre email
4. ✅ L'email sera envoyé via Brevo !

## 🔒 Sécurité

- ⚠️ Ne partagez JAMAIS votre clé SMTP
- ⚠️ Ne commitez PAS le fichier avec la clé sur Git
- 💡 Créez un fichier `.env` pour les configurations sensibles

## 🧪 Mode actuel (sans configuration)

Le système fonctionne déjà ! Il affiche le lien de réinitialisation directement dans l'interface. Pratique pour le développement local sans configuration.

## ✅ Avantages de Brevo

- 🎁 300 emails/jour gratuits à vie
- 📊 Statistiques d'envoi
- ✉️ Templates d'emails
- 🚀 API REST complète
- ✅ Pas besoin de Google Workspace

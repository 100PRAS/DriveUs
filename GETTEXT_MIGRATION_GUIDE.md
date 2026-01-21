# 🚀 Migration vers gettext - Guide complet

## ✅ Phase 1 : Initialisation complétée

Tous les fichiers de base sont maintenant en place:

```
DriveUs/
├── locales/
│   ├── fr_FR/LC_MESSAGES/
│   │   └── messages.po                 ✅ CRÉÉ
│   └── en_US/LC_MESSAGES/
│       └── messages.po                 ✅ CRÉÉ
├── Outils/config/
│   ├── i18n.php                        ✅ CRÉÉ (nouveau système)
│   └── langue.php                      ✅ MODIFIÉ (mode compatibilité)
├── compile_translations.bat            ✅ CRÉÉ (Windows)
└── compile_translations.sh             ✅ CRÉÉ (Linux/Mac)
```

---

## 🔧 Phase 2 : Compiler les fichiers .po en .mo

### Sur Windows:
```bash
# Double-cliquer sur:
compile_translations.bat

# Ou via PowerShell:
cd C:\xampp\htdocs\DriveUs
.\compile_translations.bat
```

### Sur Linux/Mac:
```bash
cd /var/www/driveus
chmod +x compile_translations.sh
./compile_translations.sh
```

### Vérifier l'installation de gettext:
```bash
# Windows - Télécharger et installer depuis:
https://gnuwin32.sourceforge.net/packages/gettext.htm

# Linux (Ubuntu/Debian)
sudo apt-get install gettext

# macOS
brew install gettext
```

**Résultat attendu après compilation:**
```
DriveUs/locales/
├── fr_FR/LC_MESSAGES/
│   ├── messages.po      (source texte - éditable)
│   └── messages.mo      ✅ NOUVEAU (binaire - rapide)
└── en_US/LC_MESSAGES/
    ├── messages.po      (source texte - éditable)
    └── messages.mo      ✅ NOUVEAU (binaire - rapide)
```

---

## 🔄 Phase 3 : Migration progressive des fichiers PHP

### Étape 1 : Remplacer l'include

**AVANT:**
```php
<?php
require_once 'Outils/config/langue.php';
echo t('Bouton_A');
?>
```

**APRÈS:**
```php
<?php
require_once 'Outils/config/i18n.php';
echo _("Home");
?>
```

### Étape 2 : Traductions simples

**AVANT:**
```php
echo $translations['email'];  // "E-mail"
echo t('email');               // "E-mail"
```

**APRÈS:**
```php
echo _("Email");  // Traduit automatiquement
```

### Étape 3 : Traductions avec pluriels

**AVANT:**
```php
echo sprintf($translations['seats'] ?? "Seats", $count);
```

**APRÈS:**
```php
printf(_n("You have %d seat", "You have %d seats", $count), $count);
```

### Étape 4 : Contexte (optionnel, pour les mots ambigus)

**AVANT:**
```php
// Problème: "Search" peut être un verbe ou un nom
echo t('search_button');
echo t('search_noun');
```

**APRÈS:**
```php
echo pgettext("button", "Search");  // "Rechercher" (bouton)
echo pgettext("noun", "Search");    // "Recherche" (nom)
```

---

## 📝 Exemple complet de migration

### Fichier original (Messagerie.php)
```php
<?php
require_once 'Outils/config/langue.php';

// Avant migration
echo "<h1>" . t('Messagerie') . "</h1>";
echo "<button>" . t('Bouton_S') . "</button>";

// Messages non traduits
$count = 5;
echo "You have " . $count . " messages";
?>
```

### Fichier migré
```php
<?php
require_once 'Outils/config/i18n.php';

// Après migration
echo "<h1>" . _("Messaging") . "</h1>";
echo "<button>" . _("Login") . "</button>";

// Avec pluralisation
$count = 5;
printf(_n("You have %d message", "You have %d messages", $count), $count);
?>
```

---

## 📚 Référence des fonctions gettext

### Traduction simple
```php
_("Hello World")                          // Traduction simple
echo _("Welcome");                        // Affiche: "Bienvenue" (si FR)
```

### Traduction plurielle
```php
_n($singular, $plural, $count)
printf(_n("1 trip", "%d trips", 5), 5)   // "5 trajets"
```

### Traduction avec contexte
```php
pgettext($context, $msgid)
pgettext("button", "Search")              // Contexte = "button"
pgettext("noun", "Search")                // Contexte = "noun"
```

### Traduction plurielle + contexte
```php
pngettext($context, $singular, $plural, $count)
pngettext("menu", "1 item", "%d items", 10)
```

### Utilitaires
```php
getCurrentLocale()                        // "fr_FR"
getLangCode()                             // "fr"
langUrl('en_US')                          // URL avec changement de langue
getAvailableLocales()                     // Lister les langues
```

---

## 🎯 Liste des fichiers à migrer en priorité

### Priorité HAUTE (pages principales):
- [ ] `Messagerie.php` - Beaucoup de texte utilisateur
- [ ] `Profil.php` - Formulaires
- [ ] `Publier_un_trajet.php` - Formulaires
- [ ] `Trouver_un_trajet.php` - UI importante
- [ ] `index.php` - Page d'accueil

### Priorité MOYENNE:
- [ ] `S_inscrire.php` - Formulaire d'inscription
- [ ] `Se_connecter.php` - Formulaire connexion
- [ ] `Outils/views/header.php` - En-tête
- [ ] `Outils/views/footer.php` - Pied de page

### Priorité BASSE:
- [ ] Autres fichiers PHP
- [ ] Admin pages

---

## ✅ Checklist de migration pour UN fichier

Pour chaque fichier à migrer:

```
[ ] 1. Remplacer: require_once 'Outils/config/langue.php' 
      par: require_once 'Outils/config/i18n.php'

[ ] 2. Trouver tous les: t('clé') 
      Remplacer par: _("Source text")

[ ] 3. Trouver tous les: $translations['clé']
      Remplacer par: _("Source text")

[ ] 4. Vérifier les pluriels
      Remplacer par: printf(_n(...), $count)

[ ] 5. Tester en FR et EN
      Changer langue avec ?lang=fr_FR ou ?lang=en_US

[ ] 6. Vérifier qu'aucune clé n'a été oubliée
      Rechercher: t(' ou $translations[
```

---

## 🛠️ Mise à jour des fichiers .po

Quand vous ajoutez un nouveau texte:

### 1. Ajouter le texte dans le code PHP:
```php
echo _("This is a new string");
```

### 2. Ajouter l'entrée dans les fichiers .po:

**locales/fr_FR/LC_MESSAGES/messages.po:**
```po
msgid "This is a new string"
msgstr "Ceci est une nouvelle chaîne"
```

**locales/en_US/LC_MESSAGES/messages.po:**
```po
msgid "This is a new string"
msgstr "This is a new string"
```

### 3. Recompiler:
```bash
# Windows
compile_translations.bat

# Linux/Mac
./compile_translations.sh
```

---

## 📋 Traducteurs : Édition visuelle des fichiers .po

Les traducteurs peuvent utiliser des outils visuels:

### Gratuit:
- **PoEdit** - https://poedit.net/ (gratuit + payant)
- **Lokalize** - Inclus dans KDE (Linux)
- **Virtaal** - http://virtaal.translatetoolkit.org/

### Workflow traducteur:
1. Ouvrir `locales/fr_FR/LC_MESSAGES/messages.po` dans PoEdit
2. Voir tous les strings à traduire
3. Éditer les traductions
4. Sauvegarder (recompile automatiquement en .mo)
5. Uploader sur Git

---

## 🔐 Sécurité & Performance

### Avantages de gettext pour la sécurité:
✅ Pas d'inclusion d'arrays PHP géantes  
✅ Pas d'injection de code via fichiers de langue  
✅ Fichiers .mo binaires (impossible à modifier accidentellement)  

### Performance:
✅ Fichiers .mo optimisés = recherches rapides  
✅ Cache au niveau du système d'exploitation  
✅ Pas de tableaux PHP à charger entièrement  

---

## 🚨 Troubleshooting

### Erreur: "msgfmt.exe: command not found"
**Solution:** Installer gettext pour Windows  
https://gnuwin32.sourceforge.net/packages/gettext.htm

### Les traductions ne s'affichent pas
**Vérifier:**
1. Les fichiers `.mo` existent dans `locales/XX_XX/LC_MESSAGES/`
2. `LC_ALL` est bien défini dans i18n.php
3. Vider le cache navigateur (`Ctrl+Shift+Delete`)
4. Vérifier les logs: `error_log()` dans PHP

### Traduction anglaise affichée au lieu du français
**Vérifier:**
1. Locale détectée: `echo getCurrentLocale();`
2. Fichier `.mo` compilé correctement
3. Clé dans le fichier `.po` (case sensible!)

---

## 📞 Besoin d'aide?

Documentation officielle PHP gettext:  
https://www.php.net/manual/fr/book.gettext.php

Documentation format .po:  
https://www.gnu.org/software/gettext/manual/html_node/PO-Files.html

---

## 🎉 Étapes suivantes

1. ✅ **Fait:** Structure gettext créée
2. ⏭️ **Suivant:** Compiler les .mo
3. ⏭️ **Puis:** Migrer Messagerie.php
4. ⏭️ **Puis:** Migrer autres fichiers
5. ⏭️ **Fin:** Supprimer l'ancien système

**Estimé:** 4-6h pour migration complète

Vous êtes prêt à commencer! 🚀

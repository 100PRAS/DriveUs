<?php
/**
 * Configuration gettext pour DriveUs
 * Initialise le système de traductions multilingues
 * 
 * À inclure au début de chaque page : require_once 'Outils/config/i18n.php';
 */

if (!isset($_SESSION)) {
    session_start();
}

// ============================================
// DÉTERMINER LA LANGUE (ordre de priorité)
// ============================================
// 1. Paramètre URL (?lang=fr_FR)
// 2. Session
// 3. Cookie
// 4. Défaut: français

$valid_locales = ['fr_FR', 'en_US'];
$default_locale = 'fr_FR';

// Récupérer la locale
if (isset($_GET['lang']) && in_array($_GET['lang'], $valid_locales)) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('lang', $_GET['lang'], time() + (365 * 24 * 60 * 60), '/DriveUs/');
} elseif (!isset($_SESSION['lang'])) {
    if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $valid_locales)) {
        $_SESSION['lang'] = $_COOKIE['lang'];
    } else {
        $_SESSION['lang'] = $default_locale;
    }
}

$current_locale = $_SESSION['lang'];

// ============================================
// CONFIGURER GETTEXT
// ============================================

// Définir les variables d'environnement
putenv("LC_ALL=$current_locale.UTF-8");
putenv("LANG=$current_locale.UTF-8");

// Appliquer la locale aux fonctions PHP
setlocale(LC_ALL, "$current_locale.UTF-8");

// Chemins des fichiers de traductions
$locale_dir = __DIR__ . '/../locales';

// Bind gettext domain
bindtextdomain('messages', $locale_dir);
textdomain('messages');
bind_textdomain_codeset('messages', 'UTF-8');

// ============================================
// FONCTIONS HELPER
// ============================================

/**
 * Traduction simple
 * @param string $msgid Texte à traduire
 * @return string Texte traduit
 */
function _($msgid) {
    return gettext($msgid);
}

/**
 * Traduction avec contexte (résout les ambiguïtés)
 * @param string $context Contexte
 * @param string $msgid Texte à traduire
 * @return string Texte traduit
 */
function pgettext($context, $msgid) {
    $contextString = "{$context}\004{$msgid}";
    $translation = gettext($contextString);
    if ($translation == $contextString) {
        return $msgid;
    }
    return $translation;
}

/**
 * Traduction plurielle
 * @param string $msgid Singulier
 * @param string $msgid_plural Pluriel
 * @param int $count Nombre pour déterminer singulier/pluriel
 * @return string Texte traduit (singulier ou pluriel)
 */
function _n($msgid, $msgid_plural, $count) {
    return ngettext($msgid, $msgid_plural, $count);
}

/**
 * Traduction plurielle avec contexte
 * @param string $context Contexte
 * @param string $msgid Singulier
 * @param string $msgid_plural Pluriel
 * @param int $count Nombre
 * @return string Texte traduit
 */
function pngettext($context, $msgid, $msgid_plural, $count) {
    $contextString = "{$context}\004{$msgid}";
    $contextStringPlural = "{$context}\004{$msgid_plural}";
    $translation = ngettext($contextString, $contextStringPlural, $count);
    if ($translation == $contextString || $translation == $contextStringPlural) {
        return ($count == 1) ? $msgid : $msgid_plural;
    }
    return $translation;
}

/**
 * Obtenir la locale actuelle
 * @return string Locale (ex: 'fr_FR')
 */
function getCurrentLocale() {
    return $_SESSION['lang'] ?? 'fr_FR';
}

/**
 * Obtenir le code de langue court (ex: 'fr')
 * @return string Code langue (ex: 'fr', 'en')
 */
function getLangCode() {
    $locale = getCurrentLocale();
    return substr($locale, 0, 2);
}

/**
 * Générer une URL avec changement de langue
 * @param string $new_locale Nouvelle locale (ex: 'en_US')
 * @return string URL modifiée
 */
function langUrl($new_locale) {
    $url = $_SERVER['REQUEST_URI'];
    
    if (strpos($url, '?') !== false) {
        // URL a déjà des paramètres
        if (preg_match('/lang=[a-z_]+/', $url)) {
            // Remplacer le paramètre lang existant
            $url = preg_replace('/lang=[a-z_]+/', 'lang=' . $new_locale, $url);
        } else {
            // Ajouter le paramètre lang
            $url .= '&lang=' . $new_locale;
        }
    } else {
        // Pas de paramètres, ajouter lang
        $url .= '?lang=' . $new_locale;
    }
    
    return $url;
}

/**
 * Lister les locales disponibles
 * @return array Liste des locales disponibles
 */
function getAvailableLocales() {
    return [
        'fr_FR' => ['name' => 'Français', 'flag' => '🇫🇷'],
        'en_US' => ['name' => 'English', 'flag' => '🇺🇸'],
    ];
}

// ============================================
// COMPATIBILITÉ AVEC L'ANCIEN SYSTÈME
// ============================================
// Pour la transition progressive, vous pouvez utiliser :
// t('clé') sera remplacé par _('texte source anglais')
//
// Ajoutez ceci si vous utilisez encore l'ancien système:
// $text = []; // Pour compatibilité avec ancien code qui utilise $text['clé']
// (Voir langue.php)

?>

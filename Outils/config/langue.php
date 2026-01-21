<?php
/**
 * ⚠️ IMPORTANT: Ce fichier est OBSOLÈTE
 * 
 * Migration vers gettext en cours!
 * 
 * ANCIEN SYSTÈME (encore actif):
 * - Utilise des arrays PHP (lang_fr.php, lang_en.php)
 * - Limité et peu performant
 * 
 * NOUVEAU SYSTÈME (à venir):
 * - Utilise gettext (fichiers .po/.mo)
 * - Standard industriel
 * - Meilleure performance
 * 
 * TRANSITION:
 * À la place de: require_once 'Outils/config/langue.php';
 * Utilisez maintenant: require_once 'Outils/config/i18n.php';
 * 
 * UTILISATION:
 * Ancien: echo t('Bouton_A');
 * Nouveau: echo _("Home");
 */

// ============================================
// ANCIEN SYSTÈME - GARDER POUR COMPATIBILITÉ
// ============================================
// Ce code n'est conservé que pour la compatibilité rétroactive
// UTILISEZ i18n.php POUR LES NOUVEAUX DÉVELOPPEMENTS

if (!isset($_SESSION)) {
    session_start();
}

// Déterminer la langue (par ordre de priorité):
// 1. Paramètre URL (?lang=fr ou ?lang=fr_FR)
// 2. Session
// 3. Cookie
// 4. Défaut: français

$valid_langs = ['fr', 'en', 'fr_FR', 'en_US'];

if (isset($_GET['lang']) && in_array($_GET['lang'], $valid_langs)) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('lang', $_GET['lang'], time() + (365 * 24 * 60 * 60), '/DriveUs/');
} elseif (!isset($_SESSION['lang'])) {
    if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $valid_langs)) {
        $_SESSION['lang'] = $_COOKIE['lang'];
    } else {
        $_SESSION['lang'] = 'fr';
    }
}

$lang = $_SESSION['lang'];

// Convertir les formats courts en longs (fr -> fr_FR)
$lang_map = ['fr' => 'fr_FR', 'en' => 'en_US'];
$locale = $lang_map[$lang] ?? $lang;

// Charger les traductions (ancien système)
$lang_file = __DIR__ . "/lang_" . ($lang == 'en' ? 'en' : 'fr') . ".php";
if (file_exists($lang_file)) {
    $translations = require $lang_file;
} else {
    $translations = [];
}

// Alias pour compatibilité
$text = $translations;

// ============================================
// FONCTIONS HELPER (ANCIEN SYSTÈME)
// ============================================

/**
 * ⚠️ OBSOLÈTE: Fonction helper pour obtenir une traduction (ancien système)
 * À remplacer par: _("texte")
 */
function t($key, $default = '') {
    global $translations;
    return $translations[$key] ?? $default;
}

/**
 * ⚠️ OBSOLÈTE: Obtenir la langue actuelle (ancien format)
 * À remplacer par: getLang() ou getCurrentLocale()
 */
function getLang() {
    return $_SESSION['lang'] ?? 'fr';
}

/**
 * Obtenir l'URL avec changement de langue
 */
function langUrl($newLang) {
    $url = $_SERVER['REQUEST_URI'];
    if (strpos($url, '?') !== false) {
        if (strpos($url, 'lang=') !== false) {
            $url = preg_replace('/lang=[a-z_]+/', 'lang=' . $newLang, $url);
        } else {
            $url .= '&lang=' . $newLang;
        }
    } else {
        $url .= '?lang=' . $newLang;
    }
    return $url;
}

// ============================================
// MIGRATION PROGRESSIVE
// ============================================
// Pour migrer vers gettext progressivement:
// 1. Incluez i18n.php à la place de langue.php
// 2. Remplacez t('clé') par _("Source text")
// 3. Mettez à jour les fichiers PHP progressivement
// 4. Une fois tous les fichiers migré, supprimez ce fichier

?>


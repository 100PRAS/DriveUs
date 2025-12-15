<?php
/**
 * Système de gestion de langue unifié pour DriveUs
 * À inclure au début de chaque page PHP
 */

if (!isset($_SESSION)) {
    session_start();
}

// Déterminer la langue (par ordre de priorité):
// 1. Paramètre URL (?lang=fr)
// 2. Session
// 3. Cookie
// 4. Défaut: français
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('lang', $_GET['lang'], time() + (365 * 24 * 60 * 60), '/DriveUs/');
} elseif (!isset($_SESSION['lang'])) {
    if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], ['fr', 'en'])) {
        $_SESSION['lang'] = $_COOKIE['lang'];
    } else {
        $_SESSION['lang'] = 'fr'; // Langue par défaut
    }
}

$lang = $_SESSION['lang'];

// Charger les traductions
$translations = require __DIR__ . "/lang_$lang.php";

/**
 * Fonction helper pour obtenir une traduction
 */
function t($key, $default = '') {
    global $translations;
    return $translations[$key] ?? $default;
}

/**
 * Fonction pour obtenir la langue actuelle
 */
function getLang() {
    return $_SESSION['lang'] ?? 'fr';
}

/**
 * Fonction pour obtenir l'URL avec changement de langue
 */
function langUrl($newLang) {
    $url = $_SERVER['REQUEST_URI'];
    if (strpos($url, '?') !== false) {
        // URL a déjà des paramètres
        if (strpos($url, 'lang=') !== false) {
            // Remplacer le paramètre lang existant
            $url = preg_replace('/lang=[a-z]{2}/', 'lang=' . $newLang, $url);
        } else {
            // Ajouter le paramètre lang
            $url .= '&lang=' . $newLang;
        }
    } else {
        // Pas de paramètres, ajouter lang
        $url .= '?lang=' . $newLang;
    }
    return $url;
}
?>

<?php
/**
 * Configuration Google OAuth
 * Fichier de configuration centralisé pour Google Sign-In
 */

// Google OAuth Configuration
define('GOOGLE_OAUTH_CLIENT_ID', '857561252718-s2t7pdiofp5hkprl7e7fmggmvvkrlhp5.apps.googleusercontent.com');

// Redirection après connexion réussie
define('GOOGLE_LOGIN_REDIRECT', 'index.php');

// Rôle par défaut pour les nouveaux utilisateurs via Google
define('GOOGLE_DEFAULT_ROLE', 'passager');

// Configuration Google API (pour les futures implémentations)
define('GOOGLE_API_TIMEOUT', 5); // Timeout en secondes

// Vérification du Client ID
if (empty(GOOGLE_OAUTH_CLIENT_ID)) {
    throw new Exception("Google OAuth Client ID n'est pas configuré");
}
?>

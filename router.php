<?php
/**
 * Routeur principal - URLs propres sans chemins d'accès exposés
 * Transforme /mes-reservations en Outils/reservations/Mes_reservations_recues.php
 */

// Récupérer la route demandée
$route = $_GET['route'] ?? '';
$route = trim($route, '/');
$route = strtolower($route); // Normaliser en minuscules

// Définir le mapping des URLs propres vers les fichiers réels
$routes = [
    // Pages principales
    '' => 'index.php',
    'accueil' => 'index.php',
    'forum' => 'Forum.php',
    'messagerie' => 'Messagerie.php',
    'messagerie-groupe' => 'Messagerie_groupe.php',
    'messages' => 'Messagerie.php',
    'profil' => 'Profil.php',
    'publier-trajet' => 'Publier_un_trajet.php',
    'trouver-trajet' => 'Trouver_un_trajet.php',
    'tableau-bord-admin' => 'Tableau_de_Bord_Admin.php',
    'gestion-admins' => 'Gestion_Administrateurs.php',
    'cgu' => 'CGU.php',
    
    // Authentification
    'inscription' => 'S_inscrire.php',
    'connexion' => 'Se_connecter.php',
    'deconnexion' => 'Se_deconnecter.php',
    'mot-de-passe-oublie' => 'Mot_de_passe_oublie.php',
    'reinitialiser-mot-de-passe' => 'Reinitialiser_mot_de_passe.php',
    
    // Réservations (URLs propres)
    'mes-reservations' => 'Outils/reservations/Mes_reservations.php',
    'mes-reservations-recues' => 'Outils/reservations/Mes_reservations_recues.php',
    'faire-reservation' => 'Outils/reservations/make_reservation.php',
    'annuler-reservation' => 'Outils/reservations/cancel_reservation.php',
    // Endpoints API réservations
    'api/reservations' => 'Outils/reservations/get_reservations.php',
    'api/reservations-received' => 'Outils/reservations/get_received_reservations.php',
    'api/reservation/cancel' => 'Outils/reservations/cancel_reservation.php',
    'api/reservation/make' => 'Outils/reservations/make_reservation.php',
    
    // Trajets
    'mes-trajets' => 'Outils/trips/Mes_trajets.php',
    'details-trajet' => 'Outils/trips/get_trip_details.php',
    'supprimer-trajet' => 'Outils/trips/delete_trip.php',
    
    // Messagerie
    'envoyer-message' => 'Outils/messaging/send_message.php',
    'conversation' => 'Outils/messaging/get_conversation.php',
    
    // Forum
    'creer-sujet' => 'Outils/forum/create_topic.php',
    'repondre-sujet' => 'Outils/forum/create_reply.php',
    
    // Admin
    'admin/diagnostics' => 'Outils/admin/diagnostic_admin.php',
    'admin/assistant' => 'Outils/admin/Assistant.php',
];

// Chercher la route correspondante
if (array_key_exists($route, $routes)) {
    $file = $routes[$route];
    
    // Vérifier que le fichier existe
    if (file_exists($file)) {
        // Inclure le fichier cible
        try {
            require $file;
        } catch (Throwable $e) {
            http_response_code(500);
            error_log("[Router] Error loading $file: " . $e->getMessage());
            echo "Error: " . $e->getMessage();
        }
        exit;
    } else {
        error_log("[Router] File not found: $file for route: $route");
    }
}

// Si la route n'existe pas, retourner 404
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page non trouvée - Drive Us</title>
    <link rel="stylesheet" href="/CSS/Outils/layout-global.css" />
    <link rel="stylesheet" href="/CSS/Outils/Header.css" />
    <link rel="icon" type="image/x-icon" href="/Image/Icone.ico">
    <style>
        .error-container {
            text-align: center;
            padding: 100px 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        .error-container h1 {
            font-size: 72px;
            color: #0066cc;
            margin-bottom: 20px;
        }
        .error-container p {
            font-size: 20px;
            margin-bottom: 30px;
        }
        .error-container a {
            display: inline-block;
            padding: 12px 30px;
            background: #0066cc;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .error-container a:hover {
            background: #0052a3;
        }
    </style>
</head>
<body>
    <?php include 'Outils/views/header.php'; ?>
    <div class="error-container">
        <h1>404</h1>
        <p>Désolé, la page que vous recherchez n'existe pas.</p>
        <a href="/">Retour à l'accueil</a>
    </div>
</body>
</html>

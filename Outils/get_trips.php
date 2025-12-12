<?php
// Configurer la sortie JSON
header('Content-Type: application/json; charset=utf-8');

// Activer affichage d'erreurs en JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    die(json_encode(['error' => "$errstr in $errfile:$errline"]));
});

try {
    // Session optionnelle
    @session_start();

    // Inclure config
    require __DIR__ . '/config.php';

    if(!$conn) {
        http_response_code(500);
        die(json_encode(['error' => 'Connexion mysqli non disponible']));
    }

    // Récupérer les paramètres
    $from = trim($_GET['from'] ?? '');
    $to = trim($_GET['to'] ?? '');
    $date = trim($_GET['date'] ?? '');
    $priceMax = (int)($_GET['priceMax'] ?? 9999);
    $seatsMin = (int)($_GET['seatsMin'] ?? 0);
    $timeBand = $_GET['timeBand'] ?? 'all';
    $sort = $_GET['sort'] ?? 'relevance';

    // Construire la requête SQL avec échappement
    $sql = "SELECT * FROM trajet WHERE statut = 'publie'";

    // Ajouter les filtres
    if($from !== ''){
        $from_escaped = $conn->real_escape_string(strtolower($from));
        $sql .= " AND LOWER(VilleDepart) LIKE '%$from_escaped%'";
    }

    if($to !== ''){
        $to_escaped = $conn->real_escape_string(strtolower($to));
        $sql .= " AND LOWER(VilleArrivee) LIKE '%$to_escaped%'";
    }

    if($date !== ''){
        $date_escaped = $conn->real_escape_string($date);
        $sql .= " AND DateDepart = '$date_escaped'";
    }

    $sql .= " AND Prix <= $priceMax AND nombre_places >= $seatsMin";

    // Filtres horaires
    if($timeBand === 'morning')   $sql .= " AND HOUR(heure) BETWEEN 6 AND 11";
    if($timeBand === 'afternoon') $sql .= " AND HOUR(heure) BETWEEN 12 AND 17";
    if($timeBand === 'evening')   $sql .= " AND HOUR(heure) BETWEEN 18 AND 23";

    // ======== FILTRES DE PRÉFÉRENCES (optionnels - filtre seulement si défini) ========

// Fumeur (filtre uniquement si le trajet a cette préférence renseignée)
if(isset($_GET['fumeur']) && $_GET['fumeur'] !== ''){
    $fumeur = $conn->real_escape_string($_GET['fumeur']);
    $sql .= " AND (fumeur IS NULL OR fumeur = '' OR fumeur = '$fumeur')";
}

// Animaux
if(isset($_GET['animaux']) && $_GET['animaux'] !== ''){
    $animaux = $conn->real_escape_string($_GET['animaux']);
    $sql .= " AND (animaux IS NULL OR animaux = '' OR animaux = '$animaux')";
}

// Enfant
if(isset($_GET['enfant']) && $_GET['enfant'] !== ''){
    $enfant = $conn->real_escape_string($_GET['enfant']);
    $sql .= " AND (enfant IS NULL OR enfant = '' OR enfant = '$enfant')";
}

// Bagage
if(isset($_GET['bagage']) && $_GET['bagage'] !== ''){
    $bagage = $conn->real_escape_string($_GET['bagage']);
    $sql .= " AND (bagage IS NULL OR bagage = '' OR bagage = '$bagage')";
}

// Genre conducteur (peut être une liste CSV envoyée depuis le client)
if(isset($_GET['genre']) && $_GET['genre'] !== ''){
    $raw = $_GET['genre'];
    $parts = array_filter(array_map('trim', explode(',', $raw)));
    $conds = [];
    $applyFilter = true;
    foreach($parts as $p){
        if(strcasecmp($p, 'Tous') === 0){
            // si 'Tous' demandé, on ignore le filtre
            $applyFilter = false;
            break;
        }
        $g = $conn->real_escape_string($p);
        // utiliser FIND_IN_SET pour les valeurs stockées en CSV dans la BDD
        $conds[] = "FIND_IN_SET('$g', genre) > 0";
    }
    if($applyFilter && !empty($conds)){
        $sql .= ' AND (genre IS NULL OR genre = "" OR (' . implode(' OR ', $conds) . '))';
    }
}

// Langue (filtre optionnel - CSV depuis le client)
if(isset($_GET['langue']) && $_GET['langue'] !== ''){
    $raw = $_GET['langue'];
    $parts = array_filter(array_map('trim', explode(',', $raw)));
    $conds = [];
    foreach($parts as $p){
        $l = $conn->real_escape_string($p);
        $conds[] = "FIND_IN_SET('$l', langue) > 0";
    }
    if(!empty($conds)){
        $sql .= ' AND (langue IS NULL OR langue = "" OR (' . implode(' OR ', $conds) . '))';
    }
}


    // Tri
    switch($sort){
        case 'priceAsc': $sql .= " ORDER BY Prix ASC"; break;
        case 'timeAsc':  $sql .= " ORDER BY heure ASC"; break;
        case 'durationAsc': $sql .= " ORDER BY duree_estimee ASC"; break;
        default: $sql .= " ORDER BY TrajetID DESC"; break;
    }

    // Exécuter la requête
    $result = $conn->query($sql);
    
    if(!$result){
        http_response_code(500);
        die(json_encode(['error' => 'Query error: '.$conn->error]));
    }

    // Récupérer les résultats
    $rows = [];

    // Préparer une requête pour récupérer prenom, email et photo du conducteur
    $stmtUser = $conn->prepare("SELECT Prenom, PhotoProfil, Mail FROM user WHERE UserID = ?");

    while($r = $result->fetch_assoc()){
        $driverId = $r['ConducteurID'] ?? null;

        // valeurs par défaut
        $driverName = $driverId ? 'Conducteur #' . $driverId : 'Conducteur inconnu';
        $driverPhoto = "Image/default.png";

        $driverEmail = null;
        if($driverId && $stmtUser){
            $stmtUser->bind_param('i', $driverId);
            if($stmtUser->execute()){
                $stmtUser->bind_result($prenom, $photo, $email);
                if($stmtUser->fetch()){
                    if(!empty($prenom)) $driverName = $prenom;
                    if(!empty($photo)) $driverPhoto = 'Image_Profil/'.$photo;
                    if(!empty($email)) $driverEmail = $email;
                }
                // reset result for next fetch
                $stmtUser->free_result();
            }
        }

        // Traiter les arrêts supplémentaires (string CSV → array)
        $stops = [];
        if(!empty($r['arrets_supplementaires'])){
            $stops = array_filter(array_map('trim', explode(',', $r['arrets_supplementaires'])));
        }

        $row = [
            'id' => $r['TrajetID'] ?? null,
            'from' => $r['VilleDepart'] ?? '',
            'to' => $r['VilleArrivee'] ?? '',
            'date' => $r['DateDepart'] ?? '',
            'depart' => $r['heure'] ?? '',
            'durationMin' => isset($r['duree_estimee']) ? (int)$r['duree_estimee'] : 0,
            'price' => isset($r['Prix']) ? (float)$r['Prix'] : 0,
            'seats' => isset($r['nombre_places']) ? (int)$r['nombre_places'] : 0,
            'rating' => 4.5,
            'driver' => $driverName,
            'driverPhoto' => $driverPhoto,
            'driverEmail' => $driverEmail,
            'vehicle' => 'Voiture',
            'notes' => $r['Description'] ?? '',
            // Champs de préférences
            'bagage' => $r['bagage'] ?? null,
            'fumeur' => $r['fumeur'] ?? null,
            'animaux' => $r['animaux'] ?? null,
            'enfant' => $r['enfant'] ?? null,
            'genre' => $r['genre'] ?? null,
            'langue' => $r['langue'] ?? null,
            // Arrêts intermédiaires
            'arrets_supplementaires' => $stops
        ];
        $rows[] = $row;
    }

    if($stmtUser) $stmtUser->close();

    // Retourner les résultats
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Exception: '.$e->getMessage()]);
}

restore_error_handler();

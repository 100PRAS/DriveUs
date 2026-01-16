<?php
// Configurer la sortie JSON
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Activer affichage d'erreurs en JSON

    // Session optionnelle
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Inclure config depuis Outils/config
    require __DIR__ . '/../config/config.php';

    // Suppression de la vérification $conn, on utilise uniquement $pdo
        if(!$pdo) {
            http_response_code(500);
            die(json_encode(['error' => 'Connexion PDO non disponible']));
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
    $sql = "SELECT 
        trajet.*,
        user.Prenom as conductor_first_name,
        user.Nom as conductor_last_name,
        user.PhotoProfil as conductor_photo,
        user.Mail as conductor_email,
        uv.heating AS vehicle_heating,
        uv.ac AS vehicle_ac
    FROM trajet
    INNER JOIN user ON trajet.ConducteurID = user.UserID
    LEFT JOIN user_vehicles uv ON (trajet.Vid = uv.Vid OR trajet.Vid = uv.id)
    WHERE trajet.statut = 'publie'";

    // Ajouter les filtres
    // ...existing code...
        $params = [];
        if($from !== ''){
            $sql .= " AND (LOWER(VilleDepart) LIKE :from OR LOWER(arrets_supplementaires) LIKE :from)";
            $params['from'] = '%' . strtolower($from) . '%';
        }

    // ...existing code...
        if($to !== ''){
            $sql .= " AND LOWER(VilleArrivee) LIKE :to";
            $params['to'] = '%' . strtolower($to) . '%';
        }

    // ...existing code...
        if($date !== ''){
            $sql .= " AND DateDepart = :date";
            $params['date'] = $date;
        }

    // ...existing code...
        $sql .= " AND Prix <= :priceMax";
        $params['priceMax'] = $priceMax;
        $sql .= " AND nombre_places >= :seatsMin";
        $params['seatsMin'] = $seatsMin;

    // Filtres horaires
    if($timeBand === 'morning')   $sql .= " AND HOUR(heure) BETWEEN 6 AND 11";
    if($timeBand === 'afternoon') $sql .= " AND HOUR(heure) BETWEEN 12 AND 17";
    if($timeBand === 'evening')   $sql .= " AND (HOUR(heure) BETWEEN 18 AND 23 OR HOUR(heure) BETWEEN 0 AND 5)";

    // ======== FILTRES DE PRÉFÉRENCES (optionnels - filtre seulement si défini) ========

// Fumeur (filtre uniquement si le trajet a cette préférence renseignée)
if(isset($_GET['fumeur']) && $_GET['fumeur'] !== ''){
    $fumeur = $_GET['fumeur'];
    $sql .= " AND (fumeur IS NULL OR fumeur = '' OR fumeur = :fumeur)";
    $params['fumeur'] = $fumeur;
}

// Animaux
if(isset($_GET['animaux']) && $_GET['animaux'] !== ''){
    $animaux = $_GET['animaux'];
    $sql .= " AND (animaux IS NULL OR animaux = '' OR animaux = :animaux)";
    $params['animaux'] = $animaux;
}

// Enfant
if(isset($_GET['enfant']) && $_GET['enfant'] !== ''){
    $enfant = $_GET['enfant'];
    $sql .= " AND (enfant IS NULL OR enfant = '' OR enfant = :enfant)";
    $params['enfant'] = $enfant;
}

// Bagage
if(isset($_GET['bagage']) && $_GET['bagage'] !== ''){
    $bagage = $_GET['bagage'];
    $sql .= " AND (bagage IS NULL OR bagage = '' OR bagage = :bagage)";
    $params['bagage'] = $bagage;
}

// Genre conducteur (peut être une liste CSV envoyée depuis le client)
if(isset($_GET['genre']) && $_GET['genre'] !== ''){
    $raw = $_GET['genre'];
    $parts = array_filter(array_map('trim', explode(',', $raw)));
    $conds = [];
    foreach($parts as $i => $p){
        $g = strtolower($p);
        $conds[] = "(LOWER(genre) LIKE :genre_$i)";
        $params["genre_$i"] = "%$g%";
    }
    if(!empty($conds)){
        $sql .= ' AND (' . implode(' OR ', $conds) . ')';
    }
}

// Langue (filtre optionnel - CSV depuis le client)
if(isset($_GET['langue']) && $_GET['langue'] !== ''){
    $raw = $_GET['langue'];
    $parts = array_filter(array_map('trim', explode(',', $raw)));
    $conds = [];
    foreach($parts as $i => $p){
        $l = $p;
        $conds[] = "FIND_IN_SET(:langue_$i, langue) > 0";
        $params["langue_$i"] = $l;
    }
    if(!empty($conds)){
        $sql .= ' AND (langue IS NULL OR langue = "" OR (' . implode(' OR ', $conds) . '))';
    }
}

// Chauffage (option véhicule)
if(isset($_GET['heating']) && $_GET['heating'] === '1'){
    $sql .= " AND uv.heating = 1";
}

// Climatisation (option véhicule)
if(isset($_GET['ac']) && $_GET['ac'] === '1'){
    $sql .= " AND uv.ac = 1";
}


    // Tri
    switch($sort){
        case 'priceAsc': $sql .= " ORDER BY Prix ASC"; break;
        case 'timeAsc':  $sql .= " ORDER BY heure ASC"; break;
        case 'durationAsc': $sql .= " ORDER BY duree_estimee ASC"; break;
        default: $sql .= " ORDER BY TrajetID DESC"; break;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        echo json_encode($trajets);
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Erreur SQL', 'message' => $e->getMessage(), 'sql' => $sql]);
    }




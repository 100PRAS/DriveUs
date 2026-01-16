<?php

require_once 'Outils/config/langue.php';


// Connexion BDD centralisée (Clever Cloud)
require_once __DIR__ . '/Outils/config/config.php';
require_once __DIR__ . '/Outils/mail/GmailSender.php';

// Pré-remplissage si modification d'un trajet existant
$trajet_a_modifier = null;
if (isset($_GET['trajet_id'])) {
  $trajet_id = (int)$_GET['trajet_id'];
  $stmt = $pdo->prepare("SELECT * FROM trajet WHERE TrajetID = ? AND ConducteurID = ? LIMIT 1");
  $stmt->execute([$trajet_id, $_SESSION['UserID'] ?? 0]);
  $trajet_a_modifier = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$trajet_a_modifier) {
    die("Trajet introuvable ou accès refusé.");
  }
}



// Vérifier si l'utilisateur est connecté via session ou cookie
if (!isset($_SESSION['UserID']) && isset($_COOKIE['UserID'])) {
    $_SESSION['UserID'] = $_COOKIE['UserID'];
}

$user = null;
if(isset($_SESSION['UserID'])){
  $stmt = $pdo->prepare("SELECT * FROM user WHERE UserID = ?");
  $stmt->execute([$_SESSION['UserID']]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Chemin de la photo de profil (défaut si absent)
$photoPath = (!empty($user['PhotoProfil'])) ? 'Image_Profil/' . htmlspecialchars($user['PhotoProfil']) : 'Image/default.png';

// Définir le rôle maintenant que $user est récupéré
$user_role = $user['role'] ?? 'passager'; // par défaut passager

// Si le rôle est conducteur, il peut publier un trajet
$peutPublier = ($user_role === 'conducteur');

// Fonction pour valider l'ordre des arrêts
function validateStopsOrder($stops) {
    // Récupérer les coordonnées pour chaque ville
    $coordinates = [];
    
    foreach ($stops as $city) {
        $coord = getCoordinates($city);
        if ($coord === null) {
            return "Erreur: Ville '{$city}' non trouvée. Vérifiez l'orthographe.";
        }
        $coordinates[] = $coord;
    }
    
    // Vérifier que chaque arrêt est sur le chemin entre le précédent et le suivant
    for ($i = 1; $i < count($stops) - 1; $i++) {
        $prev = $coordinates[$i - 1];
        $current = $coordinates[$i];
        $next = $coordinates[$i + 1];
        
        // Calculer les distances
        $dist_prev_current = haversineDistance($prev['lat'], $prev['lon'], $current['lat'], $current['lon']);
        $dist_current_next = haversineDistance($current['lat'], $current['lon'], $next['lat'], $next['lon']);
        $dist_prev_next = haversineDistance($prev['lat'], $prev['lon'], $next['lat'], $next['lon']);
        
        // Vérifier que le détour n'est pas trop important (max 50% de déviation)
        $total_with_stop = $dist_prev_current + $dist_current_next;
        $direct = $dist_prev_next;
        
        if ($total_with_stop > $direct * 1.5) {
            return "Erreur: L'ordre des arrêts semble incorrect. '{$stops[$i]}' n'est pas sur le chemin entre '{$stops[$i-1]}' et '{$stops[$i+1]}'.";
        }
    }
    
    return null; // Pas d'erreur
}

// Fonction pour obtenir les coordonnées d'une ville
function getCoordinates($city) {
    global $pdo;
    
    // Chercher dans la BDD
    $stmt = $pdo->prepare("SELECT ville_latitude_deg AS lat, ville_longitude_deg AS lon FROM villes_france_free WHERE LOWER(ville_nom) = LOWER(?) LIMIT 1");
    $stmt->execute([$city]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && !empty($result['lat']) && !empty($result['lon'])) {
        return [
            'lat' => floatval($result['lat']),
            'lon' => floatval($result['lon']),
            'name' => $city
        ];
    }
    
    return null;
}

// Fonction pour calculer la distance entre deux points (formule Haversine)
function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; // km
    
    $lat1_rad = deg2rad($lat1);
    $lat2_rad = deg2rad($lat2);
    $delta_lat = deg2rad($lat2 - $lat1);
    $delta_lon = deg2rad($lon2 - $lon1);
    
    $a = sin($delta_lat / 2) * sin($delta_lat / 2) +
         cos($lat1_rad) * cos($lat2_rad) *
         sin($delta_lon / 2) * sin($delta_lon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earth_radius * $c;
}

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if ($user_role !== 'conducteur') {
        exit; 
    }

    // Récupération des données du formulaire
    $depart = trim($_POST['depart'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $heure = trim($_POST['heure'] ?? '');
    $places = intval($_POST['places'] ?? 0);
    $prix = floatval($_POST['prix'] ?? 0);
    $description = trim($_POST['notes'] ?? '');
    $point_rencontre = trim($_POST['rencontre'] ?? '');
    $age_min = intval($_POST['age_min'] ?? 18);
    $age_max = intval($_POST['age_max'] ?? 99);
    
    // Validation: âge minimum doit être >= 18
    if ($age_min < 18) {
        echo "<script>alert('L\\'âge minimum doit être au moins 18 ans'); window.history.back();</script>";
        exit;
    }
    
    if ($age_max < 18) {
        echo "<script>alert('L\\'âge maximum doit être au moins 18 ans'); window.history.back();</script>";
        exit;
    }
    
    // Durée estimée (convertir time HH:MM en minutes)
    $duree = trim($_POST['duree'] ?? '');
    $duree_estimee = 0;
    if (!empty($duree) && strpos($duree, ':') !== false) {
        list($h, $m) = explode(':', $duree);
        $duree_estimee = (intval($h) * 60) + intval($m);
    }
    
    // Enregistrer = 1 si checkbox coché, 0 sinon
    $enregistrer = isset($_POST['enregistrer']) ? 1 : 0;
    
    // Statut : brouillon ou publié selon le bouton cliqué
    $statut = (isset($_POST['action']) && $_POST['action'] === 'brouillon') ? 'brouillon' : 'publie';
    
    // Récupérer l'ID utilisateur depuis la base de données
    $conducteur_id = $user['UserID'] ?? null;
    
    if (!$conducteur_id) {
        die("Erreur: utilisateur non identifié");
    }

    // Traitement des préférences
    $bagage = $_POST['bagage'] ?? null;
    $fumer = $_POST['fumeur'] ?? null;
    $animaux = $_POST['animaux'] ?? null;
    $enfants = $_POST['enfant'] ?? null;
    // Traitement du genre (checkboxes multiples)
    $genres = array();
    if (isset($_POST['genre']) && is_array($_POST['genre'])) {
        $genres = $_POST['genre'];
    }
    $genrePreference = !empty($genres) ? implode(', ', $genres) : null;
    
    // Traitement de la langue
    $langues = array();
    if (isset($_POST['langue']) && is_array($_POST['langue'])) {
        $langues = array_map('trim', $_POST['langue']);
    }
    $langue = !empty($langues) ? implode(', ', $langues) : null;
    
    // Traitement des arrêts supplémentaires
    $arrets_supplementaires = null;
    if (isset($_POST['stops']) && is_array($_POST['stops'])) {
        $stops_filtered = array_filter($_POST['stops'], function($stop) {
            return !empty(trim($stop));
        });
        if (!empty($stops_filtered)) {
            // Vérifier que les arrêts ne sont pas vides et ne sont pas identiques au départ/arrivée
            $cleaned_stops = array_map('trim', $stops_filtered);
            
            foreach ($cleaned_stops as $stop) {
                // Vérifier qu'aucun arrêt n'est identique à la ville de départ ou d'arrivée
                if (strtolower($stop) === strtolower($depart) || strtolower($stop) === strtolower($destination)) {
                    echo "<script>alert('Erreur: Un arrêt ne peut pas être identique à la ville de départ ou d\\'arrivée'); window.history.back();</script>";
                    exit;
                }
            }
            
            // Validation géographique: vérifier que l'ordre des arrêts est cohérent
            $all_stops = array_merge([$depart], $cleaned_stops, [$destination]);
            $validation_error = validateStopsOrder($all_stops);
            
            if ($validation_error) {
                echo "<script>alert('$validation_error'); window.history.back();</script>";
                exit;
            }
            
            $arrets_supplementaires = implode(', ', $cleaned_stops);
        }
    }
    
    // Arrêts volontaires
    $arrets_volontaires = isset($_POST['arrets_volontaires']) ? 1 : 0;
    
    // Récupérer l'ID du véhicule sélectionné
    $vehicle_id = !empty($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : null;

    // Insertion en base de données

    $stmt = $pdo->prepare("
      INSERT INTO trajet (VilleDepart, VilleArrivee, DateDepart, heure, nombre_places, Prix, ConducteurID, Description, point_rencontre, duree_estimee, age_min, age_max, enregistrer, bagage, fumeur, animaux, enfant, genre, langue, arrets_supplementaires, arrets_volontaires, statut, Vid)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $success = $stmt->execute([
      $depart,
      $destination,
      $date,
      $heure,
      $places,
      $prix,
      $conducteur_id,
      $description,
      $point_rencontre,
      $duree_estimee,
      $age_min,
      $age_max,
      $enregistrer,
      $bagage,
      $fumer,
      $animaux,
      $enfants,
      $genrePreference,
      $langue,
      $arrets_supplementaires,
      $arrets_volontaires,
      $statut,
      $vehicle_id
    ]);
// Fermer la connexion à la fin du script
$pdo = null;

    if ($success) {
    // Email de confirmation de publication
    try {
      if (!empty($user['Mail'])) {
        $gmail = new GmailSender();
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dashboardLink = $scheme . '://' . $host . '/Tableau_de_Bord_Admin.php';
        $subject = 'Trajet publié avec succès';
        $html = "<html><body style='font-family:Arial,sans-serif;'>"
          . "<h2 style='color:#4c51bf;'>Votre trajet est en ligne</h2>"
          . "<p>Bonjour " . htmlspecialchars($user['Prenom'] ?? '') . ",</p>"
          . "<p>Votre trajet " . htmlspecialchars($depart) . " → " . htmlspecialchars($destination) . " du " . htmlspecialchars($date) . " à " . htmlspecialchars($heure) . " est publié.</p>"
          . "<p><a href='" . $dashboardLink . "' style='padding:10px 16px;background:#4c51bf;color:white;text-decoration:none;border-radius:6px;'>Voir mes trajets</a></p>"
          . "</body></html>";
        $gmail->send($user['Mail'], $subject, $html);
      }
    } catch (Exception $e) {
      error_log('Email publication trajet échoué: ' . $e->getMessage());
    }
        header("Location: Publier_un_trajet.php?success=1");
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Drive Us — Publier un trajet</title>
  <link rel="icon" type="image/x-icon" href="/Image/Icone.ico">
  <link rel="stylesheet" href="/CSS/Outils/layout-global.css" />
  <link rel="stylesheet" href="/CSS/Publier_un_trajet.css" />
    <link rel="stylesheet" href="/CSS/Outils/Header.css" />
    <link rel="stylesheet" href="/CSS/Outils/responsive.css" />
        <link rel="stylesheet" href="/CSS/Outils/Footer.css" />
    <link rel="stylesheet" href="/CSS/Sombre/Sombre_Header.css" />



  <link rel="stylesheet" href="/CSS/Outils/section-accordion.css" />
  <link rel="stylesheet" href="/CSS/Sombre/Sombre_Publier.css" />
  <script src="/JS/Sombre.js"></script>
  <script src="/JS/section-accordion.js"></script>
 
</head>
<body>
  <?php include 'Outils/views/header.php'; ?>

  <main>
    <section class="hero">
      <div class="container hero-inner">

        <div class="hero-copy">
          <h1>Publiez votre trajet en quelques clics</h1>
          <p>
            Indiquez votre départ, votre destination et vos préférences.
            <strong>Partagez les frais</strong>, rencontrez des passagers et roulez ensemble.
          </p>

          <div class="hero-actions">
            <a class="btn btn-primary" href="#form-publier">Commencer</a>
            <a class="btn btn-outline" href="/trouver-trajet">Rechercher un trajet</a>
          </div>
        </div>

        <!-- Illustration simple en SVG intégrée pour rester autonome -->
        <div class="hero-illu" aria-hidden="true">
          <defs>
            <linearGradient id="g1" x1="0" x2="1">
              <stop offset="0" stop-color="#E8F3FF"/>
              <stop offset="1" stop-color="#DDF0FF"/>
            </linearGradient>
          </defs>
          <rect x="0" y="0" width="400" height="240" fill="url(#g1)"/>
          <ellipse cx="200" cy="190" rx="170" ry="20" fill="#CDE3F9"/>
          <g>
            <rect x="80" y="100" rx="18" ry="18" width="240" height="70" fill="#1f6fe5"/>
            <path d="M90 120 C120 90, 280 90, 310 120" fill="#1f6fe5"/>
            <rect x="140" y="118" width="55" height="20" rx="4" fill="#ffffff"/>
            <rect x="205" y="118" width="55" height="20" rx="4" fill="#ffffff"/>
            <circle cx="140" cy="170" r="18" fill="#0e3a8a"/>
            <circle cx="260" cy="170" r="18" fill="#0e3a8a"/>
            <circle cx="140" cy="170" r="9" fill="#fff"/>
            <circle cx="260" cy="170" r="9" fill="#fff"/>
          </g>
        </div>
      </div>
    </section>

    <!-- Formulaire -->
    <div id="form-publier" class="container">

    <form action="/publier-trajet" method="post" novalidate>
      <!-- Accordéon 1: Informations du trajet -->
      <div class="section-accordion">
        <button type="button" class="section-accordion-header active">📍 Informations du trajet</button>
        <section class="section-accordion-content open card">

        <div class="grid grid-2">
          <div class="field">
            <label for="depart">Lieu de départ</label>
            <input id="depart" name="depart" type="text" placeholder="Ville, adresse ou point de rencontre" list="villes" required value="<?= htmlspecialchars($trajet_a_modifier['VilleDepart'] ?? '') ?>" />
          </div>

          <div class="field">
            <label for="destination">Destination</label>
            <input id="destination" name="destination" type="text" placeholder="Ville ou adresse d'arrivée" list="villes" required value="<?= htmlspecialchars($trajet_a_modifier['VilleArrivee'] ?? '') ?>" />
          </div>
            <datalist id="villes">
                        <?php
                            $villes = $pdo->query("SELECT ville_nom FROM villes_france_free ORDER BY ville_nom");
                            $codes = $pdo->query("SELECT ville_code_postal FROM villes_france_free ORDER BY ville_code_postal");

                            foreach($villes as $v){
                                echo "<option value='".htmlspecialchars($v['ville_nom'])."'>";
                            }
                            foreach($codes as $c){
                                echo "<option value='".htmlspecialchars($c['ville_code_postal'])."'>";
                            }
                        ?>
                    </datalist>

          <!-- Arrêts intermédiaires -->
          <div class="field" style="grid-column: 1 / -1;">
            <label>Arrêts intermédiaires (optionnel)</label>
            <p >Ajoutez des villes où vous pouvez récupérer ou déposer des passagers</p>
            <div id="stopsContainer">
            <?php
            if (!empty($trajet_a_modifier['arrets_supplementaires'])) {
              $arrets = array_map('trim', explode(',', $trajet_a_modifier['arrets_supplementaires']));
              foreach ($arrets as $i => $arret) {
                $id = $i + 1;
                echo "<div id='stop-$id' style='display: flex; gap: 0.5rem; margin-bottom: 0.75rem; align-items: flex-end;'>";
                echo "<div style='flex: 1;'><input type='text' name='stops[]' value='" . htmlspecialchars($arret) . "' placeholder='Ville ou adresse' list='villes' style='width: 100%; padding: 0.5rem; border: 1px solid var(--border); border-radius: var(--radius);' /></div>";
                echo "<button type='button' onclick='removeStop($id)' class='btn btn-outline' style='padding: 0.5rem 1rem;'>✕ Supprimer</button>";
                echo "</div>";
              }
            }
            ?>
            </div>
            <button type="button" onclick="addStop()" class="btn btn-outline" style="margin-top: 0.5rem;">+ Ajouter un arrêt</button>
          </div>

          <!-- Option arrêts volontaires -->

          <div class="field">
            <label class="choice">
              <input type="checkbox" name="arrets_volontaires" value="1" id="arretsVolCheckbox" <?= !empty($trajet_a_modifier) && $trajet_a_modifier['arrets_volontaires'] ? 'checked' : '' ?> />
              <span id="arretsVolLabel">Les arrêts sont volontaires (le conducteur peut les sauter)</span>
            </label>
          </div>
          <script>
// Désactiver la checkbox "arrets volontaires" si aucun arrêt intermédiaire
function updateArretsVolCheckbox() {
  const stopsInputs = document.querySelectorAll('input[name="stops[]"]');
  const hasStops = Array.from(stopsInputs).some(input => input.value.trim().length > 0);
  const arretsVolCheckbox = document.getElementById('arretsVolCheckbox');
  const arretsVolLabel = document.getElementById('arretsVolLabel');
  if (arretsVolCheckbox) {
    arretsVolCheckbox.disabled = !hasStops;
    arretsVolLabel.style.color = hasStops ? '' : '#aaa';
  }
}

document.addEventListener('DOMContentLoaded', function() {
  updateArretsVolCheckbox();
  document.getElementById('stopsContainer').addEventListener('input', updateArretsVolCheckbox);
});
// Appeler aussi après ajout/suppression d'arrêt
function addStop() {
  stopCount++;
  const container = document.getElementById('stopsContainer');
  const stopDiv = document.createElement('div');
  stopDiv.id = `stop-${stopCount}`;
  stopDiv.style.cssText = 'display: flex; gap: 0.5rem; margin-bottom: 0.75rem; align-items: flex-end;';
  stopDiv.innerHTML = `
    <div style="flex: 1;">
      <input type="text" name="stops[]" placeholder="Ville ou adresse" list="villes" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border); border-radius: var(--radius);" />
    </div>
    <button type="button" onclick="removeStop(${stopCount})" class="btn btn-outline" style="padding: 0.5rem 1rem;">✕ Supprimer</button>
  `;
  container.appendChild(stopDiv);
  updateArretsVolCheckbox();
}
function removeStop(stopId) {
  const stopDiv = document.getElementById(`stop-${stopId}`);
  if (stopDiv) {
    stopDiv.remove();
    updateArretsVolCheckbox();
  }
}
</script>
          <div class="field">
            <label for="date">Date</label>
            <input id="date" name="date" type="date" required min="" value="<?= htmlspecialchars($trajet_a_modifier['DateDepart'] ?? '') ?>" />
          </div>

          <div class="field">
            <label for="heure">Heure de départ</label>
            <input id="heure" name="heure" type="time" required value="<?= htmlspecialchars($trajet_a_modifier['heure'] ?? '') ?>" />
          </div>

          <div class="field">
            <label for="places">Places disponibles</label>
            <input id="places" name="places" type="number" min="1" max="8"  required value="<?= htmlspecialchars($trajet_a_modifier['nombre_places'] ?? '') ?>" />
          </div>

          <div class="field">
            <label for="prix">Montant (€)</label>
            <input id="prix" name="prix" type="number" min="0" step="0.5" placeholder="ex. 8,00" required value="<?= htmlspecialchars($trajet_a_modifier['Prix'] ?? '') ?>" />
          </div>

          <div class="field">
            <label for="rencontre">Point de rencontre (optionnel)</label>
            <input id="rencontre" name="rencontre" type="text" placeholder="Gare centrale, entrée nord…" value="<?= htmlspecialchars($trajet_a_modifier['point_rencontre'] ?? '') ?>" />
          </div>

          <div class="field">
            <label for="duree">Durée estimée (optionnel)</label>
            <input id="duree" name="duree" type="time" placeholder="ex. 1h45" value="<?= isset($trajet_a_modifier['duree_estimee']) ? sprintf('%02d:%02d', floor($trajet_a_modifier['duree_estimee']/60), $trajet_a_modifier['duree_estimee']%60) : '' ?>" />
          </div>
        </div>
        </section>
      </div>

      <!-- Accordéon 2: Préférences -->
      <div class="section-accordion">
        <button type="button" class="section-accordion-header">❤️ Préférences</button>
        <section class="section-accordion-content card">

        <div class="grid grid-3">
          <div class="field">
            <div class="label">Bagages</div>
            <label class="choice"><input type="radio" name="bagage" value="petit" <?= (!empty($trajet_a_modifier) && $trajet_a_modifier['bagage']==='petit') ? 'checked' : '' ?> /> Petit sac</label>
            <label class="choice"><input type="radio" name="bagage" value="moyen" <?= (!empty($trajet_a_modifier) && $trajet_a_modifier['bagage']==='moyen') ? 'checked' : '' ?> /> Moyen</label>
            <label class="choice"><input type="radio" name="bagage" value="grand" <?= (!empty($trajet_a_modifier) && $trajet_a_modifier['bagage']==='grand') ? 'checked' : '' ?> /> Grand</label>
          </div>

          <div class="field">
            <div class="label">Fumeur</div>
            <label class="choice"><input type="radio" name="fumeur" value="non" <?= (!empty($trajet_a_modifier) && $trajet_a_modifier['fumeur']==='non') ? 'checked' : '' ?> /> Non-fumeur</label>
            <label class="choice"><input type="radio" name="fumeur" value="oui" <?= (!empty($trajet_a_modifier) && $trajet_a_modifier['fumeur']==='oui') ? 'checked' : '' ?> /> Fumeur</label>
          </div>

          <div class="field">
            <div class="label">Animaux</div>
            <label class="choice"><input type="radio" name="animaux" value="non" <?= (!empty($trajet_a_modifier) && $trajet_a_modifier['animaux']==='non') ? 'checked' : '' ?> /> Non</label>
            <label class="choice"><input type="radio" name="animaux" value="oui" <?= (!empty($trajet_a_modifier) && $trajet_a_modifier['animaux']==='oui') ? 'checked' : '' ?> /> Oui</label>
          </div>

          <div class="field">
            <div class="label">Enfant autorisé</div>
            <label class="choice"><input type="radio" name="enfant" value="oui" <?= (!empty($trajet_a_modifier) && $trajet_a_modifier['enfant']==='oui') ? 'checked' : '' ?> /> Oui</label>
            <label class="choice"><input type="radio" name="enfant" value="non" <?= (!empty($trajet_a_modifier) && $trajet_a_modifier['enfant']==='non') ? 'checked' : '' ?> /> Non</label>
          </div>

          <div class="field">
            <label for="age_min">Âge minimum</label>
            <input type="number" id="age_min" name="age_min" min="18" max="120" value="<?= htmlspecialchars($trajet_a_modifier['age_min'] ?? '18') ?>" >
          </div>

          <div class="field">
            <label for="age_max">Âge maximum</label>
            <input type="number" id="age_max" name="age_max" min="18" max="120" value="<?= htmlspecialchars($trajet_a_modifier['age_max'] ?? '99') ?>" >
          </div>
        </div>

        <div class="grid grid-3">
          <div class="field">
            <div class="label">Genre accepté</div>
            <label class="choice"><input type="checkbox" name="genre[]" value="Homme"  /> Homme</label>
            <label class="choice"><input type="checkbox" name="genre[]" value="Femme" /> Femme</label>
            <label class="choice"><input type="checkbox" name="genre[]" value="Autre" /> Autre</label>
            <label class="choice"><input type="checkbox" name="genre[]" value="Tous" /> Tous</label>
          </div>

          <div class="field">
            <div class="label">Langue parlée</div>
            <label class="choice"><input type="checkbox" name="langue[]" value="Français" /> Français</label>
            <label class="choice"><input type="checkbox" name="langue[]" value="Anglais" /> Anglais</label>
            <label class="choice"><input type="checkbox" name="langue[]" value="Autre" /> Autre</label>
          </div>
        </div>

        <p class="age-error" id="ageError" aria-live="polite" style="display:none;color:red;font-size:0.9rem;">
          L'âge minimum doit être inférieur ou égal à l'âge maximum.
        </p>

        <div class="field">
          <label for="notes">Commentaire pour les passagers (optionnel)</label>
          <textarea id="notes" name="notes" rows="4" placeholder="Ex. pause sur la route, musique OK, timing flexible…"><?php if(!empty($trajet_a_modifier)) echo htmlspecialchars($trajet_a_modifier['Description']); ?></textarea>
        </div>

        <label><input name="enregistrer" type="checkbox" id="enregistrer"/> Enregistrer pour les prochains trajets</label>
        </section>
      </div>

      <!-- Accordéon 3: Véhicule & contact -->
      <div class="section-accordion">
        <button type="button" class="section-accordion-header">🚗 Véhicule & contact</button>
        <section class="section-accordion-content card">


    <datalist id="voiture">
    <?php
      // Vehicules rattaches a l'utilisateur courant
      $voitures = [];
      if (!empty($_SESSION['UserID'])) {
        $stmtVeh = $pdo->prepare("SELECT Vid, model AS Modele, plate AS Plaque FROM user_vehicles WHERE UserID = ? ORDER BY created_at DESC");
        $stmtVeh->execute([$_SESSION['UserID']]);
        $voitures = $stmtVeh->fetchAll(PDO::FETCH_ASSOC);
      }

      foreach ($voitures as $voiture) {
        $val = htmlspecialchars($voiture['Modele'] . " — " . $voiture['Plaque']);
        echo "<option value='" . $val . "' data-vehicle-id='" . $voiture['id'] . "'>";
      }
    ?>
    </datalist>



        <div class="grid grid-3">
          <div class="field">
            <label for="vehicule"> Véhicule </label>
            <input id="vehicule" name="vehicule" type="text" list="voiture"placeholder="Peugeot 208, bleu" />
            <input type="hidden" id="vehicle_id" name="vehicle_id" value="" />
            <div style="margin-top:8px;">
              <a href="Profil.php?tab=vehicule" class="btn btn-outline" style="padding:8px 12px;">+ Nouveau véhicule</a>
            </div>
          </div>

          <div class="field">
            <label for="immat"> Immatriculation </label>
            <input id="immat" name="immat" type="text" placeholder="AB-123-CD" readonly />
          </div>
<script>
// Création d'objets JS pour associer modèle → plaque et modèle → ID
const voitures = {
    <?php
    foreach($voitures as $v){
        $modele = addslashes($v['Modele']);
        $plaque = addslashes($v['Plaque']);
        echo "'$modele':'$plaque',";
    }
    ?>
};

const voituresIds = {
    <?php
    foreach($voitures as $v){
        $modele = addslashes($v['Modele']);
        $id = $v['id'];
        echo "'$modele':$id,";
    }
    ?>
};

// Fonction pour extraire le modèle depuis une valeur "Modele — Plaque" ou juste "Modele"
function extractModele(valeur) {
  if (valeur.includes('—')) {
    return valeur.split('—')[0].trim();
  }
  return valeur.trim();
}

// Fonction pour extraire la plaque depuis une valeur "Modele — Plaque"
function extractPlaque(valeur) {
  if (valeur.includes('—')) {
    return valeur.split('—')[1]?.trim() || '';
  }
  return '';
}

document.getElementById('vehicule').addEventListener('input', function(){
  const valeur = this.value;
  const immatInput = document.getElementById('immat');
  const vehicleIdInput = document.getElementById('vehicle_id');
  let modele = extractModele(valeur);
  let plaque = extractPlaque(valeur);

  // Si plaque présente dans la saisie, l'utiliser, sinon chercher dans l'objet voitures
  if (plaque) {
    immatInput.value = plaque;
    this.value = modele;
    // Chercher l'ID du véhicule
    if (voituresIds[modele]) {
      vehicleIdInput.value = voituresIds[modele];
    }
  } else if (voitures[modele]) {
    immatInput.value = voitures[modele];
    vehicleIdInput.value = voituresIds[modele] || '';
  } else {
    immatInput.value = '';
    vehicleIdInput.value = '';
  }
});
</script>
          <div class="field">
            <label for="tel"> Téléphone </label>
            <input id="tel" name="tel" type="number" value="<?= htmlspecialchars($user['Numero'] ?? '') ?>" />
          </div>
        </div>
        </section>
      </div>

      <!-- Accord fermé, CGU et boutons d'action -->
      <label class="agree mt-12">
        <input type="checkbox" required />
        J'accepte les <a href="/cgu">conditions d'utilisation</a> de Drive Us.
      </label>
      <div class="actions" style="display: flex; gap: 1rem; margin-top: 24px; justify-content: flex-end;">
        <button type="submit" name="action" value="publier" class="Publier">Publier le trajet</button>
        <button type="submit" name="action" value="brouillon" class="enregistrer">Enregistrer brouillon</button>
        <button type="reset" class="btn">Effacer</button>
      </div>
    </form>


    </div>

<!-- Popup passager -->

<div id="popupOverlay" >
  <div id="popup" >
    <h2>Accès refusé</h2>
    <p>Vous êtes passager, vous ne pouvez pas publier de trajet.</p>
    <button onclick="window.location.href='/profil'">Devenir conducteur</button>
    <br><br>
    <button type="button" onclick="window.location.href='/'">Fermer</button>
  </div>
</div>
<script>
// Affiche le popup si l'utilisateur n'est pas conducteur
document.addEventListener('DOMContentLoaded', function() {
  var userRole = '<?= $user_role ?>';
  if (userRole !== 'conducteur') {
    document.getElementById('popupOverlay').style.display = 'flex';
    // Optionnel : désactiver le formulaire
    var form = document.querySelector('form');
    if (form) form.style.pointerEvents = 'none';
  }
});
</script>



  <!-- Comment ça marche -->
  <section class="how-it-works">
    <div class="container">
      <h2>Comment ça marche</h2>

      <div class="grid grid-3 steps">
        <article class="step">
          <div class="step-ico">🚗</div>
          <h3>Décrivez votre trajet</h3>
          <p>Indiquez votre lieu de départ, votre destination, la date, l’heure et le nombre de places disponibles.</p>
          <p class="muted">Partagez les informations importantes avec vos futurs passagers.</p>
        </article>

        <article class="step">
          <div class="step-ico">💬</div>
          <h3>Recevez des demandes</h3>
          <p>Les passagers peuvent consulter votre trajet et envoyer une demande de réservation.</p>
          <p class="muted">Vous recevez une notification et pouvez accepter ou refuser en un clic.</p>
        </article>

        <article class="step">
          <div class="step-ico">👥</div>
          <h3>Partez ensemble</h3>
          <p>Retrouvez vos passagers au point de rencontre convenu.</p>
          <p class="muted">Voyagez ensemble, partagez les frais et profitez d’un trajet convivial et économique.</p>
        </article>
      </div>
    </div>
  </section>
  </main>

  <footer class="site-footer">
    <div class="container">
    </div>
  </footer>




  <script>
const genreCheckboxes = document.querySelectorAll('input[name="genre[]"]');
const tousCheckbox = document.querySelector('input[name="genre[]"][value="Tous"]');

tousCheckbox.addEventListener('change', function() {
    if (this.checked) {
        // Cocher toutes les autres cases et les désactiver
        genreCheckboxes.forEach(cb => {
            cb.checked = true;
            if(cb !== tousCheckbox){
                cb.disabled = true;
            }
        });
    } else {
        // Décocher toutes les cases sauf "Tous" et les réactiver
        genreCheckboxes.forEach(cb => {
            if(cb !== tousCheckbox){
                cb.checked = false;
                cb.disabled = false;
            }
        });
    }
});

// Si on décoche manuellement une des cases, décocher "Tous"
genreCheckboxes.forEach(cb => {
    if(cb !== tousCheckbox){
        cb.addEventListener('change', function(){
            if(!this.checked){
                tousCheckbox.checked = false;
                genreCheckboxes.forEach(c => c.disabled = false);
            }
        });
    }
});


(function(){
  const ageMin = document.getElementById('age_min');
  const ageMax = document.getElementById('age_max');
  const error = document.getElementById('ageError');

  function validateAges(e){
    const min = parseInt(ageMin.value, 10) || 0;
    const max = parseInt(ageMax.value, 10) || 0;
    
    if(min < 18){
      error.textContent = "L'âge minimum doit être au moins 18 ans";
      error.style.display = 'block';
      if(e) e.preventDefault();
      return false;
    }
    
    if(max < 18){
      error.textContent = "L'âge maximum doit être au moins 18 ans";
      error.style.display = 'block';
      if(e) e.preventDefault();
      return false;
    }
    
    if(min > max){
      error.textContent = "L'âge minimum ne peut pas être supérieur à l'âge maximum";
      error.style.display = 'block';
      if(e) e.preventDefault();
      return false;
    } else {
      error.style.display = 'none';
      return true;
    }
  }

  // Validate on input
  ageMin.addEventListener('input', validateAges);
  ageMax.addEventListener('input', validateAges);

  // Validate on form submit (works for first enclosing form)
  const form = ageMin.closest('form');
  if(form){
    form.addEventListener('submit', function(e){
      if(!validateAges(e)){
        // focus on the offending field
        ageMin.focus();
      }
    });
  }
})();

// Gestion des arrêts intermédiaires (compteur utilisé par addStop/removeStop plus haut)
let stopCount = 0;

// Bloquer les dates antérieures à aujourd'hui
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().slice(0, 10);
    const dateInput = document.getElementById('date');
    if (dateInput) {
        dateInput.min = today;
    }
});

// Valider l'ordre des arrêts avant soumission
document.querySelector('form')?.addEventListener('submit', function(e) {
    const stopsInputs = document.querySelectorAll('input[name="stops[]"]');
    const stops = Array.from(stopsInputs)
        .map(input => input.value.trim())
        .filter(v => v.length > 0);
    
    if (stops.length > 0) {
        const depart = document.getElementById('depart')?.value.trim();
        const destination = document.getElementById('destination')?.value.trim();
        
        // Afficher les villes pour confirmation
        const itineraire = [depart, ...stops, destination].join(' → ');
        const confirm_order = confirm(
            'Veuillez vérifier l\'ordre de votre itinéraire:\n\n' + itineraire + 
            '\n\nCet ordre est-il correct?\n\n(Assurez-vous que chaque arrêt est sur le chemin entre les villes précédente et suivante)'
        );
        
        if (!confirm_order) {
            e.preventDefault();
        }
    }
    
    // Sauvegarder les préférences si "Enregistrer" est coché
    if (document.getElementById('enregistrer')?.checked) {
        savePreferences();
    }
});

/* ============================================
   SAUVEGARDER ET CHARGER LES PRÉFÉRENCES
   ============================================ */

function savePreferences() {
    const preferences = {
        bagage: document.querySelector('input[name="bagage"]:checked')?.value || '',
        fumeur: document.querySelector('input[name="fumeur"]:checked')?.value || '',
        animaux: document.querySelector('input[name="animaux"]:checked')?.value || '',
        enfant: document.querySelector('input[name="enfant"]:checked')?.value || '',
        age_min: document.getElementById('age_min')?.value || '18',
        age_max: document.getElementById('age_max')?.value || '99',
        genre: Array.from(document.querySelectorAll('input[name="genre[]"]:checked')).map(e => e.value),
        langue: Array.from(document.querySelectorAll('input[name="langue[]"]:checked')).map(e => e.value),
        notes: document.getElementById('notes')?.value || '',
        vehicule: document.getElementById('vehicule')?.value || '',
        immat: document.getElementById('immat')?.value || '',
        tel: document.getElementById('tel')?.value || ''
    };
    
    localStorage.setItem('driveus_preferences', JSON.stringify(preferences));
    console.log('✅ Préférences sauvegardées');
}

function loadPreferences() {
    const saved = localStorage.getItem('driveus_preferences');
    if (!saved) return;
    
    const prefs = JSON.parse(saved);
    
    // Charger les options radio
    if (prefs.bagage) document.querySelector(`input[name="bagage"][value="${prefs.bagage}"]`)?.click();
    if (prefs.fumeur) document.querySelector(`input[name="fumeur"][value="${prefs.fumeur}"]`)?.click();
    if (prefs.animaux) document.querySelector(`input[name="animaux"][value="${prefs.animaux}"]`)?.click();
    if (prefs.enfant) document.querySelector(`input[name="enfant"][value="${prefs.enfant}"]`)?.click();
    
    // Charger les âges
    if (prefs.age_min) document.getElementById('age_min').value = prefs.age_min;
    if (prefs.age_max) document.getElementById('age_max').value = prefs.age_max;
    
    // Charger les genres sélectionnés
    if (Array.isArray(prefs.genre)) {
        prefs.genre.forEach(g => {
            document.querySelector(`input[name="genre[]"][value="${g}"]`)?.click();
        });
    }
    
    // Charger les langues sélectionnées
    if (Array.isArray(prefs.langue)) {
        prefs.langue.forEach(l => {
            document.querySelector(`input[name="langue[]"][value="${l}"]`)?.click();
        });
    }
    
    // Charger les textes et champs
    if (prefs.notes) document.getElementById('notes').value = prefs.notes;
    if (prefs.vehicule) document.getElementById('vehicule').value = prefs.vehicule;
    if (prefs.immat) document.getElementById('immat').value = prefs.immat;
    if (prefs.tel) document.getElementById('tel').value = prefs.tel;
    
    console.log('✅ Préférences chargées');
}

// Charger les préférences au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    loadPreferences();
});

function clearPreferences() {
    localStorage.removeItem('driveus_preferences');
    console.log('🗑️ Préférences supprimées');
}
</script>
    </div>
  </main>
  <?php include 'Outils/views/footer.php'; ?>
</body>
</html>



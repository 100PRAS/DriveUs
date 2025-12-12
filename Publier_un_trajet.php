<?php
session_start();

// Connexion BDD
$pdo = new PDO("mysql:host=localhost;dbname=ville;charset=utf8","root","");
$ca = new PDO("mysql:host=localhost;dbname=bdd;charset=utf8","root","");

// Vérifier si l'utilisateur est connecté via session ou cookie
if (!isset($_SESSION['user_mail']) && isset($_COOKIE['user_mail'])) {
    $_SESSION['user_mail'] = $_COOKIE['user_mail'];
}

$user = null;
if(isset($_SESSION['user_mail'])){
    $stmt = $ca->prepare("SELECT * FROM user WHERE Mail = ?");
    $stmt->execute([$_SESSION['user_mail']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Chemin de la photo de profil (défaut si absent)
$photoPath = (!empty($user['PhotoProfil'])) ? 'Image_Profil/' . htmlspecialchars($user['PhotoProfil']) : 'Image/default.png';

// Définir le rôle maintenant que $user est récupéré
$user_role = $user['role'] ?? 'passager'; // par défaut passager

// Si le rôle est conducteur, il peut publier un trajet
$peutPublier = ($user_role === 'conducteur');

// Récupération des villes pour le formulaire
$req = $pdo->query("SELECT ville_nom FROM villes_france_free ORDER BY ville_nom");
$req2 = $pdo->query("SELECT ville_code_postal FROM villes_france_free ORDER BY ville_code_postal");

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
    $age_min = intval($_POST['age_min'] ?? 0);
    $age_max = intval($_POST['age_max'] ?? 99);
    
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
            $arrets_supplementaires = implode(', ', array_map('trim', $stops_filtered));
        }
    }

    // Insertion en base de données
    $stmt = $ca->prepare("
        INSERT INTO trajet (VilleDepart, VilleArrivee, DateDepart, heure, nombre_places, Prix, ConducteurID, Description, point_rencontre, duree_estimee, age_min, age_max, enregistrer, bagage, fumeur, animaux, enfant, genre, langue, arrets_supplementaires, statut)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
        $statut
    ]);

    if ($success) {
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
  <link rel="icon" type="image/x-icon" href="Image/Icone.ico">
  <link rel="stylesheet" href="CSS/Outils/layout-global.css" />
  <link rel="stylesheet" href="CSS/Publier_un_trajet.css" />
    <link rel="stylesheet" href="CSS/Outils/Header.css" />
        <link rel="stylesheet" href="CSS/Outils/Footer.css" />
    <link rel="stylesheet" href="CSS/Outils/Sombre_Header.css" />



  <link rel="stylesheet" href="CSS/Outils/section-accordion.css" />
  <link rel="stylesheet" href="CSS/Sombre/Sombre_Publier.css" />
  <script src="JS/Sombre.js"></script>
  <script src="JS/section-accordion.js"></script>
 
</head>
<body>
  <?php include 'Outils/header.php'; ?>

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
            <a class="btn btn-outline" href="/DriveUs/Trouver_un_trajet.php">Rechercher un trajet</a>
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

    <form action="Publier_un_trajet.php" method="post" novalidate>
      <!-- Accordéon 1: Informations du trajet -->
      <div class="section-accordion">
        <button type="button" class="section-accordion-header active">📍 Informations du trajet</button>
        <section class="section-accordion-content open card">

        <div class="grid grid-2">
          <div class="field">
            <label for="depart">Lieu de départ</label>
            <input id="depart" name="depart" type="text" placeholder="Ville, adresse ou point de rencontre" list="villes"required />
          </div>

          <div class="field">
            <label for="destination">Destination</label>
            <input id="destination" name="destination" type="text" placeholder="Ville ou adresse d'arrivée" list="villes"required />
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
            <div id="stopsContainer"></div>
            <button type="button" onclick="addStop()" class="btn btn-outline" style="margin-top: 0.5rem;">+ Ajouter un arrêt</button>
          </div>

          <div class="field">
            <label for="date">Date</label>
            <input id="date" name="date" type="date" required min="" />
          </div>

          <div class="field">
            <label for="heure">Heure de départ</label>
            <input id="heure" name="heure" type="time" required />
          </div>

          <div class="field">
            <label for="places">Places disponibles</label>
            <input id="places" name="places" type="number" min="1" max="8"  required />
          </div>

          <div class="field">
            <label for="prix">Montant (€)</label>
            <input id="prix" name="prix" type="number" min="0" step="0.5" placeholder="ex. 8,00" required />
          </div>

          <div class="field">
            <label for="rencontre">Point de rencontre (optionnel)</label>
            <input id="rencontre" name="rencontre" type="text" placeholder="Gare centrale, entrée nord…" />
          </div>

          <div class="field">
            <label for="duree">Durée estimée (optionnel)</label>
            <input id="duree" name="duree" type="time" placeholder="ex. 1h45" />
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
            <label class="choice"><input type="radio" name="bagage" value="petit" /> Petit sac</label>
            <label class="choice"><input type="radio" name="bagage" value="moyen" /> Moyen</label>
            <label class="choice"><input type="radio" name="bagage" value="grand" /> Grand</label>
          </div>

          <div class="field">
            <div class="label">Fumeur</div>
            <label class="choice"><input type="radio" name="fumeur" value="non"  /> Non-fumeur</label>
            <label class="choice"><input type="radio" name="fumeur" value="oui" /> Fumeur</label>
          </div>

          <div class="field">
            <div class="label">Animaux</div>
            <label class="choice"><input type="radio" name="animaux" value="non"  /> Non</label>
            <label class="choice"><input type="radio" name="animaux" value="oui" /> Oui</label>
          </div>

          <div class="field">
            <div class="label">Enfant autorisé</div>
            <label class="choice"><input type="radio" name="enfant" value="oui"  /> Oui</label>
            <label class="choice"><input type="radio" name="enfant" value="non" /> Non</label>
          </div>

          <div class="field">
            <label for="age_min">Âge minimum</label>
            <input type="number" id="age_min" name="age_min" min="18" max="120"  required>
          </div>

          <div class="field">
            <label for="age_max">Âge maximum</label>
            <input type="number" id="age_max" name="age_max" min="18" max="120"  required>
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
          <textarea id="notes" name="notes" rows="4" placeholder="Ex. pause sur la route, musique OK, timing flexible…"></textarea>
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
      $voitures = $ca->query("SELECT Modele, Plaque FROM voiture ORDER BY VoitureID")->fetchAll(PDO::FETCH_ASSOC);

      foreach($voitures as $voiture){
        echo "<option value='" . htmlspecialchars($voiture['Modele'] . " — " . $voiture['Plaque']) . "'>";
      }
    ?>
    </datalist>



        <div class="grid grid-3">
          <div class="field">
            <label for="vehicule"> Véhicule </label>
            <input id="vehicule" name="vehicule" type="text" list="voiture"placeholder="Peugeot 208, bleu" />
          </div>

          <div class="field">
            <label for="immat"> Immatriculation </label>
            <input id="immat" name="immat" type="text" placeholder="AB-123-CD" />
          </div>
<script>
// Création d'un objet JS pour associer modèle → plaque
const voitures = {
    <?php
    foreach($voitures as $v){
        $modele = addslashes($v['Modele']);
        $plaque = addslashes($v['Plaque']);
        echo "'$modele':'$plaque',";
    }
    ?>
};

// Remplissage automatique de l'immatriculation
document.getElementById('vehicule').addEventListener('input', function(){
    const valeur = this.value;
    const immatInput = document.getElementById('immat');
    if(voitures[valeur]){
        immatInput.value = voitures[valeur];
    } else {
        immatInput.value = '';
    }
});
</script>
          <div class="field">
            <label for="tel"> Téléphone </label>
            <input id="tel" name="tel" type="number" value="<?= htmlspecialchars($user['Numero'] ?? '') ?>" />
          </div>
        </div>

        <label class="agree mt-12">
          <input type="checkbox" required />
          J'accepte les <a href="CGU.php">conditions d'utilisation</a> de Drive Us.
        </label>

        </section>
        
        <div class="actions">
          <button type="submit" name="action" value="publier" class="Publier">Publier le trajet</button>
              <button type="submit" name="action" value="brouillon" class="enregistrer">Enregistrer brouillon</button>

          <button type="reset" class="btn">Effacer</button>
        </div>
      </div>
    </form>


<!-- Popup passager -->
<div id="popupOverlay">
    <div id="popup">
        <h2>Accès refusé</h2>
        <p>Vous êtes passager, vous ne pouvez pas publier de trajet.</p>
        <button onclick="window.location.href='Profil.php'">Devenir conducteur</button>
        <br><br>
        <a href ="Page_d_acceuil.php"><button onclick="document.getElementById('popupOverlay').style.display='none'">Fermer</button></a>
    </div>
        </div>

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
    if(min > max){
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

// Gestion des arrêts intermédiaires
let stopCount = 0;

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
}

function removeStop(stopId) {
    const stopDiv = document.getElementById(`stop-${stopId}`);
    if (stopDiv) {
        stopDiv.remove();
    }
}

// Bloquer les dates antérieures à aujourd'hui
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().slice(0, 10);
    const dateInput = document.getElementById('date');
    if (dateInput) {
        dateInput.min = today;
    }
});
</script>
    </div>
  </main>
  <?php include 'Outils/footer.php'; ?>
</body>
</html>



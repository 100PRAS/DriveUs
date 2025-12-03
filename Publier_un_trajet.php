<!DOCTYPE html>
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

    $VilleDepart = $_POST['depart']; 
    $VilleArrivee = $_POST['destination'];
    $DateDepart = $_POST['date'];
    $Heure = $_POST['heure'];
    $nombre_place = $_POST['places'];
    $Prix = $_POST['prix'];
    $point_rencontre = $_POST['rencontre'];
    $duree_estimee = $_POST['duree'];
    $Numero = trim($_POST['tel'] ?? '');
    $Bagage = $_POST['bagage'] ?? null;
    $Fumeur = $_POST['fumeur'] ?? null;
    $Animaux = $_POST['animaux'] ?? null;

    $Genre = isset($_POST['genre']) ? (is_array($_POST['genre']) ? implode(',', $_POST['genre']) : $_POST['genre']) : null;
    $Enfant = $_POST['enfant'] ?? null;
    $Agemin = $_POST['age_min'] ?? 18;
    $Agemax = $_POST['age_max'] ?? 99;
    $Commentaire = $_POST['notes'] ?? '';
    $enregistrer = isset($_POST['enregistrer']) ? 1 : 0;
$statut = $_POST['action'] ?? 'publier';

    // ID utilisateur
    $user_id = $user['UserID'];

    // Insert
    $stmt = $ca->prepare("INSERT INTO trajet 
        (ConducteurId, VilleDepart, VilleArrivee, DateDepart, heure, nombre_places, prix, point_rencontre, duree_estimee, bagage, fumeur, animaux, genre, enfant, age_min, age_max, Description, enregistrer, statut)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $user_id,
        $VilleDepart,
        $VilleArrivee,
        $DateDepart,
        $Heure,
        $nombre_place,
        $Prix,
        $point_rencontre,
        $duree_estimee,
        $Bagage,
        $Fumeur,
        $Animaux,
        $Genre,
        $Enfant,
        $Agemin,
        $Agemax,
        $Commentaire,
        $enregistrer,
        $statut
    ]);

    header("Location: Publier_un_trajet.php?success=1");
    exit;
}

// Langue
    if(isset($_GET["lang"])) {
        $_SESSION["lang"] = $_GET["lang"];
    }
    $lang = $_SESSION["lang"] ?? "fr";
    $text = require "Outils/lang_$lang.php";
?>




<!DOCTYPE html>
<html lang="fr">
<head>

<style>
body { font-family: Arial, sans-serif; background-color: #f0f0f0; margin:0; padding:0; }
.container { max-width: 800px; margin: 30px auto; padding:20px; background:#fff; border-radius:10px; box-shadow:0 5px 20px rgba(0,0,0,0.1);}
#popupOverlay {
    display: <?php echo ($user_role !== 'conducteur') ? 'flex' : 'none'; ?>;
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.6); justify-content: center; align-items: center; z-index: 999;
}
#popup {
    background: #fff; padding: 30px; border-radius: 10px; text-align: center;
    width: 350px; box-shadow: 0 5px 20px rgba(0,0,0,0.3);
}
#popup button { margin-top: 15px; padding: 10px 20px; border: none; background-color:#0077ff; color:#fff; border-radius:5px; cursor:pointer;}
#popup button:hover { background-color:#005bb5; }
</style>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Drive Us — Publier un trajet</title>
  <link rel="stylesheet" href="CSS/Publier_un_trajet.css" />
  <link rel="stylesheet" href="CSS/Sombre.css" />

</head>
<body>
  <!-- Header -->
<!--Bande d'ariane---------------------------------------------------------------------------------------------------------------------------->
   
        <header class="head">
            <a href=Page_d_acceuil.php><img class="logo_clair" src ="Image/LOGO.png"/></a>
            <a href=Page_d_acceuil.php><img class="logo_sombre" src ="Image/LOGO_BLANC.png"/></a>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <ul class = "Bande">
                <li><a href=Page_d_acceuil.php><Button class="Boutton_Acceuil"><?= $text["Bouton_A"] ?? "" ?></Button></a></li>
                <li><a href=Trouver_un_trajet.php><Button class="Boutton_Trouver"><?= $text["Bouton_T"] ?? "" ?></button></a></li>
                <li><a href=Publier_un_trajet.php><Button class = "Boutton_Publier"><?= $text["Bouton_P"] ?? "" ?></Button></a></li>
                <li><a href="Messagerie.php"><button class="Messagerie"><?= $text["Bouton_M"] ?? "" ?></button></a></li>
                <li>
                    <?php if (!isset($_SESSION['user_mail'])): ?>
                        <a href="Se_connecter.php"><button class="Boutton_Se_connecter">Se connecter</button></a>
                    <?php else: ?>
                        <img src="<?= $photoPath ?>" alt="Profil" style="width:50px; height:50px; border-radius:50%;" onclick="menu.hidden ^= 1">
                        <ul id="menu" hidden>
                            <li><a href="Profil.php"><button>Mon compte</button></a></li>
                            <li><a href="Mes_trajets.php"><button>Mes trajets</button></a></li>
                            <li><a href="Outils/Se_deconnecter.php"><button>Se déconnecter</button></a></li>
                        </ul>
                    <?php endif; ?>
                </li>
                <li>
                    <button class="Langue" onclick ="menuL.hidden^=1"><?php echo $lang?></button>
                       <ul id="menuL" hidden>
                            <li><a href="?lang=fr"><img src="Image/France.png"/></a></li>
                            <li><a href="?lang=en"><img src ="Image/Angleterre.png"/></a></li>
                        </ul>
                </li>
                <li>
                    <a href="javascript:void(0)" class="Sombre" onclick="darkToggle()">
                        <img src="Image/Sombre.png" class="Sombre1" />
                        <img src="Image/SombreB.png" class="SombreB" />
                    </a>
                </li>

            </ul>
        </header>


  <!-- Hero -->
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
          <a class="btn btn-outline" href="rechercher.html">Rechercher un trajet</a>
        </div>
      </div>

      <!-- Illustration simple en SVG intégrée pour rester autonome -->
      <div class="hero-illu" aria-hidden="true">
        <div class="hero-illu">
          <img src="C:\Users\apm19\Desktop\Isep\A1\Projet num. dev. web\im1.png" alt="Voiture bleue sur la route avec passagers">
        </div>
        
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
  <main id="form-publier" class="container">

    <form action="Publier_un_trajet.php" method="post" novalidate>
      <!-- Carte 1 -->
      <section class="card">
        <h2>Informations du trajet</h2>

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
          <div class="field">
            <label for="date">Date</label>
            <input id="date" name="date" type="date" required />
          </div>

          <div class="field">
            <label for="heure">Heure de départ</label>
            <input id="heure" name="heure" type="time" required />
          </div>

          <div class="field">
            <label for="places">Places disponibles</label>
            <input id="places" name="places" type="number" min="1" max="8" value="3" required />
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

      <!-- Carte 2 -->
      <section class="card">
        <h2>Préférences</h2>

        <div class="grid grid-3">
          <div class="field">
            <div class="label">Bagages</div>
            <label class="choice"><input type="radio" name="bagage" value="petit" checked /> Petit sac</label>
            <label class="choice"><input type="radio" name="bagage" value="moyen" /> Moyen</label>
            <label class="choice"><input type="radio" name="bagage" value="grand" /> Grand</label>
          </div>

          <div class="field">
            <div class="label">Fumeur</div>
            <label class="choice"><input type="radio" name="fumeur" value="non" checked /> Non-fumeur</label>
            <label class="choice"><input type="radio" name="fumeur" value="oui" /> Fumeur</label>
          </div>

          <div class="field">
            <div class="label">Animaux</div>
            <label class="choice"><input type="radio" name="animaux" value="non" checked /> Non</label>
            <label class="choice"><input type="radio" name="animaux" value="oui" /> Oui</label>
          </div>
        </div>

        <div class="field">
            <div class="label">Genre accepté</div>
            <label class="choice"><input type="checkbox" name="genre[]" value="Homme" checked /> Homme</label>
            <label class="choice"><input type="checkbox" name="genre[]" value="Femme" /> Femme</label>
            <label class="choice"><input type="checkbox" name="genre[]" value="Autre" /> Autre</label>
            <label class="choice"><input type="checkbox" name="genre[]" value="Tous" /> Tous</label>


          </div>

          <div class="field">
            <div class="label">Enfant autorisé</div>
            <label class="choice"><input type="radio" name="enfant" value="oui" checked /> Oui</label>
            <label class="choice"><input type="radio" name="enfant" value="non" /> Non</label>
          </div>
        </div>

        <div class="age-group">
  <label for="age_min">Âge minimum :</label>
  <input type="number" id="age_min" name="age_min" min="18" max="120" value="18" required>

  <label for="age_max">Âge maximum :</label>
  <input type="number" id="age_max" name="age_max" min="18" max="120" value="99" required>

  <p class="age-error" id="ageError" aria-live="polite" style="display:none;color:red;font-size:0.9rem;">
    L'âge minimum doit être inférieur ou égal à l'âge maximum.
  </p>
</div>

        <div class="field mt-12">
          <label for="notes">Commentaire pour les passagers (optionnel)</label>
          <textarea id="notes" name="notes" rows="4" placeholder="Ex. pause sur la route, musique OK, timing flexible…"></textarea>
        </div>

        <label>Enregistrer pour les prochains trajets</label>
        <input name="enregistrer"type="checkbox" id="enregistrer"/>
      </section>

      <!-- Carte 3 -->
      <section class="card">
        <h2>Véhicule & contact</h2>


    <datalist id="voiture">
    <?php
        $voitures = $ca->query("SELECT Modele, Plaque FROM voiture ORDER BY VoitureID");

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
            <input id="tel" name="tel" type="tel" value="<?= htmlspecialchars($user['Numero'] ?? '') ?>" />
          </div>
        </div>

        <label class="agree mt-12">
          <input type="checkbox" required />
          J’accepte les <a href="CGU.php">conditions d’utilisation</a> de Drive Us.
        </label>

        <div class="actions">
          <button type="submit" name="action" class="btn btn-primary">Publier le trajet</button>
              <button type="submit" name="action" value="brouillon" class="btn">Enregistrer brouillon</button>

          <button type="reset" class="btn">Effacer</button>
        </div>
      </section>
    </form>


  </main>
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

  <footer class="site-footer">
    <div class="container">
      © 2025 Drive Us — Partagez vos trajets, économisez et voyagez ensemble.
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
</script>
</body>
</html>



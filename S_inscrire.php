<!DOCTYPE html>
<?php

// Système de langue unifié
require_once 'Outils/config/langue.php';


// Connexion BDD centralisée (Clever Cloud)
require_once 'Outils/config/config.php';


// --- Traitement du formulaire ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Récupération des données (sécuriser plus tard avec validation)
    $Nom = $_POST['nom'] ?? null;
    $Prenom = $_POST['prenom'] ?? null;
    $MotDePasse = $_POST['MDP'] ?? null;
    $MotDePasseConfirm = $_POST['MDP_confirm'] ?? null;
    
    // Validation des mots de passe
    if ($MotDePasse !== $MotDePasseConfirm) {
        die("<script>alert('Les mots de passe ne correspondent pas.'); window.history.back();</script>");
    }
    
    $MotDePasseH = $MotDePasse ? password_hash($MotDePasse, PASSWORD_DEFAULT) : null;
    $Genre = $_POST['genre'] ?? null;
    $Age = $_POST['age'] ?? null;
    $Description = $_POST['description'] ?? null;
    $Mail = $_POST['mail'] ?? null;
    $Numero = $_POST['phone'] ?? null;
    $Date_naissance = $_POST['date_naissance'] ?? null;
    $role = $_POST['role'] ?? null;

    // Validation du numéro de téléphone
    if ($Numero) {
        // Nettoyer le numéro (enlever espaces, tirets, points)
        $NumeroClean = preg_replace('/[\s\-\.\(\)]/', '', $Numero);
        
        // Vérifier le format français: 0[1-9]XXXXXXXX ou +33[1-9]XXXXXXXX
        if (!preg_match('/^(\+33|0)[1-9]\d{8}$/', $NumeroClean)) {
            die("<script>alert('Format de numéro invalide. Utilisez: 06 12 34 56 78 ou +33 6 12 34 56 78'); window.history.back();</script>");
        }
        // Normaliser au format 0XXXXXXXXX
        if (strpos($NumeroClean, '+33') === 0) {
            $Numero = '0' . substr($NumeroClean, 3);
        } else {
            $Numero = $NumeroClean;
        }
    }

    // Upload permis (optionnel)
    $Permis = null;
    if (!empty($_FILES["Permis"]["name"])) {
        $Permis = uniqid("permis_") . "_" . basename($_FILES["Permis"]["name"]);
        $uploadPath = __DIR__ . "/Permis/" . $Permis;
        if (!is_dir(__DIR__ . "/Permis")) mkdir(__DIR__ . "/Permis", 0777, true);
        if (!move_uploaded_file($_FILES["Permis"]["tmp_name"], $uploadPath)) {
            // Optionnel : gérer l'erreur d'upload
            $Permis = null;
        }
    }

    // Upload photo (optionnel)
$photoName = null;

if (!empty($_FILES['avatar']['name'])) {

    $targetDir = __DIR__ . "/Image_Profil/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    // Vérification du type MIME
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png'
    ];

    $mime = $_FILES['avatar']['type'];

    if (isset($allowed[$mime])) {

        // Génère un nom propre : profile_1734000000.jpg
        $extension = $allowed[$mime];
        $photoName = "profile_" . time() . "." . $extension;

        $targetFile = $targetDir . $photoName;

        if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
            $photoName = null; // échec upload
        }

    } else {
        echo "<p style='color:red'>⚠️ Format d'image non autorisé (JPEG ou PNG uniquement)</p>";
        $photoName = null;
    }
}


    // Adresse (valeurs provenant du formulaire)
    $NumeroV = $_POST['numeroV'] ?? null;
    $Voie = $_POST['rue'] ?? null;
    $Ville = $_POST['ville'] ?? null;
    $CodePostal = $_POST['code'] ?? null;
    $Departement = $_POST['departement'] ?? null;
    $Pays = $_POST['pays'] ?? 'France';

// --- Requête d’insertion USER ---
$sqlUser = "
  INSERT INTO user (
    nom, prenom, age, date_naissance, description,
    mail, numero, genre, role, MotDePasseH, PhotoProfil
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

try {
    $stmt = $pdo->prepare($sqlUser);
    $stmt->execute([
      $Nom,
      $Prenom,
      $Age,
      $Date_naissance,
      $Description,
      $Mail,
      $Numero,
      $Genre,
      $role,
      $MotDePasseH,
      $photoName
    ]);

    // --- Récupérer l'ID inséré ---
    $UserID = $pdo->lastInsertId();

    // --- Insertion adresse ---
    $sqlAddr = "
        INSERT INTO adresse (UserID, Numero, Voie, Ville, CodePostal, Departement, Pays)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt2 = $pdo->prepare($sqlAddr);
    $stmt2->execute([
        $UserID,
        $NumeroV,
        $Voie,
        $Ville,
        $CodePostal,
        $Departement,
        $Pays
    ]);

    // --- Redirection après succès ---
    header("Location: Page_d_acceuil.php");
    exit();
} catch (PDOException $e) {
    die("<script>alert('Erreur lors de l\\'inscription: " . htmlspecialchars($e->getMessage()) . "'); window.history.back();</script>");
}

}

?>

<html>
    <head>
        <title>Inscription - Drive Us</title>
        <link rel="stylesheet" href="CSS/Outils/layout-global.css" />
        <link rel="stylesheet" href="CSS/S_inscrire_modern.css" />
        <link rel="stylesheet" href="CSS/Sombre/Sombre_Connexion1.css" />
        <link rel="stylesheet" href="CSS/Outils/Header.css" />
        <link rel="stylesheet" href="CSS/Sombre/Sombre_Header.css" />
        <link rel="stylesheet" href="CSS/Outils/Footer.css" />
        <link rel="icon" type="image/x-icon" href="Image/Icone.ico">
        <script src="JS/Inscription.js"></script>
        <script src="JS/Sombre.js"></script>
    </head>

    <body>
        <?php include 'Outils/views/header.php'; ?>
<main>

  <form action="" Method="POST" class="formulaire" enctype="multipart/form-data">
    
    <h2>Créer un compte</h2>

    <!-- Informations personnelles -->
    <div class="form-row">
      <div class="form-group">
        <label for="prénom">Prénom *</label>
        <input type="text" id="prénom" name="prenom" placeholder="Prénom" required/>
      </div>
      
      <div class="form-group">
        <label for="nom">Nom *</label>
        <input type="text" id="nom" name="nom" placeholder="Nom" required/>
      </div>
    </div>

    <label for="email">Adresse email *</label>
    <input type="Email" id="email" name="mail" placeholder="votreemail@exemple.com" required />

    <label for="phone">Numéro de téléphone *</label>
    <input type="tel" id="phone" name="phone" placeholder="06 12 34 56 78" pattern="^(\+33|0)[1-9](\s?\d{2}){4}$" title="Format: 06 12 34 56 78 ou +33 6 12 34 56 78" required />

    <label for="pass">Mot de passe *</label>
    <input type="password" id="pass" name="MDP" minlength="4" placeholder="••••••••" required/>
    
    <label for="pass_confirm">Confirmer le mot de passe *</label>
    <input type="password" id="pass_confirm" name="MDP_confirm" minlength="4" placeholder="••••••••" required/>
    <span id="password_match_error" style="color: red; font-size: 0.9em; display: none;"> Les mots de passe ne correspondent pas</span>

    <div class="form-row">
      <div class="form-group">
        <label for="Age">Âge *</label>
        <input type="number" id="Age" name="age" min="18" max="100" placeholder="18" readonly required/>
      </div>
      
      <div class="form-group">
        <label for="Date">Date de naissance *</label>
        <input type="date" id="Date" name="date_naissance" required/>
      </div>
    </div>

    <label for="Genre">Genre *</label>
    <div class="radio-group">
      <div class="radio-option">
        <input type="radio" id="homme" name="genre" value="Homme" required/>
        <label for="homme">Homme</label>
      </div>
      <div class="radio-option">
        <input type="radio" id="femme" name="genre" value="Femme"/>
        <label for="femme">Femme</label>
      </div>
      <div class="radio-option">
        <input type="radio" id="autre" name="genre" value="Autre"/>
        <label for="autre">Autre</label>
      </div>
    </div>

    <label>Vous êtes *</label>
    <div class="radio-group">
      <div class="radio-option">
        <input type="radio" name="role" value="conducteur" id="conducteur" required/>
        <label for="conducteur">🚗 Conducteur</label>
      </div>
      <div class="radio-option">
        <input type="radio" name="role" value="Passager" id="passager"/>
        <label for="passager">👤 Passager</label>
      </div>
    </div>

    <div class="form-section">
      <h3>Fichiers</h3>
      
      <label for="avatar">Photo de profil</label>
      <input type="file" id="avatar" name="avatar" accept="image/png, image/jpeg"/>

      <label for="Permis">Permis de conduire (pour conducteurs)</label>
      <input type="file" id="Permis" name="Permis" accept="image/png, image/jpeg, application/pdf" />

<script>
// Récupérer éléments
const conducteur = document.getElementById("conducteur");
const passager = document.getElementById("passager");
const permis = document.getElementById("Permis");

// Fonction qui bloque/débloque
function updatePermis() {
    if (passager.checked) {
        permis.disabled = true;
        permis.value = ""; // On efface le fichier si déjà choisi
    } else {
        permis.disabled = false;
    }
}

// Appliquer au chargement (si pré-rempli)
updatePermis();

// Sur changement de radio
conducteur.addEventListener("change", updatePermis);
passager.addEventListener("change", updatePermis);
</script>

    <div class="form-section">
      <h3>À propos de vous</h3>
      
      <label for="Description">Description (optionnel)</label>
      <textarea id="Description" name="description" placeholder="Parlez un peu de vous..." rows="4"></textarea>
    </div>

    <div class="form-section">
      <h3>Adresse</h3>
      
      <label for="adresse">Rechercher votre adresse</label>
      <input type="text" id="adresse" placeholder="Commencez à taper votre adresse..." autocomplete="off">
      
      <!-- Liste de suggestions -->
      <div id="suggestions" style="border:1px solid #e0e0e0; max-height:150px; overflow:auto; display:none; border-radius:10px; margin-top:5px;"></div>

      <div class="form-row" style="margin-top: 15px;">
        <div class="form-group">
          <label for="numero">Numéro</label>
          <input name="numeroV" type="text" id="numero" placeholder="10" required>
        </div>
        <div class="form-group">
          <label for="rue">Rue</label>
          <input name="rue" type="text" id="rue" placeholder="Rue de la République" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="code_postal">Code postal</label>
          <input name="code" type="text" id="code_postal" placeholder="75001" required>
        </div>
        <div class="form-group">
          <label for="ville">Ville</label>
          <input name="ville" type="text" id="ville" placeholder="Paris" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="departement">Département</label>
          <input name="departement" type="text" id="departement" placeholder="75" required>
        </div>
        <div class="form-group">
          <label for="region">Région</label>
          <input name="region" type="text" id="region" placeholder="Île-de-France" required>
        </div>
      </div>

      <input name="pays" type="hidden" id="pays" value="France">
      <input type="hidden" id="lat">
      <input type="hidden" id="lon">
    </div>

    <div style="margin-top: 25px; padding: 15px; background: #f0f2ff; border-radius: 10px;">
      <label for="CGU" style="display: flex; align-items: center; cursor: pointer;">
        <input type="checkbox" id="CGU" required style="margin-right: 10px; width: auto;">
        <span>J'accepte les <a href="CGU.php" style="color: #667eea; text-decoration: underline;">conditions générales d'utilisation</a></span>
      </label>
    </div>

    <input type="Submit" value="Créer mon compte">

    <div class="login-link">
      Vous avez déjà un compte ? <a href="Se_connecter.php">Se connecter</a>
    </div>

  </form>
</main>
<?php include 'Outils/views/footer.php'; ?>
</body>
</html>

    <script>
// 🟦 Déclenché quand tout le DOM est prêt
document.addEventListener("DOMContentLoaded", () => {

    const inputAdresse = document.getElementById("adresse");
    const box = document.getElementById("suggestions");

    inputAdresse.addEventListener("input", async function () {
        const q = this.value.trim();

        if (q.length < 3) {
            box.style.display = "none";
            return;
        }

        const url = "https://api-adresse.data.gouv.fr/search/?q=" 
                    + encodeURIComponent(q) + "&limit=8";

        const data = await fetch(url).then(r => r.json());

        box.innerHTML = "";
        data.features.forEach(f => {
            const div = document.createElement("div");
            div.textContent = f.properties.label;
            div.classList.add("itemSuggestion");

            div.onclick = () => remplirAdresse(f);
            box.appendChild(div);
        });

        box.style.display = "block";
    });


    function remplirAdresse(feature) {
        const props = feature.properties;
        const coords = feature.geometry.coordinates;

        document.getElementById("adresse").value = props.label ?? "";
        document.getElementById("numero").value = props.housenumber ?? "";
        document.getElementById("rue").value = props.street ?? props.name ?? "";
        document.getElementById("code_postal").value = props.postcode ?? "";
        document.getElementById("ville").value = props.city ?? props.town ?? "";

        const cp = props.postcode ?? "";
        document.getElementById("departement").value = cp.substring(0, 2);

        document.getElementById("region").value = regionFromCP(cp);
        document.getElementById("pays").value = "France";

        document.getElementById("lat").value = coords[1];
        document.getElementById("lon").value = coords[0];

        box.style.display = "none";
    }


    // 🟨 TABLE DES RÉGIONS PAR DÉPARTEMENT
    function regionFromCP(cp) {
        const dep = cp.substring(0, 2);

        const regions = {
            "75":"Île-de-France","77":"Île-de-France","78":"Île-de-France",
            "91":"Île-de-France","92":"Île-de-France","93":"Île-de-France",
            "94":"Île-de-France","95":"Île-de-France",

            "13":"Provence-Alpes-Côte d'Azur","83":"Provence-Alpes-Côte d'Azur",
            "84":"Provence-Alpes-Côte d'Azur","04":"Provence-Alpes-Côte d'Azur",
            "05":"Provence-Alpes-Côte d'Azur","06":"Provence-Alpes-Côte d'Azur",

            "33":"Nouvelle-Aquitaine","24":"Nouvelle-Aquitaine","40":"Nouvelle-Aquitaine",
            "47":"Nouvelle-Aquitaine","64":"Nouvelle-Aquitaine",
            "16":"Nouvelle-Aquitaine","17":"Nouvelle-Aquitaine","79":"Nouvelle-Aquitaine",
            "86":"Nouvelle-Aquitaine","87":"Nouvelle-Aquitaine",

            "69":"Auvergne-Rhône-Alpes","01":"Auvergne-Rhône-Alpes",
            "03":"Auvergne-Rhône-Alpes","07":"Auvergne-Rhône-Alpes",
            "15":"Auvergne-Rhône-Alpes","26":"Auvergne-Rhône-Alpes",
            "38":"Auvergne-Rhône-Alpes","42":"Auvergne-Rhône-Alpes",
            "43":"Auvergne-Rhône-Alpes","63":"Auvergne-Rhône-Alpes",
            "73":"Auvergne-Rhône-Alpes","74":"Auvergne-Rhône-Alpes",

            "59":"Hauts-de-France","62":"Hauts-de-France","02":"Hauts-de-France",
            "60":"Hauts-de-France","80":"Hauts-de-France",

            "67":"Grand Est","68":"Grand Est","88":"Grand Est","54":"Grand Est",
            "55":"Grand Est","57":"Grand Est","08":"Grand Est","10":"Grand Est",
            "51":"Grand Est","52":"Grand Est",

            "14":"Normandie","27":"Normandie","50":"Normandie","61":"Normandie",
            "76":"Normandie",

            "22":"Bretagne","29":"Bretagne","35":"Bretagne","56":"Bretagne",

            "44":"Pays de la Loire","49":"Pays de la Loire","53":"Pays de la Loire",
            "72":"Pays de la Loire","85":"Pays de la Loire",

            "09":"Occitanie","11":"Occitanie","12":"Occitanie","30":"Occitanie",
            "31":"Occitanie","32":"Occitanie","34":"Occitanie","46":"Occitanie",
            "48":"Occitanie","65":"Occitanie","66":"Occitanie","81":"Occitanie",
            "82":"Occitanie",

            "21":"Bourgogne-Franche-Comté","25":"Bourgogne-Franche-Comté",
            "39":"Bourgogne-Franche-Comté","58":"Bourgogne-Franche-Comté",
            "70":"Bourgogne-Franche-Comté","71":"Bourgogne-Franche-Comté",
            "89":"Bourgogne-Franche-Comté","90":"Bourgogne-Franche-Comté",

            "971":"Guadeloupe","972":"Martinique","973":"Guyane",
            "974":"La Réunion","976":"Mayotte"
        };

        return regions[dep] || "Inconnue";
    }

});


// Calcul automatique de l'âge à partir de la date de naissance
document.getElementById('Date').addEventListener('change', function() {
  const dateNaissance = new Date(this.value);
  const aujourdhui = new Date();
  let age = aujourdhui.getFullYear() - dateNaissance.getFullYear();
  const mois = aujourdhui.getMonth() - dateNaissance.getMonth();
  if (mois < 0 || (mois === 0 && aujourdhui.getDate() < dateNaissance.getDate())) {
    age--;
  }
  document.getElementById('Age').value = age;
});

// Validation en temps réel de la correspondance des mots de passe
function checkPasswordMatch() {
    const password = document.getElementById('pass').value;
    const confirmPassword = document.getElementById('pass_confirm').value;
    const errorMsg = document.getElementById('password_match_error');
    const successMsg = document.getElementById('password_match_success');
    const submitBtn = document.querySelector('button[type="submit"]');
    
    if (confirmPassword === '') {
        errorMsg.style.display = 'none';
        successMsg.style.display = 'none';
        return;
    }
    
    if (password !== confirmPassword) {
        errorMsg.style.display = 'block';
        successMsg.style.display = 'none';
        if (submitBtn) submitBtn.disabled = true;
    } else {
        errorMsg.style.display = 'none';
        successMsg.style.display = 'block';
        if (submitBtn) submitBtn.disabled = false;
    }
}

document.getElementById('pass').addEventListener('input', checkPasswordMatch);
document.getElementById('pass_confirm').addEventListener('input', checkPasswordMatch);

document.addEventListener("input", updateProgress);

function updateProgress() {
    let fields = [
        document.querySelector("input[name='Identifiant']"),
        document.querySelector("input[name='MDP']"),
        document.querySelector("input[name='Mail']"),
        document.querySelector("input[name='Nom']"),
        document.querySelector("input[name='Prenom']")
    ];

    let filled = fields.filter(f => f && f.value.trim() !== "").length;
    let total = fields.length;

    let percent = (filled / total) * 100;

    document.getElementById("progressBar").style.width = percent + "%";
}
</script>



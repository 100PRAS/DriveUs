<!DOCTYPE html>
<?php


// --- Connexion aux bases de données ---
$conn = new mysqli("127.0.0.1", "root", "", "bdd");

// Vérification
if ($conn->connect_error) {
    die("Erreur de connexion à la base de données : " . $conn->connect_error);
}

// --- Traitement du formulaire ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Récupération des données (sécuriser plus tard avec validation)
    $Nom = $_POST['nom'] ?? null;
    $Prenom = $_POST['prenom'] ?? null;
    $MotDePasse = $_POST['MDP'] ?? null;
    $MotDePasseH = $MotDePasse ? password_hash($MotDePasse, PASSWORD_DEFAULT) : null;
    $Genre = $_POST['genre'] ?? null;
    $Age = $_POST['age'] ?? null;
    $Description = $_POST['description'] ?? null;
    $Mail = $_POST['mail'] ?? null;
    $Numero = $_POST['phone'] ?? null;
    $Date_naissance = $_POST['date_naissance'] ?? null;
    $role = $_POST['role'] ?? null;

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

        $photoName = time() . "_" . basename($_FILES['avatar']['name']);
        $targetFile = $targetDir . $photoName;

        $allowed = ['image/jpeg', 'image/png'];
        if (in_array($_FILES['avatar']['type'], $allowed)) {
            if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
                // échec upload
                $photoName = null;
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
        INSERT INTO user (nom, prenom, age, date_naissance, description, mail, numero, genre, role, MotDePasseH, PhotoProfil, Permis)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt = $conn->prepare($sqlUser);
    if ($stmt === false) {
        die("Prepare user failed: (" . $conn->errno . ") " . $conn->error);
    }

    // Types : s=string, i=int ; adapter si besoin
$bindOk = $stmt->bind_param(
    "ssisssssssss",
    $Nom, $Prenom, $Age, $Date_naissance, $Description, $Mail, $Numero, $Genre,
    $role, $MotDePasseH, $photoName, $Permis
);

    if ($bindOk === false) {
        die("Bind param user failed: (" . $stmt->errno . ") " . $stmt->error);
    }

    // Exécution de l'insertion utilisateur
    if ($stmt->execute() === false) {
        // Affiche l'erreur SQL (utile en dev)
        echo "❌ Échec de l'inscription (user) : (" . $stmt->errno . ") " . $stmt->error;
        $stmt->close();
        exit;
    }

    // Récupérer l'ID inséré
    $UserID = $conn->insert_id;

    // --- Préparer et exécuter l'insertion adresse ---
    $sqlAddr = "
        INSERT INTO adresse (UserID, Numero, Voie, Ville, CodePostal, Departement, Pays)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt2 = $conn->prepare($sqlAddr);
    if ($stmt2 === false) {
        // Si prepare échoue, on peut supprimer l'utilisateur inséré ou loguer l'erreur
        echo "Prepare adresse failed: (" . $conn->errno . ") " . $conn->error;
        // Optionnel : rollback manuellement si tu veux supprimer l'utilisateur créé
        // $conn->query("DELETE FROM user WHERE UserID = " . intval($UserID));
        $stmt->close();
        exit;
    }

    // lier et exécuter
    $bindOk2 = $stmt2->bind_param("issssss", $UserID, $NumeroV, $Voie, $Ville, $CodePostal, $Departement, $Pays);
    if ($bindOk2 === false) {
        echo "Bind param adresse failed: (" . $stmt2->errno . ") " . $stmt2->error;
        $stmt2->close();
        $stmt->close();
        exit;
    }

    if ($stmt2->execute() === false) {
        echo "❌ Échec insertion adresse : (" . $stmt2->errno . ") " . $stmt2->error;
        // Optionnel : supprimer user inséré si l'adresse est obligatoire
        // $conn->query("DELETE FROM user WHERE UserID = " . intval($UserID));
        $stmt2->close();
        $stmt->close();
        exit;
    }

    // Tout est OK : fermer statements et rediriger
    $stmt2->close();
    $stmt->close();

    header("Location: Page_d_acceuil.php");
    exit();
}

    // Langue
    if(isset($_GET["lang"])) {
        $_SESSION["lang"] = $_GET["lang"];
    }
    $lang = $_SESSION["lang"] ?? "fr";
    $text = require "Outils/lang_$lang.php";

?>

<html>
    <head>
    <title>Drive Us</title>
</head>

<body>
    <link rel="stylesheet" href="CSS/S_inscrire1.css" />
    <script src="JS/Inscription.js"></script>

    <!--Bande d'ariane---------------------------------------------------------------------------------------------------------------------------->
    <header class="head">
        <a href=Page_d_acceuil.php><img class="logo_clair" src ="Image/LOGO.png"/></a>
        <a href=Page_d_acceuil.php><img class="logo_sombre" src ="Image/LOGO_BLANC.png"/></a>
        <a href="javascript:void(0)" class="Sombre" onclick="darkToggle()">
        <img src="Image/Sombre.png" class="Sombre1" />
    <!-- <img src="Image/SombreB.png" class="SombreB" />-->
        </a>
        <ul class = "Bande">
            <li><a href=Page_d_acceuil.php><Button class="Boutton_Acceuil"><?= $text["Bouton_A"] ?? "" ?></Button></a></li>
            <li><a href=Trouver_un_trajet.php><Button class="Boutton_Trouver"><?= $text["Bouton_T"] ?? "" ?></button></a></li>
            <li><a href=Publier_un_trajet.php><Button class = "Boutton_Publier"><?= $text["Bouton_P"] ?? "" ?></Button></a></li>
            <li><a href="Messagerie.php"><button class="Messagerie"><?= $text["Bouton_M"] ?? "" ?></button></a></li>
            <li>
                <button class="Langue" onclick ="menuL.hidden^=1"><?php echo $lang?></button>
                <ul id="menuL" hidden>
                    <li><a href="?lang=fr"><img src="Image/France.png"/></a></li>
                    <li><a href="?lang=en"><img src ="Image/Angleterre.png"/></a></li>
                </ul>
            </li>
            <li>
                <?php if (!isset($_SESSION['user_mail'])): ?>
                    <a href="Se_connecter.php"><button>Se connecter</button></a>
                <?php else: ?>
                    <img src="<?= $photoPath ?>" alt="Profil" style="width:50px; height:50px; border-radius:50%;" onclick="menu.hidden ^= 1">
                    <ul id="menu" hidden>
                        <li><a href="Page_d_acceuil.php"><button>Mon compte</button></a></li>
                        <li><a href="Se_deconnecter.php"><button>Se déconnecter</button></a></li>
                    </ul>
                <?php endif; ?>
            </li>

        </ul>
        </nav>
     
    </header>
<!---------------------------------------------------------------------------------------------------------------------------------->
   <main>

  <form action="" Method="POST"class="formulaire" enctype="multipart/form-data">
    
  <label for="Prénom">Entrer votre prénom</label>
  <input type="text" id="prénom" name="prenom" placeholder="Prénom" required/>

  <label for="nom">Entrer votre nom</label>
  <input type="text" id="nom" name="nom" placeholder="Nom" required/>

    <label for="Email">Entrer votre email:</label>
    <input type="Email" id="email" name="mail"  size="30" placeholder="E-mail" required />

  <label for="avatar">Choisisser une photo de profil</label>
<input type="file" id="avatar" name="avatar" accept="image/png, image/jpeg" placeholder="Photo de profil"required />

<label for="phone">
</label>

<input
  type="tel"
  id="phone"
  name="phone"
  placeholder="Numéro"
  required
   />

  <label for="Genre" required>
    Quel est votre genre ?<br>
</label>

  <div>
    <input type="radio" id="Genre" name="genre" value="Homme" />
    <label for="scales">Homme</label>
  </div>

  <div>
    <input type="radio" id="Genre" name="genre" value ="Femme" />
    <label for="horns">Femme</label>
  </div>

  <div>
    <input type="radio" id="Genre" name="genre" value="Autre" />
    <label for="horns">Autre</label>
  </div>

<label >
  <input type="radio" name="role" value="conducteur" id="conducteur"required/> Conducteur
</label>

<label>
  <input type="radio" name="role" value="Passager" id="passager"required/> Passager
</label>

<br><br>

<label for="Permis">Permis</label>
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

<label for="pass">MDP </label>

  <input class='MDP'type="Password"
            id="pass"
            name="MDP"
            minlength="4"
            size="30"
            placeholder="Mot de passe"required/>
  <label for="Age">Quel est votre age </label>
  <input type="number" id="Age" name="age" min="18" max="100" placeholder="Age"required/>

  <label for="Date"> Votre date de naissance</label>
  <input class="Date" 
  name="date_naissance"
                type="date"
                id="Date"
                placeholder="<?= $text["Date"] ?? "" ?>"
                name="trip-start"
                min ="today"
                required/>

  <label for="Description"> Entrer une description</label>
  <textarea id="Description" name="description" placeholder="Description"></textarea>

<input type="text" id="adresse" placeholder="Adresse complète" autocomplete="off">

<!-- Champs remplis automatiquement -->
<input name="numeroV" type="text" id="numero" placeholder="Numéro"required>
<input name="rue"type="text" id="rue" placeholder="Rue"required>
<input name="code"type="text" id="code_postal" placeholder="Code postal"required>
<input name="ville"type="text" id="ville" placeholder="Ville"required>
<input name="departement"type="text" id="departement" placeholder="Département"required>
<input name="region"type="text" id="region" placeholder="Région"required>
<input name="pays"type="text" id="pays" value="France"required>
<input type="hidden" id="lat">
<input type="hidden" id="lon">

<!-- Liste de suggestions -->
<div id="suggestions" style="border:1px solid #ccc; max-height:150px; overflow:auto; display:none;"></div>
<div class="progress-container">
    <div class="progress-bar" id="progressBar"></div>
</div>

  </div>
  <label for ="CGU">En vous inscrivant vous acceptez les <a href="CGU.php">conditions générals d'utilisation</a></label>
  <input type="checkbox" id="CGU" required></input>
      <input type="Submit" value="Confirmer">

    </form>

      </main>

<!--Pied de page ------------------------------------------------------------------------------------------------------------------->
        <footer class = "Pied">
        <p class="pC">Contact : Drive.us@gmail.com</p>
        <p class="CGU"><a href=CGU.php><?= $text["CGU"] ?? "" ?></a></p> 
    </footer>

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
</script>
<script>
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

</body>
</html>
</body>
</html>

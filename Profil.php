<!DOCTYPE html>
<?php
session_start();

// =========================================================
// 🔐 Sécurité : Vérifie si l'utilisateur est connecté
// =========================================================
if (!isset($_SESSION['user_mail']) && isset($_COOKIE['user_mail'])) {
    $_SESSION['user_mail'] = $_COOKIE['user_mail'];
}


// =========================================================
// 💾 Connexion à la base de données
// =========================================================
$pdo = new PDO("mysql:host=localhost;dbname=bdd;charset=utf8","root","");

// Identifiant utilisateur connecté
$UserID = $_SESSION['user_mail'];

// =========================================================
// 🌐 Gestion de la langue
// =========================================================

$lang = $_GET['lang'] ?? 'fr';
$texts = [
    'fr' => [
        'title' => "Mon espace personnel",
        'logout' => "Se déconnecter",
        'first_name' => "Prénom",
        'last_name' => "Nom",
        'gender' => "Genre",
        'male' => "Homme",
        'female' => "Femme",
        'other' => "Autre",
        'dob' => "Date de naissance",
        'email' => "E-mail",
        'address' => "Adresse postale",
        'phone' => "Numéro de téléphone",
        'photo' => "Photo de profil",
        'card' => "Carte bancaire",
        'save' => "Enregistrer les modifications",
        'switch' => "Switch to English 🇬🇧",
        'updated' => "Profil mis à jour avec succès !",
        'hello' => "Bonjour",
        'logout_msg' => "Déconnexion réussie."
    ],
    'en' => [
        'title' => "My personal space",
        'logout' => "Log out",
        'first_name' => "First name",
        'last_name' => "Last name",
        'gender' => "Gender",
        'male' => "Male",
        'female' => "Female",
        'other' => "Other",
        'dob' => "Date of birth",
        'email' => "Email",
        'address' => "Postal address",
        'phone' => "Phone number",
        'photo' => "Profile photo",
        'card' => "Credit card",
        'save' => "Save changes",
        'switch' => "Version française 🇫🇷",
        'updated' => "Profile updated successfully!",
        'hello' => "Hello",
        'logout_msg' => "You have been logged out successfully."
    ]
];
$t = $texts[$lang];

// =========================================================
// 🔄 Récupération de l'utilisateur
// =========================================================
$stmt = $pdo->prepare("SELECT * FROM user WHERE Mail = ?");
$stmt->execute([$UserID]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// =========================================================
// 📝 Mise à jour du profil
// =========================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $Prenom = trim($_POST['prenom'] ?? '');
    $Nom = trim($_POST['nom'] ?? '');
    $Genre = trim($_POST['genre'] ?? '');
    $Date_naissance = trim($_POST['dob'] ?? '');
    $Mail = trim($_POST['email'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $Numero = trim($_POST['tel'] ?? '');
    $RIB = trim($_POST['rib']);

    // Gestion de la photo
    if (!empty($_FILES['photo']['name'])) {
        $photo_name = "user_" . $UserID . "_" . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], "Image_Profil/" . $photo_name);
        $PhotoProfil = $photo_name; // nom de fichier pour BDD
    } else {
        $PhotoProfil = $user['photo'] ?? null;
    }

    // Mise à jour en BDD
    $sql = "UPDATE user SET prenom=?, nom=?, genre=?, date_naissance=?, Mail=?, adresse=?, Numero=?, PhotoProfil=? RIB=?,WHERE Mail=?";
    $pdo->prepare($sql)->execute([
        $Prenom,
        $Nom,
        $Genre,
        $Date_naissance,
        $Mail,
        $adresse,
        $Numero,
        $PhotoProfil,
        $UserID,
        $RIB
    ]);

    echo "<script>alert('{$t['updated']}');</script>";

    // Rafraîchit les données
    $stmt = $pdo->prepare("SELECT * FROM user WHERE Mail = ?");
    $stmt->execute([$UserID]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}


// =========================================================
// 🚪 Déconnexion
// =========================================================
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: Se_connecter.php?msg=" . urlencode($t['logout_msg']));
    exit;
}
include("Outils/config.php");

$photo = null; // Valeur par défaut

if (isset($_SESSION['user_mail'])) {
    $mail = $_SESSION['user_mail'];
    $stmt = $conn->prepare("SELECT PhotoProfil FROM user WHERE Mail = ?");
    $stmt->bind_param("s", $mail);
    $stmt->execute();
    $stmt->bind_result($photo);
    $stmt->fetch();
    $stmt->close();
}

$photoPath = $photo ? "Image_Profil/" . htmlspecialchars($photo) : "Image/default.png";
$fields = [];
$params = [];

if (!empty($_POST['prenom'])) {
    $fields[] = "prenom=?";
    $params[] = trim($_POST['prenom']);
}
if (!empty($_POST['nom'])) {
    $fields[] = "nom=?";
    $params[] = trim($_POST['nom']);
}
if (!empty($_POST['dob'])) {
    $fields[] = "date_naissance=?";
    $params[] = trim($_POST['dob']);
}
// etc. pour tous les champs

// Gestion photo
if (!empty($_FILES['photo']['name'])) {
    $photo_name = "user_" . $UserID . "_" . basename($_FILES['photo']['name']);
    move_uploaded_file($_FILES['photo']['tmp_name'], "Image_Profil/" . $photo_name);
    $fields[] = "photo=?";
    $params[] = $photo_name;
}

// On finit par le WHERE
$params[] = $UserID;

// Prépare et execute
if (!empty($fields)) {
    $sql = "UPDATE user SET " . implode(", ", $fields) . " WHERE Mail=?";
    $pdo->prepare($sql)->execute($params);
}

// Langue
if(isset($_GET["lang"])) {
    $_SESSION["lang"] = $_GET["lang"];
}
$lang = $_SESSION["lang"] ?? "fr";
$text = require "Outils/lang_$lang.php";
?>

<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($t['title']) ?></title>
    <link rel="stylesheet" href="CSS/Profil.css">
    <link rel="stylesheet" href="CSS/Sombre_Profil.css">

    <script>
    function showForm(formId) {

        // cacher tous les formulaires
        document.querySelectorAll("form").forEach(f => f.classList.add("hidden"));

        // afficher le bon formulaire
        document.getElementById(formId).classList.remove("hidden");

        // mettre à jour menu actif
        document.querySelectorAll(".menu-item").forEach(item => item.classList.remove("active"));
        event.target.classList.add("active");
        
    }
</script>
</head>
<body>
<header class="head">
        <a href=Page_d_acceuil.php><img class="logo_clair" src ="Image/LOGO.png"/></a>
        <a href=Page_d_acceuil.php><img class="logo_sombre" src ="Image/LOGO_BLANC.png"/></a>
        <a href="javascript:void(0)" class="Sombre" onclick="darkToggle()">
        <img src="Image/Sombre.png" class="Sombre1" />
        <img src="Image/SombreB.png" class="SombreB" />
        </a>
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
                        <li><a href="Se_deconnecter.php"><button>Se déconnecter</button></a></li>
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

        </ul>
        </nav>
     
    </header>

<main style="display:flex; gap:20px;">

    <!-- MENU GAUCHE -->
    <div class="sidebar">
        <h2>Menu</h2>
        <div class="menu-item active" onclick="showForm('form1', this)">Informations personnelles</div>
        <div class="menu-item" onclick="showForm('form2', this)">Informations de connexion</div>
        <div class="menu-item" onclick="showForm('form3', this)">Moyen de paiement</div>
        <div class="menu-item" onclick="showForm('form4', this)">Espace conducteur</div>
        <div class="menu-item" onclick="showForm('form5', this)">Historique</div>

    </div>

    <!-- CONTENU DROITE -->
    <div class="content">

        <!-- FORM 1 -->
        <form id="form1" class="form-section" method="POST" enctype="multipart/form-data">
<!--Photo--------------------------------------------------------------------------------------------------------------------------------------->
            <label><?= $t['photo'] ?> :</label>
            <img src="<?= $photoPath ?>" style="width:120px; height:120px; border-radius:50%; display:block;">
            <input type="file" name="PhotoProfil" accept="image/*">
<!--Prenom--------------------------------------------------------------------------------------------------------------------------------------->
            <label><?= $t['first_name'] ?> :</label>
            <input type="text" name="prenom" value="<?= htmlspecialchars($user['Prenom'] ?? '') ?>">
<!--Nom--------------------------------------------------------------------------------------------------------------------------------------->
            <label><?= $t['last_name'] ?> :</label>
            <input type="text" name="nom" value="<?= htmlspecialchars($user['Nom'] ?? '') ?>">
<!--Genre--------------------------------------------------------------------------------------------------------------------------------------->
            <label><?= $t['gender'] ?> :</label>
            <select name="genre" value="<?= htmlspecialchars($user['Genre'] ?? '') ?>">
                <option value=""><?= $lang === 'fr' ? '-- Sélectionner --' : '-- Select --' ?></option>
                <option value="male"   <?= ($user['genre'] ?? '') === 'male'   ? 'selected' : '' ?>><?= $t['male'] ?></option>
                <option value="female" <?= ($user['genre'] ?? '') === 'female' ? 'selected' : '' ?>><?= $t['female'] ?></option>
                <option value="other"  <?= ($user['genre'] ?? '') === 'other'  ? 'selected' : '' ?>><?= $t['other'] ?></option>
            </select>
<!--Date de naissonce--------------------------------------------------------------------------------------------------------------------------------------->
            <label><?= $t['dob'] ?> :</label>
            <input type="date" name="dob" value="<?= htmlspecialchars($user['date_naissance'] ?? '') ?>">
<!--Mail--------------------------------------------------------------------------------------------------------------------------------------->
            <label><?= $t['email'] ?> :</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['Mail'] ?? '') ?>">
<!--Adresse--------------------------------------------------------------------------------------------------------------------------------------->
            <label><?= $t['address'] ?> :</label>
            <input type="text" name="adresse" value="<?= htmlspecialchars($user['adresse'] ?? '') ?>">
<!--Telephone--------------------------------------------------------------------------------------------------------------------------------------->
            <label><?= $t['phone'] ?> :</label>
            <input type="tel" name="tel" value="<?= htmlspecialchars($user['Numero'] ?? '') ?>">
<!-------------------------------------------------------------------------------------------------------------------------------------------------->

            <button type="submit" class="save"><?= $t['save'] ?></button>
        </form>

        <!-- FORM 2 -->
        <form id="form2" class="form-section hidden" method="POST">
            <h3>Modifier votre mot de passe</h3>

            <label>Mot de passe actuel :</label>
            <input type="password" name="old_pass">

            <label>Nouveau mot de passe :</label>
            <input type="password" name="new_pass">

            <button type="submit" class="save"><?= $t['save'] ?></button>
        </form>

        <!-- FORM 3 -->
        <form id="form3" class="form-section hidden" method="POST">
            <h3><?= $t['card'] ?></h3>

            <input type="text" placeholder="**** **** **** ****" disabled>

            <h4>RIB</h3>
            <input type="text" name="rib"value="<?= htmlspecialchars($user['RIB'] ?? '') ?>"placeholder="FR***********************">

            <button type="submit" class="save"><?= $t['save'] ?></button>
        </form>

    </div>
</main>

<footer>
    <p>© 2025 Drive Us — <?= $lang === 'fr' ? 'Tous droits réservés' : 'All rights reserved' ?></p>
</footer>


<script>
function showForm(formId, element) {

    // Cacher tous les formulaires
    document.querySelectorAll(".form-section").forEach(f => f.classList.add("hidden"));

    // Afficher le bon formulaire
    document.getElementById(formId).classList.remove("hidden");

    // Active menu
    document.querySelectorAll(".menu-item").forEach(item => item.classList.remove("active"));
    element.classList.add("active");
}
</script>

</body>

<?php
session_start();

if (!isset($_SESSION['user_mail']) && isset($_COOKIE['user_mail'])) {
    $_SESSION['user_mail'] = $_COOKIE['user_mail'];
}

// Langue
    if(isset($_GET["lang"])) {
        $_SESSION["lang"] = $_GET["lang"];
    }
    $lang = $_SESSION["lang"] ?? "fr";
    $text = require "Outils/lang_$lang.php";
// Connexion BDD
$ca = new PDO("mysql:host=localhost;dbname=bdd;charset=utf8", "root", "");

// Récupérer l'utilisateur connecté
$user = null;
if(isset($_SESSION['user_mail'])){
    $stmt = $ca->prepare("SELECT * FROM user WHERE Mail = ?");
    $stmt->execute([$_SESSION['user_mail']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$user) die("Utilisateur introuvable !");
}

// Gestion des actions (supprimer, publier, brouillon)
if(isset($_GET['action'], $_GET['trajet_id'])){
    $trajet_id = (int)$_GET['trajet_id'];
    $action = $_GET['action'];

    if($action === 'supprimer'){
        $stmt = $ca->prepare("DELETE FROM trajet WHERE TrajetID=? AND ConducteurId=?");
        $stmt->execute([$trajet_id, $user['UserID']]);
    } elseif($action === 'publier' || $action === 'brouillon'){
        $stmt = $ca->prepare("UPDATE trajet SET statut=? WHERE TrajetID=? AND ConducteurId=?");
        $stmt->execute([$action, $trajet_id, $user['UserID']]);
    }

    header("Location: Mes_trajets.php");
    exit;
}

// Récupérer les trajets de l'utilisateur
$stmt = $ca->prepare("SELECT * FROM trajet WHERE ConducteurId=? ORDER BY DateDepart DESC");
$stmt->execute([$user['UserID']]);
$trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mes trajets — Drive Us</title>
<link rel="stylesheet" href="CSS/Mes_trajet.css">
</head>
<body>

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
                <li>
                    <a href="javascript:void(0)" class="Sombre" onclick="darkToggle()">
                        <img src="Image/Sombre.png" class="Sombre1" />
                        <img src="Image/SombreB.png" class="SombreB" />
                    </a>
                </li>

            </ul>
        </header>


<main class="container">
    <h1>Mes trajets</h1>

    <?php if(empty($trajets)): ?>
        <p>Vous n'avez encore publié aucun trajet.</p>
    <?php else: ?>
        <table border="1" cellpadding="10" cellspacing="0">
            <thead>
                <tr>
                    <th>Départ → Arrivée</th>
                    <th>Date / Heure</th>
                    <th>Places</th>
                    <th>Prix (€)</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($trajets as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['VilleDepart'] . " → " . $t['VilleArrivee']) ?></td>
                    <td><?= htmlspecialchars($t['DateDepart'] . " " . $t['heure']) ?></td>
                    <td><?= htmlspecialchars($t['nombre_places']) ?></td>
                    <td><?= htmlspecialchars($t['Prix']) ?></td>
                    <td><?= htmlspecialchars($t['statut']) ?></td>
                    <td>
                        <a href="Publier_un_trajet.php?trajet_id=<?= $t['TrajetID'] ?>">Modifier</a> |
                        <?php if($t['statut'] === 'brouillon'): ?>
                            <a href="?action=publier&trajet_id=<?= $t['TrajetID'] ?>">Publier</a> |
                        <?php else: ?>
                            <a href="?action=brouillon&trajet_id=<?= $t['TrajetID'] ?>">Brouillon</a> |
                        <?php endif; ?>
                        <a href="?action=supprimer&trajet_id=<?= $t['TrajetID'] ?>" onclick="return confirm('Voulez-vous vraiment supprimer ce trajet ?');">Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

<footer class="site-footer">
    <div class="container">
        © 2025 Drive Us
    </div>
</footer>

</body>
</html>

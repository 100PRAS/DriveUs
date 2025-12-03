<!DOCTYPE html>
<?php
include("Outils/config.php"); 
session_start();

// Si déjà connecté, rediriger
if (isset($_SESSION['user_mail']) || isset($_COOKIE['user_mail'])) {
    header("Location: Page_d_acceuil.php");
    exit;
}

$message = ""; // pour afficher les erreurs

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mail = trim($_POST['Identifiant']);
    $mdp = trim($_POST['MDP']);

    $stmt = $conn->prepare("SELECT MotDePasseH FROM user WHERE Mail = ?");
    $stmt->bind_param("s", $mail);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($hash);
    $stmt->fetch();

    if ($hash && password_verify($mdp, $hash)) {
        // Connexion réussie
        $_SESSION['user_mail'] = $mail;
        setcookie('user_mail', $mail, time() + (30*24*60*60), "/"); // remember me
        header("Location: Page_d_acceuil.php");
        exit;
    } else {
        $message = "Identifiant ou mot de passe incorrect.";
    }

    $stmt->close();
}
?>


<html>
<head>
    <title>Drive Us</title>
    <link rel="stylesheet" href="CSS/Se_connecter.css" />
    <link rel="stylesheet" href="CSS/Sombre_Connexion.css" />
    <script src="JS/Popup.js"></script>
    <script src="JS/Sombre.js"></script>
    <?php
    // Langue
    if(isset($_GET["lang"])) $_SESSION["lang"] = $_GET["lang"];
    $lang = $_SESSION["lang"] ?? "fr";
    $text = require "Outils/lang_$lang.php";
    ?>
</head>
<body>
    <!-- Bande d'ariane -->
    <header class="head">
            <a href=Page_d_acceuil.php><img class="logo_clair" src ="Image/LOGO.png"/></a>
            <a href=Page_d_acceuil.php><img class="logo_sombre" src ="Image/LOGO_BLANC2.png"/></a>
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

    <!-- Zone de connexion -->
    <main>
        <div class="rectangle">
            <form class="formulaire"method="POST" action="">
                <input class="Mail" type="text" name="Identifiant" placeholder="<?= $text["identifiant"] ?? "" ?>" required minlength="4" size="30" required/>
                <input class="MDP" type="password" name="MDP" placeholder="<?= $text["Mot2"] ?? "" ?>" required minlength="4" size="30" required/>
                <?php if($message): ?>
                    <p style="color:red;"><?= $message ?></p>
                <?php endif; ?>
                    <button type="submit" class="connexion"><?= $text["Bouton_S"] ?? "Se connecter" ?></button>
            </form>
            <script src="https://accounts.google.com/gsi/client" async></script>
            <div class="google-wrapper">
                <div id="g_id_onload"
                    data-client_id="857561252718-s2t7pdiofp5hkprl7e7fmggmvvkrlhp5.apps.googleusercontent.com"
                    data-callback="handleCredentialResponse"
                    data-auto_prompt="false">
                </div>
                <div class="g_id_signin" data-type="standard"></div>
            </div>
            <script>
                function handleCredentialResponse(response) {
                fetch("Outils/google_login.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: "credential=" + encodeURIComponent(response.credential)
                })
                .then(() => window.location.href = "Page_d_acceuil.php");
                }
            </script>
            <a href="S_inscrire.php"><button class="Inscription"><?= $text["Inscription"] ?? "" ?></button></a>
            <button class="reinitialisation" onclick="togglePopup()"><?= $text["Mot"] ?? "" ?></button>
            <div id="popup-overlay" class="overlay">
                <div class="popup-content">
                    <a href="javascript:void(0)" class="fermer" onclick="togglePopup()">
                        <img class ="fermer"src="Image/croix.png" alt="Fermer">
                    </a>
                    <iframe src="Outils/Reinitialiser.php" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </main>
    <!--Pied de page----------------------------------------------------------------------------------------------------------------------------->

    <footer class = "Pied">
        <p>© 2025 Drive Us — Partagez vos trajets, économisez et voyagez ensemble.<p>
        <p class="pC">Contact : Drive.us@gmail.com</p>
        <p class="CGU"><a href=CGU.php><?= $text["CGU"] ?? "" ?></a></p> 
    </footer>
    <script src="JS/Hamburger.js"></script>

</body>
</html>

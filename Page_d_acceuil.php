<?php
    session_start();

    // BDD Ville
    $pdo = new PDO("mysql:host=localhost;dbname=ville;charset=utf8","root","");

    $req = $pdo->query("SELECT ville_nom FROM villes_france_free ORDER BY ville_nom");
    $req2 = $pdo->query("SELECT ville_code_postal FROM villes_france_free ORDER BY ville_code_postal");

    // Cookie
    if (!isset($_SESSION['user_mail']) && isset($_COOKIE['user_mail'])) {
        $_SESSION['user_mail'] = $_COOKIE['user_mail'];
    }

    // Langue
    if(isset($_GET["lang"])) {
        $_SESSION["lang"] = $_GET["lang"];
    }
    $lang = $_SESSION["lang"] ?? "fr";
    $text = require "Outils/lang_$lang.php";

    // Photo
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
?>

<!DOCTYPE html>
<html>
    <head>     
        <title> Drive Us </title>
        <link rel="stylesheet" href="CSS/Page_d_accueil1.css" />
                <link rel="stylesheet" href="CSS/Sombre_Acceuil.css" />

        <link rel="icon" type="Image/vnd.icon" href="/Image/Icone.ico">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src = "JS/Popup.js"></script>
        <script src = "JS/Date.js"></script>
        <script src="JS/Sombre.js"></script>
    </head>

    <body>
    
<!--Bande d'ariane---------------------------------------------------------------------------------------------------------------------------->
   
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

<!--Presentation------------------------------------------------------------------------------------------------------------------------------>
        <main>
            
            <p class="p1"><b><?= $text["titre1"] ?? "" ?><br><?= $text["titre2"] ?? "" ?></b></p>
            <p class="p2"><br><?= $text["titre3"] ?? "" ?></p>
            <ul class = "T_P">
                <li><a href=Trouver_un_trajet.php><button class="Boutton_Rechercher"><?= $text["bouton_rechercher"] ?? "" ?></button></a></li>
                <li><a href=Publier_un_trajet.php><button class ="Boutton_Proposer"><?= $text["bouton_proposer"] ?? "" ?></button></a></li>
            </ul>
            <img class="img" src="Image/Illustration.png" alt="Illusatrtion"/>
            <img class="imgB" src="Image/IllustrationB.png" alt="Illusatrtion"/>


            <ul class="Zone_Recherche">
                <li>
                    <input type="text" list="villes" class="Depart"
                        id="Villes_Depart"
                        placeholder="<?= $text["Placeholder_D"] ?? "" ?>"
                        name="ville_depart"
                        required
                        minlength="2"
                        size="20"/>

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
                </li>

                <li>
                    <input list="villes"class='Arrivé'type="text"
                        id="Villes-Arivé"
                        placeholder="<?= $text["Placeholder_A"] ?? "" ?>"
                        name="Ville_d_arrivé'"
                        required
                        minlength="2"
                        size="20"/>

                </li>
            
                <li>
                    <input class="Date" 
                        type="date"
                        id="start"
                        placeholder="<?= $text["Date"] ?? "" ?>"
                        name="trip-start"/>
                </li>
                <li>
                    <button class='Boutton_Recherche'><?= $text["bouton_recherche"] ?? "" ?></button>
                </li>
            </ul>
            <hr>


            <div class="rectangle">
                <p class = "p3"><b><?= $text["Comment_ça_marche"] ?? "" ?></b></p>
                <ul class = "A_propos">
                    <li class="Voiture">
                        <img  src ="Image/Voiture.png" Alt="Voiture"/>
                        <BR><p><b><?= $text["Publier"] ?? "" ?></b><BR><?= $text["text1"] ?? "" ?><BR><?= $text["text2"] ?? "" ?></p>
                    </li>
                    <li class="Position">
                        <img  src ="Image/Position.png" Alt="Position"/>
                        <BR><p><b><?= $text["Reserver"] ?? "" ?></b><BR><?= $text["text4"] ?? "" ?><BR><?= $text["text5"] ?? "" ?></p>
                    </li>
                    <li class="Personne">
                        <img  src ="Image/Personne.png" Alt="Personne"/>
                        <BR><p><b><?= $text["text6"] ?? "" ?></b><BR><?= $text["text7"] ?? "" ?><BR><?= $text["text8"] ?? "" ?></p>
                    </li>
                </ul>
                <p class=p4><b><?= $text["Pourquoi_nous_choisir"] ?? "" ?></b></p>
                <ul class = "A_propos2">
                    <li>
                        <img class="economique" src ="Image/economique.png"/>
                        <BR><p><b><?= $text["Economiqe"] ?? "" ?></b><BR><?= $text["Texte1"] ?? "" ?></p>
                    </li>
                    <li>
                        <img class="ecologique" src ="Image/ecologgique.png"/>
                        <BR><p><b><?= $text["Ecologique"] ?? "" ?></b><BR><?= $text["Texte2"] ?? "" ?></p>
                    </li>
                    <li>
                        <img class="Rencontre" src ="Image/Rencontre.png"/>
                        <BR><p><b><?= $text["Rencontre"] ?? "" ?></b><BR><?= $text["Texte3"] ?? "" ?></p>
                    </li>
                    <li>
                        <img class="Fiable" src ="Image/Fiable.png"/>
                        <BR><p><b><?= $text["Fiable"] ?? "" ?></b><BR><?= $text["Texte4"] ?? "" ?></p>
                    </li>
                </ul>
            </div>
            <button class="assistant"onclick="togglePopup()"> <img  class="IA"src="Image/assistant.png" alt="Assistant"> </button>
                <div id="popup-overlay" class="overlay" onclick ="closePopup()">
                    <div class="popup-content">
                        <a href="javascript:void(0)" class="fermer" onclick="togglePopup()">
                            <p><?= $text["Fermer"] ?? "" ?></p>
                        </a>
                        <a href="javascript:void(0)" class="fermer" onclick="togglePopup()"></a>
                        <iframe src="Outils/Assistant.php" frameborder="0"></iframe>
                    </div>
                </div>
        </main>
        <footer class = "Pied">
            <p>© 2025 Drive Us — Partagez vos trajets, économisez et voyagez ensemble.<p>
            <p class="pC">Contact : Drive.us@gmail.com</p>
            <p class="CGU"><a href=CGU.php><?= $text["CGU"] ?? "" ?></a></p> 
        </footer>

        <script src="JS/Hamburger.js"></script>

    </body>   
</html>

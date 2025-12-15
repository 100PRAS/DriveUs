<?php
session_start();

// Système de langue unifié
require_once 'Outils/config/langue.php';

// BDD Ville
$pdo = new PDO("mysql:host=localhost;dbname=ville;charset=utf8","root","");

$req = $pdo->query("SELECT ville_nom FROM villes_france_free ORDER BY ville_nom");
$req2 = $pdo->query("SELECT ville_code_postal FROM villes_france_free ORDER BY ville_code_postal");

// Cookie
if (!isset($_SESSION['UserID']) && isset($_COOKIE['UserID'])) {
    $_SESSION['UserID'] = $_COOKIE['UserID'];
}
?>

<!DOCTYPE html>
<html>
    <head>     
        <title> Drive Us </title>
        <link rel="stylesheet" href="CSS/Outils/layout-global.css" />
        <link rel="stylesheet" href="CSS/Page_d_accueil1.css" />
        <link rel="stylesheet" href="CSS/Sombre/Sombre_Acceuil.css" />
        <link rel="stylesheet" href="CSS/Outils/Footer.css" />
        <link rel="stylesheet" href="CSS/Outils/Header.css" />
  <link rel="icon" type="image/x-icon" href="Image/Icone.ico">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src = "JS/Popup.js"></script>
        <script src = "JS/Date.js"></script>
        <?php 
            // Charger les infos de session et langue DEPUIS le header
            if (!isset($_SESSION)) {
                session_start();
            }
            if(isset($_GET["lang"])) {
                $_SESSION["lang"] = $_GET["lang"];
            }
            $lang = $_SESSION["lang"] ?? "fr";
            $text = require __DIR__ . "/Outils/config/lang_$lang.php";
        ?>
    </head>

    <body>
        <?php include 'Outils/views/header.php'; ?>

<!--Presentation------------------------------------------------------------------------------------------------------------------------------>
        <main>
            
            <p class="p1"><b><?= $text["titre1"] ?? "" ?><br><?= $text["titre2"] ?? "" ?></b></p>
            <p class="p2"><br><?= $text["titre3"] ?? "" ?></p>
            <ul class = "T_P">
                            <ul class = "T_P">
                <li><a href=Trouver_un_trajet.php><button class="Boutton_Rechercher"><?= $text["bouton_rechercher"] ?? "" ?></button></a></li>
                <li><a href=Publier_un_trajet.php><button class ="Boutton_Proposer"><?= $text["bouton_proposer"] ?? "" ?></button></a></li>
            </ul>
            <img class="img" src="Image/Illustration2.png" alt="Illusatrtion"/>
            <img class="imgB" src="Image/Illustration2.png" alt="Illusatrtion"/>


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
                        name="trip-start"
                        min=""/>
                </li>
                <li>
                    <button class='Boutton_Recherche' id="searchButton"><?= $text["bouton_recherche"] ?? "" ?></button>
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
        <?php include 'Outils/views/footer.php'; ?>

        <script src="JS/Hamburger.js"></script>
<script>
// Charger le thème dès l'ouverture de la page
if (localStorage.getItem("theme") === "dark") {
    document.body.classList.add("dark");
}

// Bouton du thème
const themeToggleBtn = document.getElementById("themeToggle");
if (themeToggleBtn) {
    themeToggleBtn.addEventListener("click", function () {
        document.body.classList.toggle("dark");

        // Sauvegarder le choix
        if (document.body.classList.contains("dark")) {
            localStorage.setItem("theme", "dark");
        } else {
            localStorage.setItem("theme", "light");
        }
    });
}

// Lier la zone de recherche à la page de recherche
document.getElementById('searchButton').addEventListener('click', function() {
    const from = document.getElementById('Villes_Depart').value.trim();
    const to = document.getElementById('Villes-Arivé').value.trim();
    const date = document.getElementById('start').value;
    
    // Construire l'URL avec les paramètres
    let url = 'Trouver_un_trajet.php?';
    const params = [];
    
    if (from) params.push('from=' + encodeURIComponent(from));
    if (to) params.push('to=' + encodeURIComponent(to));
    if (date) params.push('date=' + encodeURIComponent(date));
    
    url += params.join('&');
    
    // Rediriger vers la page de recherche
    window.location.href = url;
});

// Permettre la recherche avec la touche Entrée
['Villes_Depart', 'Villes-Arivé', 'start'].forEach(id => {
    const element = document.getElementById(id);
    if (element) {
        element.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('searchButton').click();
            }
        });
    }
});

// Bloquer les dates antérieures à aujourd'hui
const today = new Date().toISOString().slice(0, 10);
const startInput = document.getElementById('start');
if (startInput) {
    startInput.min = today;
}
</script>

    </body>   
</html>

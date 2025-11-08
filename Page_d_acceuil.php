<!DOCTYPE html>
<?php
// connexion à la BDD
$pdo = new PDO("mysql:host=localhost;dbname=ville;charset=utf8","root","");

// récupération des villes
$req = $pdo->query("SELECT ville_nom FROM villes_france_free ORDER BY ville_nom");
$req2 = $pdo->query("SELECT ville_code_postal FROM villes_france_free ORDER BY ville_code_postal")
?>
<html>
<head>     
    <title> Drive Us </title>
    <link rel="stylesheet" href="CSS/Page_d_acceuil_CSS.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


</head>

<body>
    <script src = "JS/Popup.js"></script>
    <script src = "JS/Date.js"></script>

<!--Bande d'ariane---------------------------------------------------------------------------------------------------------------------------->
    <header class="head">
        <a href=Page_d_acceuil.php><img class="logo" src ="Image/LOGO.png"/></a>
        <nav class=nav>  
            <ul class = "Bande">
                <li><a href=Page_d_acceuil.php><Button class="Boutton_Acceuil">Acceuil</Button></a></li>
                <li><a href=Trouver_un_trajet.php><Button class="Boutton_Trouver">Trouver un trajet</button></a></li>
                <li><a href=Publier_un_trajet.php><Button class = "Boutton_Publier">Publier un trajet</Button></a></li>
                <li><a href="Messagerie.php"><button class="Messagerie">Messagerie</button></a></li>
                <li><a href=Se_connecter.php><button class="Boutton_Se_connecter">Se connecter</button></a></li>
            </ul>
        </nav>

    </header>
<!--Presentation------------------------------------------------------------------------------------------------------------------------------>
<main>
    <p class="p1"><b>Partager vos trajets,<BR> économiser et voyager ensemble ! </b></p>
    <p class = "p2">Trouver un conducteur ou un passager en quelques clics</p>
    <ul class = "T_P">
        <li><a href=Trouver_un_trajet.php><button class="Boutton_Rechercher">Rechercher un trajet</button></a></li>
        <li><a href=Publier_un_trajet.php><button class ="Boutton_Proposer">Prosposer un trajet</button></a></li>
    </ul>
    <img class="img" src="Image/Illustration.png"/>
<!--Recherche----------------------------------------------------------------------------------------------------------------------------->

    <ul class="Zone_Recherche">
        <li>
            <input type="text" list="villes" class="Depart"
                id="Villes"
                placeholder="Ville de départ"
                name="ville_depart"
                required
                minlength="2"
                size="20"/>

            <datalist id="villes">
                <?php
                while($v = $req->fetch() ){
                echo "<option value='".htmlspecialchars($v['ville_nom'])."'>";
                }
                while($c = $req2->fetch() ){
                echo "<option value='".htmlspecialchars($c['ville_code_postal'])."'>";
                }
                ?>
            </datalist>
        </li>

        <li>
            <input list="villes"class='Arrivé'type="text"
                id="Villes"
                placeholder="Ville d'arrivé"
                name="Ville d'arrivé'"
                required
                minlength="2"
                size="20"/>
            <datalist id="villes">
                <?php
                while($v = $req->fetch()){
                echo "<option value='".htmlspecialchars($v['ville_nom'])."'>";
                }
                ?>
            </datalist>
        </li>
            
        <li>
            <input class="Date" 
                type="date"
                id="start"
                name="trip-start"
                min ="today"/>
        </li>
        <li>
            <button class='Boutton_Recherche'>Recherche</button>
        </li>
    </ul>
    <button class="assistant"onclick="togglePopup()"> <img  class="IA"src="Image/assistant.png" alt="Assistant"> </button>
    <div id="popup-overlay" class="overlay" onclick ="closePopup()">
        <div class="popup-content">
            <a href="javascript:void(0)" class="fermer" onclick="togglePopup()">
            <img class ="fermer"src="Image/croix.png" alt="Fermer">
            </a>
            <a href="javascript:void(0)" class="fermer" onclick="togglePopup()">
            </a>
            
            <iframe src="Assistant.php" frameborder="0"></iframe>
        </div>
    </div>
<hr>

<!--A propos1--------------------------------------------------------------------------------------------------------------------------------->

    <div class="rectangle">
        <p class = "p3"><b> Comment ça marche ?</b></p>
            <ul class = "A_propos">
                <li class="Voiture">
                    <img  src ="Image/Voiture.png"/>
                    <BR><p><b>Publier votre trajet</b><BR>Entrer votre départ, destination, date <BR>et nombre de place</p>
                </li>
                <li class="Position">
                    <img  src ="Image/Position.png"/>
                    <BR><p><b>Réserver facilement</b><BR>Choissiser un trajet selon<BR> vos préferences</p>
                </li>
                <li class="Personne">
                    <img  src ="Image/Personne.png"/>
                    <BR><p><b>Voyager ensemble!</b><BR>Rencontrer, partager<BR> et économiser</p>
                </li>
            </ul>
    <!--A propos 2--------------------------------------------------------------------------------------------------------------------------->
        <p class=p4><b> Pourquoi nous choisir ?</b></p>
            <ul class = "A_propos2">
                <li>
                    <img class="economique" src ="Image/economique.png"/>
                    <BR><p><b>Economique</b><BR>Parteger les frais de trajets</p>
                </li>
                <li>
                    <img class="ecologique" src ="Image/ecologgique.png"/>
                    <BR><p><b>Ecologique</b><BR>Reduisser votre empreinte carbonne</p>
                </li>
                <li>
                    <img class="Rencontre" src ="Image/Rencontre.png"/>
                    <BR><p><b>Convivial</b><BR>Rencontrer des personnes près de chez vous</p>
                </li>
                <li>
                    <img class="Fiable" src ="Image/Fiable.png"/>
                    <BR><p><b>Fiable</b><BR>Profils vérifiés</p>
                </li>
            </ul>
    </div>
    <div class="blanc"> </div>
    <!---------------------------------------------------------------------------------------------------------------------------------->
</main>
    <footer class = "Pied">
        <p>Contact : Drive.us@gmail.com</p>
        <p class="CGU"><a href=CGU.php>Conditions génerales d'utilisation</a></p> 
    </footer>
</body>   
</html>

<!DOCTYPE html>
<html>
<head>
    <title>Drive Us</title>
    <script src="Date.js"></script>
    <script src = "JS/Popup.js"></script>
    <link rel="stylesheet" href="CSS/Trouver_un_trajet1.css" />

</head>
<body>
    
<!--Bande d'ariane--------------------------------------------------------------------------------------------------------------------->
    <header class="head">
        <a href=Page_d_acceuil.php><img class="logo" src ="Image/LOGO.png"/></a>
        <nav class=nav>  
            <ul class = "Bande">
                <li><a href=Page_d_acceuil.php><Button class="Boutton_Acceuil">Acceuil</Button></a></li>
                <li><a href=Trouver_un_trajet.php><Button class="Boutton_Trouver">Trouver un trajet</button></a></li>
                <li><a href=Publier_un_trajet.php><Button class = "Boutton_Publier">Publier un trajet</Button></a></li>
                <li><a href=Se_connecter.php><button class="Boutton_Se_connecter">Se connecter</button></a></li>
                <li><a href="Messagerie.php"><button class="Messagerie">Messagerie</button></a></li>
            </ul>
        </nav>
    </header>
<main>


    <div>
        <input type="checkbox" id="Animaux" name="Animaux" checked />
        <label for="scales">Animaux</label>
    </div>

    <div rectangle class ="liste">
        <scroller>
        </scroller>
    </div>

<!-- Fenêtre popup -->
<button class="assistant"onclick="togglePopup()">
    <img  class="IA"src="Image/assistant.png" alt="Assistant">
</button>
<div id="popup-overlay" class="overlay">
    <div class="popup-content">
        <a href="javascript:void(0)" class="fermer" onclick="togglePopup()">
            <img class ="fermer"src="Image/croix.png" alt="Fermer">
        </a>

        <!-- Contenu de ton assistant PHP chargé ici -->
        <iframe src="Assistant.php" frameborder="0"></iframe>
    </div>
</div>
</main>

<footer class = "Pied">
        <p>Contact : Drive.us@gmail.com</p>
        <p><a href=CGU.php>Conditions génerales d'utilisation</a></p> 
</footer>
</body>
</html>
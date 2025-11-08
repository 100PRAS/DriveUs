<!DOCTYPE html>
<html>
<head>
    <title>Drive Us</title>
</head>
<body>
    <link rel="stylesheet" href="CSS/Se_connecter.css" />
    <script src = "JS/Popup.js"></script>
    <script src = "JS/Google.js"></script>

    <!--Bande d'ariane---------------------------------------------------------------------------------------------------------------------------->
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
    <!--Zone de connexion---------------------------------------------------------------------------------------------------------------------------->
<main>
    <div class= "rectangle">
        <div class="Crectangle"><p1> Se connecter</p1></Button></div>
             
        <input class='Mail'type="text"
            id="Mail"
            placeholder="Pseudo, adresse-mail ou numéro"
            name="Identifiant'"
            required
            minlength="4"
            size="30"/>
            
        <input class='MDP'type="Password"
            id="pass"
            placeholder="Mot de passe"
            name="MDP"
            required
            minlength="4"
            size="30"/>

        <script src="https://accounts.google.com/gsi/client" async>
        </script>
        <div id="g_id_onload"
            data-client_id="YOUR_GOOGLE_CLIENT_ID"
            data-login_uri="https://your.domain/your_login_endpoint"
            data-auto_prompt="false">
        </div>
        <div class="g_id_signin"
            data-type="standard"
            data-size="short"
            data-theme="outline"
            data-text="sign_in_with"
            data-shape="rectangular"
            data-logo_alignment="left"
            data-width="100">
        </div>

        <a href ="S_inscrire.php"><Button class="Inscription">S'incrire</button></a>

        <button class="reinitialisation" onclick="togglePopup()">Mot de passe oublié ?</button>
        <div id="popup-overlay" class="overlay">
            <div class="popup-content">
                <a href="javascript:void(0)" class="fermer" onclick="togglePopup()">
                <img class ="fermer"src="Image/croix.png" alt="Fermer">
                </a>
                <iframe src="Reinitialiser.php" frameborder="0"></iframe>
            </div>
        </div>

    </div>
</main>
    <!--Pied de page----------------------------------------------------------------------------------------------------------------------------->

    <footer class = "Pied">
        <p>Contact : Drive.us@gmail.com</p>
        <p><a href=CGU.php><button class="CGU">Conditions génerales d'utilisation</button></a>
    </footer>
</body>
</html>
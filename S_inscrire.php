<!DOCTYPE html>
<html>
    <head>
    <title>Drive Us</title>
</head>
<body>
    <link rel="stylesheet" href="CSS/S_inscrire1.css" />
    <script src="JS/Inscription.js"></script>

    <!--Bande d'ariane---------------------------------------------------------------------------------------------------------------------------->
    <script src="Date.js"></script>
   <header class="head">
        <a href=Page_d_acceuil.php><img class="logo" src ="Image/LOGO.png"/></a>
        <nav class=nav>  
            <ul class = "Bande">
                <li><a href=Page_d_acceuil.php><Button class="Boutton_Acceuil">Acceuil</Button></a></li>
                <li><a href=Trouver_un_trajet.php><Button class="Boutton_Trouver">Trouver un trajet</button></a></li>
                <li><a href=Publier_un_trajet.php><Button class = "Boutton_Publier">Publier un trajet</Button></a></li>
                <li><a href=Se_connecter.php><button class="Boutton_Se_connecter">Se connecter</button></a></li>
                <li><a href="#"><button class="Messagerie">Messagerie</button></a></li>
            </ul>
        </nav>
    </header>
<!---------------------------------------------------------------------------------------------------------------------------------->
    <main>
    <ul class="menu">
      <li>Informations personelles
        <ul class="sousmenu">
          <li><input class ="Prénom" type="Name"
             id="Prénom"
             placeholder="Prénom"
             name="Prénom"
             required
             minlength="0"
             size="10"/></li>
          <li> <input class ="Nom" type="Name"
             id="Nom"
             placeholder="Nom"
             name="Nom"
             required
             minlength="0"
             size="10"/></li>
          <li><input type="number" id="Age" name="Age" min="18" max="100" placeholder="Age"/></li>
        </ul>
      </li>
      <li>Menu 2
        <ul class="sousmenu">
          <li><a href="#">Sous-menu 2</a></li>
          <li><a href="#">Sous-menu 3</a></li>
        </ul>
      </li>
      <li>Moyen de payement
        <ul class="sousmenu">
          <li>Pour payer vos payer vos réservation</li>
          <hr>
          <li>Compte pour vous virez l'argent de votre cagnotte</li>
        </ul>
      </li>
    </ul>
    </main>
<!--Pied de page ------------------------------------------------------------------------------------------------------------------->
     <footer class = "Pied">
        <p>Contact : Drive.us@gmail.com</p>
        <p><a href=CGU.php>Conditions génerales d'utilisation</a></p>
    </footer>
</body>
</html>
</body>
</html>

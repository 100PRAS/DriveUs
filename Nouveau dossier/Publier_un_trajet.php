<!DOCTYPE html>
<?php
// connexion à la BDD
$pdo = new PDO("mysql:host=localhost;dbname=ville;charset=utf8","root","");
$voitureSQL= new PDO("mysql:host=localhost;dbname=bdd;charset=utf8","root","");
// récupération des villes
$req = $pdo->query("SELECT ville_nom FROM villes_france_free ORDER BY ville_nom");
$req2 = $pdo->query("SELECT ville_code_postal FROM villes_france_free ORDER BY ville_code_postal")
?>
<html>
<head>
    <title>Drive Us</title>
</head>
<body>
    <link rel="stylesheet" href="CSS/Publier_un_trajet1.css" />
       <header class="head">
        <nav>
            <ul class = "Bande">
                <li><a href=Page_d_acceuil.php><img class="logo" src ="Image/logo.png"/></a></li>
                <li><a href=Page_d_acceuil.php><Button class="Acceuil">Acceuil</Button></a></li>
                <li><a href=Trouver_un_trajet.php><Button class="Trouver">Trouver un trajet</button></a></li>
                <li><a href=Publier_un_trajet.php><Button class = "Publier">Publier un trajet</Button></a></li>
                <li><a href="Messagerie.php"><button class="Messagerie">Messagerie</button></a></li>
                <li><a href=Se_connecter.php><button class="Se_connecter">Se connecter</button></a></li>
            </ul>
        </nav>
    </header>
<main>

 <ul class="Info"><p>Informations du trajet</p>

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
                 while($c = $req2->fetch() ){
                echo "<option value='".htmlspecialchars($c['ville_code_postal'])."'>";
                }
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
            <input type="time" id="appt" name="appt" min="09:00" max="18:00" required />
            </li>
            <li>
                <input class="Rencontre"
                type="text"
                placeholder="Lieu de rencontré"
                name="meet">
            </li>
            <li>
                <input class="Nombre de place"
                type="number"
                placeholder="Nombre de place"
                min=1
                >
            </li>
            <li>
               <input type="number" inputmode="decimal" pattern="[0-9]+([.,][0-9]+)?" 
               min="0.0"
               step="0,1"
               placeholder="Prix"/>

            </li>
            <li>
                 <input
    type="range"
    id="cowbell"
    name="cowbell"
    min="0"
    max="100"
    value="90"
    step="10" />
  <label for="cowbell">Cowbell</label>
            </li>
            </ul>
<!--Préférence------------------------------------------------------------------------------------------------------------------------>
<ul>
    <input class="description"
    type ="text"
    min ="0"
    max="250"
    placeholder="Description">
</ul>
<ul>
    <li>
    <input class="plaque"
    min ="9"
    type="text"
    placeholder="Plaque d'immatriculation"
    list="plaque"
    >
        <datalist id="Plaque">
                <?php
                $voiture = $voitureSQL->query("SELECT Plaque FROM Voiture WHERE UserID = $UserID");
                while($v1 = $voiture->fetch()){
                echo "<option value='".htmlspecialchars($v1['Plaque'])."'>";
                }
                ?>
            </datalist>
                        </li>
                        <li>
                               <input class="Modèle"
    type="text"
    placeholder="Modèle de la voiture"
    list="Modele"
    >
        <datalist id="Modele">
                <?php
                $voiture = $voitureSQL->query("SELECT Modele FROM Voiture WHERE UserID = $UserID");
                while($v1 = $voiture->fetch()){
                echo "<option value='".htmlspecialchars($v1['Modele'])."'>";
                }
                ?>
            </datalist>
                        </li>

            </ul>
    <input type="checkbox" id="scales" name="scales" checked /><p>J'accecpte  <a href="CGU.php">les condition générale</a> de Drive Us</p>
    <hr>
    <ul>
        <li><p>Comment ça marche</p></li>
        <hr>
            <li>
                <img src="Image/Voiture.png"/>
            </li>
        </hr>
    </ul>
</main>
    <footer class = "Pied">
        <p>Contact : Drive.us@gmail.com</p>
        <p><a href=CGU.php>Conditions génerales d'utilisation</a></p> 
    </footer>
</body>
</html>
<header class="head">

    <!-- LOGO -->
    <a href="Page_d_acceuil.php" class="logo-container">
        <img class="logo_clair" src="Image/LOGO.png" />
        <img class="logo_sombre" src="Image/LOGO_BLANC.png" />
    </a>

    <!-- MODE SOMBRE -->
    <a class="toggle-dark" onclick="darkToggle()">
        <img src="Image/Sombre.png" class="Sombre1" />
        <img src="Image/SombreB.png" class="SombreB" />
    </a>

    <!-- HAMBURGER -->
    <div class="hamburger" onclick="toggleMenu()">
        <span></span>
        <span></span>
        <span></span>
    </div>

    <!-- MENU -->
    <ul class="Bande" id="menuMobile">

        <li><a href="Page_d_acceuil.php"><button><?= $text["Bouton_A"] ?? "" ?></button></a></li>

        <li><a href="Trouver_un_trajet.php"><button><?= $text["Bouton_T"] ?? "" ?></button></a></li>

        <li><a href="Publier_un_trajet.php"><button><?= $text["Bouton_P"] ?? "" ?></button></a></li>

        <li><a href="Messagerie.php"><button><?= $text["Bouton_M"] ?? "" ?></button></a></li>

        <!-- LANGUE -->
        <li class="lang-container">
            <button class="Langue" onclick="menuL.hidden ^= 1"><?= $lang ?></button>
            <ul id="menuL" hidden>
                <li><a href="?lang=fr"><img src="Image/France.png" /></a></li>
                <li><a href="?lang=en"><img src="Image/Angleterre.png" /></a></li>
            </ul>
        </li>

        <!-- PROFIL -->
        <li class="profil-container">
            <?php if (!isset($_SESSION['user_mail'])): ?>
                <a href="Se_connecter.php"><button class="Boutton_Se_connecter">Se connecter</button></a>
            <?php else: ?>
                <img src="<?= $photoPath ?>" class="profil-img" onclick="menu.hidden ^= 1">
                <ul id="menu" hidden>
                    <li><a href="Page_d_acceuil.php"><button>Mon compte</button></a></li>
                    <li><a href="Se_deconnecter.php"><button>Se déconnecter</button></a></li>
                </ul>
            <?php endif; ?>
        </li>
    </ul>
</header>
<style>
    /* HEADER */
.head {
    background-color: hsla(0,0%,97%,0.97);
    padding: 10px 20px;
    width: 100%;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1000;

    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* LOGO */
.logo-container img {
    height: 50px;
    width: auto;
}

.logo_sombre { display: none; }

/* DARK MODE BUTTON */
.toggle-dark img {
    width: 35px;
    height: 35px;
    cursor: pointer;
}

/* MENU */
.Bande {
    display: flex;
    align-items: center;
    gap: 15px;
    list-style: none;
}

/* BOUTONS */
.Bande button {
    background-color: hsla(0,0%,97%,0.97);
    border: none;
    padding: 10px 18px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    transition: .3s;
}

.Bande button:hover {
    background-color: #515151;
    color: white;
}

/* HAMBURGER */
.hamburger {
    display: none;
    flex-direction: column;
    cursor: pointer;
    gap: 5px;
}

.hamburger span {
    width: 28px;
    height: 3px;
    background: black;
    border-radius: 5px;
    transition: .3s;
}

/* MOBILE MENU HIDDEN */
#menuMobile {
    transition: right .4s ease;
}

/* ✨ VERSION MOBILE ✨ */
@media (max-width: 900px) {

    /* Hamburger visible */
    .hamburger {
        display: flex;
    }

    /* Cacher le menu desktop */
    .Bande {
        position: fixed;
        top: 0;
        right: -250px;
        height: 100vh;
        width: 250px;
        flex-direction: column;
        padding-top: 80px;
        gap: 25px;
        background: white;
        box-shadow: -3px 0 10px rgba(0,0,0,0.2);
    }

    /* Quand ouvert */
    .Bande.open {
        right: 0;
    }

    .toggle-dark {
        margin-left: auto;
        margin-right: 15px;
    }
}

</style>
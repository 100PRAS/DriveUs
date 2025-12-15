<?php
// header.php - Header réutilisable pour toutes les pages

// Session et cookies
if (!isset($_SESSION)) {
    session_start();
}

// Restaurer la session depuis le cookie si nécessaire
if (!isset($_SESSION['UserID']) && isset($_COOKIE['UserID'])) {
    $_SESSION['UserID'] = $_COOKIE['UserID'];
}

// Système de langue unifié
require_once __DIR__ . '/../config/langue.php';
$text = $translations;

// Photo de profil
$photo = null;
if (isset($_SESSION['UserID'])) {
    $userId = $_SESSION['UserID'];
    require_once __DIR__ . '/../config/config.php';
    $stmt = $conn->prepare("SELECT PhotoProfil FROM user WHERE UserID = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($photo);
        $stmt->fetch();
        $stmt->close();
    }
}
// Si pas de photo, utiliser l'image par défaut
$photoPath = (!empty($photo) && $photo !== NULL) 
    ? "/DriveUs/Image_Profil/" . htmlspecialchars($photo) 
    : "/DriveUs/Image_Profil/default.png";
?>

<!-- Pré-application du thème pour éviter les flashs -->
<script>
    (function(){
        try {
            var mode = localStorage.getItem('driveus_theme');
            console.log('[Header PreLoad] Mode from localStorage:', mode);
            
            // Nettoyer d'abord
            document.documentElement.classList.remove('dark');
            
            // Appliquer le mode sauvegardé
            if(mode === 'dark') {
                document.documentElement.classList.add('dark');
                console.log('[Header PreLoad] Dark mode applied');
            } else {
                console.log('[Header PreLoad] Light mode');
            }
        } catch(e) {
            console.error('[Header PreLoad] Error:', e);
        }
    })();
</script>

<!-- Styles partagés header/footer -->
<link rel="stylesheet" href="/DriveUs/CSS/theme-init.css">
<link rel="stylesheet" href="/DriveUs/CSS/Header.css">
<link rel="stylesheet" href="/DriveUs/CSS/Footer.css">
<script src="/DriveUs/JS/Sombre.js"></script>

<header class="head">
    <a href="/DriveUs/Page_d_acceuil.php"><img class="logo_clair" src="/DriveUs/Image/LOGO.png" alt="DriveUs"/></a>
    <a href="/DriveUs/Page_d_acceuil.php"><img class="logo_sombre" src="/DriveUs/Image/LOGO_BLANC2.png" alt="DriveUs Sombre"/></a>
    <div class="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </div>
    <ul class="Bande">
        <li><a href="/DriveUs/Page_d_acceuil.php"><Button class="Boutton_Acceuil"><?= $text["Bouton_A"] ?? "Accueil" ?></Button></a></li>
        <li><a href="/DriveUs/Trouver_un_trajet.php"><Button class="Boutton_Trouver"><?= $text["Bouton_T"] ?? "Trouver" ?></Button></a></li>
        <li><a href="/DriveUs/Publier_un_trajet.php"><Button class="Boutton_Publier"><?= $text["Bouton_P"] ?? "Publier" ?></Button></a></li>
        <li><a href="/DriveUs/Messagerie.php"><button class="Messagerie"><?= $text["Bouton_M"] ?? "Messages" ?></button></a></li>
        <li><a href="/DriveUs/Forum.php"><button class="Messagerie">Forum</button></a></li>
        <li>
            <?php if (!isset($_SESSION['UserID'])): ?>
                <a href="/DriveUs/Se_connecter.php"><button class="Boutton_Se_connecter">Se connecter</button></a>
            <?php else: ?>
                <img src="<?= $photoPath ?>" alt="Profil" style="width:50px; height:50px; border-radius:50%;" onclick="menu.hidden ^= 1">
                <ul id="menu" hidden>
                    <li><a href="/DriveUs/Profil.php"><button>Mon compte</button></a></li>
                    <li><a href="/DriveUs/Outils/reservations/Mes_reservations.php"><button>Mes réservations</button></a></li>
                    <li><a href="/DriveUs/Outils/reservations/Mes_reservations_recues.php"><button>Réservations reçues</button></a></li>
                    <li><a href="/DriveUs/Outils/trips/Mes_trajets.php"><button>Mes trajets</button></a></li>
                    <li><a href="/DriveUs/Se_deconnecter.php"><button>Se déconnecter</button></a></li>
                </ul>
            <?php endif; ?>
        </li>
        <li>
            <select id="languageSelect" onchange="location.href=updateUrlParam(&quot;lang&quot;, this.value);">
                <option value="fr" <?php echo getLang() === 'fr' ? 'selected' : ''; ?>>Français</option>
                <option value="en" <?php echo getLang() === 'en' ? 'selected' : ''; ?>>English</option>
            </select>
        </li>
        <li>
            <a href="javascript:void(0)" class="Sombre" onclick="darkToggle()">
                <img src="/DriveUs/Image/Sombre.png" class="Sombre1" />
                <img src="/DriveUs/Image/SombreB.png" class="SombreB" />
            </a>
        </li>
    </ul>
</header>

<script>
    function updateUrlParam(param, value) {
        const url = new URL(window.location);
        url.searchParams.set(param, value);
        return url.toString();
    }
</script>

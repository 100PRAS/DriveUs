<?php
// header.php - Header réutilisable pour toutes les pages

// Session et cookies
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Restaurer la session depuis le cookie si nécessaire
if (!isset($_SESSION['UserID']) && isset($_COOKIE['UserID'])) {
    $_SESSION['UserID'] = $_COOKIE['UserID'];
}

// Système de langue unifié
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/langue.php';

// Photo de profil et niveau
$photo = null;
$user_niveau = null;
if (isset($_SESSION['UserID']) && $pdo instanceof PDO) {
    try {
        $stmt = $pdo->prepare("SELECT PhotoProfil, niveau FROM user WHERE UserID = :id");
        $stmt->execute(['id' => $_SESSION['UserID']]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user_data) {
            $photo = $user_data['PhotoProfil'];
            $user_niveau = $user_data['niveau'];
        }
    } catch (Throwable $e) {
        error_log('[header.php] User data fetch failed: ' . $e->getMessage());
    }
}
// Si pas de photo, utiliser l'image par défaut
$photoPath = (!empty($photo) && $photo !== NULL) 
    ? "/Image_Profil/" . htmlspecialchars($photo) 
    : "/Image_Profil/default.png";
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
<link rel="stylesheet" href="/CSS/Outils/theme-init.css">
<link rel="stylesheet" href="/CSS/Outils/Header.css">
<link rel="stylesheet" href="/CSS/Outils/Footer.css">
<script src="/JS/Sombre.js"></script>

<header class="head">
    <a href="/"><img class="logo_clair" src="/Image/LOGO.png" alt="DriveUs"/></a>
    <a href="/"><img class="logo_sombre" src="/Image/LOGO_BLANC2.png" alt="DriveUs Sombre"/></a>
    <div class="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </div>
    <ul class="Bande">
        <li><a href="/"><Button class="Boutton_Acceuil"><?= $text["Bouton_A"] ?? "Accueil" ?></Button></a></li>
        <li><a href="/trouver-trajet"><Button class="Boutton_Trouver"><?= $text["Bouton_T"] ?? "Trouver" ?></Button></a></li>
        <li><a href="/publier-trajet"><Button class="Boutton_Publier"><?= $text["Bouton_P"] ?? "Publier" ?></Button></a></li>
        <li><a href="/messages"><button class="Messagerie"><?= $text["Bouton_M"] ?? "Messages" ?></button></a></li>
        <li><a href="/forum"><button class="Messagerie">Forum</button></a></li>
        <li>
            <?php if (!isset($_SESSION['UserID'])): ?>
                <a href="/connexion"><button class="Boutton_Se_connecter">Se connecter</button></a>
            <?php else: ?>
                <img src="<?= $photoPath ?>" alt="Profil" style="width:50px; height:50px; border-radius:50%;" onclick="menu.hidden ^= 1">
                <ul id="menu" hidden>
                    <li><a href="/profil"><button>Mon compte</button></a></li>
                    <li><a href="/mes-reservations"><button>Mes réservations</button></a></li>
                    <li><a href="/mes-reservations-recues"><button>Réservations reçues</button></a></li>
                    <li><a href="/mes-trajets"><button>Mes trajets</button></a></li>
                    <li><a href="/deconnexion"><button>Se déconnecter</button></a></li>
                    <?php if ($user_niveau == 1 || $user_niveau == 2): ?>
                        <li><a href="/tableau-bord-admin"><button>Admin Dashboard</button></a></li>
                    <?php endif; ?>
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
                <img src="/Image/Sombre.png" class="Sombre1" />
                <img src="/Image/SombreB.png" class="SombreB" />
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

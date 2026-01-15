<!DOCTYPE html>
<?php

require_once 'Outils/config/config.php';
require_once 'Outils/config/langue.php';
require_once 'Outils/config/google_config.php';

// Si déjà connecté, rediriger
if (isset($_SESSION['UserID']) || isset($_COOKIE['UserID'])) {
    header("Location: index.php");
    exit;
}

$message = ""; // pour afficher les erreurs

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifiant = trim($_POST['Identifiant']);
    $mdp = trim($_POST['MDP']);

    try {
        // Chercher par Mail, Pseudo ou Numéro de téléphone
        $stmt = $pdo->prepare("SELECT UserID, MotDePasseH FROM user WHERE Mail = ? OR Nom = ? OR numero = ?");
        $stmt->execute([$identifiant, $identifiant, $identifiant]);
        $row = $stmt->fetch();
        if ($row && isset($row['UserID'], $row['MotDePasseH']) && password_verify($mdp, $row['MotDePasseH'])) {
            // Connexion réussie
            $_SESSION['UserID'] = $row['UserID'];
            setcookie('UserID', $row['UserID'], time() + (30*24*60*60), "/"); // remember me
            header("Location: index.php");
            exit;
        } else {
            $message = "Identifiant ou mot de passe incorrect.";
        }
    } catch (PDOException $e) {
        $message = "Erreur de connexion à la base de données. (" . $e->getMessage() . ")";
    }
}
?>

<html>
<head>
    <title>Drive Us</title>
    <link rel="icon" type="image/x-icon" href="Image/Icone.ico">
    <link rel="stylesheet" href="CSS/Outils/layout-global.css" />
    <link rel="stylesheet" href="CSS/Se_connecter.css" />
    <link rel="stylesheet" href="CSS/Sombre/Sombre_Connexion1.css" />
    <link rel="stylesheet" href="CSS/Outils/Header.css" />
    <link rel="stylesheet" href="CSS/Sombre/Sombre_Header.css" />
    <link rel="stylesheet" href="CSS/Outils/Footer.css" />
    <script src="JS/Popup.js"></script>
    <script src="JS/Sombre.js"></script>
</head>
<body>
    <?php include 'Outils/views/header.php'; ?>

    <!-- Zone de connexion -->
    <main>
        <div class="rectangle">
            <form class="formulaire" method="POST" action="">
                <input class="Mail" type="text" name="Identifiant" placeholder="<?= $text["identifiant"] ?? "" ?>" required minlength="4" size="30" required/>
                <input class="MDP" type="password" name="MDP" placeholder="<?= $text["Mot2"] ?? "" ?>" required minlength="4" size="30" required/>
                <?php if($message): ?>
                    <p style="color:red;"><?= $message ?></p>
                <?php endif; ?>
                <div class="actions">
                    <button type="submit" class="connexion"><?= $text["Bouton_S"] ?? "Se connecter" ?></button>
                    <a href="S_inscrire.php"><button type="button" class="Inscription"><?= $text["Inscription"] ?? "" ?></button></a>
                    <button type="button" class="reinitialisation" onclick="togglePopup()"><?= $text["Mot"] ?? "" ?></button>
                </div>
            </form>
            <script src="https://accounts.google.com/gsi/client" async defer></script>
            <div class="google-wrapper">
                <div id="g_id_onload"
                    data-client_id="<?php echo GOOGLE_OAUTH_CLIENT_ID; ?>"
                    data-callback="handleCredentialResponse"
                    data-auto_prompt="false"
                    data-itp_support="true">
                </div>
                <div class="g_id_signin" data-type="standard"></div>
            </div>
            <script>
                const GOOGLE_CLIENT_ID = "<?php echo GOOGLE_OAUTH_CLIENT_ID; ?>";
                
                function handleCredentialResponse(response) {
                    fetch("Outils/auth/google_login.php", {
                        method: "POST",
                        headers: {"Content-Type": "application/x-www-form-urlencoded"},
                        body: "credential=" + encodeURIComponent(response.credential)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // Redirection vers la page d'accueil après connexion réussie
                            window.location.href = data.redirect || "index.php";
                        } else {
                            alert("Erreur: " + (data.message || "Connexion échouée"));
                        }
                    })
                    .catch(error => {
                        console.error("Erreur:", error);
                        alert("Erreur de connexion Google");
                    });
                }
                
                // Initialiser Google Sign-In
                window.onload = function() {
                    if (window.google) {
                        google.accounts.id.initialize({
                            client_id: GOOGLE_CLIENT_ID,
                            callback: handleCredentialResponse,
                            auto_select: false
                        });
                        google.accounts.id.renderButton(
                            document.querySelector('.g_id_signin'),
                            { theme: 'outline', size: 'large' }
                        );
                    }
                }
            </script>
            
            <div id="popup-overlay" class="overlay">
                <div class="popup-content">
                    <a href="javascript:void(0)" class="fermer" onclick="togglePopup()">
                        <img class ="fermer"src="Image/croix.png" alt="Fermer">
                    </a>
                    <iframe src="Outils/views/reset_password_form.php" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </main>
    <!--Pied de page----------------------------------------------------------------------------------------------------------------------------->

    <?php include 'Outils/views/footer.php'; ?>
    <script src="JS/Hamburger.js"></script>

</body>
</html>

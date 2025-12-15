<!DOCTYPE html>
<?php
session_start();

// Système de langue unifié
require_once 'Outils/config/langue.php';
require_once 'Outils/config/config.php';

// Si déjà connecté, rediriger
if (isset($_SESSION['UserID']) || isset($_COOKIE['UserID'])) {
    header("Location: Page_d_acceuil.php");
    exit;
}

$message = ""; // pour afficher les erreurs

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mail = trim($_POST['Identifiant']);
    $mdp = trim($_POST['MDP']);

    $stmt = $conn->prepare("SELECT UserID, MotDePasseH FROM user WHERE Mail = ?");
    $stmt->bind_param("s", $mail);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($userId, $hash);
    $stmt->fetch();

    if ($hash && password_verify($mdp, $hash)) {
        // Connexion réussie
        $_SESSION['UserID'] = $userId;
        setcookie('UserID', $userId, time() + (30*24*60*60), "/"); // remember me
        header("Location: Page_d_acceuil.php");
        exit;
    } else {
        $message = "Identifiant ou mot de passe incorrect.";
    }

    $stmt->close();
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
    <link rel="stylesheet" href="CSS/Outils/Sombre_Header.css" />
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
            <script src="https://accounts.google.com/gsi/client" async></script>
            <div class="google-wrapper">
                <div id="g_id_onload"
                    data-client_id="857561252718-s2t7pdiofp5hkprl7e7fmggmvvkrlhp5.apps.googleusercontent.com"
                    data-callback="handleCredentialResponse"
                    data-auto_prompt="false">
                </div>
                <div class="g_id_signin" data-type="standard"></div>
            </div>
            <script>
                function handleCredentialResponse(response) {
                    fetch("Outils/auth/google_login.php", {
                        method: "POST",
                        headers: {"Content-Type": "application/x-www-form-urlencoded"},
                        body: "credential=" + encodeURIComponent(response.credential)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = "Page_d_acceuil.php";
                        } else {
                            alert("Erreur: " + (data.message || "Connexion échouée"));
                        }
                    })
                    .catch(error => {
                        console.error("Erreur:", error);
                        alert("Erreur de connexion Google");
                    });
                }
            </script>
            
            <div id="popup-overlay" class="overlay">
                <div class="popup-content">
                    <a href="javascript:void(0)" class="fermer" onclick="togglePopup()">
                        <img class ="fermer"src="Image/croix.png" alt="Fermer">
                    </a>
                    <iframe src="Outils/Reinitialiser.php" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </main>
    <!--Pied de page----------------------------------------------------------------------------------------------------------------------------->

    <?php include 'Outils/views/footer.php'; ?>
    <script src="JS/Hamburger.js"></script>

</body>
</html>

<!DOCTYPE html>
<?php
session_start();

// Système de langue unifié
require_once 'Outils/langue.php';
require_once 'Outils/config.php';

if (!isset($_SESSION['user_mail']) && isset($_COOKIE['user_mail'])) {
    $_SESSION['user_mail'] = $_COOKIE['user_mail'];
}

if (!isset($_SESSION['user_mail'])) {
    header("Location: Se_connecter.php");
    exit;
}
$UserID = $_SESSION['user_mail'];

// =========================================================
// 🔄 Récupération de l'utilisateur
// =========================================================
$stmt = $conn->prepare("SELECT * FROM user WHERE Mail = ?");
$stmt->bind_param("s", $UserID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: Se_connecter.php");
    exit;
}

$photoPath = $user['PhotoProfil'] ? "/DriveUs/Image_Profil/" . htmlspecialchars($user['PhotoProfil']) : "/DriveUs/Image/default.png";

// =========================================================
// 📝 Mise à jour du profil
// =========================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $Prenom = trim($_POST['prenom'] ?? '');
    $Nom = trim($_POST['nom'] ?? '');
    $Genre = trim($_POST['genre'] ?? '');
    $Date_naissance = trim($_POST['dob'] ?? '');
    $Mail = trim($_POST['email'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $Numero = trim($_POST['tel'] ?? '');
    $RIB = trim($_POST['rib'] ?? '');

    // Gestion de la photo
    if (!empty($_FILES['photo']['name'])) {
        $photo_name = "user_" . time() . "_" . basename($_FILES['photo']['name']);
        if (move_uploaded_file($_FILES['photo']['tmp_name'], "Image_Profil/" . $photo_name)) {
            $PhotoProfil = $photo_name;
        } else {
            $PhotoProfil = $user['PhotoProfil'] ?? null;
        }
    } else {
        $PhotoProfil = $user['PhotoProfil'] ?? null;
    }

    $sql = "UPDATE user SET Prenom=?, Nom=?, Genre=?, date_naissance=?, Mail=?, adresse=?, Numero=?, PhotoProfil=?, RIB=? WHERE Mail=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssss", $Prenom, $Nom, $Genre, $Date_naissance, $Mail, $adresse, $Numero, $PhotoProfil, $RIB, $UserID);
    $stmt->execute();

    echo "<script>alert('Profil enregistré');</script>";

    $stmt = $conn->prepare("SELECT * FROM user WHERE Mail = ?");
    $stmt->bind_param("s", $UserID);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $photoPath = $user['PhotoProfil'] ? "/DriveUs/Image_Profil/" . htmlspecialchars($user['PhotoProfil']) : "/DriveUs/Image/default.png";
}

// =========================================================
// 🚪 Déconnexion
// =========================================================
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: Se_connecter.php");
    exit;
}
?>

<html lang="<?= getLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= t('my_account') ?> - DriveUs</title>
    <link rel="icon" type="image/x-icon" href="CSS/Outils/layout-global.css">
    <link rel="stylesheet" href="CSS/Outils/layout-global.css">
    <link rel="stylesheet" href="CSS/Profil.css">
    <link rel="stylesheet" href="CSS/Sombre/Sombre_Profil.css">
    <script src="JS/Sombre.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
*{
  font-family: 'Poppins', sans-serif;

}
        :root {
            --primary: #667eea;
            --primary-dark: #764ba2;
            --bg: white;
            --bg-secondary: #f5f5f5;
            --text: #333;
            --text-light: #666;
            --border: #e0e0e0;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --radius: 12px;
        }

        html.dark {
            --bg: #1a1a1a;
            --bg-secondary: #2a2a2a;
            --text: #e0e0e0;
            --text-light: #b0b0b0;
            --border: #404040;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        main {
            padding: 2rem 1rem;
            display: flex;
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            min-height: calc(100vh - 200px);
        }

        .sidebar {
            width: 250px;
            flex-shrink: 0;
        }

        .sidebar h2 {
            color: var(--text);
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .menu-item {
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            background: var(--bg-secondary);
            color: var(--text);
            border-left: 4px solid transparent;
            transition: all 0.3s;
            font-weight: 500;
        }

        .menu-item:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-left-color: var(--primary);
        }

        .menu-item.active {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%);
            color: var(--primary);
            border-left-color: var(--primary);
            font-weight: 600;
        }

        .content {
            flex: 1;
            min-width: 0;
        }

        .form-section {
            display: none;
        }

        .form-section.open {
            display: block;
        }

        .form-card {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .profile-photo-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2rem;
        }

        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary);
            box-shadow: var(--shadow);
            margin-bottom: 1rem;
        }

        .file-upload {
            position: relative;
            display: inline-block;
        }

        .file-upload input {
            display: none;
        }

        .file-upload label {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: var(--radius);
            cursor: pointer;
            font-weight: 500;
            transition: transform 0.2s;
        }

        .file-upload label:hover {
            transform: translateY(-2px);
        }

        .form-section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-row.full {
            grid-template-columns: 1fr;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="date"],
        select {
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            background: var(--bg);
            color: var(--text);
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="tel"]:focus,
        input[type="date"]:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-save {
            padding: 0.8rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
            margin-top: 1rem;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .info-box {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-left: 4px solid var(--primary);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .info-box h3 {
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .info-box p {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .info-box a {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.6rem 1.2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .info-box a:hover {
            transform: translateY(-2px);
        }

        @media (max-width: 900px) {
            main {
                flex-direction: column;
                gap: 1rem;
            }

            .sidebar {
                width: 100%;
                display: flex;
                gap: 1rem;
                overflow-x: auto;
            }

            .menu-item {
                white-space: nowrap;
                flex-shrink: 0;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'Outils/header.php'; ?>

    <main>
        <!-- MENU GAUCHE -->
        <div class="sidebar">
            <h2>Menu</h2>
            <div class="menu-item active" onclick="showForm('form1', this)">👤 Informations</div>
            <div class="menu-item" onclick="showForm('form2', this)">📍 Coordonnées</div>
            <div class="menu-item" onclick="showForm('form3', this)">💳 Paiement</div>
            <div class="menu-item" onclick="showForm('form4', this)">📋 Mes trajets</div>
            <div class="menu-item" onclick="showForm('form5', this)">📥 Historique</div>
        </div>

        <!-- CONTENU DROITE -->
        <div class="content">

            <!-- FORM 1 - Informations personnelles -->
            <div id="form1" class="form-section open">
                <div class="form-card">
                    <div class="profile-photo-section">
                        <img id="photoPreview" src="<?= $photoPath ?>" alt="Photo de profil" class="profile-photo">
                        <div class="file-upload">
                            <label for="photoInput">📸 Changer la photo</label>
                            <input type="file" id="photoInput" name="photo" accept="image/*" onchange="previewPhoto(event)">
                        </div>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-section-title">Informations personnelles</div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><?= t('first_name') ?></label>
                                <input type="text" name="prenom" value="<?= htmlspecialchars($user['Prenom'] ?? '') ?>" placeholder="Votre prénom">
                            </div>
                            <div class="form-group">
                                <label><?= t('last_name') ?></label>
                                <input type="text" name="nom" value="<?= htmlspecialchars($user['Nom'] ?? '') ?>" placeholder="Votre nom">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><?= t('gender') ?></label>
                                <select name="genre">
                                    <option value="">-- Sélectionner --</option>
                                    <option value="Homme" <?= ($user['Genre'] ?? '') === 'Homme' ? 'selected' : '' ?>><?= t('male') ?></option>
                                    <option value="Femme" <?= ($user['Genre'] ?? '') === 'Femme' ? 'selected' : '' ?>><?= t('female') ?></option>
                                    <option value="Autre" <?= ($user['Genre'] ?? '') === 'Autre' ? 'selected' : '' ?>><?= t('other') ?></option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?= t('date_of_birth') ?></label>
                                <input type="date" name="dob" value="<?= htmlspecialchars($user['date_naissance'] ?? '') ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn-save">✓ Enregistrer</button>
                    </form>
                </div>
            </div>

            <!-- FORM 2 - Coordonnées -->
            <div id="form2" class="form-section">
                <div class="form-card">
                    <div class="form-section-title">📍 Coordonnées</div>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-row full">
                            <div class="form-group">
                                <label><?= t('email') ?></label>
                                <input type="email" name="email" value="<?= htmlspecialchars($user['Mail'] ?? '') ?>" placeholder="votre@email.com">
                            </div>
                        </div>

                        <div class="form-row full">
                            <div class="form-group">
                                <label><?= t('address') ?></label>
                                <input type="text" name="adresse" value="<?= htmlspecialchars($user['adresse'] ?? '') ?>" placeholder="Votre adresse postale">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><?= t('phone') ?></label>
                                <input type="tel" name="tel" value="<?= htmlspecialchars($user['Numero'] ?? '') ?>" placeholder="+33 6 00 00 00 00">
                            </div>
                        </div>

                        <button type="submit" class="btn-save">✓ Enregistrer</button>
                    </form>
                </div>
            </div>

            <!-- FORM 3 - Paiement -->
            <div id="form3" class="form-section">
                <div class="form-card">
                    <div class="form-section-title">💳 Moyen de paiement</div>

                    <form method="POST">
                        <div class="form-row full">
                            <div class="form-group">
                                <label>RIB</label>
                                <input type="text" name="rib" value="<?= htmlspecialchars($user['RIB'] ?? '') ?>" placeholder="FR****">
                            </div>
                        </div>

                        <button type="submit" class="btn-save">✓ Enregistrer</button>
                    </form>
                </div>
            </div>

            <!-- FORM 4 - Mes trajets -->
            <div id="form4" class="form-section">
                <div class="info-box">
                    <h3>📋 Espace conducteur</h3>
                    <p>Publiez vos trajets, gérez vos passagers et suivez vos revenus en temps réel.</p>
                    <a href="/DriveUs/Outils/Mes_trajets.php">Voir mes trajets →</a>
                </div>
            </div>

            <!-- FORM 5 - Historique -->
            <div id="form5" class="form-section">
                <div class="info-box">
                    <h3>📥 Mes réservations</h3>
                    <p>Consultez votre historique de trajets et de réservations en tant que passager.</p>
                    <a href="/DriveUs/Mes_reservations.php">Voir mes réservations →</a>
                </div>
                <div class="info-box">
                    <h3>📤 Réservations reçues</h3>
                    <p>Gérez les demandes de réservation pour vos trajets.</p>
                    <a href="/DriveUs/Mes_reservations_recues.php">Voir les réservations →</a>
                </div>
            </div>

        </div>
    </main>

    <script>
        function showForm(formId, element) {
            // Cacher tous les formulaires
            document.querySelectorAll(".form-section").forEach(f => f.classList.remove("open"));
            // Afficher le bon formulaire
            document.getElementById(formId).classList.add("open");
            // Mettre à jour menu actif
            document.querySelectorAll(".menu-item").forEach(item => item.classList.remove("active"));
            element.classList.add("active");
        }

        function previewPhoto(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoPreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }
    </script>

    <?php include 'Outils/footer.php'; ?>
</body>
</html>


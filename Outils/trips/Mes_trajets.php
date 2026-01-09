<?php
session_start();

if (!isset($_SESSION['UserID']) && isset($_COOKIE['UserID'])) {
    $_SESSION['UserID'] = $_COOKIE['UserID'];
}
    // Langue
            if (!isset($_SESSION)) {
                session_start();
            }
            if(isset($_GET["lang"])) {
                $_SESSION["lang"] = $_GET["lang"];
            }
            $lang = $_SESSION["lang"] ?? "fr";
            $text = require __DIR__ . "/../config/lang_$lang.php";
// Connexion BDD centralisée (Clever Cloud)
define('USE_MYSQLI', true);
require_once __DIR__ . '/../config/config.php';
// Récupérer l'utilisateur connecté
$user = null;
if(isset($_SESSION['UserID'])){
    $stmt = $pdo->prepare("SELECT * FROM user WHERE UserID = ?");
    $stmt->execute([$_SESSION['UserID']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$user) die("Utilisateur introuvable !");
}

// Gestion des actions (supprimer, publier, brouillon)
if(isset($_GET['action'], $_GET['trajet_id'])){
    $trajet_id = (int)$_GET['trajet_id'];
    $action = $_GET['action'];

        if($action === 'supprimer'){
            $stmt = $pdo->prepare("UPDATE trajet SET statut='supprimé' WHERE TrajetID=? AND ConducteurId=?");
            $stmt->execute([$trajet_id, $user['UserID']]);
        } elseif($action === 'publier' || $action === 'brouillon'){
            $stmt = $pdo->prepare("UPDATE trajet SET statut=? WHERE TrajetID=? AND ConducteurId=?");
            $stmt->execute([$action, $trajet_id, $user['UserID']]);
    }

    header("Location: Mes_trajets.php");
    exit;
}

// Récupérer les trajets de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM trajet WHERE ConducteurId=? ORDER BY DateDepart DESC");
$stmt->execute([$user['UserID']]);
$trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mes trajets — Drive Us</title>
<link rel="stylesheet" href="../../CSS/Outils/Mes_trajet.css">
<link rel="stylesheet" href="../../CSS/Outils/Mes_trajets_table.css">
<link rel="stylesheet" href="../../CSS/Outils/Header.css">
<link rel="stylesheet" href="../../CSS/Outils/Sombre_Header.css">
<link rel="stylesheet" href="../../CSS/Outils/layout-global.css"> 
<link rel="stylesheet" href="../../CSS/Outils/Footer.css">
<link rel="stylesheet" href="../../CSS/Sombre/Sombre_Mes_trajets.css">
<script src="../../JS/Sombre.js"></script>
</head>
<body>
<?php include __DIR__ . '/../views/header.php'; ?>

<main class="container">
    <h1>Mes trajets</h1>

    <?php if(empty($trajets)): ?>
        <div class="empty-state">
            <p>Vous n'avez encore publié aucun trajet.</p>
            <a href="Publier_un_trajet.php" style="color: #007bff; text-decoration: none;">Publier votre premier trajet</a>
        </div>
    <?php else: ?>
        <table class="trajets-table">
            <thead>
                <tr>
                    <th>Départ → Arrivée</th>
                    <th>Date / Heure</th>
                    <th>Places</th>
                    <th>Prix (€)</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($trajets as $t): ?>
                <tr>
                    <td class="trajet-route"><?= htmlspecialchars($t['VilleDepart'] . " → " . $t['VilleArrivee']) ?></td>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($t['DateDepart'] . ' ' . $t['heure']))) ?></td>
                    <td><?= htmlspecialchars($t['nombre_places']) ?></td>
                    <td><?= htmlspecialchars($t['Prix']) ?> €</td>
                    <td>
                        <span class="trajet-statut statut-<?= htmlspecialchars($t['statut']) ?>">
                            <?= htmlspecialchars(ucfirst($t['statut'])) ?>
                        </span>
                    </td>
                    <td>
                        <div class="actions">
                            <a href="../Messagerie_groupe.php?trajet_id=<?= $t['TrajetID'] ?>" class="btn-groupe" style="background: #28a745;">💬 Groupe</a>
                            <a href="../../Publier_un_trajet.php?trajet_id=<?= $t['TrajetID'] ?>" class="btn-modifier">Modifier</a>
                            <?php if($t['statut'] === 'brouillon'): ?>
                                <a href="?action=publier&trajet_id=<?= $t['TrajetID'] ?>" class="btn-publier">Publier</a>
                            <?php else: ?>
                                <a href="?action=brouillon&trajet_id=<?= $t['TrajetID'] ?>" class="btn-brouillon">Brouillon</a>
                            <?php endif; ?>
                            <a href="?action=supprimer&trajet_id=<?= $t['TrajetID'] ?>" class="btn-supprimer" onclick="return confirm('Voulez-vous vraiment supprimer ce trajet ?');">Supprimer</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../views/footer.php'; ?>
</body>
</html>

<?php
session_start();

if (!isset($_SESSION['user_mail']) && isset($_COOKIE['user_mail'])) {
    $_SESSION['user_mail'] = $_COOKIE['user_mail'];
}
    // Langue
    if(isset($_GET["lang"])) {
        $_SESSION["lang"] = $_GET["lang"];
    }
    $lang = $_SESSION["lang"] ?? "fr";
    $text = require "lang_$lang.php";

// Connexion BDD
$ca = new PDO("mysql:host=localhost;dbname=bdd;charset=utf8", "root", "");

// Récupérer l'utilisateur connecté
$user = null;
if(isset($_SESSION['user_mail'])){
    $stmt = $ca->prepare("SELECT * FROM user WHERE Mail = ?");
    $stmt->execute([$_SESSION['user_mail']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$user) die("Utilisateur introuvable !");
}

// Gestion des actions (supprimer, publier, brouillon)
if(isset($_GET['action'], $_GET['trajet_id'])){
    $trajet_id = (int)$_GET['trajet_id'];
    $action = $_GET['action'];

    if($action === 'supprimer'){
        $stmt = $ca->prepare("DELETE FROM trajet WHERE TrajetID=? AND ConducteurId=?");
        $stmt->execute([$trajet_id, $user['UserID']]);
    } elseif($action === 'publier' || $action === 'brouillon'){
        $stmt = $ca->prepare("UPDATE trajet SET statut=? WHERE TrajetID=? AND ConducteurId=?");
        $stmt->execute([$action, $trajet_id, $user['UserID']]);
    }

    header("Location: Mes_trajets.php");
    exit;
}

// Récupérer les trajets de l'utilisateur
$stmt = $ca->prepare("SELECT * FROM trajet WHERE ConducteurId=? ORDER BY DateDepart DESC");
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
<link rel="stylesheet" href="../CSS/Mes_trajet.css">
<link rel="stylesheet" href="../CSS/Mes_trajets_table.css">
<link rel="stylesheet" href="../CSS/Sombre_Mes_trajets.css">
<script src="../JS/Sombre.js"></script>
</head>
<body>
<?php include 'header.php'; ?>

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
                            <a href="../Messagerie_groupe.php?trajet_id=<?= $t['TrajetID'] ?>" class="btn-small" style="background: #28a745;">💬 Groupe</a>
                            <a href="Publier_un_trajet.php?trajet_id=<?= $t['TrajetID'] ?>" class="btn-small">Modifier</a>
                            <?php if($t['statut'] === 'brouillon'): ?>
                                <a href="?action=publier&trajet_id=<?= $t['TrajetID'] ?>" class="btn-small btn-secondary">Publier</a>
                            <?php else: ?>
                                <a href="?action=brouillon&trajet_id=<?= $t['TrajetID'] ?>" class="btn-small btn-secondary">Brouillon</a>
                            <?php endif; ?>
                            <a href="?action=supprimer&trajet_id=<?= $t['TrajetID'] ?>" class="btn-small btn-danger" onclick="return confirm('Voulez-vous vraiment supprimer ce trajet ?');">Supprimer</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>
</body>
</html>

<?php
// Système de langue unifié
require_once 'Outils/config/langue.php';
require_once 'Outils/config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['UserID'])) {
    if (isset($_COOKIE['UserID'])) {
        $_SESSION['UserID'] = $_COOKIE['UserID'];
    } else {
        header("Location: Se_connecter.php");
        exit;
    }
}

$userId = $_SESSION['UserID'];

// Récupérer le solde total de cagnotte de l'utilisateur
$stmt = $pdo->prepare("SELECT COALESCE(SUM(Valeur), 0) as total_cagnotte FROM cagnotte WHERE UserID = ?");
$stmt->execute([$userId]);
$solde = $stmt->fetch(PDO::FETCH_ASSOC)['total_cagnotte'] ?? 0;

// Récupérer l'historique des cagnottes
$stmt = $pdo->prepare("
    SELECT 
        c.CagnotteID,
        c.Valeur,
        c.TrajetID,
        t.VilleDepart,
        t.VilleArrivee,
        t.DateDepart,
        t.heure,
        u.Prenom as ConductorName
    FROM cagnotte c
    LEFT JOIN trajet t ON c.TrajetID = t.TrajetID
    LEFT JOIN user u ON t.ConducteurID = u.UserID
    WHERE c.UserID = ?
    ORDER BY c.CagnotteID DESC
");
$stmt->execute([$userId]);
$historique = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traiter les retraits
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'withdraw') {
        $montant = (float)($_POST['montant'] ?? 0);
        
        if ($montant > 0 && $montant <= $solde) {
            try {
                // Créer une entrée de retrait (valeur négative)
                $stmt = $pdo->prepare("
                    INSERT INTO cagnotte (UserID, Valeur, TrajetID)
                    VALUES (?, ?, NULL)
                ");
                $stmt->execute([$userId, -$montant]);
                
                header("Location: Cagnotte.php?success=1");
                exit;
            } catch (PDOException $e) {
                $error = "Erreur lors du retrait : " . $e->getMessage();
            }
        } else {
            $error = "Montant invalide ou solde insuffisant";
        }
    }
}

$success = isset($_GET['success']);
$error = $error ?? null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Cagnotte - DriveUs</title>
    <link rel="icon" type="image/x-icon" href="Image/Icone.ico">
    <link rel="stylesheet" href="CSS/Outils/layout-global.css" />
    <link rel="stylesheet" href="CSS/Outils/Header.css" />
    <link rel="stylesheet" href="CSS/Outils/responsive.css" />
    <link rel="stylesheet" href="CSS/Outils/Footer.css" />
    <link rel="stylesheet" href="CSS/Sombre/Sombre_Header.css" />
    <script src="JS/Sombre.js"></script>
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #764ba2;
            --bg: white;
            --text: #333;
            --border: #e0e0e0;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        html.dark {
            --bg: #1a1a1a;
            --text: #e0e0e0;
            --border: #404040;
        }

        main {
            min-height: calc(100vh - 200px);
            padding: 2rem;
        }

        .cagnotte-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .solde-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .solde-card h2 {
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .solde-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .solde-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
        }

        .btn-secondary:hover {
            background: white;
            color: #667eea;
        }

        .section {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .section h3 {
            color: var(--text);
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text);
        }

        input[type="number"] {
            width: 100%;
            max-width: 300px;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
        }

        .btn-submit {
            background: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-submit:hover {
            background: var(--primary-dark);
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .historique-table {
            width: 100%;
            border-collapse: collapse;
        }

        .historique-table th {
            background: var(--primary);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }

        .historique-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .historique-table tr:hover {
            background: var(--bg);
        }

        .montant {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .montant.positif {
            color: #28a745;
        }

        .montant.negatif {
            color: #dc3545;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text);
        }

        .empty-state p {
            margin-top: 0.5rem;
        }

        @media (max-width: 768px) {
            main {
                padding: 1rem;
            }

            .solde-card {
                padding: 1.5rem;
            }

            .historique-table {
                font-size: 0.9rem;
            }

            .historique-table th,
            .historique-table td {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'Outils/views/header.php'; ?>

    <main>
        <div class="cagnotte-container">
            <h1> Ma Cagnotte</h1>

            <?php if ($success): ?>
                <div class="message success">
                    ✓ Retrait effectué avec succès !
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message error">
                    ✗ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Carte de solde -->
            <div class="solde-card">
                <h2>Solde disponible</h2>
                <div class="solde-value"><?= number_format($solde, 2, ',', ' ') ?> €</div>
                <div class="solde-actions">
                    <button class="btn btn-secondary" onclick="document.getElementById('withdrawForm').scrollIntoView({behavior: 'smooth'})">
                        💸 Retirer de l'argent
                    </button>
                </div>
            </div>

            <!-- Formulaire de retrait -->
            <?php if ($solde > 0): ?>
            <div class="section" id="withdrawForm">
                <h3>Retirer de l'argent</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="withdraw">
                    <div class="form-group">
                        <label for="montant">Montant à retirer (€)</label>
                        <input 
                            type="number" 
                            id="montant" 
                            name="montant" 
                            placeholder="Ex: 50.00"
                            min="0.01"
                            max="<?= $solde ?>"
                            step="0.01"
                            required
                        >
                        <small style="display: block; margin-top: 0.5rem; color: #666;">
                            Solde disponible : <?= number_format($solde, 2, ',', ' ') ?> €
                        </small>
                    </div>
                    <button type="submit" class="btn-submit">Confirmer le retrait</button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Historique -->
            <div class="section">
                <h3>Historique des transactions</h3>
                
                <?php if (empty($historique)): ?>
                    <div class="empty-state">
                        <p>Aucune transaction pour le moment.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="historique-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Trajet</th>
                                    <th>Conducteur</th>
                                    <th>Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historique as $transaction): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        if ($transaction['DateDepart']) {
                                            echo htmlspecialchars(date('d/m/Y H:i', strtotime($transaction['DateDepart'] . ' ' . ($transaction['heure'] ?? '00:00'))));
                                        } else {
                                            echo 'Retrait manuel';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($transaction['VilleDepart']) {
                                            echo htmlspecialchars($transaction['VilleDepart'] . ' → ' . $transaction['VilleArrivee']);
                                        } else {
                                            echo '—';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo $transaction['ConductorName'] ? htmlspecialchars($transaction['ConductorName']) : '—'; ?>
                                    </td>
                                    <td>
                                        <span class="montant <?= $transaction['Valeur'] > 0 ? 'positif' : 'negatif' ?>">
                                            <?= ($transaction['Valeur'] > 0 ? '+' : '') . number_format($transaction['Valeur'], 2, ',', ' ') ?> €
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'Outils/views/footer.php'; ?>
</body>
</html>

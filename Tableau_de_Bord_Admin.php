<?php

require_once 'Outils/config/langue.php';
require_once __DIR__ . '/Outils/config/config.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['UserID'])) {
    header('Location: /connexion');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM user WHERE UserID = ? LIMIT 1");
$stmt->execute([$_SESSION['UserID']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$is_admin = ($user && ((int)$user['niveau'] == 1 || (int)$user['niveau'] == 2));

if (!$is_admin) {
    die("<h2 style='color: red; text-align: center;'>Accès refusé. Vous devez être administrateur.</h2>");
}

// Récupérer les statistiques
$stats = [];

// Total utilisateurs
$stmt = $pdo->query("SELECT COUNT(*) as count FROM user");
$stats['total_users'] = $stmt->fetch()['count'];

// Total administrateurs
$stmt = $pdo->query("SELECT COUNT(*) as count FROM user WHERE niveau IN (1, 2)");
$stats['total_admins'] = $stmt->fetch()['count'];

// Total conducteurs
$stmt = $pdo->query("SELECT COUNT(*) as count FROM user WHERE role = 'conducteur'");
$stats['total_drivers'] = $stmt->fetch()['count'];

// Total passagers
$stmt = $pdo->query("SELECT COUNT(*) as count FROM user WHERE role = 'passager'");
$stats['total_passengers'] = $stmt->fetch()['count'];

// Total trajets
$stmt = $pdo->query("SELECT COUNT(*) as count FROM trajet");
$stats['total_trips'] = $stmt->fetch()['count'];

// Total trajets publiés
$stmt = $pdo->query("SELECT COUNT(*) as count FROM trajet WHERE statut = 'publie'");
$stats['published_trips'] = $stmt->fetch()['count'];

// Total réservations
$stmt = $pdo->query("SELECT COUNT(*) as count FROM reservations");
$stats['total_reservations'] = $stmt->fetch()['count'];

// Utilisateurs actifs (log-in les 7 derniers jours) - si table disponible
try {
    $stmt = $pdo->query("SELECT COUNT(DISTINCT UserID) as count FROM presence_last_activity WHERE last_activity > DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $active_result = $stmt->fetch();
    $stats['active_users_7d'] = $active_result ? $active_result['count'] : 0;
} catch (Exception $e) {
    $stats['active_users_7d'] = 0;
}

// Derniers utilisateurs inscrits
$recent_users = $pdo->query("SELECT UserID, Nom, Prenom, Mail, niveau FROM user LIMIT 10")->fetchAll();

// Trajets les plus populaires
$popular_trips = $pdo->query("SELECT t.TrajetID, t.VilleDepart, t.VilleArrivee, t.Prix, COUNT(r.ReservationID) as nb_reservations FROM trajet t LEFT JOIN reservations r ON t.TrajetID = r.TrajetID GROUP BY t.TrajetID ORDER BY nb_reservations DESC LIMIT 10")->fetchAll();

// Rapports en attente
try {
    $pending_reports = $pdo->query("SELECT * FROM admin_reports WHERE Status = 'pending' LIMIT 10")->fetchAll();
} catch (Exception $e) {
    $pending_reports = [];
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Admin — Drive Us</title>
    <link rel="icon" type="image/x-icon" href="/Image/Icone.ico">
    <link rel="stylesheet" href="/CSS/Outils/layout-global.css" />
    <link rel="stylesheet" href="/CSS/Outils/Header.css" />
    <link rel="stylesheet" href="/CSS/Outils/Footer.css" />
    <style>
        main {
            min-height: calc(100vh - 200px);
            padding: 40px 20px;
            background-color: #f5f5f5;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .dashboard-header h1 {
            margin: 0;
            font-size: 2rem;
            color: var(--primary);
        }
        
        .dashboard-header .welcome-text {
            color: #666;
            font-size: 0.95rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .stat-card .icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary);
            margin: 10px 0;
        }
        
        .stat-card .label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .section h2 {
            margin-top: 0;
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }
        
        table th {
            background-color: #f0f0f0;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid var(--primary);
        }
        
        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        table tr:hover {
            background-color: #f9f9f9;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-admin {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .badge-driver {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }
        
        .badge-passenger {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .badge-pending {
            background-color: #fff3e0;
            color: #e65100;
        }
        
        .admin-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .admin-links a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: var(--primary);
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .admin-links a:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .no-data {
            text-align: center;
            padding: 30px;
            color: #999;
        }
        
        .columns-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .admin-links {
                justify-content: center;
            }
            
            .columns-2 {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'Outils/views/header.php'; ?>
    <?php include 'Outils/views/header.php'; ?>
    
    <main>
        <div class="admin-container">
            <div class="dashboard-header">
                <div>
                    <h1>📊 Tableau de Bord Admin</h1>
                    <p class="welcome-text">Bienvenue, <?= htmlspecialchars($user['Prénom']) ?> 👋</p>
                </div>
                <div class="admin-links">
                    <a href="/gestion-admins">🔐 Gérer admins</a>
                    <a href="index.php">🏠 Retour au site</a>
                </div>
            </div>
            
            <!-- Grille de statistiques -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon">👥</div>
                    <div class="number"><?= number_format($stats['total_users']) ?></div>
                    <div class="label">Utilisateurs totaux</div>
                </div>
                
                <div class="stat-card">
                    <div class="icon">🔐</div>
                    <div class="number"><?= $stats['total_admins'] ?></div>
                    <div class="label">Administrateurs</div>
                </div>
                
                <div class="stat-card">
                    <div class="icon">🚗</div>
                    <div class="number"><?= $stats['total_drivers'] ?></div>
                    <div class="label">Conducteurs</div>
                </div>
                
                <div class="stat-card">
                    <div class="icon">🛑</div>
                    <div class="number"><?= $stats['total_passengers'] ?></div>
                    <div class="label">Passagers</div>
                </div>
                
                <div class="stat-card">
                    <div class="icon">🛣️</div>
                    <div class="number"><?= $stats['total_trips'] ?></div>
                    <div class="label">Trajets (total)</div>
                </div>
                
                <div class="stat-card">
                    <div class="icon">📍</div>
                    <div class="number"><?= $stats['published_trips'] ?></div>
                    <div class="label">Trajets publiés</div>
                </div>
                
                <div class="stat-card">
                    <div class="icon">📋</div>
                    <div class="number"><?= $stats['total_reservations'] ?></div>
                    <div class="label">Réservations</div>
                </div>
                
                <div class="stat-card">
                    <div class="icon">🟢</div>
                    <div class="number"><?= $stats['active_users_7d'] ?></div>
                    <div class="label">Utilisateurs actifs (7j)</div>
                </div>
            </div>
            
            <div class="columns-2">
                <!-- Derniers utilisateurs -->
                <div class="section">
                    <h2>👥 Derniers utilisateurs inscrits</h2>
                    
                    <?php if (count($recent_users) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_users as $u): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($u['Prénom'] . ' ' . $u['Nom']) ?></strong></td>
                                        <td><?= htmlspecialchars($u['Mail']) ?></td>
                                        <td>
                                            <?php
                                            $niveau = (int)($u['niveau'] ?? 0);
                                            if ($niveau == 1 || $niveau == 2) {
                                                echo '<span class="badge badge-admin">Admin</span>';
                                            } elseif (isset($u['role']) && $u['role'] === 'conducteur') {
                                                echo '<span class="badge badge-driver">Conducteur</span>';
                                            } else {
                                                echo '<span class="badge badge-passenger">Passager</span>';
                                            }
                                            ?>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($u['created_at'] ?? 'now')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">Aucun utilisateur</div>
                    <?php endif; ?>
                </div>
                
                <!-- Trajets populaires -->
                <div class="section">
                    <h2>🔥 Trajets les plus populaires</h2>
                    
                    <?php if (count($popular_trips) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Trajet</th>
                                    <th>Prix</th>
                                    <th>Réservations</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popular_trips as $trip): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($trip['VilleDepart'] . ' → ' . $trip['VilleArrivee']) ?></strong>
                                        </td>
                                        <td><?= number_format($trip['Prix'], 2) ?>€</td>
                                        <td><?= $trip['nb_reservations'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">Aucun trajet</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recherche d'utilisateur -->
            <div class="section">
                <h2>🔍 Rechercher un utilisateur</h2>
                
                <form method="GET" style="margin-bottom: 20px;">
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="text" name="search_user" placeholder="Nom, prénom ou email..." 
                               value="<?= htmlspecialchars($_GET['search_user'] ?? '') ?>"
                               style="flex: 1; padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.95rem;">
                        <button type="submit" style="padding: 12px 25px; background: var(--primary); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                            🔍 Rechercher
                        </button>
                    </div>
                </form>
                
                <?php if (isset($_GET['search_user']) && !empty($_GET['search_user'])): ?>
                    <?php
                    $search = '%' . $_GET['search_user'] . '%';
                    $stmt = $pdo->prepare("SELECT * FROM user WHERE Nom LIKE ? OR Prenom LIKE ? OR Mail LIKE ? LIMIT 10");
                    $stmt->execute([$search, $search, $search]);
                    $search_results = $stmt->fetchAll();
                    ?>
                    
                    <?php if (count($search_results) > 0): ?>
                        <div style="margin-top: 20px;">
                            <h3 style="color: #333; margin-bottom: 15px;"><?= count($search_results) ?> résultat(s) trouvé(s)</h3>
                            
                            <?php foreach ($search_results as $found_user): ?>
                                <?php
                                // Récupérer les statistiques de l'utilisateur
                                $user_id = $found_user['UserID'];
                                
                                // Trajets publiés
                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM trajet WHERE ConducteurID = ?");
                                $stmt->execute([$user_id]);
                                $user_trips = $stmt->fetch()['count'];
                                
                                // Réservations effectuées
                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reservations WHERE PassagerID = ?");
                                $stmt->execute([$user_id]);
                                $user_reservations = $stmt->fetch()['count'];
                                
                                // Réservations reçues
                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reservations r JOIN trajet t ON r.TrajetID = t.TrajetID WHERE t.ConducteurID = ?");
                                $stmt->execute([$user_id]);
                                $received_reservations = $stmt->fetch()['count'];
                                ?>
                                
                                <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid var(--primary);">
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                        <div>
                                            <h4 style="margin: 0 0 10px 0; color: var(--primary);">👤 Informations générales</h4>
                                            <p><strong>Nom complet:</strong> <?= htmlspecialchars($found_user['Prenom'] . ' ' . $found_user['Nom']) ?></p>
                                            <p><strong>Email:</strong> <?= htmlspecialchars($found_user['Mail']) ?></p>
                                            <p><strong>Rôle:</strong> 
                                                <?php
                                                $niveau = (int)($found_user['niveau'] ?? 0);
                                                if ($niveau == 1 || $niveau == 2) {
                                                    echo '<span class="badge badge-admin">Admin</span>';
                                                } elseif (isset($found_user['role']) && $found_user['role'] === 'conducteur') {
                                                    echo '<span class="badge badge-driver">Conducteur</span>';
                                                } else {
                                                    echo '<span class="badge badge-passenger">Passager</span>';
                                                }
                                                ?>
                                            </p>
                                            <p><strong>Date d'inscription:</strong> <?= date('d/m/Y', strtotime($found_user['created_at'] ?? 'now')) ?></p>
                                        </div>
                                        
                                        <div>
                                            <h4 style="margin: 0 0 10px 0; color: var(--primary);">📊 Activité</h4>
                                            <p><strong>Trajets publiés:</strong> <?= $user_trips ?></p>
                                            <p><strong>Réservations effectuées:</strong> <?= $user_reservations ?></p>
                                            <p><strong>Réservations reçues:</strong> <?= $received_reservations ?></p>
                                            <p><strong>ID utilisateur:</strong> <?= $user_id ?></p>
                                        </div>
                                        
                                        <div>
                                            <h4 style="margin: 0 0 10px 0; color: var(--primary);">🎫 Informations complémentaires</h4>
                                            <p><strong>Préférences:</strong> <?= htmlspecialchars($found_user['preferences'] ?? 'Non renseignées') ?></p>
                                            <p><strong>Langue:</strong> <?= strtoupper($found_user['langue'] ?? 'fr') ?></p>
                                            <?php if (isset($found_user['PhotoProfil']) && !empty($found_user['PhotoProfil'])): ?>
                                                <p><strong>Photo de profil:</strong> ✅ Oui</p>
                                            <?php else: ?>
                                                <p><strong>Photo de profil:</strong> ❌ Non</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                                        <a href="Messagerie.php?contact=<?= urlencode($found_user['Mail']) ?>" 
                                           style="display: inline-block; padding: 8px 15px; background: var(--primary); color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;">
                                            💬 Contacter
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-data">Aucun utilisateur trouvé pour "<?= htmlspecialchars($_GET['search_user']) ?>"</div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Rapports en attente -->
            <?php if (count($pending_reports) > 0): ?>
                <div class="section">
                    <h2>⚠️ Rapports en attente (<?= count($pending_reports) ?>)</h2>
                    <div class="alert">
                        <?= count($pending_reports) ?> rapport(s) en attente de révision
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Utilisateur</th>
                                <th>Date</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_reports as $report): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($report['ReportType']) ?></strong></td>
                                    <td><?= htmlspecialchars(substr($report['Description'], 0, 50)) ?>...</td>
                                    <td><?= $report['UserID'] ?? 'N/A' ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($report['CreatedAt'])) ?></td>
                                    <td><span class="badge badge-pending">En attente</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include 'Outils/views/footer.php'; ?>
</body>
</html>

<?php $pdo = null; ?>

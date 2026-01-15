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

$is_admin = ($user && ($user['niveau'] == 1 || $user['niveau'] == 2));

if (!$is_admin) {
    die("<h2 style='color: red; text-align: center;'>Accès refusé. Vous devez être administrateur.</h2>");
}

// Traitement des actions admin
$message = '';
$error = '';

// Créer un nouvel administrateur
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'create_admin') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = "L'email est requis.";
    } else {
        // Vérifier si l'utilisateur existe
        $stmt = $pdo->prepare("SELECT UserID FROM user WHERE Mail = ? LIMIT 1");
        $stmt->execute([$email]);
        $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$target_user) {
            $error = "Utilisateur non trouvé avec cet email.";
        } else {
            // Mettre à jour le niveau (1 = admin principal, 2 = admin secondaire)
            $stmt = $pdo->prepare("UPDATE user SET niveau = 1 WHERE UserID = ?");
            $stmt->execute([$target_user['UserID']]);
            
            // Enregistrer dans les logs
            $stmt = $pdo->prepare("INSERT INTO admin_logs (AdminID, Action, Description, TargetUserID) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['UserID'],
                'CREATE_ADMIN',
                "Création d'un nouvel administrateur",
                $target_user['UserID']
            ]);
            
            $message = "✅ L'utilisateur " . htmlspecialchars($email) . " est maintenant administrateur.";
        }
    }
}

// Retirer les droits admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'remove_admin') {
    $target_user_id = (int)($_POST['user_id'] ?? 0);
    
    if ($target_user_id === $_SESSION['UserID']) {
        $error = "Vous ne pouvez pas retirer vos propres droits admin.";
    } else {
        $stmt = $pdo->prepare("UPDATE user SET niveau = 0 WHERE UserID = ? AND (niveau = 1 OR niveau = 2)");
        $stmt->execute([$target_user_id]);
        
        $stmt = $pdo->prepare("INSERT INTO admin_logs (AdminID, Action, Description, TargetUserID) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['UserID'],
            'REMOVE_ADMIN',
            "Suppression des droits admin",
            $target_user_id
        ]);
        
        $message = "✅ Les droits admin ont été retirés.";
    }
}

// Récupérer tous les administrateurs
$admins_query = $pdo->query("SELECT UserID, Nom, Prenom, Mail, niveau FROM user WHERE niveau IN (1,2)");
$admins = $admins_query->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les logs d'administration
$logs = [];
try {
    $logs_query = $pdo->query("SELECT l.*, u.Nom, u.Prenom, tu.Mail FROM admin_logs l LEFT JOIN user u ON l.AdminID = u.UserID LEFT JOIN user tu ON l.TargetUserID = tu.UserID ORDER BY l.Timestamp DESC LIMIT 50");
    $logs = $logs_query->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table admin_logs n'existe pas
    $logs = [];
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Administrateurs — Drive Us</title>
    <link rel="icon" type="image/x-icon" href="/Image/Icone.ico">
    <link rel="stylesheet" href="/CSS/Outils/layout-global.css" />
    <link rel="stylesheet" href="/CSS/Outils/Header.css" />
    <link rel="stylesheet" href="/CSS/Outils/responsive.css" />
    <link rel="stylesheet" href="/CSS/Outils/Footer.css" />
    <style>
        main {
            min-height: calc(100vh - 200px);
            padding: 40px 20px;
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 20px;
        }
        
        .admin-header h1 {
            font-size: 2.5rem;
            margin: 0;
        }
        
        .admin-header .badge {
            background: var(--primary);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .form-section h2 {
            margin-top: 0;
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
            align-items: end;
        }
        
        .form-group input,
        .form-group select {
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .table-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .table-section h2 {
            margin-top: 0;
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }
        
        table th {
            background-color: var(--primary);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        table tr:hover {
            background-color: #f9f9f9;
        }
        
        .action-btns {
            display: flex;
            gap: 8px;
        }
        
        .action-btns button {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-admin {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .stat-card .label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <?php include 'Outils/views/header.php'; ?>
    
    <main>
        <div class="admin-container">
            <div class="admin-header">
                <h1>🔐 Gestion des Administrateurs</h1>
                <div class="badge">Panel Admin</div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <!-- Statistiques -->
            <div class="stats">
                <div class="stat-card">
                    <div class="number"><?= count($admins) ?></div>
                    <div class="label">Administrateurs actifs</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= count($logs) ?></div>
                    <div class="label">Actions enregistrées (50 dernières)</div>
                </div>
            </div>
            
            <!-- Formulaire de création -->
            <div class="form-section">
                <h2>➕ Créer un nouvel administrateur</h2>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <div>
                            <label for="email" style="display: block; margin-bottom: 8px; font-weight: 600;">Email de l'utilisateur</label>
                            <input type="email" id="email" name="email" placeholder="exemple@email.com" required />
                        </div>
                        <div>
                            <button type="submit" name="action" value="create_admin" class="btn btn-primary">Promouvoir en admin</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Liste des administrateurs -->
            <div class="table-section">
                <h2>👥 Administrateurs actuels</h2>
                
                <?php if (count($admins) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Date de création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($admin['Prenom'] . ' ' . $admin['Nom']) ?></strong>
                                        <br />
                                        <span class="status-badge status-admin">Admin</span>
                                    </td>
                                    <td><?= htmlspecialchars($admin['Mail']) ?></td>
                                    <td>-</td>
                                    <td>
                                        <div class="action-btns">
                                            <?php if ($admin['UserID'] !== $_SESSION['UserID']): ?>
                                                <form method="POST" action="" style="display: inline; margin: 0;">
                                                    <input type="hidden" name="action" value="remove_admin" />
                                                    <input type="hidden" name="user_id" value="<?= $admin['UserID'] ?>" />
                                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir retirer les droits admin ?')">
                                                        Retirer les droits
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span style="color: #999;">Vous (admin courant)</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        Aucun administrateur trouvé
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Journal des actions -->
            <div class="table-section">
                <h2>📋 Journal des actions administrateur (50 dernières)</h2>
                
                <?php if (count($logs) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Utilisateur cible</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['Prenom'] . ' ' . $log['Nom']) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($log['Action']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($log['Description'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($log['Mail'] ?? '-') ?></td>
                                    <td><?= date('d/m/Y H:i:s', strtotime($log['Timestamp'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        Aucune action enregistrée
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </main>
    
    <?php include 'Outils/views/footer.php'; ?>
</body>
</html>

<?php $pdo = null; ?>

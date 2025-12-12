<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_mail'])) {
    if (isset($_COOKIE['user_mail'])) {
        $_SESSION['user_mail'] = $_COOKIE['user_mail'];
    } else {
        header("Location: Se_connecter.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Réservations reçues - DriveUs</title>
    <link rel="icon" type="image/x-icon" href="Image/Icone.ico">
    <link rel="stylesheet" href="CSS/Outils/layout-global.css" />
    <link rel="stylesheet" href="CSS/Trouver_un_trajet1.css" />
    <link rel="stylesheet" href="CSS/Sombre/Sombre_Trouver.css" />
    <script src="JS/Sombre.js"></script>
    <style>
        .reservation-card {
            background: var(--bg-secondary);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary);
        }

        .reservation-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .reservation-route {
            font-size: 1.25rem;
            font-weight: bold;
        }

        .reservation-status {
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-confirmée {
            background: var(--success);
            color: white;
        }

        .status-pending {
            background: var(--warning);
            color: white;
        }

        .status-annulée {
            background: var(--danger);
            color: white;
        }

        .reservation-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .detail-label {
            color: var(--muted);
            font-size: 0.875rem;
            text-transform: uppercase;
        }

        .detail-value {
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--muted);
        }

        main {
            padding: 2rem 1rem;
            max-width: 900px;
            margin: 0 auto;
        }

        h1 {
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <?php include 'Outils/header.php'; ?>
    
    <main>
        <h1>Réservations reçues sur mes trajets</h1>
        
        <div id="reservationsList"></div>

        <div id="emptyState" class="empty-state">
            <h2>Aucune réservation</h2>
            <p>Aucun passager n'a réservé sur vos trajets.</p>
            <a href="Publier_un_trajet.php" style="display:inline-block; margin-top:1rem; color:var(--primary); text-decoration:none; font-weight:500;">Publier un trajet →</a>
        </div>
    </main>

    <script>
        async function loadReceivedReservations() {
            try {
                const response = await fetch("Outils/get_received_reservations.php");
                const reservations = await response.json();
                const list = document.getElementById('reservationsList');
                const empty = document.getElementById('emptyState');

                if (!reservations || reservations.length === 0) {
                    empty.style.display = 'block';
                    list.innerHTML = '';
                    return;
                }

                empty.style.display = 'none';
                list.innerHTML = reservations.map(r => `
                    <div class="reservation-card">
                        <div class="reservation-header">
                            <div class="reservation-route">${r.from} → ${r.to}</div>
                            <span class="reservation-status status-${r.status}">${r.status}</span>
                        </div>

                        <div class="reservation-details">
                            <div class="detail-item">
                                <span class="detail-label">Passager</span>
                                <span class="detail-value">${r.passenger}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Date</span>
                                <span class="detail-value">${r.date}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Places réservées</span>
                                <span class="detail-value">${r.seats}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Réservé le</span>
                                <span class="detail-value">${new Date(r.bookingDate).toLocaleDateString('fr-FR')}</span>
                            </div>
                        </div>
                    </div>
                `).join('');
            } catch (error) {
                console.error("Erreur:", error);
            }
        }

        loadReceivedReservations();
    </script>
</main>
<?php include 'Outils/footer.php'; ?>
</body>
</html>

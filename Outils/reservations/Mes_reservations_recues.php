<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['UserID'])) {
    if (isset($_COOKIE['UserID'])) {
        $_SESSION['UserID'] = $_COOKIE['UserID'];
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
    <link rel="icon" type="image/x-icon" href="../../Image/Icone.ico">
    <link rel="stylesheet" href="../../CSS/Outils/layout-global.css" />
    <link rel="stylesheet" href="../../CSS/MRR.css" />
    <link rel="stylesheet" href="../../CSS/Outils/Header.css" />
    <link rel="stylesheet" href="../../CSS/Outils/Sombre_Header.css" />
    <link rel="stylesheet" href="../../CSS/Outils/Footer.css" />
    <link rel="stylesheet" href="../../CSS/Sombre/Sombre_Trouver.css" />
    <script src="../../JS/Sombre.js"></script>

</head>
<body>
    <?php include __DIR__ . '/../views/header.php'; ?>
    
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
                const response = await fetch("Outils/reservations/get_received_reservations.php");
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
<?php include __DIR__ . '/../views/footer.php'; ?>
</body>
</html>

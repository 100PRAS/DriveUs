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
    <title>Mes réservations - DriveUs</title>
    <link rel="icon" type="image/x-icon" href="../../Image/Icone.ico">
    <link rel="stylesheet" href="../../CSS/Outils/layout-global.css" />
    <link rel="stylesheet" href="../../CSS/MR.css" />
    <link rel="stylesheet" href="../../CSS/Outils/Header.css" />
    <link rel="stylesheet" href="../../CSS/Outils/Sombre_Header.css" />
    <link rel="stylesheet" href="../../CSS/Outils/Footer.css" />
    <link rel="stylesheet" href="../../CSS/MR.css" />
        <link rel="stylesheet" href="../../CSS/Sombre/Sombre_MR.css" />

    <script src="../../JS/Sombre.js"></script>

</head>
<body>
    <?php include __DIR__ . '/../views/header.php'; ?>
    
    <main>
        <h1>Mes réservations</h1>
        
        <div id="reservationsList"></div>

        <div id="emptyState" class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h2>Aucune réservation</h2>
            <p>Vous n'avez pas encore réservé de trajet.</p>
            <a href="Trouver_un_trajet.php" style="display:inline-block; margin-top:1rem; color:var(--primary); text-decoration:none; font-weight:500;">Trouver un trajet →</a>
        </div>
    </main>

    <script>
        async function loadReservations() {
            try {
                const response = await fetch("Outils/reservations/get_reservations.php");
                const reservations = await response.json();
                const list = document.getElementById('reservationsList');
                const empty = document.getElementById('emptyState');

                if (reservations.length === 0) {
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
                                <span class="detail-label">Date</span>
                                <span class="detail-value">${r.date}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Heure</span>
                                <span class="detail-value">${r.time}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Places</span>
                                <span class="detail-value">${r.seats}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Prix total</span>
                                <span class="detail-value">${(r.price * r.seats).toFixed(2)} €</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Conducteur</span>
                                <span class="detail-value">${r.driver}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Réservation</span>
                                <span class="detail-value">${new Date(r.bookingDate).toLocaleDateString('fr-FR')}</span>
                            </div>
                        </div>

                        <div class="reservation-actions">
                            <button class="btn btn-primary" onclick="contactDriver('${r.driver}')">Contacter le conducteur</button>
                            <button class="btn btn-secondary" onclick="cancelReservation(${r.id})">Annuler</button>
                        </div>
                    </div>
                `).join('');
            } catch (error) {
                console.error("Erreur:", error);
            }
        }

        function contactDriver(driverEmail) {
            // Ouvrir la messagerie avec le conducteur
            window.location.href = `Messagerie.php?contact=${encodeURIComponent(driverEmail)}`;
        }

        async function cancelReservation(reservationId) {
            if (!confirm("Êtes-vous sûr de vouloir annuler cette réservation ?")) return;

            try {
                const response = await fetch("Outils/reservations/cancel_reservation.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ reservationId })
                });

                const result = await response.json();
                if (result.success) {
                    alert("Réservation annulée");
                    loadReservations();
                } else {
                    alert(result.message || "Erreur lors de l'annulation");
                }
            } catch (error) {
                console.error("Erreur:", error);
            }
        }

        // Charger les réservations au démarrage
        loadReservations();
    </script>
</main>
<?php include __DIR__ . '/../views/footer.php'; ?>
</body>
</html>

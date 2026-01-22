<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/langue.php';
require_once __DIR__ . '/../config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['UserID'])) {
    if (isset($_COOKIE['UserID'])) {
        $_SESSION['UserID'] = $_COOKIE['UserID'];
    } else {
        header("Location: /connexion");
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
    <link rel="icon" type="image/x-icon" href="/Image/Icone.ico">
    <link rel="stylesheet" href="/CSS/Outils/layout-global.css" />
    <link rel="stylesheet" href="/CSS/MRR.css" />
    <link rel="stylesheet" href="/CSS/Outils/Header.css" />
    <link rel="stylesheet" href="/CSS/Outils/responsive.css" />
    <link rel="stylesheet" href="/CSS/Sombre/Sombre_Header.css" />
    <link rel="stylesheet" href="/CSS/Outils/Footer.css" />
    <link rel="stylesheet" href="/CSS/Sombre/Sombre_Trouver.css" />
    <script src="/JS/Sombre.js"></script>

</head>
<body>
    <?php include __DIR__ . '/../views/header.php'; ?>
    
    <main>
        <h1>Réservations reçues sur mes trajets</h1>
        
        <div id="reservationsList"></div>

        <div id="emptyState" class="empty-state">
            <h2>Aucune réservation</h2>
            <p>Aucun passager n'a réservé sur vos trajets.</p>
            <a href="/publier-trajet" style="display:inline-block; margin-top:1rem; color:var(--primary); text-decoration:none; font-weight:500;">Publier un trajet →</a>
        </div>
    </main>

    <script>
        async function loadReceivedReservations() {
            try {
                const response = await fetch("/api/reservations-received");
                const data = await response.json();
                const list = document.getElementById('reservationsList');
                const empty = document.getElementById('emptyState');

                // Vérifier si la réponse est un tableau
                const reservations = Array.isArray(data) ? data : (data.error ? [] : [data]);

                if (!reservations || reservations.length === 0) {
                    empty.style.display = 'block';
                    list.innerHTML = '';
                    return;
                }

                empty.style.display = 'none';
                list.innerHTML = reservations.map(r => {
                    const statusLower = (r.status || '').toLowerCase();
                    const isWaitingPassenger = statusLower === 'attente_passager';
                    // Afficher le bouton Avis pour les réservations terminées, achevées ou complétées
                    const canRate = statusLower === 'terminee' || statusLower === 'terminée' || statusLower === 'confirmée' || statusLower === 'confirmee' || statusLower === 'achevee' || statusLower === 'achevée';
                    return `
                    <div class="reservation-card">
                        <div class="reservation-header">
                            <div class="reservation-route">${r.from} → ${r.to}</div>
                            <span class="reservation-status status-${statusLower}">${r.status}</span>
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

                        <div class="reservation-actions">
                            <button class="btn btn-primary" onclick="contactPassenger('${r.passengerEmail}')">💬 Contacter</button>
                            ${statusLower !== 'terminee' && statusLower !== 'terminée' && statusLower !== 'annulee' && statusLower !== 'annulée' ? `<button class="btn btn-outline" onclick="cancelReservation(${r.id})">✕ Annuler</button>` : ''}
                            ${canRate ? `<button class="btn btn-secondary" onclick="rateReservation(${r.id})">⭐ Avis</button>` : ''}
                            ${isWaitingPassenger ? `<span class="badge info">En attente du passager</span>` : ''}
                        </div>
                    </div>
                `;
                }).join('');
            } catch (error) {
                console.error("Erreur:", error);
            }
        }

        // Retiré: finishReservation (bouton Terminer seulement sur Mes trajets)

        function contactPassenger(passengerEmail) {
            // Ouvrir la messagerie avec le passager
            window.location.href = `../../Messagerie.php?contact=${encodeURIComponent(passengerEmail)}`;
        }

        async function cancelReservation(reservationId) {
            if (!confirm("Êtes-vous sûr de vouloir annuler cette réservation ?")) return;

            try {
                const response = await fetch("/api/reservation/cancel", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ reservationId })
                });

                const result = await response.json();
                if (result.success) {
                    alert("Réservation annulée");
                    loadReceivedReservations();
                } else {
                    alert(result.message || "Erreur lors de l'annulation");
                }
            } catch (error) {
                console.error("Erreur:", error);
            }
        }

        async function rateReservation(reservationId) {
            // Charger l'avis existant s'il existe
            const response = await fetch(`/api/avis/get?reservation_id=${reservationId}`);
            const data = await response.json();
            const existingAvis = data.avis;

            // Créer et afficher le modal
            const modal = document.createElement('div');
            modal.className = 'modal-overlay';
            modal.id = 'avisModal';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 500px;">
                    <div class="modal-header">
                        <h2>Laisser un avis</h2>
                        <button class="close-btn" onclick="document.getElementById('avisModal').remove()">✕</button>
                    </div>
                    <form onsubmit="submitAvis(event, ${reservationId})">
                        <div class="form-group">
                            <label>Note</label>
                            <div class="rating-stars">
                                <input type="hidden" id="noteInput" value="${existingAvis?.note || 5}">
                                <div class="stars-display">
                                    ${[1,2,3,4,5].map(i => `
                                        <span class="star ${i <= (existingAvis?.note || 5) ? 'active' : ''}" 
                              onclick="setRating(${i})">★</span>
                                    `).join('')}
                                </div>
                                <span id="ratingText">${existingAvis?.note || 5}/5</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="commentaire">Commentaire (optionnel)</label>
                            <textarea id="commentaire" placeholder="Partagez votre expérience..." maxlength="500" rows="4">${existingAvis?.commentaire || ''}</textarea>
                            <small id="charCount">${(existingAvis?.commentaire || '').length}/500</small>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn btn-outline" onclick="document.getElementById('avisModal').remove()">Annuler</button>
                            <button type="submit" class="btn btn-primary">Envoyer l'avis</button>
                        </div>
                    </form>
                </div>
            `;
            document.body.appendChild(modal);
            modal.style.display = 'block';
        }

        function setRating(rating) {
            document.getElementById('noteInput').value = rating;
            document.getElementById('ratingText').textContent = rating + '/5';
            document.querySelectorAll('.star').forEach((star, index) => {
                star.classList.toggle('active', index < rating);
            });
        }

        document.addEventListener('input', function(e) {
            if (e.target.id === 'commentaire') {
                const charCount = e.target.value.length;
                document.getElementById('charCount').textContent = charCount + '/500';
            }
        });

        async function submitAvis(event, reservationId) {
            event.preventDefault();
            const note = parseInt(document.getElementById('noteInput').value);
            const commentaire = document.getElementById('commentaire').value;

            try {
                const response = await fetch('/api/avis/create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        reservation_id: reservationId,
                        note: note,
                        commentaire: commentaire
                    })
                });

                const result = await response.json();
                if (result.success) {
                    alert('Avis enregistré avec succès !');
                    document.getElementById('avisModal').remove();
                    loadReceivedReservations();
                } else {
                    alert(result.message || 'Erreur lors de l\'enregistrement');
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'enregistrement');
            }
        }

        loadReceivedReservations();
    </script>
</main>
<?php include __DIR__ . '/../views/footer.php'; ?>
</body>
</html>

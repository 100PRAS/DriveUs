<?php
session_start();

// Système de langue unifié
require_once 'Outils/config/langue.php';
require_once 'Outils/config/config.php';

$pdo = new PDO("mysql:host=localhost;dbname=ville;charset=utf8","root","");

$req = $pdo->query("SELECT ville_nom FROM villes_france_free ORDER BY ville_nom");
$req2 = $pdo->query("SELECT ville_code_postal FROM villes_france_free ORDER BY ville_code_postal");

// Récupérer les données de l'utilisateur connecté
$currentUserAge = null;
$currentUserGender = null;
$isUserLoggedIn = false;

if (isset($_SESSION['UserID'])) {
    $isUserLoggedIn = true;
    $stmt = $conn->prepare("SELECT Age, Genre FROM user WHERE UserID = ?");
    $stmt->bind_param("i", $_SESSION['UserID']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $currentUserAge = (int)$row['Age'];
        $currentUserGender = $row['Genre'];
    }
}
?>

<!doctype html>
<html lang="<?= getLang() ?>">
<head>
  <meta charset="utf-8">
  <link rel="stylesheet" href="CSS/Outils/layout-global.css" />
    <link rel="stylesheet" href="CSS/Outils/Header.css" />
    <link rel="stylesheet" href="CSS/Sombre/Sombre_Header.css" />
    <link rel="stylesheet" href="CSS/Outils/Footer.css" />

  <link rel="stylesheet" href="CSS/Trouver_un_trajet1.css" />
  <link rel="stylesheet" href="CSS/Outils/filter-accordion.css" />
  <link rel="stylesheet" href="CSS/Sombre/Sombre_Trouver.css" />
  <title>Drive Us – Trouver un trajet</title>
  <link rel="icon" type="image/x-icon" href="Image/Icone.ico">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="JS/Sombre.js"></script>
  <script src="JS/filter-accordion.js"></script>
</head>
<body>
  <?php include 'Outils/views/header.php'; ?>

  <div class="container">
    <!-- Barre de recherche -->
    <main>
    <section class="search-bar" aria-label="Recherche de trajets">
      <label class="field" aria-label="Lieu de départ">
        <span class="icon">📍</span>
        <input list="villes" id="fromInput" type="text" placeholder="Lieu de départ" autocomplete="off">
      </label>
      <label class="field" aria-label="Destination">
        <input list="villes" id="toInput" type="text" placeholder="Destination" autocomplete="off">
      </label>
      <label class="field" aria-label="Date de départ">
        <span class="icon">📅</span>
        <input id="dateInput" type="date" min="">
      </label>
      <button id="searchBtn" class="btn btn-primary" aria-label="Rechercher">Rechercher</button>
    </section>
               <datalist id="villes">
                        <?php
                            $villes = $pdo->query("SELECT ville_nom FROM villes_france_free ORDER BY ville_nom");
                            $codes = $pdo->query("SELECT ville_code_postal FROM villes_france_free ORDER BY ville_code_postal");

                            foreach($villes as $v){
                                echo "<option value='".htmlspecialchars($v['ville_nom'])."'>";
                            }
                            foreach($codes as $c){
                              echo "<option value='".htmlspecialchars($c['ville_code_postal'])."'>";
                            }
                        ?>
                    </datalist>
    <!-- Filtres -->
    <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 20px;">
    <section class="filters" aria-label="Filtres">
      <div>
        <h4>Heure de départ</h4>
        <div id="timeGroup" class="segmented" role="group" aria-label="Plage horaire">
          <button class="chip active" data-time="all">Toute la journée</button>
          <button class="chip" data-time="morning">Matin</button>
          <button class="chip" data-time="afternoon">Après-midi</button>
          <button class="chip" data-time="evening">Soir</button>
        </div>
      </div>
      <div>
        <h4>Prix max (€)</h4>
        <div class="range">
          <span id="priceOut" aria-live="polite">100</span>
          <input id="priceRange" type="range" min="0" max="100"  step="1" value="100" aria-label="Prix maximum">
        </div>
      </div>
      <div>
        <h4>Places min</h4>
        <div class="range">
          <span id="seatsOut" aria-live="polite">1</span>
          <input id="seatsRange" type="range" min="1" max="4"  step="1" value="1" aria-label="Nombre de places minimum">
        </div>
      </div>
      <div>
        <h4>Note min</h4>
        <div id="rating" class="rating" aria-label="Note minimum">
          <span class="star" data-val="1">★</span>
          <span class="star" data-val="2">★</span>
          <span class="star" data-val="3">★</span>
          <span class="star" data-val="4">★</span>
          <span class="star" data-val="5">★</span>
          <span id="ratingOut" class="sub" style="margin-left:6px;color:var(--muted)">0+</span>
        </div>
      </div>
      <div class="sorter">
        <h4>Trier par</h4>
        <select id="sortSelect" aria-label="Tri">
          <option value="relevance">Pertinence</option>
          <option value="priceAsc">Prix croissant</option>
          <option value="timeAsc">Départ le plus tôt</option>
          <option value="ratingDesc">Meilleure note</option>
          <option value="durationAsc">Durée la plus courte</option>
        </select>
      </div>
    </section>

    <!-- Accordéon pour filtres additionnels -->
    <div class="filter-accordion">
      <button id="filterAccordionBtn" class="filter-accordion-btn">
        ⚙️ Filtres avancés
      </button>
      <div id="filterAccordionContent" class="filter-accordion-content">
        <div class="field">
          <label class="label">Bagages</label>
          <label class="choice"><input type="radio" name="bagage" value="petit" /> Petit sac</label>
          <label class="choice"><input type="radio" name="bagage" value="moyen" /> Moyen</label>
          <label class="choice"><input type="radio" name="bagage" value="grand" /> Grand</label>
        </div>

        <div class="field">
          <label class="label">Fumeur</label>
          <label class="choice"><input type="radio" name="fumeur" value="non" /> Non-fumeur</label>
          <label class="choice"><input type="radio" name="fumeur" value="oui" /> Fumeur</label>
        </div>

        <div class="field">
          <label class="label">Animaux</label>
          <label class="choice"><input type="radio" name="animaux" value="non" /> Non</label>
          <label class="choice"><input type="radio" name="animaux" value="oui" /> Oui</label>
        </div>

        <div class="field">
          <label class="label">Genre accepté</label>
          <label class="choice"><input type="checkbox" name="genre[]" value="Homme" /> Homme</label>
          <label class="choice"><input type="checkbox" name="genre[]" value="Femme" /> Femme</label>
          <label class="choice"><input type="checkbox" name="genre[]" value="Autre" /> Autre</label>
        </div>

        <div class="field">
          <label class="label">Langue parlée</label>
          <label class="choice"><input type="checkbox" name="langue[]" value="Français" /> Français</label>
          <label class="choice"><input type="checkbox" name="langue[]" value="Anglais" /> Anglais</label>
          <label class="choice"><input type="checkbox" name="langue[]" value="Autre" /> Autre</label>
        </div>

        <div class="field">
          <label class="label">Enfant autorisé</label>
          <label class="choice"><input type="radio" name="enfant" value="oui" /> Oui</label>
          <label class="choice"><input type="radio" name="enfant" value="non" /> Non</label>
        </div>
      </div>
    </div>
    </div>

    <!-- Résultats -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
      <h3 style="margin:0;font-weight:600;font-size:16px;">Trajets disponibles</h3>
      <button id="resetFiltersBtn" class="btn btn-outline" style="padding:8px 16px;font-size:13px;">Réinitialiser filtres</button>
    </div>

    <section id="results" class="results" aria-live="polite"></section>

    <div id="emptyState" class="empty hidden">
      Aucun trajet ne correspond à votre recherche. Essayez d’élargir les filtres.
    </div>
  </div>

  <!-- Modale détails -->
  <div id="modal" class="modal-backdrop" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal">
      <header style="display:flex;justify-content:space-between;align-items:center">
        <h3 id="modalTitle" style="margin:0">Détails du trajet</h3>
        <button id="closeModal" class="chip">Fermer</button>
      </header>
      <div id="modalBody"></div>
      <div id="reservationForm" style="display:none; margin-top:1rem; padding:1rem; border-top:1px solid var(--border);">
        <label style="display:block; margin-bottom:0.5rem;">
          Nombre de places :
          <input type="number" id="seatsInput" min="1" max="4" value="1" style="width:60px; padding:0.5rem;">
        </label>
        <p id="totalPrice" style="color:var(--muted); margin:0.5rem 0;"></p>
      </div>
                          </main>
      <footer>
        <button class="btn chip" id="shareBtn">Partager</button>
        <button class="btn btn-primary" id="contactBtn" onclick="contactDriver()">Contacter le conducteur</button>
        <button class="btn btn-success" id="bookBtn" onclick="showReservationForm()">Réserver ce trajet</button>
        <button class="btn btn-success" id="confirmBookBtn" style="display:none;" onclick="confirmReservation()">Confirmer la réservation</button>
      </footer>
    </div>
  </div>

  <script>
    let trips = []; // sera rempli par fetch()

    // --- Données de démonstration (peuvent venir d'une API plus tard)
async function runSearch() {
    const fromVal = fromInput.value.trim();
    const toVal   = toInput.value.trim();
    const dateVal = dateInput.value;

    try {
        // Inclure les préférences utilisateur dans l'appel API (bagage, fumeur, animaux, enfant, genre, langue)
        const genreParam = Array.isArray(state.genre) ? state.genre.join(',') : (state.genre || '');
        const langueParam = Array.isArray(state.langue) ? state.langue.join(',') : (state.langue || '');
        const base = `Outils/trips/get_trips.php?from=${encodeURIComponent(fromVal)}&to=${encodeURIComponent(toVal)}&priceMax=${priceRange.value}&seatsMin=${seatsRange.value}&timeBand=${state.timeBand}&minRating=${state.ratingMin}&sort=${sortSelect.value}&bagage=${encodeURIComponent(state.bagage)}&fumeur=${encodeURIComponent(state.fumeur)}&animaux=${encodeURIComponent(state.animaux)}&enfant=${encodeURIComponent(state.enfant)}&genre=${encodeURIComponent(genreParam)}&langue=${encodeURIComponent(langueParam)}`;
        const url = dateVal ? `${base}&date=${encodeURIComponent(dateVal)}` : base;
        console.log('Appel API:', url);
        
        const res = await fetch(url);
        console.log('Status:', res.status, res.statusText);
        
        const text = await res.text();
        console.log('Réponse brute:', text);
        
        if (!res.ok) throw new Error('Erreur serveur ' + res.status);
        
        trips = JSON.parse(text);
        console.log('Trajets parsés:', trips);
        apply();
    } catch(e) {
        console.error('Erreur complète:', e);
        results.innerHTML = `<p>Erreur: ${e.message}</p>`;
    }
}







    // --- Sélecteurs
    const fromInput = document.getElementById('fromInput');
    const toInput   = document.getElementById('toInput');
    const dateInput = document.getElementById('dateInput');
    const searchBtn = document.getElementById('searchBtn');

    const priceRange = document.getElementById('priceRange');
    const priceOut   = document.getElementById('priceOut');
    const seatsRange = document.getElementById('seatsRange');
    const seatsOut   = document.getElementById('seatsOut');
    const timeGroup  = document.getElementById('timeGroup');
    const ratingOut  = document.getElementById('ratingOut');
    const sortSelect = document.getElementById('sortSelect');

    const results    = document.getElementById('results');
    const emptyState = document.getElementById('emptyState');

    const modal      = document.getElementById('modal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody  = document.getElementById('modalBody');
    const closeModal = document.getElementById('closeModal');
    const bookBtn    = document.getElementById('bookBtn');
    const shareBtn   = document.getElementById('shareBtn');
    const reservationForm = document.getElementById('reservationForm');
    const seatsInput = document.getElementById('seatsInput');
    const totalPrice = document.getElementById('totalPrice');
    const confirmBookBtn = document.getElementById('confirmBookBtn');

    // Trajet actuellement affiché dans la modale
    let currentTrip = null;

    // Données utilisateur
    const currentUserData = {
        isLoggedIn: <?= json_encode($isUserLoggedIn) ?>,
        age: <?= json_encode($currentUserAge) ?>,
        gender: <?= json_encode($currentUserGender) ?>
    };

    // Fonction de vérification du profil
    function checkProfileCompatibility(trip) {
        if (!currentUserData.isLoggedIn) {
            return { 
                compatible: false, 
                message: "⚠️ Vous devez être connecté pour réserver." 
            };
        }

        // Vérifier l'âge minimum
        if (trip.ageMin && currentUserData.age < trip.ageMin) {
            return { 
                compatible: false, 
                message: `❌ Âge insuffisant. Le conducteur exige un minimum de ${trip.ageMin} ans. Vous avez ${currentUserData.age} ans.` 
            };
        }

        // Vérifier l'âge maximum
        if (trip.ageMax && currentUserData.age > trip.ageMax) {
            return { 
                compatible: false, 
                message: `❌ Âge trop élevé. Le conducteur accepte un maximum de ${trip.ageMax} ans. Vous avez ${currentUserData.age} ans.` 
            };
        }

        // Vérifier le genre
        if (trip.genreAccepte && trip.genreAccepte.length > 0) {
            const genreList = Array.isArray(trip.genreAccepte) ? trip.genreAccepte : trip.genreAccepte.split(',');
            if (!genreList.includes(currentUserData.gender)) {
                return { 
                    compatible: false, 
                    message: `❌ Le conducteur n'accepte que les passagers de genre: ${genreList.join(', ')}. Votre profil indique: ${currentUserData.gender}` 
                };
            }
        }

        // Vérifier l'âge (enfants)
        const isChild = currentUserData.age < 18;
        if (trip.enfantAutorise === 0 && isChild) {
            return { 
                compatible: false, 
                message: "❌ Le conducteur n'accepte pas les enfants. Vous devez être majeur pour cette réservation." 
            };
        }

        return { compatible: true, message: "✓ Votre profil est compatible avec ce trajet." };
    }

    // Synchroniser les affichages des sliders au chargement
    priceOut.textContent = priceRange.value;
    seatsOut.textContent = seatsRange.value;

    // Synchroniser slider → affichage (prix)
    priceRange.addEventListener('input', () => {
      priceOut.textContent = priceRange.value;
    });

    // Synchroniser slider → affichage (places)
    seatsRange.addEventListener('input', () => {
      seatsOut.textContent = seatsRange.value;
    });

    // --- État des filtres
    const state = {
      from: "", to: "", date: "",
      priceMax: 100,
      seatsMin: 1,
      timeBand: "all", ratingMin: 0, sort: "relevance",
      bagage: "", fumeur: "", animaux: "", genre: [], enfant: "", langue: []
    };

    // Helpers
    const toMinutes = t => {
      const [h,m] = t.split(':').map(Number);
      return h*60+m;
    };
    const inBand = (time, band) => {
      const min = toMinutes(time);
      if (band==='morning')   return min>=6*60 && min<12*60;
      if (band==='afternoon') return min>=12*60 && min<18*60;
      if (band==='evening')   return min>=18*60 && min<24*60;
      return true;
    };
    const formatDuration = mins => {
      const h = Math.floor(mins/60), m = mins%60;
      return `${h} h ${String(m).padStart(2,'0')} min`;
    };
    const starRow = rating => {
      const full = Math.round(rating);
      return '★'.repeat(full)+'☆'.repeat(5-full);
    };

    // Rendu d’une carte
    function renderCard(t) {
  const card = document.createElement('article');
  card.className = 'card';
  card.setAttribute('tabindex','0');

  const avatarHtml = t.driverPhoto ? `<img src="${t.driverPhoto}" alt="Profil" style="width:50px;height:50px;border-radius:50%;" />` : (t.driver?.[0] ?? '?');

  // Construire l'itinéraire avec arrêts supplémentaires
  let routeDisplay = t.from;
  if (t.arrets_supplementaires && t.arrets_supplementaires.length > 0) {
    routeDisplay += ' → ' + t.arrets_supplementaires.join(' → ');
  }
  routeDisplay += ' → ' + t.to;

  // Vérifier si le trajet est en favoris
  const tripId = `trip_${t.id || t.driver}_${t.date}_${t.depart}`;
  const isFavorite = localStorage.getItem(`favorite_${tripId}`) === 'true';
  const heartIcon = isFavorite ? '❤️' : '🤍';

  card.innerHTML = `
    <div class="avatar" aria-hidden="true">${avatarHtml}</div>
    <div>
      <div class="driver">
        <span class="name">${t.driver}</span>
        <div class="route">${routeDisplay}</div>
        <div class="time">Départ ${t.depart} • ${formatDuration(t.durationMin)} • ${t.seats} place(s) dispo</div>
      </div>
      <div class="price">${t.price} €<div class="sub">/passager</div></div>
      <div class="reserve">
        <button class="btn-heart" aria-label="Ajouter aux favoris" title="Ajouter aux favoris">${heartIcon}</button>
        <button class="btn btn-success" aria-label="Réserver">Réserver</button>
      </div>
    </div>
  `;

  const open = () => openModal(t);
  const heartBtn = card.querySelector('.btn-heart');
  
  // Événement pour le cœur
  heartBtn.addEventListener('click', e => {
      e.stopPropagation();
      const isFav = localStorage.getItem(`favorite_${tripId}`) === 'true';
      if (isFav) {
          localStorage.removeItem(`favorite_${tripId}`);
          heartBtn.textContent = '🤍';
          heartBtn.title = 'Ajouter aux favoris';
      } else {
          localStorage.setItem(`favorite_${tripId}`, 'true');
          heartBtn.textContent = '❤️';
          heartBtn.title = 'Retirer des favoris';
      }
  });

  card.addEventListener('click', e => {
      if(e.target.closest('button')) return;
      open();
  });
  card.querySelector('.btn-success').addEventListener('click', e => {
      e.stopPropagation();
      open();
  });
  return card;
}



    // Filtrage + tri + rendu
function apply() {
    const termFrom = state.from.trim().toLowerCase();
    const termTo   = state.to.trim().toLowerCase();

    let list = trips.filter(t => {
        const okFrom  = !termFrom || (t.from?.toLowerCase().includes(termFrom));
        const okTo    = !termTo   || (t.to?.toLowerCase().includes(termTo));
        const okDate  = !state.date || (t.date === state.date);
        const okPrice = t.price <= state.priceMax;
        const okSeat  = t.seats >= state.seatsMin;
        const okRate  = t.rating >= state.ratingMin;
        const okBand  = inBand(t.depart, state.timeBand);
        
        return okFrom && okTo && okDate && okPrice && okSeat && okRate && okBand;
    });

    // Tri
    switch(state.sort) {
        case 'priceAsc':    list.sort((a,b)=>a.price-b.price); break;
        case 'timeAsc':     list.sort((a,b)=>toMinutes(a.depart)-toMinutes(b.depart)); break;
        case 'ratingDesc':  list.sort((a,b)=>b.rating-a.rating); break;
        case 'durationAsc': list.sort((a,b)=>a.durationMin-b.durationMin); break;
        default: // pertinence simple
            list.sort((a,b)=> (b.seats-(b.price/100)) - (a.seats-(a.price/100)));
    }

    results.innerHTML = '';
    if(list.length === 0){
        emptyState.classList.remove('hidden');
    } else {
        emptyState.classList.add('hidden');
        list.forEach(t => results.appendChild(renderCard(t)));
    }
}

    // Modale
function openModal(t) {
    currentTrip = t;
    modalTitle.textContent = `${t.from} → ${t.to} — ${t.depart}`;
    
    // Construire l'affichage des arrêts supplémentaires
    let stopsHtml = '';
    if (t.arrets_supplementaires && t.arrets_supplementaires.length > 0) {
        stopsHtml = `<p><strong>Arrêts intermédiaires :</strong> ${t.arrets_supplementaires.join(' → ')}</p>`;
    }
    
    modalBody.innerHTML = `
        <p><strong>Trajet :</strong> ${t.from} → ${stopsHtml ? t.arrets_supplementaires.join(' → ') + ' → ' : ''}${t.to}</p>
        <p><strong>Conducteur :</strong> ${t.driver} (${t.rating?.toFixed(1) ?? 'N/A'} ${starRow(t.rating ?? 0)})</p>
        <p><strong>Date :</strong> ${t.date} • <strong>Durée :</strong> ${formatDuration(t.durationMin)}</p>
        <p><strong>Véhicule :</strong> ${t.vehicle}</p>
        <p><strong>Prix :</strong> ${t.price} € par personne</p>
        <p><strong>Places disponibles :</strong> ${t.seats}</p>
        ${stopsHtml}
        <p style="color:var(--muted)">${t.notes}</p>
    `;
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
}

    // Contacter le conducteur
    function contactDriver() {
        if (!currentTrip) {
            alert("Erreur : Aucun trajet sélectionné");
            return;
        }

        if (!currentTrip.driverEmail) {
            alert("Erreur : Email du conducteur non disponible");
            return;
        }

        // Fermer la modale
        closeModalFn();

        // Rediriger vers la messagerie avec le conducteur pré-sélectionné
        window.location.href = `Messagerie.php?contact=${encodeURIComponent(currentTrip.driverEmail)}&trip=${encodeURIComponent(currentTrip.from + ' → ' + currentTrip.to)}`;
    }

    // Afficher le formulaire de réservation
    function showReservationForm() {
        if (!currentTrip) return;
        
        bookBtn.style.display = 'none';
        confirmBookBtn.style.display = 'inline-block';
        reservationForm.style.display = 'block';
        seatsInput.max = currentTrip.seats;
        updateTotalPrice();
    }

    // Mettre à jour le prix total
    function updateTotalPrice() {
        const seats = parseInt(seatsInput.value) || 1;
        const price = currentTrip.price * seats;
        totalPrice.textContent = `Prix total : ${price.toFixed(2)} € (${seats} place(s) × ${currentTrip.price} €)`;
    }

    // Confirmer la réservation
    async function confirmReservation() {
        if (!currentTrip) return;

        // Vérifier la compatibilité du profil
        const compatibility = checkProfileCompatibility(currentTrip);
        if (!compatibility.compatible) {
            alert(compatibility.message);
            return;
        }

        const seats = parseInt(seatsInput.value) || 1;

        try {
            const response = await fetch("Outils/reservations/make_reservation.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    tripId: currentTrip.id,
                    numberOfSeats: seats
                })
            });

            const raw = await response.text();
            let result;
            try { result = JSON.parse(raw); } catch(e) {
                console.error('Réponse non-JSON:', raw);
                alert('Erreur serveur: réponse invalide');
                return;
            }
            console.log('Réservation → résultat:', result);

            if (!result.success) {
                alert(result.message || "Erreur lors de la réservation");
                return;
            }

            // Succès
            alert(`✓ Réservation confirmée !\nTrajet: ${currentTrip.from} → ${currentTrip.to}\nPlaces: ${seats}\nPrix: ${(currentTrip.price * seats).toFixed(2)} €`);
            
            // Mettre à jour le nombre de places restantes dans le trajet et la carte
            if (result.seatsRemaining !== undefined) {
                currentTrip.seats = result.seatsRemaining;
                
                // Chercher et mettre à jour la carte du trajet dans le DOM
                const allCards = document.querySelectorAll('article.card');
                for (let card of allCards) {
                    const nameEl = card.querySelector('.name');
                    const timeEl = card.querySelector('.time');
                    if (nameEl && nameEl.textContent === currentTrip.driver && timeEl) {
                        const duration = formatDuration(currentTrip.durationMin);
                        timeEl.textContent = `Départ ${currentTrip.depart} • ${duration} • ${result.seatsRemaining} place(s) dispo`;
                        
                        // Si plus de places, désactiver le bouton et estomper la carte
                        if (result.seatsRemaining === 0) {
                            card.style.opacity = '0.6';
                            card.style.pointerEvents = 'none';
                            const btn = card.querySelector('button');
                            if (btn) {
                                btn.disabled = true;
                                btn.textContent = 'Complet';
                            }
                        }
                        break;
                    }
                }
            }
            
            // Masquer le formulaire et réinitialiser
            reservationForm.style.display = 'none';
            bookBtn.style.display = 'inline-block';
            confirmBookBtn.style.display = 'none';
            seatsInput.value = 1;
            
            // Fermer la modale
            closeModalFn();
        } catch (error) {
            console.error("Erreur lors de la réservation:", error);
            alert("Erreur de communication avec le serveur");
        }
    }

    function closeModalFn(){
      modal.style.display='none';
      modal.setAttribute('aria-hidden','true');
    }
    modal.addEventListener('click', (e)=>{ if(e.target===modal) closeModalFn(); });
    closeModal.addEventListener('click', closeModalFn);
    document.addEventListener('keydown', e=>{ if(e.key==='Escape') closeModalFn(); });

    // Event listener pour mettre à jour le prix total lors de la réservation
    if (seatsInput) {
        seatsInput.addEventListener('input', updateTotalPrice);
    }

    // Interactions filtres
    priceRange.addEventListener('input', ()=>{ state.priceMax=Number(priceRange.value); priceOut.textContent=state.priceMax; apply(); });
    seatsRange.addEventListener('input', ()=>{ state.seatsMin=Number(seatsRange.value); seatsOut.textContent=state.seatsMin; apply(); });

    // Time band chips
    timeGroup.addEventListener('click', (e)=>{
      const btn = e.target.closest('.chip'); if(!btn) return;
      [...timeGroup.querySelectorAll('.chip')].forEach(c=>c.classList.remove('active'));
      btn.classList.add('active'); state.timeBand = btn.dataset.time; apply();
    });

    // Rating stars
    const starEls = [...document.querySelectorAll('.star')];
    starEls.forEach(s=>{
      s.addEventListener('mouseenter', ()=> highlight(Number(s.dataset.val)));
      s.addEventListener('mouseleave', ()=> highlight(state.ratingMin));
      s.addEventListener('click', ()=> { state.ratingMin = Number(s.dataset.val); ratingOut.textContent = state.ratingMin + '+'; apply(); });
    });
    function highlight(val){
      starEls.forEach(st=> st.classList.toggle('active', Number(st.dataset.val) <= val));
    }
    highlight(0);

    // Préférences - Radios (bagage, fumeur, animaux, enfant) - déclencher une nouvelle recherche
    document.querySelectorAll('input[name="bagage"]').forEach(el => {
      el.addEventListener('change', ()=>{ state.bagage = document.querySelector('input[name="bagage"]:checked')?.value || ''; runSearch(); });
    });
    document.querySelectorAll('input[name="fumeur"]').forEach(el => {
      el.addEventListener('change', ()=>{ state.fumeur = document.querySelector('input[name="fumeur"]:checked')?.value || ''; runSearch(); });
    });
    document.querySelectorAll('input[name="animaux"]').forEach(el => {
      el.addEventListener('change', ()=>{ state.animaux = document.querySelector('input[name="animaux"]:checked')?.value || ''; runSearch(); });
    });
    document.querySelectorAll('input[name="enfant"]').forEach(el => {
      el.addEventListener('change', ()=>{ state.enfant = document.querySelector('input[name="enfant"]:checked')?.value || ''; runSearch(); });
    });

    // Préférences - Checkboxes (genre)
    document.querySelectorAll('input[name="genre[]"]').forEach(el => {
      el.addEventListener('change', ()=>{ 
        state.genre = [...document.querySelectorAll('input[name="genre[]"]:checked')].map(e => e.value);
        runSearch(); 
      });
    });

    // Préférences - Checkboxes (langue)
    document.querySelectorAll('input[name="langue[]"]').forEach(el => {
      el.addEventListener('change', ()=>{ 
        state.langue = [...document.querySelectorAll('input[name="langue[]"]:checked')].map(e => e.value);
        runSearch(); 
      });
    });

    // Bouton réinitialiser filtres
    document.getElementById('resetFiltersBtn').addEventListener('click', ()=>{
      // Réinitialiser les ranges
      priceRange.value = 100;
      priceOut.textContent = 100;
      state.priceMax = 100;
      seatsRange.value = 1;
      seatsOut.textContent = 1;
      state.seatsMin = 1;
      
      // Réinitialiser time band
      [...timeGroup.querySelectorAll('.chip')].forEach(c=>c.classList.remove('active'));
      timeGroup.querySelector('[data-time="all"]').classList.add('active');
      state.timeBand = 'all';
      
      // Réinitialiser rating
      state.ratingMin = 0;
      ratingOut.textContent = '0+';
      highlight(0);
      
      // Réinitialiser sort
      sortSelect.value = 'relevance';
      state.sort = 'relevance';
      
      // Réinitialiser préférences (tout décocher)
      document.querySelectorAll('input[name="bagage"]').forEach(r => r.checked = false);
      state.bagage = '';
      document.querySelectorAll('input[name="fumeur"]').forEach(r => r.checked = false);
      state.fumeur = '';
      document.querySelectorAll('input[name="animaux"]').forEach(r => r.checked = false);
      state.animaux = '';
      document.querySelectorAll('input[name="enfant"]').forEach(r => r.checked = false);
      state.enfant = '';
      
      // Réinitialiser genre (tout décocher)
      document.querySelectorAll('input[name="genre[]"]').forEach(cb => cb.checked = false);
      state.genre = [];
      
      // Réinitialiser langue (tout décocher)
      document.querySelectorAll('input[name="langue[]"]').forEach(cb => cb.checked = false);
      state.langue = [];
      
      apply();
    });

    // Recherche
    searchBtn.addEventListener('click', runSearch);
    [fromInput,toInput,dateInput].forEach(el=> el.addEventListener('keydown', e=>{ if(e.key==='Enter') runSearch(); }));
    sortSelect.addEventListener('change', ()=>{ state.sort = sortSelect.value; apply(); });

    // Bouton Partager
    if (shareBtn) {
        shareBtn.addEventListener('click', function() {
            if (!currentTrip) {
                alert("Erreur : Aucun trajet sélectionné");
                return;
            }

            // Construire l'URL avec les paramètres du trajet
            const shareUrl = `${window.location.origin}/DriveUs/Trouver_un_trajet.php?from=${encodeURIComponent(currentTrip.from)}&to=${encodeURIComponent(currentTrip.to)}&date=${encodeURIComponent(currentTrip.date)}`;

            // Copier dans le presse-papiers
            navigator.clipboard.writeText(shareUrl).then(() => {
                // Afficher une confirmation visuelle
                const originalText = shareBtn.textContent;
                shareBtn.textContent = '✓ Lien copié !';
                shareBtn.style.background = '#28a745';
                
                setTimeout(() => {
                    shareBtn.textContent = originalText;
                    shareBtn.style.background = '';
                }, 2000);
            }).catch(err => {
                console.error('Erreur copie:', err);
                // Fallback: afficher le lien
                prompt('Copiez ce lien :', shareUrl);
            });
        });
    }

    // Fonction pour annuler une réservation
    async function cancelReservation(reservationId) {
        if (!confirm("Etes-vous sur de vouloir annuler cette reservation ?")) return;

        try {
            const response = await fetch("Outils/reservations/cancel_reservation.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ reservationId: reservationId })
            });

            const result = await response.json();

            if (!result.success) {
                alert(result.message || "Erreur lors de l'annulation");
                return;
            }

            alert("Reservation annulee avec succes. " + result.seatsRestored + " place(s) liberee(s).");
            
            // Recharger les trajets et maj de la liste
            runSearch();
        } catch (error) {
            console.error("Erreur lors de l'annulation:", error);
            alert("Erreur de communication avec le serveur");
        }
    }

    // Init
    const today = new Date().toISOString().slice(0,10);
    dateInput.min = today;
    
    // Récupérer les paramètres de l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const fromParam = urlParams.get('from') || '';
    const toParam = urlParams.get('to') || '';
    const dateParam = urlParams.get('date') || '';
    
    // Pré-remplir les champs avec les paramètres de l'URL
    fromInput.value = fromParam;
    toInput.value = toParam;
    dateInput.value = dateParam;
    
    state.from = fromParam;
    state.to = toParam;
    state.date = dateParam;
    priceOut.textContent = state.priceMax;
    seatsOut.textContent = state.seatsMin;
    
    // Charger les trajets au démarrage
    runSearch();
    
  </script>
</main>
  <?php include 'Outils/views/footer.php'; ?>
  <script src="JS/Hamburger.js"></script>

</body>
</html>
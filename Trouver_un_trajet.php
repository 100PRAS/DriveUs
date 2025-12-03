<?php
    // Langue
    if(isset($_GET["lang"])) {
        $_SESSION["lang"] = $_GET["lang"];
    }
    $lang = $_SESSION["lang"] ?? "fr";
    $text = require "Outils/lang_$lang.php";
?>


<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Drive Us – Trouver un trajet</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Design tokens (couleurs, rayons, ombres) alignés avec l'accueil -->
  <style>
    :root{
      --blue:#2F6FE4;
      --blue-600:#1f58c7;
      --green:#31C76A;
      --green-600:#22a455;
      --bg:#f6f8fb;
      --card:#ffffff;
      --text:#1d2330;
      --muted:#6b7280;
      --ring:#e5e7eb;
      --radius:14px;
      --shadow:0 8px 24px rgba(0,0,0,0.08);
      --shadow-sm:0 4px 12px rgba(0,0,0,0.06);
    }
    *{box-sizing:border-box}
    html,body{margin:0;padding:0;background:var(--bg);color:var(--text);font:16px/1.6 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,"Helvetica Neue","Noto Sans",Arial}
    a{color:inherit;text-decoration:none}
    .container{max-width:1100px;margin:0 auto;padding:24px}
  .head{
  background-color: hsla(0, 0%, 97%, 0.94);   /* Couleur de fond (gris foncé) */
  padding: 0.5%;            /* Espacement interne */
  text-align: center;       /* Centrer le texte */
  font-size: 15%;  
  width: 100%;
  top: 0%;
  left: 0%;
  position:fixed;
  z-index: 1000;
  justify-content: space-evenly;

}



.logo_clair{
  width: 5%;
  height:3%;
  float:left;
  max-width: 100%;
  height: auto;
}
.logo_sombre{
  width: 10%;
  height:6%;
  float:left;
  max-width: 100%;
  height: auto;
}
.logo_dark{
    display:none;
}
.Bande li{
  display: inline;
}


.Boutton_Acceuil {
  background-color:hsla(0, 0%, 97%,0.97); /* Bleu */
  color: rgb(0, 0, 0);              /* Couleur du texte */
  border: none;              /* Pas de bordure */
  padding: 1% 2%;        /* Espacement interne */
    border-radius: 10px;        /* Bords arrondis */
        /* Bords arrondis */
  cursor: pointer;           /* Curseur "main" */
  font-size: 16px;
  transition: background-color 0.3s; /* Animation fluide */
}

.Boutton_Acceuil:hover {
  background-color: #515151; /* Bleu plus foncé */
}



.Boutton_Trouver {
  background-color: hsla(0, 0%, 97%,0.97); /* Bleu */
  color: rgb(0, 0, 0);              /* Couleur du texte */
  border: none;              /* Pas de bordure */
  padding: 1% 2%;        /* Espacement interne */
  border-radius: 10px;        /* Bords arrondis */
  cursor: pointer;           /* Curseur "main" */
  font-size: 16px;
  transition: background-color 0.3s; /* Animation fluide */

}

.Boutton_Trouver:hover {
  background-color: #515151; /* Bleu plus foncé */
}


.Boutton_Publier {
  background-color:hsla(0, 0%, 97%,0.97); /* Bleu */
  color: rgb(0, 0, 0);              /* Couleur du texte */
  border: none;              /* Pas de bordure */
  padding: 1% 1%;        /* Espacement interne */
  border-radius: 10px;        /* Bords arrondis */
  cursor: pointer;           /* Curseur "main" */
  font-size: 16px;
  transition: background-color 0.3s; /* Animation fluide */
}

.Boutton_Publier:hover {
  background-color: #515151; /* Bleu plus foncé */
}



.Messagerie {
  background-color:hsla(0, 0%, 97%,0.97); /* Bleu */
  color: rgb(0, 0, 0);              /* Couleur du texte */
  border: none;              /* Pas de bordure */
  padding: 1% 2%;        /* Espacement interne */
  border-radius: 10px;        /* Bords arrondis */
  cursor: pointer;           /* Curseur "main" */
  font-size: 16px;
  transition: background-color 0.3s; /* Animation fluide */
}

.Messagerie:hover {
  background-color: #515151; /* Bleu plus foncé */
}



.Boutton_Se_connecter {
  background-color: #007BFF; /* Bleu */
  color: white;              /* Couleur du texte */
  border: none;              /* Pas de bordure */
  padding: 1% 1%;        /* Espacement interne */
  border-radius: 10px;        /* Bords arrondis */
  cursor: pointer;           /* Curseur "main" */
  font-size: 16px;
  transition: background-color 0.3s; /* Animation fluide */
}

.Boutton_Se_connecter:hover {
  background-color: #0056b3; /* Bleu plus foncé */
}



.Langue {
   background-color:hsla(0, 0%, 97%,0.97); /* Bleu */
  color: rgb(0, 0, 0);              /* Couleur du texte */
  border: none;              /* Pas de bordure */
  padding: 0.5% 1%;        /* Espacement interne */
  border-radius: 8%;        /* Bords arrondis */
  cursor: pointer;           /* Curseur "main" */
  font-size: 16px;
  float: right;

}

.Langue:hover {
  background-color: #515151; /* Bleu plus foncé */
}

.Langue li {
    float: left;
}

.Langue li a:link, .Langue li a:visited {
    display: block;
    color: #000000;
    background-color:hsla(0, 0%, 97%,0.97); /* Bleu */
    padding: 1% 2%;
    border-right: 1% solid #FFF;
    text-align: center;
    text-decoration: none;
    border-radius: 8%;        /* Bords arrondis */

}
.France{
  height: 10%;
  width: 10%;
  border-radius: 10%;
}

.Langue li a:active {background-color:hsla(0, 0%, 97%,0.97);}

.Langue .sousmenu {
    list-style-type: none;
    display: none;
    padding: 0;
    margin: 0;
    position: absolute;
}

.Langue .sousmenu li {
    float: none;
    margin: 0;
    padding: 0;
    border-top: 1% solid transparent;
    border-right: 1% solid transparent;
}

.Langue .sousmenu li a:link, .Langue li a:visited {
    display: block;
    color: #FFF;
    text-decoration: none;
    background-color: #808080;
}

.Langue li:hover .sousmenu {
    display: block;
}



.Sombre1, .SombreB{
    width:3%;
    height:3%;
    float:right;
    cursor:pointer;}



.hamburger {
    display: none;
    flex-direction: column;
    justify-content: space-around;
    width: 30px;
    height: 25px;
    cursor: pointer;
    float: right;
    margin-right: 5%;
}

.hamburger span {
    display: block;
    height: 4px;
    width: 100%;
    background: #333;
    border-radius: 2px;
}

.hamburger div {
    width: 30px;  /* largeur de la barre */
    height: 3px;  /* épaisseur de la barre */
    background-color: black;
    border-radius: 2px;
}
/* Menu mobile */
@media (max-width: 768px) {
    .Bande {
        display: none;
        flex-direction: column;
        background-color: #ececec;
        position: fixed;
        top: 60px; /* juste en dessous du header */
        right: 0;
        width: 70%;
        height: calc(100% - 60px);
        padding-top: 2rem;
        text-align: center;
        gap: 1.5rem;
        z-index: 999;
    }

    .Bande li {
        display: block;
        margin: 1rem 0;
    }

    .hamburger {
        display: flex;
    }

    /* Quand menu actif */
    .Bande.active {
        display: flex;
    }
}



    /* Barre de recherche */
    .search-bar{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);padding:14px 14px 8px;display:grid;grid-template-columns:1fr 1fr 180px 150px;gap:10px;align-items:center;position:sticky;top:12px;z-index:5}
    .field{position:relative}
    .field input{width:100%;border:1px solid var(--ring);background:#fff;border-radius:12px;padding:12px 14px 12px 40px;outline:none;transition:border .15s}
    .field input:focus{border-color:var(--blue)}
    .field .icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:18px;color:var(--muted)}
    .btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:12px;font-weight:700;cursor:pointer;transition:transform .02s ease, background .2s ease;user-select:none}
    .btn:active{transform:translateY(1px)}
    .btn-primary{background:var(--blue);color:#fff;height:44px}
    .btn-primary:hover{background:var(--blue-600)}
    .btn-success{background:var(--green);color:#fff}
    .btn-success:hover{background:var(--green-600)}
    /* Filtres */
    .filters{margin-top:14px;background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);padding:14px;display:grid;grid-template-columns:repeat(4,1fr) 210px;gap:16px;align-items:center}
    .filters h4{margin:0 0 6px 0;font-size:13px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.06em}
    .segmented{display:flex;gap:8px;flex-wrap:wrap}
    .chip{padding:8px 12px;border:1px solid var(--ring);border-radius:999px;background:#fff;cursor:pointer}
    .chip.active{border-color:var(--blue);box-shadow:inset 0 0 0 1px var(--blue);color:var(--blue);font-weight:700}
    .range{display:flex;gap:10px;align-items:center}
    .range input[type=range]{width:100%}
    .rating{display:flex;gap:4px;align-items:center}
    .star{font-size:20px;cursor:pointer;color:#d1d5db}
    .star.active{color:#f59e0b}
    .sorter{display:flex;flex-direction:column}
    .sorter select{border:1px solid var(--ring);border-radius:12px;padding:10px 12px;background:#fff}
    /* Résultats */
    .results{margin-top:18px;display:grid;gap:12px}
    .card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow-sm);padding:14px;display:grid;grid-template-columns:64px 1fr 140px 140px;gap:16px;align-items:center;border:1px solid transparent}
    .card:hover{border-color:var(--ring)}
    .avatar{width:64px;height:64px;border-radius:50%;background:#eef2ff;display:grid;place-items:center;font-weight:800;color:var(--blue)}
    .driver{display:flex;flex-direction:column;gap:2px}
    .driver .name{font-weight:800}
    .driver .sub{color:var(--muted);font-size:14px}
    .route{font-weight:700}
    .time{color:var(--muted);font-size:14px}
    .price{font-size:22px;font-weight:900;text-align:right}
    .reserve{display:flex;justify-content:flex-end}
    .empty{padding:30px;text-align:center;color:var(--muted);background:#fff;border-radius:var(--radius);border:1px dashed var(--ring)}
    /* Modale détails */
    .modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.4);display:none;align-items:center;justify-content:center;padding:24px}
    .modal{background:#fff;border-radius:18px;max-width:560px;width:100%;padding:18px;box-shadow:var(--shadow)}
    .modal header{margin:0 0 8px 0}
    .modal footer{display:flex;justify-content:flex-end;gap:10px;margin-top:12px}
    .hidden{display:none}
    /* Desktop first, mais on garde une adaptabilité simple */
    @media (max-width: 980px){
      .search-bar{grid-template-columns:1fr 1fr 1fr 140px}
      .filters{grid-template-columns:1fr 1fr}
      .card{grid-template-columns:56px 1fr 120px 120px}
    }
    @media (max-width: 680px){
      .search-bar{grid-template-columns:1fr; gap:8px; position:static}
      .filters{grid-template-columns:1fr}
      .card{grid-template-columns:1fr; text-align:left}
      .price,.reserve{justify-content:flex-start}
    }
  </style>
</head>
<body>
  <div class="container">
 <header class="head">
            <a href=Page_d_acceuil.php><img class="logo_clair" src ="Image/LOGO.png"/></a>
            <a href=Page_d_acceuil.php><img class="logo_sombre" src ="Image/LOGO_BLANC2.png"/></a>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <ul class = "Bande">
                <li><a href=Page_d_acceuil.php><Button class="Boutton_Acceuil"><?= $text["Bouton_A"] ?? "" ?></Button></a></li>
                <li><a href=Trouver_un_trajet.php><Button class="Boutton_Trouver"><?= $text["Bouton_T"] ?? "" ?></button></a></li>
                <li><a href=Publier_un_trajet.php><Button class = "Boutton_Publier"><?= $text["Bouton_P"] ?? "" ?></Button></a></li>
                <li><a href="Messagerie.php"><button class="Messagerie"><?= $text["Bouton_M"] ?? "" ?></button></a></li>
                <li>
                    <?php if (!isset($_SESSION['user_mail'])): ?>
                        <a href="Se_connecter.php"><button class="Boutton_Se_connecter">Se connecter</button></a>
                    <?php else: ?>
                        <img src="<?= $photoPath ?>" alt="Profil" style="width:50px; height:50px; border-radius:50%;" onclick="menu.hidden ^= 1">
                        <ul id="menu" hidden>
                            <li><a href="Profil.php"><button>Mon compte</button></a></li>
                            <li><a href="Mes_trajets.php"><button>Mes trajets</button></a></li>
                            <li><a href="Se_deconnecter.php"><button>Se déconnecter</button></a></li>
                        </ul>
                    <?php endif; ?>
                </li>
                <li>
                    <button class="Langue" onclick ="menuL.hidden^=1"><?php echo $lang?></button>
                       <ul id="menuL" hidden>
                            <li><a href="?lang=fr"><img src="Image/France.png"/></a></li>
                            <li><a href="?lang=en"><img src ="Image/Angleterre.png"/></a></li>
                        </ul>
                </li>
                <li>
                    <a href="javascript:void(0)" class="Sombre" onclick="darkToggle()">
                        <img src="Image/Sombre.png" class="Sombre1" />
                        <img src="Image/SombreB.png" class="SombreB" />
                    </a>
                </li>

            </ul>
        </header>

    <!-- Barre de recherche -->
    <section class="search-bar" aria-label="Recherche de trajets">
      <label class="field" aria-label="Lieu de départ">
        <span class="icon">📍</span>
        <input id="fromInput" type="text" placeholder="Lieu de départ" autocomplete="off">
      </label>
      <label class="field" aria-label="Destination">
        <span class="icon">🚩</span>
        <input id="toInput" type="text" placeholder="Destination" autocomplete="off">
      </label>
      <label class="field" aria-label="Date de départ">
        <span class="icon">📅</span>
        <input id="dateInput" type="date">
      </label>
      <button id="searchBtn" class="btn btn-primary" aria-label="Rechercher">Rechercher</button>
    </section>

    <!-- Filtres -->
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
          <span id="priceOut" aria-live="polite">50</span>
          <input id="priceRange" type="range" min="5" max="80" value="50" step="1" aria-label="Prix maximum">
        </div>
      </div>
      <div>
        <h4>Places min</h4>
        <div class="range">
          <span id="seatsOut" aria-live="polite">1</span>
          <input id="seatsRange" type="range" min="1" max="4" value="1" step="1" aria-label="Nombre de places minimum">
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

    <!-- Résultats -->
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
      <footer>
        <button class="btn chip" id="shareBtn">Partager</button>
        <button class="btn btn-success" id="bookBtn">Réserver ce trajet</button>
      </footer>
    </div>
  </div>

  <script>
    // --- Données de démonstration (peuvent venir d'une API plus tard)
    const trips = [
      {id:1, driver:"Antoine", rating:4.8, from:"Lyon", to:"Paris", date:"2025-11-12", depart:"08:00", durationMin:330, price:35, seats:2, vehicle:"Peugeot 308", notes:"Petit bagage cabine ok."},
      {id:2, driver:"Claire",  rating:4.7, from:"Lyon", to:"Paris", date:"2025-11-12", depart:"09:30", durationMin:340, price:30, seats:3, vehicle:"Renault Clio", notes:"Pause café en route."},
      {id:3, driver:"Julien",  rating:4.9, from:"Lyon", to:"Paris", date:"2025-11-12", depart:"13:00", durationMin:330, price:28, seats:1, vehicle:"VW Golf", notes:"Pas d’animaux svp."},
      {id:4, driver:"Sarah",   rating:5.0, from:"Lyon", to:"Paris", date:"2025-11-12", depart:"17:00", durationMin:320, price:32, seats:2, vehicle:"Tesla Model 3", notes:"Charge rapide, musique ok."},
      {id:5, driver:"Yassine", rating:4.6, from:"Marseille", to:"Nice", date:"2025-11-12", depart:"07:15", durationMin:130, price:18, seats:3, vehicle:"Dacia Sandero", notes:"Dépose possible à l’aéroport."},
      {id:6, driver:"Inès",    rating:4.5, from:"Lyon", to:"Grenoble", date:"2025-11-12", depart:"18:45", durationMin:90,  price:12, seats:2, vehicle:"Toyota Yaris", notes:"Un sac par personne."},
      {id:7, driver:"Karim",   rating:4.2, from:"Lyon", to:"Paris", date:"2025-11-12", depart:"06:30", durationMin:325, price:26, seats:4, vehicle:"Citroën C4", notes:"RDV métro Part-Dieu."},
    ];

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

    // --- État des filtres
    const state = {
      from: "", to: "", date: "", priceMax: Number(priceRange.value),
      seatsMin: Number(seatsRange.value), timeBand: "all", ratingMin: 0, sort: "relevance"
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
    function renderCard(t){
      const card = document.createElement('article');
      card.className = 'card';
      card.setAttribute('tabindex','0');
      card.innerHTML = `
        <div class="avatar" aria-hidden="true">${t.driver[0]}</div>
        <div>
          <div class="driver">
            <span class="name">${t.driver} <span style="color:#f59e0b">(${t.rating.toFixed(1)} ${starRow(t.rating)})</span></span>
            <div class="route">${t.from} → ${t.to}</div>
            <div class="time">Départ ${t.depart} • ${formatDuration(t.durationMin)} • ${t.seats} place(s) dispo</div>
          </div>
        </div>
        <div class="price">${t.price} €<div class="sub" style="font-size:12px;color:var(--muted)">/passager</div></div>
        <div class="reserve">
          <button class="btn btn-success" aria-label="Réserver">Réserver</button>
        </div>
      `;
      // Ouvrir la modale détails au clic (sur carte ou bouton)
      const open = () => openModal(t);
      card.addEventListener('click', e=>{
        // éviter double ouverture quand on clique le bouton qui est dans la carte
        if(e.target.closest('button')) return;
        open();
      });
      card.querySelector('button').addEventListener('click', e=>{
        e.stopPropagation();
        open();
      });
      return card;
    }

    // Filtrage + tri + rendu
    function apply(){
      const termFrom = state.from.trim().toLowerCase();
      const termTo   = state.to.trim().toLowerCase();

      let list = trips.filter(t=>{
        const okFrom = !termFrom || t.from.toLowerCase().includes(termFrom);
        const okTo   = !termTo   || t.to.toLowerCase().includes(termTo);
        const okDate = !state.date || t.date === state.date;
        const okPrice= t.price <= state.priceMax;
        const okSeat = t.seats >= state.seatsMin;
        const okRate = t.rating >= state.ratingMin;
        const okBand = inBand(t.depart, state.timeBand);
        return okFrom && okTo && okDate && okPrice && okSeat && okRate && okBand;
      });

      switch(state.sort){
        case 'priceAsc':    list.sort((a,b)=>a.price-b.price); break;
        case 'timeAsc':     list.sort((a,b)=>toMinutes(a.depart)-toMinutes(b.depart)); break;
        case 'ratingDesc':  list.sort((a,b)=>b.rating-a.rating); break;
        case 'durationAsc': list.sort((a,b)=>a.durationMin-b.durationMin); break;
        default: // pertinence simple
          list.sort((a,b)=> (b.seats-b.price/100) - (a.seats-a.price/100));
      }

      results.innerHTML = '';
      if(list.length===0){
        emptyState.classList.remove('hidden');
      } else {
        emptyState.classList.add('hidden');
        list.forEach(t=>results.appendChild(renderCard(t)));
      }
    }

    // Modale
    function openModal(t){
      modalTitle.textContent = `${t.from} → ${t.to} — ${t.depart}`;
      modalBody.innerHTML = `
        <p><strong>Conducteur :</strong> ${t.driver} (${t.rating.toFixed(1)} ${starRow(t.rating)})</p>
        <p><strong>Date :</strong> ${t.date} • <strong>Durée :</strong> ${formatDuration(t.durationMin)}</p>
        <p><strong>Véhicule :</strong> ${t.vehicle}</p>
        <p><strong>Places disponibles :</strong> ${t.seats}</p>
        <p style="color:var(--muted)">${t.notes}</p>
      `;
      bookBtn.onclick = ()=> alert(`Réservation simulée pour le trajet #${t.id}`);
      shareBtn.onclick = async ()=>{
        const shareData = {title:`Drive Us`, text:`Trajet ${t.from} → ${t.to} à ${t.depart} – ${t.price}€`, url: location.href};
        if(navigator.share){ try{ await navigator.share(shareData);}catch{} }
        else{ navigator.clipboard.writeText(`${shareData.text} • ${shareData.url}`); alert('Lien copié dans le presse-papier'); }
      };
      modal.style.display='flex';
      modal.setAttribute('aria-hidden','false');
    }
    function closeModalFn(){
      modal.style.display='none';
      modal.setAttribute('aria-hidden','true');
    }
    modal.addEventListener('click', (e)=>{ if(e.target===modal) closeModalFn(); });
    closeModal.addEventListener('click', closeModalFn);
    document.addEventListener('keydown', e=>{ if(e.key==='Escape') closeModalFn(); });

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

    // Recherche
    const doSearch = ()=>{
      state.from = fromInput.value;
      state.to   = toInput.value;
      state.date = dateInput.value;
      apply();
    }
    searchBtn.addEventListener('click', doSearch);
    [fromInput,toInput,dateInput].forEach(el=> el.addEventListener('keydown', e=>{ if(e.key==='Enter') doSearch(); }));

    // Init
    // (pré-remplir la date du jour pour un rendu réaliste si dans l’intervalle des données)
    const today = new Date().toISOString().slice(0,10);
    dateInput.value = "2025-11-12"; // pour matcher les données de démo
    state.date = dateInput.value;
    priceOut.textContent = state.priceMax;
    seatsOut.textContent = state.seatsMin;
    apply();
  </script>
          <script src="JS/Hamburger.js"></script>

</body>
</html>
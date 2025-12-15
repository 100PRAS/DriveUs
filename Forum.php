<?php
// Pour plus tard quand tu feras l'intégration MySQL
// session_start();
// include("config.php");
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Drive Us – Forum</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

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
    body{
      margin:0;
      background:var(--bg);
      color:var(--text);
      font:16px/1.6 system-ui,-apple-system,Segoe UI,Roboto;
    }



    /* SEARCH BAR */
    .search-wrap{
      background:var(--card);
      padding:16px;
      border-radius:var(--radius);
      box-shadow:var(--shadow);
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:16px;
    }
    .search-wrap input{
      flex:1;
      padding:12px 16px;
      border-radius:12px;
      border:1px solid var(--ring);
      font-size:15px;
      outline:none;
    }

    /* NEW TOPIC BUTTON */
    .create-btn{
      padding:12px 22px;
      border-radius:12px;
      background:var(--green);
      color:#fff;
      font-weight:800;
      border:0;
      cursor:pointer;
      transition:0.2s;
    }
    .create-btn:hover{background:var(--green-600);}

    /* TOPIC LIST */
    .topic-list{
      margin-top:20px;
      display:grid;
      gap:12px;
    }
    .topic{
      background:var(--card);
      padding:16px;
      border-radius:var(--radius);
      box-shadow:var(--shadow-sm);
      border:1px solid transparent;
      cursor:pointer;
      transition:0.15s;
    }
    .topic:hover{border-color:var(--ring);}

    .topic-title{font-size:18px;font-weight:800;}
    .topic-info{color:var(--muted);font-size:14px;margin-top:4px;}

    /* MODAL */
    .modal-back{
      position:fixed;inset:0;
      background:rgba(0,0,0,0.35);
      display:none;
      align-items:center;
      justify-content:center;
      padding:20px;
    }
    .modal{
      background:#fff;
      border-radius:18px;
      max-width:600px;width:100%;
      padding:20px;
      box-shadow:var(--shadow);
    }
    .modal textarea{
      width:100%;height:100px;
      border-radius:12px;
      border:1px solid var(--ring);
      padding:12px;
      resize:none;
      margin-top:10px;
      font-size:15px;
    }

    /* DISCUSSION VIEW */
    .view{
      display:none;
      margin-top:20px;
    }
    .post{
      background:#fff;
      padding:20px;
      border-radius:var(--radius);
      box-shadow:var(--shadow-sm);
      margin-bottom:16px;
    }
    .reply-form textarea{
      width:100%;height:80px;
      border-radius:12px;border:1px solid var(--ring);
      padding:12px;margin-top:10px;
      resize:none;
    }
  </style>
          <link rel="stylesheet" href="CSS/Outils/Header.css" />
        <link rel="stylesheet" href="CSS/Outils/Sombre_Header.css" />
        <link rel="stylesheet" href="CSS/Outils/Footer.css" />
        <link rel="icon" type="image/x-icon" href="Image/Icone.ico">
                <link rel="stylesheet" href="CSS/Outils/layout-global.css" />

</head>
<body>

        <?php include 'Outils/views/header.php'; ?>



<div class="container">

  <!-- SEARCH + NEW TOPIC -->
  <div class="search-wrap">
    <input type="text" id="searchTopic" placeholder="🔍 Rechercher un sujet…">
    <button class="create-btn" id="openModal">+ Nouveau sujet</button>
  </div>

  <!-- LISTE DES SUJETS -->
  <div id="topicList" class="topic-list"></div>

  <!-- PAGE DISCUSSION -->
  <div id="view" class="view">
    <button class="btn btn-light" id="backBtn">← Retour</button>

    <div id="discussion"></div>

    <div class="reply-form">
      <h3>Répondre</h3>
      <textarea id="replyContent" placeholder="Écrire une réponse…"></textarea>
      <button class="btn btn-primary" id="sendReply">Envoyer</button>
    </div>
  </div>
</div>

<!-- MODALE CREATION SUJET -->
<div class="modal-back" id="modalNew">
  <div class="modal">
    <h2>Nouveau sujet</h2>
    <input type="text" id="newTitle" placeholder="Titre du sujet" style="width:100%;padding:12px;border:1px solid var(--ring);border-radius:12px;font-size:15px;">
    <textarea id="newContent" placeholder="Contenu du sujet…"></textarea>
    <div style="text-align:right;margin-top:10px;">
      <button class="btn btn-light" id="closeModal">Annuler</button>
      <button class="btn btn-success" id="createTopic">Créer</button>
    </div>
  </div>
</div>

<script>
  // ---------- DONNÉES SIMULÉES ----------
  let topics = [
    {
      id:1,
      title:"Conseils pour un covoiturage sécurisé",
      author:"Alice",
      content:"Salut ! Avez-vous des astuces pour que le covoiturage soit safe ?",
      replies:[
        {author:"Karim", text:"Toujours vérifier les avis avant de réserver."},
        {author:"Sarah", text:"Prévenir quelqu'un de ton trajet, ça rassure !"}
      ]
    },
    {
      id:2,
      title:"Trajets Lyon → Paris : vos expériences",
      author:"Julien",
      content:"Je fais souvent cet aller-retour, comment vous le gérez ?",
      replies:[
        {author:"Antoine", text:"Toujours partir tôt pour éviter les bouchons."}
      ]
    }
  ];

  const topicList = document.getElementById("topicList");
  const view = document.getElementById("view");
  const discussion = document.getElementById("discussion");

  function renderTopics(){
    topicList.innerHTML = "";
    topics.forEach(t=>{
      const el = document.createElement("div");
      el.className="topic";
      el.innerHTML = `
        <div class="topic-title">${t.title}</div>
        <div class="topic-info">Posté par ${t.author} • ${t.replies.length} réponse(s)</div>
      `;
      el.onclick = ()=> openDiscussion(t.id);
      topicList.appendChild(el);
    });
  }
  renderTopics();

  // ---------- DISCUSSION ----------
  function openDiscussion(id){
    const topic = topics.find(t=>t.id===id);
    view.style.display="block";
    topicList.style.display="none";

    let repliesHTML = topic.replies.map(r=>`
      <div class="post">
        <strong>${r.author}</strong><br>
        ${r.text}
      </div>
    `).join("");

    discussion.innerHTML = `
      <div class="post">
        <h2>${topic.title}</h2>
        <p><strong>${topic.author}</strong></p>
        <p>${topic.content}</p>
      </div>
      ${repliesHTML}
    `;

    document.getElementById("sendReply").onclick = ()=>{
      let content = document.getElementById("replyContent").value.trim();
      if(!content) return alert("Réponse vide.");
      topic.replies.push({author:"Utilisateur", text:content});
      document.getElementById("replyContent").value="";
      openDiscussion(id);
    };
  }

  document.getElementById("backBtn").onclick = ()=>{
    view.style.display="none";
    topicList.style.display="grid";
  };

  // ---------- MODALE ----------
  const modal = document.getElementById("modalNew");
  document.getElementById("openModal").onclick = ()=> modal.style.display="flex";
  document.getElementById("closeModal").onclick = ()=> modal.style.display="none";

  document.getElementById("createTopic").onclick = ()=>{
    const title = document.getElementById("newTitle").value.trim();
    const content = document.getElementById("newContent").value.trim();

    if(!title || !content) return alert("Complète le formulaire.");

    topics.push({
      id:Date.now(),
      title,
      author:"Utilisateur",
      content,
      replies:[]
    });

    modal.style.display="none";
    document.getElementById("newTitle").value="";
    document.getElementById("newContent").value="";
    renderTopics();
  };

  // ---------- RECHERCHE ----------
  document.getElementById("searchTopic").oninput = (e)=>{
    const q = e.target.value.toLowerCase();
    topicList.innerHTML="";
    topics
      .filter(t=> t.title.toLowerCase().includes(q))
      .forEach(t=>{
        const el = document.createElement("div");
        el.className="topic";
        el.innerHTML = `
          <div class="topic-title">${t.title}</div>
          <div class="topic-info">Posté par ${t.author} • ${t.replies.length} réponse(s)</div>
        `;
        el.onclick = ()=> openDiscussion(t.id);
        topicList.appendChild(el);
      });
  };
</script>

</body>
</html>
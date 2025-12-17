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
  // ---------- CHARGEMENT DES SUJETS DEPUIS LA BDD ----------
  const topicList = document.getElementById("topicList");
  const view = document.getElementById("view");
  const discussion = document.getElementById("discussion");
  let currentTopicId = null;

  async function loadTopics(search = '') {
    try {
      const url = search 
        ? `Outils/forum/get_topics.php?search=${encodeURIComponent(search)}`
        : 'Outils/forum/get_topics.php';
      
      const response = await fetch(url);
      const topics = await response.json();
      
      topicList.innerHTML = "";
      
      if (topics.length === 0) {
        topicList.innerHTML = '<p style="text-align:center;color:var(--muted);padding:40px;">Aucun sujet trouvé</p>';
        return;
      }

      topics.forEach(t => {
        const el = document.createElement("div");
        el.className = "topic";
        
        const date = new Date(t.created_at);
        const dateStr = date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
        
        el.innerHTML = `
          <div class="topic-title">${escapeHtml(t.title)}</div>
          <div class="topic-info">Posté par ${escapeHtml(t.author_name)} le ${dateStr} • ${t.reply_count} réponse(s)</div>
        `;
        el.onclick = () => openDiscussion(t.id);
        topicList.appendChild(el);
      });
    } catch (error) {
      console.error('Erreur chargement sujets:', error);
      topicList.innerHTML = '<p style="text-align:center;color:red;padding:40px;">Erreur de chargement</p>';
    }
  }

  // Fonction pour échapper le HTML (sécurité)
  function escapeHtml(text) {
    const map = {'&': '&amp;','<': '&lt;','>': '&gt;','"': '&quot;',"'": '&#039;'};
    return text.replace(/[&<>"']/g, m => map[m]);
  }

  // ---------- AFFICHAGE D'UNE DISCUSSION ----------
  async function openDiscussion(id) {
    try {
      currentTopicId = id;
      const response = await fetch(`Outils/forum/get_topic.php?id=${id}`);
      const topic = await response.json();
      
      if (topic.error) {
        alert(topic.error);
        return;
      }

      view.style.display = "block";
      topicList.style.display = "none";

      const topicDate = new Date(topic.created_at);
      const topicDateStr = topicDate.toLocaleDateString('fr-FR', { 
        day: '2-digit', 
        month: 'long', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });

      let repliesHTML = topic.replies.map(r => {
        const replyDate = new Date(r.created_at);
        const replyDateStr = replyDate.toLocaleDateString('fr-FR', { 
          day: '2-digit', 
          month: 'long', 
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
        
        return `
          <div class="post">
            <strong>${escapeHtml(r.author_name)}</strong>
            <span style="color:var(--muted);font-size:13px;margin-left:10px;">${replyDateStr}</span>
            <p style="margin-top:8px;">${escapeHtml(r.content)}</p>
          </div>
        `;
      }).join("");

      discussion.innerHTML = `
        <div class="post" style="background:var(--bg);border:2px solid var(--blue);">
          <h2>${escapeHtml(topic.title)}</h2>
          <p><strong>${escapeHtml(topic.author_name)}</strong> 
             <span style="color:var(--muted);font-size:13px;">• ${topicDateStr}</span>
          </p>
          <p style="margin-top:12px;white-space:pre-wrap;">${escapeHtml(topic.content)}</p>
        </div>
        <h3 style="margin:20px 0 10px;">Réponses (${topic.replies.length})</h3>
        ${repliesHTML}
      `;

      document.getElementById("replyContent").value = "";
    } catch (error) {
      console.error('Erreur chargement discussion:', error);
      alert('Erreur lors du chargement de la discussion');
    }
  }

  // ---------- ENVOYER UNE RÉPONSE ----------
  document.getElementById("sendReply").onclick = async () => {
    const content = document.getElementById("replyContent").value.trim();
    
    if (!content) {
      alert("Veuillez écrire une réponse.");
      return;
    }

    try {
      const response = await fetch('Outils/forum/create_reply.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          topic_id: currentTopicId,
          content: content
        })
      });

      const result = await response.json();

      if (result.error) {
        alert(result.error);
        return;
      }

      // Recharger la discussion
      openDiscussion(currentTopicId);
    } catch (error) {
      console.error('Erreur création réponse:', error);
      alert('Erreur lors de l\'envoi de la réponse');
    }
  };

  // ---------- RETOUR À LA LISTE ----------
  document.getElementById("backBtn").onclick = () => {
    view.style.display = "none";
    topicList.style.display = "grid";
    loadTopics();
  };

  // ---------- MODALE CRÉATION SUJET ----------
  const modal = document.getElementById("modalNew");
  document.getElementById("openModal").onclick = () => modal.style.display = "flex";
  document.getElementById("closeModal").onclick = () => modal.style.display = "none";

  document.getElementById("createTopic").onclick = async () => {
    const title = document.getElementById("newTitle").value.trim();
    const content = document.getElementById("newContent").value.trim();

    if (!title || !content) {
      alert("Veuillez compléter le titre et le contenu.");
      return;
    }

    try {
      const response = await fetch('Outils/forum/create_topic.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ title, content })
      });

      const result = await response.json();

      if (result.error) {
        alert(result.error);
        return;
      }

      modal.style.display = "none";
      document.getElementById("newTitle").value = "";
      document.getElementById("newContent").value = "";
      
      loadTopics();
    } catch (error) {
      console.error('Erreur création sujet:', error);
      alert('Erreur lors de la création du sujet');
    }
  };

  // ---------- RECHERCHE ----------
  let searchTimeout;
  document.getElementById("searchTopic").oninput = (e) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      loadTopics(e.target.value);
    }, 300);
  };

  // Chargement initial
  loadTopics();
</script>

</body>
</html>
<?php

// Système de langue unifié
require_once 'Outils/config/langue.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['UserID'])) {
    // Si pas de session, essayer le cookie
    if (isset($_COOKIE['UserID'])) {
        $_SESSION['UserID'] = $_COOKIE['UserID'];
    } else {
        // Rediriger vers la connexion
        header("Location: Se_connecter.php");
        exit;
    }
}


require_once 'Outils/config/config.php';

$userEmail = null;
try {
  $stmt = $pdo->prepare("SELECT Mail FROM user WHERE UserID = ?");
  $stmt->execute([$_SESSION['UserID']]);
  $row = $stmt->fetch();
  $userEmail = $row['Mail'] ?? null;
} catch (PDOException $e) {
  $userEmail = null;
}
if (!$userEmail) {
  header("Location: Se_connecter.php");
  exit;
}

// Récupérer le prénom et la photo de profil de l'utilisateur
$userPrenom = '';
$userPhoto = "/DriveUs/Image_Profil/default.png";
try {
  $stmt = $pdo->prepare("SELECT Prenom, PhotoProfil FROM user WHERE Mail = ?");
  $stmt->execute([$userEmail]);
  if ($userData = $stmt->fetch()) {
    if (!empty($userData['Prenom'])) {
      $userPrenom = $userData['Prenom'];
    }

    // Resolve user photo across known upload locations
    $photoFile = $userData['PhotoProfil'] ?? '';
    if (!empty($photoFile)) {
      $candidates = [];

      // If the value already looks like a URL or absolute path, allow it
      if (preg_match('~^https?://~i', $photoFile)) {
        $candidates[] = $photoFile;
      } else {
        $relative = '/' . ltrim($photoFile, '/');
        $candidates[] = $relative; // stored with path
        $candidates[] = '/DriveUs/Image_Profil/' . ltrim($photoFile, '/');
        $candidates[] = '/DriveUs/Outils/handlers/Image_Profil/' . ltrim($photoFile, '/');
      }

      foreach ($candidates as $candidate) {
        $absolute = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $candidate;
        if (file_exists($absolute)) {
          $userPhoto = $candidate;
          break;
        }
      }
    }
  }
} catch (PDOException $e) {}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/x-icon" href="Image/Icone.ico">
  <link rel="stylesheet" href="CSS/Outils/layout-global.css" />
    <link rel="stylesheet" href="CSS/Outils/Header.css" />
    <link rel="stylesheet" href="CSS/Sombre/Sombre_Header.css" />
    <link rel="stylesheet" href="CSS/Outils/Footer.css" />


  <link rel="stylesheet" href="CSS/Messagerie1.css" />
  <link rel="stylesheet" href="CSS/Sombre/Sombre_Messagerie.css" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DriveUs - Messagerie</title>
  <script src="JS/Sombre.js"></script>
    <script src="JS/Hamburger.js"></script>

</head>
<body>
  <?php include 'Outils/views/header.php'; ?>

<main>
  <!-- MESSAGERIE -->
  <section class="messagerie">


    <div class="messagerie-container">

      <!-- LEFT PANEL -->
      <div class="left-panel">

        <div class="search">
          <input type="text" id="searchInput" placeholder="Rechercher une conversation...">
        </div>

        <div class="conversations">

          <!-- SEULE conversation conservée comme demandé -->
          <div class="conv" data-contact="Assistant DriveUs (24h/24)">
            <img src="https://cdn-icons-png.flaticon.com/512/4712/4712108.png" alt="Assistant">
            <div class="conv-info">
              <h4>Assistant DriveUs (24h/24)</h4>
              <p style="color:green;">En ligne</p>
            </div>
          </div>

        </div>

        <!-- NOUS CONTACTER -->
        <div class="contact">
          <h3>Nous contacter</h3>

          <input type="text" placeholder="Nom" value="<?= htmlspecialchars($userPrenom) ?>">
          <input type="text" placeholder="Votre message">
          <input type="email" placeholder="Email" value="<?= htmlspecialchars($userEmail) ?>">
          <button>Envoyer</button>

          <div class="contact-actions">
            <span>Ajouter un document</span>
            <button class="attach" id="contactAttachBtn" title="Ajouter un document"></button>
            <input type="file" id="contactFileInput" class="file-input">
          </div>

          <div id="attachedFileName" class="attached-file"></div>
        </div>

      </div>

      <!-- RIGHT CHAT -->
      <div class="chat">

        <div class="chat-header">
          <img src="https://cdn-icons-png.flaticon.com/512/4712/4712108.png" alt="Assistant">
          <div>
            <h4>Assistant DriveUs (24h/24)</h4>
            <p>En ligne</p>
          </div>
        </div>

        <div class="messages">
          <!-- Les messages seront chargés dynamiquement par JavaScript -->
        </div>

        <div class="chat-input">
          <input type="file" id="fileInput" class="file-input" />
          <button id="attachBtn">📎</button>
          <input type="text" placeholder="Écrire un message...">
          <button>➤</button>
        </div>

      </div>

    </div>
  </section>




        <script>

  /* ================================
        VARIABLES GLOBALES
  ================================== */

  const chatHeader = document.querySelector('.chat-header');
  const messagesContainer = document.querySelector('.messages');
  const messageInput = document.querySelector('.chat-input input[type="text"]');
  const sendButton = document.querySelector('.chat-input button:last-child');
  const fileInput = document.getElementById('fileInput');
  const attachBtn = document.getElementById('attachBtn');
  const newMsgBtn = document.getElementById('newMsgBtn');
  const conversationsList = document.querySelector('.conversations');

  // Email de l'utilisateur connecté (passé depuis PHP)
  const currentUser = '<?= $userEmail ?>';
  const currentUserPrenom = '<?= $userPrenom ?>';
  const currentUserPhoto = '<?= $userPhoto ?>';

  // État du chatbot
  let assistantState = {
    role: null,
    awaiting_more: false,
    asking_for_help: true,
    lang: 'fr'
  };

  // Contact actif (email clé pour l'API)
  let activeContactEmail = "Assistant DriveUs (24h/24)";
  let activeContactName = "Assistant DriveUs (24h/24)";
  let activeContactPhoto = "https://cdn-icons-png.flaticon.com/512/4712/4712108.png";

  // Récupérer les paramètres URL pour pré-charger une conversation
  const urlParams = new URLSearchParams(window.location.search);
  const contactParam = urlParams.get('contact');
  const tripParam = urlParams.get('trip');

  /* ================================
        RECHERCHER UNE CONVERSATION
  ================================== */

  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      const query = this.value.toLowerCase().trim();
      const conversations = document.querySelectorAll('.conversations .conv');
      
      conversations.forEach(conv => {
        const name = conv.getAttribute('data-name') || conv.querySelector('h4')?.textContent || '';
        const contact = conv.getAttribute('data-contact') || '';
        
        // Afficher si le texte correspond au nom ou à l'email
        const matches = name.toLowerCase().includes(query) || contact.toLowerCase().includes(query);
        conv.style.display = matches ? 'flex' : 'none';
      });
    });
  }

  /* ================================
        CHANGER DE CONVERSATION
  ================================== */

  if (conversationsList) {
    conversationsList.addEventListener('click', e => {
      const conv = e.target.closest('.conv');
      if (!conv) return;

      const contact = conv.getAttribute('data-contact') || conv.querySelector('h4').textContent;
      const name = conv.getAttribute('data-name') || conv.querySelector('h4').textContent;
      const img = conv.querySelector('img').src;
      const statusFromList = conv.querySelector('.conv-info p')?.textContent || (name === "Assistant DriveUs (24h/24)" ? 'En ligne' : 'Hors ligne');

      activeContactEmail = contact;
      activeContactName = name;
      activeContactPhoto = img;

      // Met à jour l'en-tête du chat
      if (chatHeader) {
        chatHeader.innerHTML = `
          <img src="${img}" alt="${name}">
          <div>
            <h4>${name}</h4>
            <p id="chatStatus">${statusFromList}</p>
          </div>
        `;
      }

      // Affiche les messages
      loadMessages(contact);
    });
  }


  /* ================================
        ENVOYER UN MESSAGE
  ================================== */

  if (sendButton && messageInput) {
    sendButton.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', e => {
      if (e.key === 'Enter') sendMessage();
    });
  }

  async function sendMessage() {
    if (!messageInput) return;
    
    const text = messageInput.value.trim();
    if (text === "") return;

    const receiver = activeContactEmail;
    if (!receiver) {
        alert("Veuillez sélectionner un destinataire !");
        return;
    }

    // ----- Affichage immédiat pour UX fluide -----
    if (messagesContainer) {
      const div = document.createElement('div');
      div.classList.add('bubble', 'right');
      
      const textSpan = document.createElement('span');
      textSpan.textContent = text;
      div.appendChild(textSpan);

      // Heure d'envoi immédiate (client)
      const now = new Date();
      const nowStr = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
      const timeEl = document.createElement('span');
      timeEl.className = 'msg-time';
      timeEl.textContent = ` ${nowStr}`;
      div.appendChild(timeEl);
      
      const img = document.createElement('img');
      img.src = currentUserPhoto;
      img.alt = 'Photo de profil';
      img.style.width = '32px';
      img.style.height = '32px';
      img.style.borderRadius = '50%';
      img.style.marginLeft = '8px';
      div.appendChild(img);
      
      messagesContainer.appendChild(div);
      messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    messageInput.value = "";

    // ----- ASSISTANT: Utiliser le chatbot FAQ -----
    if (receiver === "Assistant DriveUs (24h/24)") {
        try {
            const formData = new FormData();
            formData.append('message', text);
            formData.append('lang', assistantState.lang);
            formData.append('role', assistantState.role || '');
            formData.append('asking_for_help', assistantState.asking_for_help ? '1' : '0');
            if (assistantState.awaiting_more) {
                formData.append('awaiting_more', '1');
            }

            console.log('État avant envoi:', assistantState);

            const response = await fetch("Outils/messaging/chatbot_response.php", {
                method: "POST",
                body: formData
            });
            const result = await response.json();

            console.log('Réponse du serveur:', result);

            if (result.error) {
                console.error("Erreur chatbot:", result.error);
                return;
            }

            // Mettre à jour l'état
            if (result.reset) {
                assistantState.role = null;
                assistantState.awaiting_more = false;
                assistantState.asking_for_help = true;
            }
            if (result.role) {
                assistantState.role = result.role;
                assistantState.awaiting_more = false;
            }
            if (result.asking_for_help !== undefined) {
                assistantState.asking_for_help = result.asking_for_help;
            }
            if (result.awaiting_more !== undefined) {
                assistantState.awaiting_more = result.awaiting_more;
            }

            console.log('État après mise à jour:', assistantState);

            // Afficher la réponse
            if (messagesContainer && result.response) {
                const responseDiv = document.createElement('div');
                responseDiv.classList.add('bubble', 'left');
                responseDiv.textContent = result.response;
                messagesContainer.appendChild(responseDiv);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        } catch (error) {
            console.error("Erreur lors de la communication avec le chatbot:", error);
        }
        return;
    }

    // ----- UTILISATEURS: Envoyer à la BDD -----
    try {
      const response = await fetch("Outils/messaging/send_message.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
              receiver: receiver,
              message: text
          })
      });
      const result = await response.json();
      
      if (!result.success) {
          console.error("Erreur serveur:", result);
          alert(result.message || "Erreur lors de l'envoi du message.");
      }
    } catch (error) {
      console.error("Erreur lors de l'envoi:", error);
    }
  }




  /* ================================
        ENVOYER UN FICHIER
  ================================== */

  if (attachBtn && fileInput) {
    attachBtn.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', e => {
      const file = e.target.files[0];
      if (!file || !messagesContainer) return;

      const fileLink = URL.createObjectURL(file);

      const div = document.createElement('div');
      div.classList.add('bubble', 'right');
      div.innerHTML = `📎 <a href="${fileLink}" download="${file.name}" style="color:white;text-decoration:underline;">${file.name}</a>`;

      messagesContainer.appendChild(div);
      messagesContainer.scrollTop = messagesContainer.scrollHeight;
    });
  }


  /* ================================
        AJOUTER UNE NOUVELLE CONVERSATION
  ================================== */

  if (newMsgBtn && conversationsList) {
    newMsgBtn.addEventListener('click', async () => {
      // Créer un modal pour sélectionner un utilisateur
      const modal = document.createElement('div');
      modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000;
      `;
      
      const modalContent = document.createElement('div');
      modalContent.style.cssText = `
        background: white;
        padding: 20px;
        border-radius: 10px;
        max-width: 500px;
        width: 90%;
        max-height: 70vh;
        overflow-y: auto;
      `;
      
      modalContent.innerHTML = `
        <h3 style="margin-top: 0;">Nouvelle conversation</h3>
        <input type="text" id="userSearch" placeholder="Rechercher un utilisateur..." 
          style="width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px;">
        <div id="usersList" style="max-height: 400px; overflow-y: auto;"></div>
        <button onclick="this.closest('[style*=fixed]').remove()" 
          style="margin-top: 15px; padding: 8px 15px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer;">
          Fermer
        </button>
      `;
      
      modal.appendChild(modalContent);
      document.body.appendChild(modal);
      
      // Charger les utilisateurs
      const loadUsers = async (search = '') => {
        try {
          const response = await fetch(`Outils/messaging/get_users.php?search=${encodeURIComponent(search)}`);
          const users = await response.json();
          
          const usersList = document.getElementById('usersList');
          if (users.length === 0) {
            usersList.innerHTML = '<p style="color: #999; text-align: center;">Aucun utilisateur trouvé</p>';
            return;
          }
          
          usersList.innerHTML = users.map(user => `
            <div class="user-item" data-email="${user.email}" data-name="${user.displayName}" data-photo="${user.photo}" data-online="${user.online ? 1 : 0}" data-last="${user.last_activity ?? ''}"
              style="display: flex; align-items: center; padding: 10px; cursor: pointer; border-radius: 5px; margin-bottom: 5px;">
              <img src="${user.photo}" alt="${user.displayName}" 
                style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 10px;">
              <div>
                <div style="font-weight: 600;">${user.displayName}</div>
                <div style="font-size: 0.85em; color: #666;">${user.email}</div>
              </div>
            </div>
          `).join('');
          
          // Ajouter les événements de clic
          document.querySelectorAll('.user-item').forEach(item => {
            item.addEventListener('mouseover', () => item.style.background = '#f0f0f0');
            item.addEventListener('mouseout', () => item.style.background = 'transparent');
            item.addEventListener('click', () => {
              const email = item.dataset.email;
              const name = item.dataset.name;
              const photo = item.dataset.photo;
              const isOnline = item.dataset.online === '1';
              const last = item.dataset.last || '';
              const statusText = isOnline ? 'Connecté' : (last ? `Dernière connexion: ${new Date(last).toLocaleString('fr-FR', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit' })}` : 'Hors ligne');
              
              // Vérifier si la conversation existe déjà
              const exists = Array.from(conversationsList.querySelectorAll('.conv'))
                .some(conv => conv.getAttribute('data-contact') === email);
              
              if (exists) {
                alert("Cette conversation existe déjà");
                modal.remove();
                return;
              }
              
              // Créer la nouvelle conversation
              const newConv = document.createElement('div');
              newConv.classList.add('conv');
              newConv.setAttribute('data-contact', email);
              newConv.setAttribute('data-name', name);
              newConv.innerHTML = `
                <img src="${photo}" alt="${name}" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                <div class="conv-info">
                  <h4>${name}</h4>
                  <p>${statusText}</p>
                </div>
              `;
              conversationsList.appendChild(newConv);
              
              // Ouvrir la conversation
              if (chatHeader) {
                chatHeader.innerHTML = `
                  <img src="${photo}" alt="${name}">
                  <div>
                    <h4>${name}</h4>
                    <p id="chatStatus">${statusText}</p>
                  </div>
                `;
              }
              activeContactEmail = email;
              activeContactName = name;
              activeContactPhoto = photo;
              loadMessages(email);
              
              modal.remove();
            });
          });
        } catch (error) {
          console.error("Erreur chargement utilisateurs:", error);
        }
      };
      
      // Charger initialement
      loadUsers();
      
      // Recherche en temps réel
      const searchInput = document.getElementById('userSearch');
      searchInput.addEventListener('input', (e) => {
        loadUsers(e.target.value);
      });
      
      return;
      
      // Ancien code (conservé en commentaire)
      const email = prompt("Entrez l'email du destinataire :");
      if (!email || !email.includes('@')) {
        alert("Veuillez entrer un email valide");
        return;
      }

      // Vérifier si la conversation existe déjà
      const exists = Array.from(conversationsList.querySelectorAll('.conv h4'))
        .some(h4 => h4.textContent === email);
      
      if (exists) {
        alert("Cette conversation existe déjà");
        return;
      }

      // Création d'une nouvelle conversation
      const newConv = document.createElement('div');
      newConv.classList.add('conv');
      newConv.innerHTML = `
        <img src="https://randomuser.me/api/portraits/lego/${Math.floor(Math.random()*10)}.jpg" alt="${email}">
        <div class="conv-info"><h4>${email}</h4><p>Nouveau contact</p></div>
      `;

      conversationsList.appendChild(newConv);
      
      console.log("Nouvelle conversation créée:", email);
    });
  }

  /* ================================
        FORMULAIRE "NOUS CONTACTER"
  ================================== */

  const contactFileInput = document.getElementById('contactFileInput');
  const contactAttachBtn = document.getElementById('contactAttachBtn');
  const attachedFileName = document.getElementById('attachedFileName');
  const contactForm = document.querySelector('.contact');
  const contactSendBtn = contactForm?.querySelector('button');

  if (contactAttachBtn && contactFileInput) {
    contactAttachBtn.addEventListener('click', () => contactFileInput.click());

    contactFileInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (!file) {
        if (attachedFileName) attachedFileName.innerHTML = '';
        return;
      }

      const fileLink = URL.createObjectURL(file);

      if (attachedFileName) {
        attachedFileName.innerHTML = `
          📎 <a href="${fileLink}" download="${file.name}" 
          style="color:#1e73d9;text-decoration:underline;">
            ${file.name}
          </a>`;
      }
    });
  }

  // Envoi du formulaire de contact
  if (contactSendBtn) {
    contactSendBtn.addEventListener('click', () => {
      const nameInput = contactForm.querySelector('input[placeholder="Nom"]');
      const messageInput = contactForm.querySelector('input[placeholder="Votre message"]');
      const emailInput = contactForm.querySelector('input[type="email"]');

      const name = nameInput?.value.trim() || '';
      const message = messageInput?.value.trim() || '';
      const email = emailInput?.value.trim() || '';

      if (!name || !message) {
        alert("Veuillez remplir le nom et le message.");
        return;
      }

      // Simulation d'envoi (vous pouvez créer un fichier PHP dédié)
      alert(`Message envoyé avec succès !\n\nNom: ${name}\nEmail: ${email}\nMessage: ${message}`);
      
      // Réinitialiser le formulaire
      if (nameInput) nameInput.value = '';
      if (messageInput) messageInput.value = '';
      if (emailInput) emailInput.value = '';
      if (attachedFileName) attachedFileName.innerHTML = '';
    });
  }

/* =================================================
   CHARGEMENT DES CONVERSATIONS DEPUIS LA BDD
=================================================== */

async function loadConversations() {
    try {
        const response = await fetch("Outils/messaging/get_conversation.php");
        const contacts = await response.json();
        
        if (!Array.isArray(contacts) || contacts.length === 0) {
            console.log("Aucune conversation trouvée");
            return; // Garde l'assistant par défaut
        }

        contacts.forEach(contact => {
            const email = contact.email || contact;
            const name = contact.name || contact;
            const photo = contact.photo || "/DriveUs/Image_Profil/default.png";
          const statusText = contact.online ? 'Connecté' : (contact.last_activity ? `Dernière connexion: ${new Date(contact.last_activity).toLocaleString('fr-FR', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit' })}` : 'Hors ligne');
            
            // Ignorer si c'est l'utilisateur lui-même
            if (email === currentUser) {
                return;
            }
            
            // Vérifier si le contact existe déjà dans la liste
            const exists = Array.from(conversationsList.querySelectorAll('.conv'))
                .some(conv => conv.getAttribute('data-contact') === email);
            
            if (!exists) {
                const newConv = document.createElement('div');
                newConv.classList.add('conv');
                newConv.setAttribute('data-contact', email);
                newConv.setAttribute('data-name', name);
                newConv.innerHTML = `
                  <img src="${photo}" alt="${name}" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                  <div class="conv-info">
                    <h4>${name}</h4>
                    <p>${statusText}</p>
                  </div>
                `;
                
                // Insérer après l'Assistant
                const assistantConv = conversationsList.querySelector('[data-contact="Assistant DriveUs (24h/24)"]');
                if (assistantConv && assistantConv.nextSibling) {
                    conversationsList.insertBefore(newConv, assistantConv.nextSibling);
                } else {
                    conversationsList.appendChild(newConv);
                }
                
                console.log(`✅ Conversation ajoutée: ${name}`);
            } else {
                // Mettre à jour le statut visuel si la conversation existe déjà
                const convEl = Array.from(conversationsList.querySelectorAll('.conv'))
                  .find(conv => conv.getAttribute('data-contact') === email);
                const p = convEl?.querySelector('.conv-info p');
                if (p) p.textContent = statusText;
            }
        });
    } catch (error) {
        console.error("❌ Erreur chargement conversations:", error);
    }
}

// Charger automatiquement la conversation du conducteur si elle est en paramètre URL
async function loadDriverContact() {
    if (!contactParam) return;
    
    // Ajouter le conducteur aux conversations s'il n'existe pas
    try {
        // Essayer de récupérer prénom/photo via l'API des conversations
        let displayName = contactParam;
        let displayPhoto = "/DriveUs/Image_Profil/default.png";
        try {
          const resp = await fetch("Outils/messaging/get_conversation.php");
          const contacts = await resp.json();
          const match = Array.isArray(contacts) ? contacts.find(c => c.email === contactParam) : null;
          if (match) {
            displayName = match.name || displayName;
            displayPhoto = match.photo || displayPhoto;
          }
        } catch (e) {
          console.warn("Impossible de charger les infos contact", e);
        }

        const existing = Array.from(conversationsList.querySelectorAll('h4'))
          .find(h4 => h4.textContent === displayName);

        if (!existing) {
          const newConv = document.createElement('div');
          newConv.classList.add('conv');
          newConv.setAttribute('data-contact', contactParam);
          newConv.setAttribute('data-name', displayName);
          newConv.innerHTML = `
            <img src="${displayPhoto}" alt="${displayName}">
            <div class="conv-info">
              <h4>${displayName}</h4>
              <p>Cliquez pour voir les messages</p>
            </div>
          `;
          conversationsList.appendChild(newConv);
        }

        // Charger les messages
        if (chatHeader) {
          chatHeader.innerHTML = `
            <img src="${displayPhoto}" alt="${displayName}">
            <div>
              <h4>${displayName}</h4>
              <p>En ligne</p>
            </div>
          `;
        }

        activeContactEmail = contactParam;
        activeContactName = displayName;
        activeContactPhoto = displayPhoto;

        await loadMessages(contactParam);

        // Si c'est un trajet qui est passé en paramètre, ajouter un message initial suggéré
        if (tripParam && messagesContainer) {
            const welcomeMsg = document.createElement('div');
            welcomeMsg.classList.add('system-message');
            welcomeMsg.style.cssText = 'color: var(--muted); font-size: 0.9em; margin: 1rem; text-align: center;';
            welcomeMsg.textContent = `Trajet: ${tripParam}`;
            messagesContainer.appendChild(welcomeMsg);
        }
    } catch (error) {
        console.error("Erreur chargement conducteur:", error);
    }
}

/* =================================================
   CHARGEMENT DES MESSAGES D'UNE CONVERSATION
=================================================== */

async function loadMessages(contact) {
    if (contact === "Assistant DriveUs (24h/24)") {
        // Réinitialiser l'état de l'assistant
        assistantState.role = null;
        assistantState.awaiting_more = false;
        assistantState.asking_for_help = true;
        
        // Charger le message d'accueil depuis le chatbot
        if (messagesContainer) {
            messagesContainer.innerHTML = '<div class="bubble left">Chargement...</div>';
            
            try {
                const formData = new FormData();
                formData.append('message', '/reset');
                formData.append('lang', assistantState.lang);
                
                const response = await fetch("Outils/messaging/chatbot_response.php", {
                    method: "POST",
                    body: formData
                });
                const result = await response.json();
                
                if (result.response) {
                    messagesContainer.innerHTML = `<div class="bubble left">${result.response}</div>`;
                }
            } catch (error) {
                console.error("Erreur chargement assistant:", error);
                messagesContainer.innerHTML = '<div class="bubble left">Bonjour 👋, comment puis-je vous aider ?</div>';
            }
        }
        return;
    }

    try {
        // Utiliser l'email du contact (data-contact) pour charger les messages
        const response = await fetch(`Outils/messaging/get_message.php?contact=${encodeURIComponent(contact)}`);
        const messages = await response.json();
        
        if (!messagesContainer) return;

        const currentUserEmail = "<?= htmlspecialchars($userEmail, ENT_QUOTES) ?>";
        messagesContainer.innerHTML = "";

        if (!Array.isArray(messages) || messages.length === 0) {
            messagesContainer.innerHTML = `<div class="bubble left">Aucun message pour l'instant.</div>`;
            return;
        }

        messages.forEach(msg => {
            const div = document.createElement('div');
            const isFromMe = msg.sender === currentUserEmail;
            div.classList.add('bubble', isFromMe ? 'right' : 'left');
            if (msg.id) {
              div.dataset.messageId = msg.id;
            }
            
            const textSpan = document.createElement('span');
            textSpan.textContent = msg.message;
            div.appendChild(textSpan);

          // Ajout de l'heure d'envoi
          if (msg.created_at) {
            const date = new Date(msg.created_at);
            const timeStr = date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            const timeEl = document.createElement('span');
            timeEl.className = 'msg-time';
            timeEl.textContent = ` ${timeStr}`;
            div.appendChild(timeEl);
          }
            
            if (isFromMe) {
                // Bouton de suppression pour mes propres messages
                if (msg.id) {
                  const deleteBtn = document.createElement('button');
                  deleteBtn.textContent = '✕';
                  deleteBtn.title = 'Supprimer ce message';
                  deleteBtn.style.cssText = 'margin-right:8px;border:none;background:transparent;color:#999;font-size:0.9em;cursor:pointer;';
                  deleteBtn.addEventListener('click', async (event) => {
                    event.stopPropagation();
                    if (!confirm('Supprimer ce message ?')) return;
                    await deleteMessage(msg.id, div);
                  });
                  div.appendChild(deleteBtn);
                }

                const img = document.createElement('img');
                img.src = currentUserPhoto;
                img.alt = 'Photo de profil';
                img.style.width = '32px';
                img.style.height = '32px';
                img.style.borderRadius = '50%';
                img.style.marginLeft = '8px';
                div.appendChild(img);
            }
            
            messagesContainer.appendChild(div);
        });

        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    } catch (error) {
        console.error("Erreur chargement messages:", error);
    }
}

  // Supprime un message de l'utilisateur connecté
  async function deleteMessage(messageId, bubbleEl) {
    if (!messageId) return;
    try {
      const formData = new FormData();
      formData.append('message_id', messageId);

      const response = await fetch('Outils/messaging/delete_message.php', {
        method: 'POST',
        body: formData
      });
      const result = await response.json();

      if (result.status === 'success') {
        if (bubbleEl) {
          bubbleEl.remove();
        }
      } else {
        alert(result.message || 'Suppression impossible');
      }
    } catch (error) {
      console.error('Erreur suppression message:', error);
      alert('Erreur lors de la suppression');
    }
  }

/* =================================================
   RAFRAÎCHISSEMENT AUTO TOUTES LES 3 SECONDES
=================================================== */

// Chargement initial
(async () => {
    console.log('🔄 Chargement initial...');
    
    // Initialiser avec l'assistant comme contact actif par défaut
    activeContactEmail = "Assistant DriveUs (24h/24)";
    activeContactName = "Assistant DriveUs (24h/24)";
    activeContactPhoto = "https://cdn-icons-png.flaticon.com/512/4712/4712108.png";
    
    await loadConversations();
    
    // Charger automatiquement l'assistant au démarrage
    await loadMessages("Assistant DriveUs (24h/24)");
    
    // Si un conducteur est passé en paramètre, le charger automatiquement
    if (contactParam) {
        await loadDriverContact();
    }
    
    console.log('✅ Chargement initial terminé');
})();

// Rafraîchissement auto toutes les 3 secondes
setInterval(() => {
    loadConversations();

  const activeConv = activeContactEmail;
  
  if (activeConv && activeConv !== "Assistant DriveUs (24h/24)") {
    // Sauvegarder la position de scroll avant le rechargement
    const scrollPos = messagesContainer.scrollTop;
    const isAtBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop === messagesContainer.clientHeight;
    
    loadMessages(activeConv).then(() => {
      // Restaurer la position si l'utilisateur n'était pas en bas
      if (!isAtBottom) {
        messagesContainer.scrollTop = scrollPos;
      }
    });
    
    // Mettre à jour le statut du header via données conversations
    fetch('Outils/messaging/get_conversation.php').then(r=>r.json()).then(list=>{
      const match = Array.isArray(list) ? list.find(c=>c.email===activeConv) : null;
      const statusEl = document.getElementById('chatStatus');
      if (match && statusEl) {
        const statusText = match.online ? 'Connecté' : (match.last_activity ? `Dernière connexion: ${new Date(match.last_activity).toLocaleString('fr-FR', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit' })}` : 'Hors ligne');
        statusEl.textContent = statusText;
      }
    }).catch(()=>{});
  }
}, 3000);

// Ping présence toutes les 30s
setInterval(()=>{
  fetch('Outils/messaging/presence_update.php').catch(()=>{});
}, 30000);

</script>
</main>
<?php include 'Outils/views/footer.php'; ?>
</body>
</html>
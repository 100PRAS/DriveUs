<?php
session_start();

// Système de langue unifié
require_once 'Outils/langue.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_mail'])) {
    // Si pas de session, essayer le cookie
    if (isset($_COOKIE['user_mail'])) {
        $_SESSION['user_mail'] = $_COOKIE['user_mail'];
    } else {
        // Rediriger vers la connexion
        header("Location: Se_connecter.php");
        exit;
    }
}

$userEmail = $_SESSION['user_mail'];

// Récupérer le prénom et la photo de profil de l'utilisateur
require_once 'Outils/config.php';
$userPrenom = $userEmail; // Valeur par défaut
$userPhoto = "Image/default.png"; // Valeur par défaut

$stmt = $conn->prepare("SELECT Prenom, PhotoProfil FROM user WHERE Mail = ?");
$stmt->bind_param("s", $userEmail);
$stmt->execute();
$result = $stmt->get_result();
if ($userData = $result->fetch_assoc()) {
    if (!empty($userData['Prenom'])) {
        $userPrenom = $userData['Prenom'];
    }
    if (!empty($userData['PhotoProfil'])) {
        $userPhoto = "Image_Profil/" . $userData['PhotoProfil'];
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/x-icon" href="Image/Icone.ico">
  <link rel="stylesheet" href="CSS/Outils/layout-global.css" />
  <link rel="stylesheet" href="CSS/Messagerie1.css" />
  <link rel="stylesheet" href="CSS/Sombre/Sombre_Messagerie.css" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DriveUs - Messagerie</title>
  <script src="JS/Sombre.js"></script>
</head>
<body>
  <?php include 'Outils/header.php'; ?>

<main>
  <!-- MESSAGERIE -->
  <section class="messagerie">


    <div class="messagerie-container">

      <!-- LEFT PANEL -->
      <div class="left-panel">

        <button id="newMsgBtn">Nouveau message</button>

        <div class="search">
          <input type="text" placeholder="Rechercher une conversation...">
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
            <button class="attach" id="contactAttachBtn" title="Ajouter un document">📎</button>
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

  // Récupérer les paramètres URL pour pré-charger une conversation
  const urlParams = new URLSearchParams(window.location.search);
  const contactParam = urlParams.get('contact');
  const tripParam = urlParams.get('trip');

  /* ================================
        CHANGER DE CONVERSATION
  ================================== */

  if (conversationsList) {
    conversationsList.addEventListener('click', e => {
      const conv = e.target.closest('.conv');
      if (!conv) return;

      const contact = conv.getAttribute('data-contact') || conv.querySelector('h4').textContent;
      const name = conv.querySelector('h4').textContent;
      const img = conv.querySelector('img').src;

      // Met à jour l'en-tête du chat
      if (chatHeader) {
        chatHeader.innerHTML = `
          <img src="${img}" alt="${name}">
          <div>
            <h4>${name}</h4>
            <p>${name === "Assistant DriveUs (24h/24)" ? 'En ligne' : 'Cliquez pour envoyer un message'}</p>
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

    const receiver = document.querySelector(".chat-header h4")?.textContent?.trim();
    if (!receiver) {
        alert("Veuillez sélectionner un destinataire !");
        return;
    }

    // ----- Affichage immédiat pour UX fluide -----
    if (messagesContainer) {
      const div = document.createElement('div');
      div.classList.add('bubble', 'right');
      div.textContent = text;
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
            formData.append('role', assistantState.role);
            formData.append('asking_for_help', assistantState.asking_for_help ? '1' : '0');
            if (assistantState.awaiting_more) {
                formData.append('awaiting_more', '1');
            }

            const response = await fetch("Outils/chatbot_response.php", {
                method: "POST",
                body: formData
            });
            const result = await response.json();

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
            if (result.awaiting_more) {
                assistantState.awaiting_more = true;
            } else if (result.awaiting_more === false) {
                assistantState.awaiting_more = false;
            }

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
      const response = await fetch("Outils/send_message.php", {
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
          const response = await fetch(`Outils/get_users.php?search=${encodeURIComponent(search)}`);
          const users = await response.json();
          
          const usersList = document.getElementById('usersList');
          if (users.length === 0) {
            usersList.innerHTML = '<p style="color: #999; text-align: center;">Aucun utilisateur trouvé</p>';
            return;
          }
          
          usersList.innerHTML = users.map(user => `
            <div class="user-item" data-email="${user.email}" data-name="${user.displayName}" data-photo="${user.photo}"
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
              newConv.innerHTML = `
                <img src="${photo}" alt="${name}" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                <div class="conv-info">
                  <h4>${name}</h4>
                  <p>Nouveau contact</p>
                </div>
              `;
              conversationsList.appendChild(newConv);
              
              // Ouvrir la conversation
              if (chatHeader) {
                chatHeader.innerHTML = `
                  <img src="${photo}" alt="${name}">
                  <div>
                    <h4>${email}</h4>
                    <p>En ligne</p>
                  </div>
                `;
              }
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
        const response = await fetch("Outils/get_conversation.php");
        const contacts = await response.json();
        
        if (!Array.isArray(contacts) || contacts.length === 0) {
            return; // Garde l'assistant par défaut
        }

        // Garde l'assistant et ajoute les vraies conversations
        const assistantDiv = conversationsList.querySelector('[data-contact="Assistant DriveUs (24h/24)"]');
        
        contacts.forEach(contact => {
            const email = contact.email || contact; // Compatibilité ancien format
            const name = contact.name || contact;
            const photo = contact.photo || "https://randomuser.me/api/portraits/lego/" + Math.floor(Math.random()*10) + ".jpg";
            
            const exists = Array.from(conversationsList.querySelectorAll('.conv h4'))
                .some(h4 => h4.textContent === name || h4.textContent === email);
            
            if (!exists) {
                const newConv = document.createElement('div');
                newConv.classList.add('conv');
                newConv.setAttribute('data-contact', email);
                newConv.innerHTML = `
                    <img src="${photo}" alt="${name}" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                    <div class="conv-info">
                        <h4>${name}</h4>
                        <p>Cliquez pour voir les messages</p>
                    </div>
                `;
                conversationsList.appendChild(newConv);
            }
        });
    } catch (error) {
        console.error("Erreur chargement conversations:", error);
    }
}

// Charger automatiquement la conversation du conducteur si elle est en paramètre URL
async function loadDriverContact() {
    if (!contactParam) return;
    
    // Ajouter le conducteur aux conversations s'il n'existe pas
    try {
        const existing = Array.from(conversationsList.querySelectorAll('h4'))
            .find(h4 => h4.textContent === contactParam);

        if (!existing) {
            const newConv = document.createElement('div');
            newConv.classList.add('conv');
            newConv.setAttribute('data-contact', contactParam);
            newConv.innerHTML = `
                <img src="https://randomuser.me/api/portraits/lego/${Math.floor(Math.random()*10)}.jpg" alt="${contactParam}">
                <div class="conv-info">
                    <h4>${contactParam}</h4>
                    <p>Cliquez pour voir les messages</p>
                </div>
            `;
            conversationsList.appendChild(newConv);
        }

        // Charger les messages
        if (chatHeader) {
            chatHeader.innerHTML = `
                <img src="https://randomuser.me/api/portraits/lego/${Math.floor(Math.random()*10)}.jpg" alt="${contactParam}">
                <div>
                    <h4>${contactParam}</h4>
                    <p>En ligne</p>
                </div>
            `;
        }

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
        
        // Afficher le message d'accueil initial
        if (messagesContainer) {
            messagesContainer.innerHTML = `<div class="bubble left">Bonjour 👋, comment puis-je vous aider ?</div>`;
        }
        return;
    }

    try {
        const response = await fetch(`Outils/get_message.php?contact=${encodeURIComponent(contact)}`);
        const messages = await response.json();
        
        if (!messagesContainer) return;

        const currentUser = "<?php echo $_SESSION['user_mail'] ?? ''; ?>";
        messagesContainer.innerHTML = "";

        if (messages.length === 0) {
            messagesContainer.innerHTML = `<div class="bubble left">Aucun message pour l'instant.</div>`;
            return;
        }

        messages.forEach(msg => {
            const div = document.createElement('div');
            const isFromMe = msg.sender === currentUser;
            div.classList.add('bubble', isFromMe ? 'right' : 'left');
            div.textContent = msg.message;
            messagesContainer.appendChild(div);
        });

        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    } catch (error) {
        console.error("Erreur chargement messages:", error);
    }
}

/* =================================================
   RAFRAÎCHISSEMENT AUTO TOUTES LES 3 SECONDES
=================================================== */

// Chargement initial
loadConversations();

// Si un conducteur est passé en paramètre, le charger automatiquement
if (contactParam) {
    // Attendre que loadConversations soit terminée
    setTimeout(() => {
        loadDriverContact();
    }, 500);
}

// Afficher le message de bienvenue de l'assistant au démarrage
setTimeout(() => {
    if (messagesContainer && messagesContainer.children.length === 0) {
        const welcomeDiv = document.createElement('div');
        welcomeDiv.classList.add('bubble', 'left');
        welcomeDiv.textContent = 'Bonjour 👋, comment puis-je vous aider ?';
        messagesContainer.appendChild(welcomeDiv);
    }
}, 100);

setInterval(() => {
    loadConversations();

    const activeConv = document.querySelector(".chat-header h4")?.textContent;
    if (activeConv && activeConv !== "Assistant DriveUs (24h/24)") {
        loadMessages(activeConv);
    }
}, 3000);

</script>
</main>
<?php include 'Outils/footer.php'; ?>
</body>
</html>
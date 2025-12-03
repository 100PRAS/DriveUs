<?php
session_start();


    // Cookie
    if (!isset($_SESSION['user_mail']) && isset($_COOKIE['user_mail'])) {
        $_SESSION['user_mail'] = $_COOKIE['user_mail'];
    }

    // Langue
    if(isset($_GET["lang"])) {
        $_SESSION["lang"] = $_GET["lang"];
    }
    $lang = $_SESSION["lang"] ?? "fr";
    $text = require "Outils/lang_$lang.php";

    // Photo
    include("Outils/config.php");

    $photo = null; // Valeur par défaut

    if (isset($_SESSION['user_mail'])) {
        $mail = $_SESSION['user_mail'];
        $stmt = $conn->prepare("SELECT PhotoProfil FROM user WHERE Mail = ?");
        $stmt->bind_param("s", $mail);
        $stmt->execute();
        $stmt->bind_result($photo);
        $stmt->fetch();
        $stmt->close();
    }

    $photoPath = $photo ? "Image_Profil/" . htmlspecialchars($photo) : "Image/default.png";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
          <link rel="stylesheet" href="CSS/Messagerie1.css" />
          <link rel="stylesheet" href="CSS/Sombre_Messagerie.css" />

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DriveUs - Messagerie</title>
  <style>

  </style>
</head>
<body>

 <!--Bande d'ariane---------------------------------------------------------------------------------------------------------------------------->
   
       <!-- <header class="head">
            <a href=Page_d_acceuil.php><img class="logo_clair" src ="Image/LOGO.png"/></a>
            <a href=Page_d_acceuil.php><img class="logo_sombre" src ="Image/LOGO_BLANC.png"/></a>
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

          <!-- ⭐ SEULE conversation conservée comme demandé -->
          <div class="conv">
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

          <input type="text" placeholder="Nom">
          <input type="text" placeholder="Votre message">
          <input type="email" placeholder="codeandcofee94@gmail.com">
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
          <div class="bubble left">Bonjour 👋, comment puis-je vous aider ?</div>
          <div class="bubble right">Bonjour, j’ai une question concernant mon trajet</div>
          <div class="bubble left">Très bien, je vous écoute.</div>
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

  <!-- FOOTER -->
<footer class = "Pied">
            <p class="pC">Contact : Drive.us@gmail.com</p>
            <p class="CGU"><a href=CGU.php><?= $text["CGU"] ?? "" ?></a></p> 
        </footer>


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

  /* ================================
        DONNÉES DES MESSAGES 
  ================================== */

  const messagesData = {
    "Assistant DriveUs (24h/24)": [
      { from: "left", text: "Bonjour 👋, comment puis-je vous aider ?" }
    ]
  };

  /* ================================
        CHANGER DE CONVERSATION
  ================================== */

  if (conversationsList) {
    conversationsList.addEventListener('click', e => {
      const conv = e.target.closest('.conv');
      if (!conv) return;

      const name = conv.querySelector('h4').textContent;
      const img = conv.querySelector('img').src;

      // Met à jour l'en-tête du chat
      if (chatHeader) {
        chatHeader.innerHTML = `
          <img src="${img}" alt="${name}">
          <div>
            <h4>${name}</h4>
            <p>En ligne</p>
          </div>
        `;
      }

      // Affiche les messages
      if (messagesContainer) {
        messagesContainer.innerHTML = "";
        if (messagesData[name]) {
          messagesData[name].forEach(msg => {
            const div = document.createElement('div');
            div.classList.add('bubble', msg.from);
            div.textContent = msg.text;
            messagesContainer.appendChild(div);
          });
        } else {
          messagesContainer.innerHTML = `<div class="bubble left">Aucun message pour l'instant.</div>`;
        }
      }
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

    // ----- Envoi BDD -----
    console.log("Receiver:", receiver);
    console.log("Message:", text);

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
      console.log("Réponse serveur:", result);
    } catch (error) {
      console.error("Erreur lors de l'envoi:", error);
      alert("Erreur lors de l'envoi du message.");
      return;
    }

    // ----- Affichage immédiat -----
    if (messagesContainer) {
      const div = document.createElement('div');
      div.classList.add('bubble', 'right');
      div.textContent = text;
      messagesContainer.appendChild(div);
      messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    messageInput.value = "";
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
    newMsgBtn.addEventListener('click', () => {
      const userName = prompt("Entrez le nom du destinataire :");
      if (!userName) return;

      // Création d'une nouvelle conversation
      const newConv = document.createElement('div');
      newConv.classList.add('conv');
      newConv.innerHTML = `
        <img src="https://randomuser.me/api/portraits/lego/${Math.floor(Math.random()*10)}.jpg" alt="${userName}">
        <div class="conv-info"><h4>${userName}</h4></div>
        <div class="conv-time">Nouveau</div>
      `;

      conversationsList.appendChild(newConv);

      // Initialise la nouvelle conversation dans la base
      messagesData[userName] = [];
      
      console.log("Nouvelle conversation créée:", userName);
    });
  }

  const contactFileInput = document.getElementById('contactFileInput');
  const contactAttachBtn = document.getElementById('contactAttachBtn');
  const attachedFileName = document.getElementById('attachedFileName');

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

</script>
</body>
</html>
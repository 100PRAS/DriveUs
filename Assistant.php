<!DOCTYPE html>
<html lang="fr">
<head>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <meta charset="UTF-8">
  <title>Drive Us</title>
  <style>
    body { font-family: 'Poppins', sans-serif; margin: 30px; background-color: #f8f9fa; }
    h1 { color: #2c3e50; text-align: center; }
    #chatbox { border: 1px solid #ccc; border-radius: 10px; padding: 15px; width: 420px; height: 300px; margin: 20px auto; background-color: #fff; overflow-y: auto; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .bot, .user { margin: 5px 0; padding: 8px 10px; border-radius: 10px; max-width: 80%; word-wrap: break-word; }
    .bot { background-color: #e3f2fd; color: #1565c0; text-align: left; }
    .user { background-color: #c8e6c9; color: #2e7d32; text-align: right; margin-left: auto; }
    input { width: 310px; padding: 8px; border-radius: 5px; border: 1px solid #ccc;  margin-left: 8%; }
    button { padding: 8px 12px; border: none; background-color: #1565c0; color: white; border-radius: 5px; cursor: pointer; }
    button:hover { background-color: #0d47a1; }
    #languageSelect { display: block; margin: 0 auto 15px auto; padding: 8px; border-radius: 5px; border: 1px solid #ccc; }
  </style>
</head>
<body>

<h1>Drive Us - FAQ Interactive 🚗</h1>

<select id="languageSelect">
  <option value="fr">Français</option>
  <option value="en">English</option>
</select>

<div id="chatbox"></div>

<input type="text" id="userInput" placeholder="Écrivez votre question ici... / Type your question here...">
<button onclick="sendQuestion()">Envoyer / Send</button>

<script>
const faq = {
  fr: {
    welcome: "Bonjour 👋 ! Je suis l’assistant Drive Us. Si vous avez besoin d’aide pour utiliser le site ou pour vos trajets, je suis là pour vous guider. Avez‑vous besoin d’aide ?",
    notFound: "Désolé, je n’ai pas encore la réponse à cette question. 😅",
    askRole: "Parfait ! 😊 Êtes-vous conducteur ou passager ?",
    askMore: "Avez-vous d'autres questions ? (oui/non)",
    contact: "Si vous ne trouvez pas la réponse à votre question, contactez-nous à 👉 codeandcofee94@gmail.com 📩",
    noResponse: "D’accord 👍. Je vous souhaite une excellente journée et une bonne route ! 🚗💨",
    roleQuestions: {
      conducteur: {
        text: "Voici des questions utiles pour les conducteurs :\n1️⃣ Comment publier un trajet ?\n2️⃣ Comment fixer le prix ?\n3️⃣ Comment recevoir le paiement ?\n4️⃣ Puis-je refuser un passager ?\n5️⃣ Que faire si un passager ne se présente pas ?\n6️⃣ Comment signaler un problème ?\n7️⃣ Que faire en cas d’accident ?\n8️⃣ Comment gérer un retard du passager ?",
        answers: [
          "Pour publier un trajet, allez dans votre tableau de bord et cliquez sur 'Nouveau trajet'.",
          "Le prix est calculé automatiquement selon la distance, mais vous pouvez l’ajuster légèrement.",
          "Les paiements sont transférés sur votre compte après le trajet.",
          "Oui, vous pouvez refuser une demande avant de la confirmer.",
          "Signalez-le via l’application pour obtenir une compensation.",
          "Utilisez la section 'Aide' pour signaler un comportement inapproprié.",
          "Assurez-vous que tout le monde va bien, puis contactez le support Drive Us immédiatement.",
          "Si un passager est en retard, contactez-le via l’application et ajustez le départ si possible."
        ]
      },
      passager: {
        text: "Voici des questions utiles pour les passagers :\n1️⃣ Comment réserver un trajet ?\n2️⃣ Comment payer un trajet ?\n3️⃣ Puis-je annuler une réservation ?\n4️⃣ Comment contacter le conducteur ?\n5️⃣ Puis-je emmener un animal ?\n6️⃣ Puis-je voyager avec un ami ?\n7️⃣ Est-ce sécurisé ?\n8️⃣ Que faire en cas de retard du conducteur ?",
        answers: [
          "Pour réserver un trajet, connectez-vous, recherchez un itinéraire et cliquez sur 'Réserver'.",
          "Le paiement se fait en ligne avant le départ via une plateforme sécurisée.",
          "Oui, vous pouvez annuler depuis votre profil avant le départ du trajet.",
          "Vous pouvez contacter le conducteur grâce à la messagerie intégrée après avoir réservé.",
          "Cela dépend du conducteur. Vérifiez la description du trajet avant de réserver.",
          "Oui, vous pouvez réserver plusieurs places si elles sont disponibles.",
          "Oui, le service Drive Us est sécurisé et les conducteurs sont vérifiés.",
          "Si le conducteur a du retard, contactez-le via l’application ou consultez les notifications de suivi."
        ]
      }
    }
  },
  en: {
    welcome: "Hello 👋! I am the Drive Us assistant. If you need help using the site or with your rides, I am here to guide you. Do you need help?",
    notFound: "Sorry, I don't have an answer for that yet. 😅",
    askRole: "Great! 😊 Are you a driver or a passenger?",
    askMore: "Do you have any other questions? (yes/no)",
    contact: "If you can't find the answer, contact us at 👉 codeandcofee94@gmail.com 📩",
    noResponse: "Alright 👍. Have a great day and safe travels! 🚗💨",
    roleQuestions: {
      conducteur: {
        text: "Here are some useful questions for drivers:\n1️⃣ How to publish a ride?\n2️⃣ How to set the price?\n3️⃣ How to receive payment?\n4️⃣ Can I refuse a passenger?\n5️⃣ What if a passenger doesn't show up?\n6️⃣ How to report a problem?\n7️⃣ What to do in case of an accident?\n8️⃣ How to manage a passenger's delay?",
        answers: [
          "To publish a ride, go to your dashboard and click 'New Ride'.",
          "The price is automatically calculated based on distance, but you can adjust it slightly.",
          "Payments are transferred to your account after the ride.",
          "Yes, you can refuse a request before confirming it.",
          "Report it via the app to get compensation.",
          "Use the 'Help' section to report inappropriate behavior.",
          "Ensure everyone is safe, then contact Drive Us support immediately.",
          "If a passenger is late, contact them via the app and adjust departure if possible."
        ]
      },
      passager: {
        text: "Here are some useful questions for passengers:\n1️⃣ How to book a ride?\n2️⃣ How to pay for a ride?\n3️⃣ Can I cancel a booking?\n4️⃣ How to contact the driver?\n5️⃣ Can I bring a pet?\n6️⃣ Can I travel with a friend?\n7️⃣ Is it safe?\n8️⃣ What to do if the driver is late?",
        answers: [
          "To book a ride, log in, search for your route and click 'Book'.",
          "Payment is made online before departure via a secure platform.",
          "Yes, you can cancel from your profile before the ride starts.",
          "You can contact the driver via the built-in messaging after booking.",
          "It depends on the driver. Check the ride description before booking.",
          "Yes, you can book multiple seats if available.",
          "Yes, Drive Us service is safe and drivers are verified.",
          "If the driver is late, contact them via the app or check tracking notifications."
        ]
      }
    }
  }
};

const chatbox = document.getElementById("chatbox");
const input = document.getElementById("userInput");
const languageSelect = document.getElementById("languageSelect");

let currentLang = "fr";
let currentRole = null;
let roleAsked = false;
let awaitingMoreQuestions = false;

function displayBotMessage(message) {
  const p = document.createElement("p");
  p.className = "bot";
  p.innerText = message;
  chatbox.appendChild(p);
  chatbox.scrollTop = chatbox.scrollHeight;
}

function displayUserMessage(message) {
  const p = document.createElement("p");
  p.className = "user";
  p.innerText = message;
  chatbox.appendChild(p);
  chatbox.scrollTop = chatbox.scrollHeight;
}

function sendQuestion() {
  const userText = input.value.trim().toLowerCase();
  if (!userText) return;
  displayUserMessage(userText);
  input.value = "";

  const langData = faq[currentLang];

  // Réponse "oui/non" pour plus de questions
  if (awaitingMoreQuestions) {
    if (["oui","ouais","yes","yeah","yep"].some(w => userText.includes(w))) {
      displayBotMessage(langData.roleQuestions[currentRole].text);
      awaitingMoreQuestions = false;
      return;
    } 
    if (["non","no","nop","nah"].some(w => userText.includes(w))) {
      displayBotMessage(langData.noResponse);
      displayBotMessage(langData.contact);
      awaitingMoreQuestions = false;
      return;
    }
    displayBotMessage(langData.notFound);
    displayBotMessage(langData.askMore);
    return;
  }

  // Réponse initiale oui/non
  if (!currentRole && ["oui","ouais","yes","yeah","yep"].some(w => userText.includes(w))) {
    roleAsked = true;
    displayBotMessage(langData.askRole);
    return;
  }

  if (!currentRole && ["non","no","nop","nah"].some(w => userText.includes(w))) {
    displayBotMessage(langData.noResponse);
    displayBotMessage(langData.contact);
    return;
  }

  // Choix du rôle
  if (roleAsked && !currentRole) {
    if (userText.includes("conducteur") || userText.includes("driver")) {
      currentRole = "conducteur";
      roleAsked = false;
      displayBotMessage(langData.roleQuestions.conducteur.text);
      return;
    }
    if (userText.includes("passager") || userText.includes("passenger")) {
      currentRole = "passager";
      roleAsked = false;
      displayBotMessage(langData.roleQuestions.passager.text);
      return;
    }
    displayBotMessage(langData.notFound);
    return;
  }

  // Gestion numéro de question
  const number = parseInt(userText);
  if (!isNaN(number) && currentRole && number >= 1 && number <= 8) {
    const answer = langData.roleQuestions[currentRole].answers[number - 1];
    displayBotMessage(answer);
    setTimeout(() => displayBotMessage(langData.askMore), 400);
    awaitingMoreQuestions = true;
    return;
  }

  // Réponse par défaut si non reconnue
  displayBotMessage(langData.notFound);
  displayBotMessage(langData.contact);
}

input.addEventListener("keypress", (e) => {
  if (e.key === "Enter") sendQuestion();
});

languageSelect.addEventListener("change", (e) => {
  currentLang = e.target.value;
  chatbox.innerHTML = "";
  currentRole = null;
  roleAsked = false;
  awaitingMoreQuestions = false;
  displayBotMessage(faq[currentLang].welcome);
});

displayBotMessage(faq[currentLang].welcome);
</script>
</body>
</html>

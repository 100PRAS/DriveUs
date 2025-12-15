<?php
session_start();

// Système de langue unifié
require_once 'Outils/config/langue.php';

if (!isset($_SESSION['UserID'])) {
    header("Location: Se_connecter.php");
    exit;
}

$trajetId = $_GET['trajet_id'] ?? null;
if (!$trajetId) {
    header("Location: Outils/Mes_trajets.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= getLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversation de Groupe - DriveUs</title>
    <link rel="icon" type="image/x-icon" href="Image/Icone.ico">
    <link rel="stylesheet" href="CSS/Outils/layout-global.css">
    <link rel="stylesheet" href="CSS/Messagerie1.css">
    <link rel="stylesheet" href="CSS/Sombre/Sombre_Messagerie.css">
    <script src="JS/Sombre.js"></script>
    <style>
        .group-header {
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        html.dark .group-header {
            background: #2a2a2a;
            border-color: #444;
        }
        
        .group-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        html.dark .group-title {
            color: #e0e0e0;
        }
        
        .participants-list {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .participant-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: 2px solid #fff;
            object-fit: cover;
        }
        
        .participants-count {
            font-size: 14px;
            color: #666;
            margin-left: 10px;
        }
        
        html.dark .participants-count {
            color: #aaa;
        }
        
        .group-message-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #fff;
        }
        
        html.dark .group-message-container {
            background: #1e1e1e;
        }
        
        .group-message {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .group-message.own {
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .message-content {
            max-width: 60%;
        }
        
        .message-sender {
            font-size: 12px;
            font-weight: 600;
            color: #007bff;
            margin-bottom: 3px;
        }
        
        .message-bubble {
            background: #e9ecef;
            padding: 10px 15px;
            border-radius: 18px;
            word-wrap: break-word;
        }
        
        .group-message.own .message-bubble {
            background: #007bff;
            color: white;
        }
        
        html.dark .message-bubble {
            background: #2a2a2a;
            color: #e0e0e0;
        }
        
        html.dark .group-message.own .message-bubble {
            background: #0056b3;
        }
        
        .message-time {
            font-size: 11px;
            color: #999;
            margin-top: 3px;
        }
        
        .group-input-container {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            display: flex;
            gap: 10px;
        }
        
        html.dark .group-input-container {
            background: #2a2a2a;
            border-color: #444;
        }
        
        .group-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            font-size: 14px;
        }
        
        html.dark .group-input {
            background: #1e1e1e;
            border-color: #444;
            color: #e0e0e0;
        }
        
        .send-btn-group {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .send-btn-group:hover {
            background: #0056b3;
        }
        
        .back-btn {
            padding: 8px 15px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .back-btn:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <?php include 'Outils/views/header.php'; ?>
    
    <main style="display: flex; flex-direction: column; height: calc(100vh - 70px); max-width: 1200px; margin: 0 auto;">
        <div class="group-header">
            <div>
                <a href="/DriveUs/Outils/Mes_trajets.php" class="back-btn">← Retour</a>
                <span class="group-title" id="groupTitle">Conversation de groupe</span>
                <span class="participants-count" id="participantsCount"></span>
            </div>
            <div class="participants-list" id="participantsList"></div>
        </div>
        
        <div class="group-message-container" id="groupMessages">
            <p style="text-align: center; color: #999;">Chargement...</p>
        </div>
        
        <div class="group-input-container">
            <input 
                type="text" 
                class="group-input" 
                id="groupMessageInput" 
                placeholder="Écrivez votre message..."
                onkeypress="if(event.key === 'Enter') sendGroupMessage()"
            >
            <button class="send-btn-group" onclick="sendGroupMessage()">Envoyer</button>
        </div>
    </main>
    
    <?php include 'Outils/views/footer.php'; ?>
    
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const trajetId = urlParams.get('trajet_id');
        const currentUserId = '<?= $_SESSION['UserID'] ?? '' ?>';
        let currentUserEmail = ''; // sera remplacé dynamiquement si nécessaire
        
        if (!trajetId) {
            alert('ID de trajet manquant');
            window.location.href = '/DriveUs/Outils/Mes_trajets.php';
        }
        
        function loadGroupConversation() {
            fetch(`/DriveUs/Outils/get_group_conversation.php?trajet_id=${trajetId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert('Erreur: ' + data.error);
                        return;
                    }
                    
                    // Afficher les participants
                    displayParticipants(data.participants);
                    
                    // Afficher les messages
                    displayMessages(data.messages);
                })
                .catch(err => console.error('Erreur chargement:', err));
        }
        
        function displayParticipants(participants) {
            const list = document.getElementById('participantsList');
            const count = document.getElementById('participantsCount');
            
            count.textContent = `${participants.length} participant${participants.length > 1 ? 's' : ''}`;
            
            list.innerHTML = participants.slice(0, 5).map(p => 
                `<img src="${p.photo}" alt="${p.prenom}" class="participant-avatar" title="${p.prenom} (${p.role})">`
            ).join('');
            
            if (participants.length > 5) {
                list.innerHTML += `<span style="font-size: 12px; color: #666;">+${participants.length - 5}</span>`;
            }
        }
        
        function displayMessages(messages) {
            const container = document.getElementById('groupMessages');
            
            if (messages.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #999;">Aucun message. Soyez le premier à écrire !</p>';
                return;
            }
            
            container.innerHTML = messages.map(m => {
                const isOwn = m.sender === currentUserEmail;
                const date = new Date(m.date);
                const timeStr = date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                
                return `
                    <div class="group-message ${isOwn ? 'own' : ''}">
                        ${!isOwn ? `<img src="${m.photo}" class="message-avatar" alt="${m.prenom}">` : ''}
                        <div class="message-content">
                            ${!isOwn ? `<div class="message-sender">${m.prenom}</div>` : ''}
                            <div class="message-bubble">${escapeHtml(m.message)}</div>
                            <div class="message-time">${timeStr}</div>
                        </div>
                        ${isOwn ? `<img src="${m.photo}" class="message-avatar" alt="${m.prenom}">` : ''}
                    </div>
                `;
            }).join('');
            
            // Scroll vers le bas
            container.scrollTop = container.scrollHeight;
        }
        
        function sendGroupMessage() {
            const input = document.getElementById('groupMessageInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            fetch('/DriveUs/Outils/send_group_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    trajet_id: trajetId,
                    message: message
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadGroupConversation();
                } else {
                    alert('Erreur: ' + (data.message || data.error));
                }
            })
            .catch(err => console.error('Erreur envoi:', err));
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Charger la conversation au démarrage
        loadGroupConversation();
        
        // Rafraîchir toutes les 3 secondes
        setInterval(loadGroupConversation, 3000);
    </script>
</body>
</html>

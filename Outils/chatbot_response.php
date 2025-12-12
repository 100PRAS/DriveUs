<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// Configuration du chatbot FAQ
$faq = [
    'fr' => [
        'welcome' => 'Bonjour 👋 ! Je suis l\'assistant Drive Us. Si vous avez besoin d\'aide pour utiliser le site ou pour vos trajets, je suis là pour vous guider. Avez‑vous besoin d\'aide ?',
        'notFound' => 'Désolé, je n\'ai pas encore la réponse à cette question. 😅',
        'askRole' => 'Parfait ! 😊 Êtes-vous conducteur ou passager ?',
        'askMore' => 'Avez-vous d\'autres questions ? (oui/non)',
        'contact' => 'Si vous ne trouvez pas la réponse à votre question, contactez-nous à 👉 codeandcofee94@gmail.com 📩',
        'noResponse' => 'D\'accord 👍. Je vous souhaite une excellente journée et une bonne route ! 🚗💨',
        'roleQuestions' => [
            'conducteur' => [
                'text' => "Voici des questions utiles pour les conducteurs :\n1️⃣ Comment publier un trajet ?\n2️⃣ Comment fixer le prix ?\n3️⃣ Comment recevoir le paiement ?\n4️⃣ Puis-je refuser un passager ?\n5️⃣ Que faire si un passager ne se présente pas ?\n6️⃣ Comment signaler un problème ?\n7️⃣ Que faire en cas d'accident ?\n8️⃣ Comment gérer un retard du passager ?",
                'answers' => [
                    'Pour publier un trajet, allez dans votre tableau de bord et cliquez sur \'Nouveau trajet\'.',
                    'Le prix est calculé automatiquement selon la distance, mais vous pouvez l\'ajuster légèrement.',
                    'Les paiements sont transférés sur votre compte après le trajet.',
                    'Oui, vous pouvez refuser une demande avant de la confirmer.',
                    'Signalez-le via l\'application pour obtenir une compensation.',
                    'Utilisez la section \'Aide\' pour signaler un comportement inapproprié.',
                    'Assurez-vous que tout le monde va bien, puis contactez le support Drive Us immédiatement.',
                    'Si un passager est en retard, contactez-le via l\'application et ajustez le départ si possible.'
                ]
            ],
            'passager' => [
                'text' => "Voici des questions utiles pour les passagers :\n1️⃣ Comment réserver un trajet ?\n2️⃣ Comment payer un trajet ?\n3️⃣ Puis-je annuler une réservation ?\n4️⃣ Comment contacter le conducteur ?\n5️⃣ Puis-je emmener un animal ?\n6️⃣ Puis-je voyager avec un ami ?\n7️⃣ Est-ce sécurisé ?\n8️⃣ Que faire en cas de retard du conducteur ?",
                'answers' => [
                    'Pour réserver un trajet, connectez-vous, recherchez un itinéraire et cliquez sur \'Réserver\'.',
                    'Le paiement se fait en ligne avant le départ via une plateforme sécurisée.',
                    'Oui, vous pouvez annuler depuis votre profil avant le départ du trajet.',
                    'Vous pouvez contacter le conducteur grâce à la messagerie intégrée après avoir réservé.',
                    'Cela dépend du conducteur. Vérifiez la description du trajet avant de réserver.',
                    'Oui, vous pouvez réserver plusieurs places si elles sont disponibles.',
                    'Oui, le service Drive Us est sécurisé et les conducteurs sont vérifiés.',
                    'Si le conducteur a du retard, contactez-le via l\'application ou consultez les notifications de suivi.'
                ]
            ]
        ]
    ],
    'en' => [
        'welcome' => 'Hello 👋! I am the Drive Us assistant. If you need help using the site or with your rides, I am here to guide you. Do you need help?',
        'notFound' => 'Sorry, I don\'t have an answer for that yet. 😅',
        'askRole' => 'Great! 😊 Are you a driver or a passenger?',
        'askMore' => 'Do you have any other questions? (yes/no)',
        'contact' => 'If you can\'t find the answer, contact us at 👉 codeandcofee94@gmail.com 📩',
        'noResponse' => 'Alright 👍. Have a great day and safe travels! 🚗💨',
        'roleQuestions' => [
            'conducteur' => [
                'text' => "Here are some useful questions for drivers:\n1️⃣ How to publish a ride?\n2️⃣ How to set the price?\n3️⃣ How to receive payment?\n4️⃣ Can I refuse a passenger?\n5️⃣ What if a passenger doesn't show up?\n6️⃣ How to report a problem?\n7️⃣ What to do in case of an accident?\n8️⃣ How to manage a passenger's delay?",
                'answers' => [
                    'To publish a ride, go to your dashboard and click \'New Ride\'.',
                    'The price is automatically calculated based on distance, but you can adjust it slightly.',
                    'Payments are transferred to your account after the ride.',
                    'Yes, you can refuse a request before confirming it.',
                    'Report it via the app to get compensation.',
                    'Use the \'Help\' section to report inappropriate behavior.',
                    'Ensure everyone is safe, then contact Drive Us support immediately.',
                    'If a passenger is late, contact them via the app and adjust departure if possible.'
                ]
            ],
            'passager' => [
                'text' => "Here are some useful questions for passengers:\n1️⃣ How to book a ride?\n2️⃣ How to pay for a ride?\n3️⃣ Can I cancel a booking?\n4️⃣ How to contact the driver?\n5️⃣ Can I bring a pet?\n6️⃣ Can I travel with a friend?\n7️⃣ Is it safe?\n8️⃣ What to do if the driver is late?",
                'answers' => [
                    'To book a ride, log in, search for your route and click \'Book\'.',
                    'Payment is made online before departure via a secure platform.',
                    'Yes, you can cancel from your profile before the ride starts.',
                    'You can contact the driver via the built-in messaging after booking.',
                    'It depends on the driver. Check the ride description before booking.',
                    'Yes, you can book multiple seats if available.',
                    'Yes, Drive Us service is safe and drivers are verified.',
                    'If the driver is late, contact them via the app or check tracking notifications.'
                ]
            ]
        ]
    ]
];

// Récupérer les paramètres
$lang = $_POST['lang'] ?? 'fr';
$message = trim($_POST['message'] ?? '');
$role = $_POST['role'] ?? null;
$asking_for_help = isset($_POST['asking_for_help']) ? (bool)$_POST['asking_for_help'] : true;

if (empty($message)) {
    echo json_encode(['error' => 'Pas de message']);
    exit;
}

$langData = $faq[$lang] ?? $faq['fr'];
$response = '';

// Traitement des messages
$userTextLower = strtolower($message);

// Réinitialisation (commande spéciale)
if ($userTextLower === '/reset') {
    echo json_encode(['response' => $langData['welcome'], 'reset' => true, 'asking_for_help' => true, 'role' => null]);
    exit;
}

// Réponse aux "oui/non" pour plus de questions
if (isset($_POST['awaiting_more'])) {
    if (preg_match('/(oui|ouais|yes|yeah|yep|si|da)/i', $userTextLower)) {
        $response = $langData['roleQuestions'][$role]['text'] ?? $langData['notFound'];
        echo json_encode(['response' => $response, 'awaiting_more' => false]);
        exit;
    } 
    if (preg_match('/(non|no|nop|nah|nein)/i', $userTextLower)) {
        $response = $langData['noResponse'] . "\n" . $langData['contact'];
        echo json_encode(['response' => $response, 'reset' => true, 'asking_for_help' => true, 'role' => null]);
        exit;
    }
    $response = $langData['notFound'] . "\n" . $langData['askMore'];
    echo json_encode(['response' => $response, 'awaiting_more' => true]);
    exit;
}

// Réponse initiale oui/non pour avoir besoin d'aide
if ($asking_for_help && !$role) {
    if (preg_match('/(oui|ouais|yes|yeah|yep|si|da)/i', $userTextLower)) {
        echo json_encode(['response' => $langData['askRole'], 'asking_for_help' => false]);
        exit;
    }
    if (preg_match('/(non|no|nop|nah|nein)/i', $userTextLower)) {
        $response = $langData['noResponse'] . "\n" . $langData['contact'];
        echo json_encode(['response' => $response, 'reset' => true, 'asking_for_help' => true, 'role' => null]);
        exit;
    }
    // Si la réponse n'est pas claire, redemander
    echo json_encode(['response' => 'Pardon, je n\'ai pas compris. Avez-vous besoin d\'aide ? (oui/non)', 'asking_for_help' => true]);
    exit;
}

// Choix du rôle (conducteur ou passager)
if (!$asking_for_help && !$role) {
    if (preg_match('/(conducteur|driver|conduct)/i', $userTextLower)) {
        $response = $langData['roleQuestions']['conducteur']['text'];
        echo json_encode(['response' => $response, 'role' => 'conducteur']);
        exit;
    }
    if (preg_match('/(passager|passenger|passag)/i', $userTextLower)) {
        $response = $langData['roleQuestions']['passager']['text'];
        echo json_encode(['response' => $response, 'role' => 'passager']);
        exit;
    }
    echo json_encode(['response' => $langData['askRole']]);
    exit;
}

// Gestion numéro de question (1-8)
$number = intval($message);
if ($number >= 1 && $number <= 8 && $role) {
    $answers = $langData['roleQuestions'][$role]['answers'] ?? [];
    if (isset($answers[$number - 1])) {
        $response = $answers[$number - 1] . "\n" . $langData['askMore'];
        echo json_encode(['response' => $response, 'awaiting_more' => true]);
        exit;
    }
}

// Réponse par défaut
$response = $langData['notFound'] . "\n" . $langData['contact'];
echo json_encode(['response' => $response]);

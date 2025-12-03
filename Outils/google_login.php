<?php
include("config.php"); // Connexion à la BDD
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['credential'])) {
    $jwt = $_POST['credential'];

    // Vérification du JWT via Google
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $jwt;
    $data = json_decode(file_get_contents($url), true);

    if (isset($data['email'])) {
        $mail = $data['email'];
        $nom = $data['family_name'] ?? '';
        $prenom = $data['given_name'] ?? '';
        $photo = $data['picture'] ?? '';

        // Vérifier si l'utilisateur existe déjà
        $stmt = $conn->prepare("SELECT Mail FROM user WHERE Mail=?");
        $stmt->bind_param("s", $mail);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            // Ajouter nouvel utilisateur
            $stmt2 = $conn->prepare("INSERT INTO user (nom, prenom, mail, PhotoProfil) VALUES (?, ?, ?, ?)");
            $stmt2->bind_param("ssss", $nom, $prenom, $mail, $photo);
            $stmt2->execute();
            $stmt2->close();
        }

        $stmt->close();

        // Connecter l'utilisateur
        $_SESSION['user_mail'] = $mail;
        setcookie('user_mail', $mail, time() + (30*24*60*60), "/");
        http_response_code(200);
        echo "Connexion réussie";
    } else {
        http_response_code(400);
        echo "Erreur: utilisateur non valide";
    }
}
?>

<?php
// =========================================================
// Handler POST pour Profil.php
// =========================================================

session_start();
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['form_type'])) {
    // Initialiser tous les champs avec les valeurs actuelles
    $Prenom = $user['Prenom'] ?? '';
    $Nom = $user['Nom'] ?? '';
    $Genre = $user['Genre'] ?? '';
    $Date_naissance = $user['date_naissance'] ?? '';
    $Mail = $user['Mail'] ?? '';
    $Numero = $user['Numero'] ?? '';
    $RIB = $user['RIB'] ?? '';
    $PhotoProfil = $user['PhotoProfil'] ?? null;
    
    $form_type = $_POST['form_type'];
    error_log("DEBUG: Processing form: " . $form_type);
    
    // Récupérer les données selon le formulaire
    if ($form_type === 'form1') {
        // Infos personnelles + photo
        $Prenom = trim($_POST['prenom'] ?? '');
        $Nom = trim($_POST['nom'] ?? '');
        $Genre = trim($_POST['genre'] ?? '');
        $Date_naissance = trim($_POST['dob'] ?? '');
        
        // Gestion de la photo
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] == 0) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . "/DriveUs/Image_Profil/";
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0755, true);
            }
            
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
            $file_tmp = $_FILES['photo']['tmp_name'];
            $file_name = $_FILES['photo']['name'];
            $file_size = $_FILES['photo']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            if ($file_size > 5 * 1024 * 1024) {
                error_log("File too large: " . $file_size);
            } elseif (!in_array($file_ext, $allowed_exts)) {
                error_log("Invalid extension: " . $file_ext);
            } else {
                // Supprimer l'ancienne photo
                if (!empty($PhotoProfil) && file_exists($upload_dir . $PhotoProfil)) {
                    @unlink($upload_dir . $PhotoProfil);
                }
                
                $photo_name = "profile_" . time() . "_" . $userId . "." . $file_ext;
                $upload_path = $upload_dir . $photo_name;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $PhotoProfil = $photo_name;
                    error_log("Photo uploaded: " . $upload_path);
                } else {
                    error_log("move_uploaded_file failed for: " . $upload_path);
                }
            }
        }
    } elseif ($form_type === 'form2') {
        // Coordonnées (adresse gérée dans table séparée)
        $Mail = trim($_POST['email'] ?? '');
        $Numero = trim($_POST['tel'] ?? '');
    } elseif ($form_type === 'form3') {
        // RIB
        $RIB = trim($_POST['rib'] ?? '');
        
        if (!empty($RIB) && $RIB !== ($user['RIB'] ?? '')) {
            $ribValidation = validateRIB($RIB);
            if (!$ribValidation['valid']) {
                $_SESSION['profile_error'] = 'Erreur RIB: ' . $ribValidation['message'];
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $RIB = $ribValidation['formatted'];
            }
        }
    }
    
    // Mise à jour en base de données (sans Adresse qui est dans une table séparée)
    $sql = "UPDATE user SET Prenom=?, Nom=?, Genre=?, date_naissance=?, Mail=?, Numero=?, PhotoProfil=? WHERE UserID=?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo "<script>alert('Erreur lors de la préparation de la requête. Consultez les logs.');</script>";
    } else {
        $stmt->bind_param("sssssssi", $Prenom, $Nom, $Genre, $Date_naissance, $Mail, $Numero, $PhotoProfil, $userId);
        
        if ($stmt->execute()) {
            // Pattern PRG (Post-Redirect-Get) pour eviter re-soumission
            $_SESSION['profile_success'] = 'Profil enregistre avec succes!';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            error_log("Execute failed: " . $stmt->error);
            $_SESSION['profile_error'] = 'Erreur lors de la sauvegarde. Consultez les logs.';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// Afficher les messages de session
if (isset($_SESSION['profile_success'])) {
    echo "<script>alert('" . addslashes($_SESSION['profile_success']) . "');</script>";
    unset($_SESSION['profile_success']);
}
if (isset($_SESSION['profile_error'])) {
    echo "<script>alert('" . addslashes($_SESSION['profile_error']) . "');</script>";
    unset($_SESSION['profile_error']);
}

// Fin du handler POST
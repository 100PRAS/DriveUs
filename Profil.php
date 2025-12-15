

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Systeme de langue unifie
require_once 'Outils/config/langue.php';
require_once 'Outils/config/config.php';

if (!isset($_SESSION['UserID']) && isset($_COOKIE['UserID'])) {
    $_SESSION['UserID'] = $_COOKIE['UserID'];
}

if (!isset($_SESSION['UserID'])) {
    header("Location: Se_connecter.php");
    exit;
}
$userId = $_SESSION['UserID'];

// =========================================================
// Recuperation de l'utilisateur
// =========================================================
$stmt = $conn->prepare("SELECT * FROM `user` WHERE UserID = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: Se_connecter.php");
    exit;
}



if (isset($_FILES['photo'])) {
    echo "DEBUG: ";
    echo "<pre>";
    print_r($_FILES['photo']);
    echo "</pre>";
    if ($_FILES['photo']['error'] !== 0) {
        echo "Erreur upload code : " . $_FILES['photo']['error'];
    } else {
        echo "Upload OK";
    }
} else {
    echo "Aucun fichier reçu (champ 'photo' manquant ou formulaire mal envoyé)";
}

$photoPath = $user['PhotoProfil']
    ? "/DriveUs/Image_Profil/" . htmlspecialchars($user['PhotoProfil'])
    : "/DriveUs/Image_Profil/default.png";

// Inclure le handler POST (apres avoir recupere $user)
require_once 'Outils/handlers/post_handler.php';

// =========================================================
// Fonction de validation RIB
// =========================================================
function validateRIB($rib) {
    // Nettoyer le RIB (enlever espaces et tirets)
    $rib = preg_replace('/[\s-]/', '', strtoupper($rib));
    
    // Verifier le format de base (23 caracteres)
    if (strlen($rib) !== 23) {
        return ['valid' => false, 'message' => 'Le RIB doit contenir 23 caracteres'];
    }
    
    // Extraire les composants
    $codeBanque = substr($rib, 0, 5);
    $codeGuichet = substr($rib, 5, 5);
    $numeroCompte = substr($rib, 10, 11);
    $cleRIB = substr($rib, 21, 2);
    
    // Verifier que code banque et guichet sont numeriques
    if (!ctype_digit($codeBanque) || !ctype_digit($codeGuichet)) {
        return ['valid' => false, 'message' => 'Code banque et guichet doivent etre numeriques'];
    }
    
    // Verifier que la cle est numerique
    if (!ctype_digit($cleRIB)) {
        return ['valid' => false, 'message' => 'La cle RIB doit etre numerique'];
    }
    
    // Calculer la cle de controle
    // Remplacer les lettres par des chiffres (A=1, B=2, ..., Z=26)
    $numeroCompteConverti = '';
    for ($i = 0; $i < strlen($numeroCompte); $i++) {
        $char = $numeroCompte[$i];
        if (ctype_alpha($char)) {
            $numeroCompteConverti .= (ord($char) - ord('A') + 1);
        } else {
            $numeroCompteConverti .= $char;
        }
    }
    
    // Formule de calcul de la cle RIB
    $base = $codeBanque . $codeGuichet . $numeroCompteConverti;
    $cleCalculee = 97 - (bcmod($base, '97'));
    $cleCalculee = str_pad($cleCalculee, 2, '0', STR_PAD_LEFT);
    
    if ($cleCalculee !== $cleRIB) {
        return ['valid' => false, 'message' => 'La cle RIB est incorrecte (attendue: ' . $cleCalculee . ')'];
    }
    
    return ['valid' => true, 'message' => 'RIB valide', 'formatted' => $rib];
}

// =========================================================
// Fonction de validation Carte Bancaire (Luhn)
// =========================================================
function validateCard($card) {
    // Nettoyer la carte (enlever espaces et tirets)
    $card = preg_replace('/[\s-]/', '', $card);
    
    // Verifier que c'est uniquement des chiffres
    if (!ctype_digit($card)) {
        return ['valid' => false, 'message' => 'La carte doit contenir uniquement des chiffres'];
    }
    
    // Verifier la longueur (13 a 19 chiffres)
    $length = strlen($card);
    if ($length < 13 || $length > 19) {
        return ['valid' => false, 'message' => 'La carte doit contenir entre 13 et 19 chiffres'];
    }
    
    // Algorithme de Luhn
    $sum = 0;
    $parity = $length % 2;
    
    for ($i = 0; $i < $length; $i++) {
        $digit = (int)$card[$i];
        
        // Doubler chaque deuxieme chiffre en partant de la droite
        if ($i % 2 === $parity) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        
        $sum += $digit;
    }
    
    // La somme doit etre divisible par 10
    if ($sum % 10 !== 0) {
        return ['valid' => false, 'message' => 'Numero de carte invalide (verification Luhn echouee)'];
    }
    
    // Detecter le type de carte
    $type = 'Inconnue';
    if (preg_match('/^4/', $card)) {
        $type = 'Visa';
    } elseif (preg_match('/^5[1-5]/', $card)) {
        $type = 'MasterCard';
    } elseif (preg_match('/^3[47]/', $card)) {
        $type = 'American Express';
    } elseif (preg_match('/^6(?:011|5)/', $card)) {
        $type = 'Discover';
    }
    
    return ['valid' => true, 'message' => 'Carte valide (' . $type . ')', 'formatted' => $card, 'type' => $type];
}

// =========================================================
// Mise a jour du profil
// =========================================================
if (false) { // Ancien POST desactive - gere par post_handler.php
    // Ce bloc est desactive et reste pour compatibilite
}

// =========================================================
// Deconnexion
// =========================================================
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: Se_connecter.php");
    exit;
}
?>
<!DOCTYPE html>


<html lang="<?= getLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= t('my_account') ?> - DriveUs</title>
    <link rel="icon" type="image/x-icon" href="CSS/Outils/layout-global.css">
    <link rel="stylesheet" href="CSS/Outils/layout-global.css">
    <link rel="stylesheet" href="CSS/Profil.css">
    <link rel="stylesheet" href="CSS/Sombre/Sombre_Profil.css">
    <script src="JS/Sombre.js"></script>
    <link rel="stylesheet" href="CSS/Outils/Header.css">
    <link rel="stylesheet" href="CSS/Sombre/Sombre_Header.css">
    <link rel="stylesheet" href="CSS/Outils/Footer.css">
</head>
<body>
    <?php include 'Outils/views/header.php'; ?>

    <main>
        <!-- MENU GAUCHE -->
        <div class="sidebar">
            <h2>Menu</h2>
            <div class="menu-item active" onclick="showForm('form1', this)">Informations</div>
            <div class="menu-item" onclick="showForm('form2', this)">Coordonnees</div>
            <div class="menu-item" onclick="showForm('form3', this)">Paiement</div>
            <div class="menu-item" onclick="showForm('form4', this)">Mes trajets</div>
            <div class="menu-item" onclick="showForm('form5', this)">Historique</div>
            <div class="menu-item" onclick="showForm('form6', this)">Conducteur</div>
        </div>

        <!-- CONTENU DROITE -->
        <div class="content">

            <!-- FORM 1 - Informations personnelles -->
            <div id="form1" class="form-section open">
                <div class="form-card">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="form_type" value="form1">
                        <div class="profile-photo-section">
                            <img id="photoPreview" src="<?= htmlspecialchars($photoPath) ?>" alt="Photo de profil" class="profile-photo">
                            
                            <div class="file-upload">
                                <label for="photoInput">Changer la photo</label>
<input type="file" id="photoInput" name="photo" accept="image/*" onchange="previewPhoto(event)">
                            </div>
                        </div>

                        <div class="form-section-title">Informations personnelles</div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><?= t('first_name') ?></label>
                                <input type="text" name="prenom" value="<?= htmlspecialchars($user['Prenom'] ?? '') ?>" placeholder="Votre prenom">
                            </div>
                            <div class="form-group">
                                <label><?= t('last_name') ?></label>
                                <input type="text" name="nom" value="<?= htmlspecialchars($user['Nom'] ?? '') ?>" placeholder="Votre nom">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><?= t('gender') ?></label>
                                <select name="genre">
                                    <option value="">-- Selectionner --</option>
                                    <option value="Homme" <?= ($user['Genre'] ?? '') === 'Homme' ? 'selected' : '' ?>><?= t('male') ?></option>
                                    <option value="Femme" <?= ($user['Genre'] ?? '') === 'Femme' ? 'selected' : '' ?>><?= t('female') ?></option>
                                    <option value="Autre" <?= ($user['Genre'] ?? '') === 'Autre' ? 'selected' : '' ?>><?= t('other') ?></option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?= t('date_of_birth') ?></label>
                                <input type="date" name="dob" value="<?= htmlspecialchars($user['date_naissance'] ?? '') ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn-save">Enregistrer</button>
                    </form>
                </div>
            </div>

            <!-- FORM 2 - Coordonnees -->
            <div id="form2" class="form-section">
                <div class="form-card">
                    <div class="form-section-title">Coordonnees</div>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="form_type" value="form2">
                        <div class="form-row full">
                            <div class="form-group">
                                <label><?= t('email') ?></label>
                                <input type="email" name="email" value="<?= htmlspecialchars($user['Mail'] ?? '') ?>" placeholder="votre@email.com">
                            </div>
                        </div>

                        <div class="form-row full">
                            <div class="form-group">
                                <label><?= t('address') ?></label>
                                <input type="text" name="adresse" value="" placeholder="Adresse geree dans table separee" disabled>
                                <small style="color: var(--text-light); font-size: 0.85rem;">La gestion de l'adresse sera bientot disponible</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><?= t('phone') ?></label>
                                <input type="tel" name="tel" value="<?= htmlspecialchars($user['Numero'] ?? '') ?>" placeholder="+33 6 00 00 00 00">
                            </div>
                        </div>

                        <button type="submit" class="btn-save">Enregistrer</button>
                    </form>
                </div>
            </div>

            <!-- FORM 3 - Paiement -->
            <div id="form3" class="form-section">
                <!-- Cartes bancaires enregistrees -->
                <div class="form-card">
                    <div class="form-section-title">Cartes bancaires</div>
                    
                    <div id="cards-list" class="cards-container">
                        <div class="loading-cards">Chargement...</div>
                    </div>
                    
                    <!-- Formulaire d'ajout de carte -->
                    <div id="add-card-section" style="margin-top: 1.5rem;">
                        <button type="button" class="btn-add-card" onclick="toggleAddCardForm()">
                            + Ajouter une carte bancaire
                        </button>
                        
                        <div id="add-card-form" style="display: none; margin-top: 1rem;">
                            <div class="form-row">
                                <div class="form-group" style="grid-column: 1/-1;">
                                    <label>Numero de carte (13-19 chiffres)</label>
                                    <input 
                                        type="text" 
                                        id="new-card-number" 
                                        placeholder="1234 5678 9012 3456"
                                        oninput="formatCard(this)"
                                        maxlength="23"
                                        autocomplete="cc-number"
                                    >
                                    <div id="card-feedback" class="card-feedback"></div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Mois d'expiration</label>
                                    <select id="new-card-month">
                                        <option value="">--</option>
                                        <option value="01">01 - Janvier</option>
                                        <option value="02">02 - Fevrier</option>
                                        <option value="03">03 - Mars</option>
                                        <option value="04">04 - Avril</option>
                                        <option value="05">05 - Mai</option>
                                        <option value="06">06 - Juin</option>
                                        <option value="07">07 - Juillet</option>
                                        <option value="08">08 - Aout</option>
                                        <option value="09">09 - Septembre</option>
                                        <option value="10">10 - Octobre</option>
                                        <option value="11">11 - Novembre</option>
                                        <option value="12">12 - Decembre</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Annee d'expiration</label>
                                    <select id="new-card-year">
                                        <option value="">--</option>
                                        <?php for($y = date('Y'); $y <= date('Y') + 15; $y++): ?>
                                        <option value="<?= $y ?>"><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>CVV / Code de securite (3-4 chiffres)</label>
                                    <input 
                                        type="text" 
                                        id="new-card-cvv" 
                                        placeholder="123"
                                        oninput="formatCVV(this)"
                                        maxlength="4"
                                        autocomplete="cc-csc"
                                        style="max-width: 150px;"
                                    >
                                    <div id="cvv-feedback" class="cvv-feedback"></div>
                                    <small style="color: var(--text-light); font-size: 0.85rem;">Le CVV ne sera jamais stocke (securite PCI-DSS)</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <button type="button" class="btn-save" onclick="addCard()">Ajouter la carte</button>
                                <button type="button" class="btn-cancel" onclick="toggleAddCardForm()">Annuler</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Section RIB -->
                <div class="form-card" style="margin-top: 1.5rem;">
                    <div class="form-section-title">RIB (Releve d'Identite Bancaire)</div>
                    
                    <form method="POST">
                        <input type="hidden" name="form_type" value="form3">
                        <div class="form-row full">
                            <div class="form-group">
                                <label>RIB (23 caracteres)</label>
                                <input 
                                    type="text" 
                                    name="rib" 
                                    value="<?= htmlspecialchars($user['RIB'] ?? '') ?>" 
                                    placeholder="30004 00000 00000000097 62"
                                    oninput="formatRIB(this)"
                                    maxlength="27"
                                >
                                <div id="rib-feedback" class="rib-feedback"></div>
                                <small style="color: var(--text-light); font-size: 0.85rem;">Format: Code Banque (5) + Code Guichet (5) + Numero de compte (11) + Cle (2)</small>
                            </div>
                        </div>

                        <button type="submit" class="btn-save">Enregistrer le RIB</button>
                    </form>
                </div>
            </div>

            <!-- FORM 4 - Mes trajets -->
            <div id="form4" class="form-section">
                <div class="info-box">
                    <h3>Espace conducteur</h3>
                    <p>Publiez vos trajets, gerez vos passagers et suivez vos revenus en temps reel.</p>
                    <a href="/DriveUs/Outils/Mes_trajets.php">Voir mes trajets</a>
                </div>
            </div>

            <!-- FORM 5 - Historique -->
            <div id="form5" class="form-section">
                <div class="info-box">
                    <h3>Mes reservations</h3>
                    <p>Consultez votre historique de trajets et de reservations en tant que passager.</p>
                    <a href="/DriveUs/Mes_reservations.php">Voir mes reservations</a>
                </div>
                <div class="info-box">
                    <h3>Reservations recues</h3>
                    <p>Gerez les demandes de reservation pour vos trajets.</p>
                    <a href="/DriveUs/Mes_reservations_recues.php">Voir les reservations</a>
                </div>
            </div>
            
            <!-- FORM 6 - Conducteur -->
            <div id="form6" class="form-section">
                <!-- Permis de conduire -->
                <div class="form-card">
                    <div class="form-section-title">Permis de conduire</div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-row full">
                            <div class="form-group">
                                <label>Numero de permis</label>
                                <input 
                                    type="text" 
                                    name="permis_numero" 
                                    value="<?= htmlspecialchars($user['PermisNumero'] ?? '') ?>" 
                                    placeholder="Ex: 12345678901234"
                                >
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Date d'obtention</label>
                                <input type="date" name="permis_obtention" value="<?= htmlspecialchars($user['PermisObtention'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Date d'expiration</label>
                                <input type="date" name="permis_expiration" value="<?= htmlspecialchars($user['PermisExpiration'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-row full">
                            <div class="form-group">
                                <label>Document du permis</label>
                                <input type="file" name="permis_document" accept="image/*,application/pdf">
                                <?php if (!empty($user['PermisDocument'])): ?>
                                <small style="color: var(--text-light);">Document actuel: <?= htmlspecialchars($user['PermisDocument']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-save">Enregistrer permis</button>
                    </form>
                </div>
                
                <!-- Vehicules -->
                <div class="form-card" style="margin-top: 1.5rem;" id="vehicles-card" <?php echo empty($user['PermisNumero']) ? 'class="disabled-section"' : ''; ?>>
                    <?php if (empty($user['PermisNumero'])): ?>
                    <div class="driver-disabled-notice">
                        Veuillez d'abord ajouter votre numero de permis pour acceder a la gestion des vehicules.
                    </div>
                    <?php endif; ?>
                    <div class="form-section-title" <?php echo empty($user['PermisNumero']) ? 'style="opacity: 0.5;"' : ''; ?>>Mes vehicules</div>
                    
                    <div id="vehicles-list" class="vehicles-container" <?php echo empty($user['PermisNumero']) ? 'style="opacity: 0.6; pointer-events: none;"' : ''; ?>>
                        <div class="loading-vehicles">Chargement...</div>
                    </div>
                    
                    <!-- Formulaire d'ajout de vehicule -->
                    <div id="add-vehicle-section" style="margin-top: 1.5rem;" <?php echo empty($user['PermisNumero']) ? 'style="opacity: 0.6; pointer-events: none;"' : ''; ?>>
                        <button type="button" class="btn-add-vehicle" onclick="toggleAddVehicleForm()" <?php echo empty($user['PermisNumero']) ? 'disabled' : ''; ?>>
                            + Ajouter un vehicule
                        </button>
                        
                        <div id="add-vehicle-form" style="display: none; margin-top: 1rem;">
                            <div class="form-row full">
                                <div class="form-group">
                                    <label>Marque - Modele</label>
                                    <input 
                                        type="text" 
                                        id="new-vehicle-model" 
                                        placeholder="Ex: Toyota Prius"
                                        required
                                    >
                                </div>
                            </div>
                            
                            <div class="form-row full">
                                <div class="form-group">
                                    <label>Plaques d'immatriculation</label>
                                    <input 
                                        type="text" 
                                        id="new-vehicle-plate" 
                                        placeholder="AB-123-CD"
                                        required
                                    >
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Annee de fabrication</label>
                                    <input 
                                        type="number" 
                                        id="new-vehicle-year" 
                                        placeholder="2020"
                                        min="1990"
                                        max="<?= date('Y') + 1 ?>"
                                    >
                                </div>
                                <div class="form-group">
                                    <label>Nombre de places (+ conducteur)</label>
                                    <input 
                                        type="number" 
                                        id="new-vehicle-seats" 
                                        placeholder="4"
                                        min="1"
                                        max="9"
                                        value="4"
                                    >
                                </div>
                            </div>
                            
                            <div class="form-row full">
                                <div class="form-group">
                                    <label>Type de carburant</label>
                                    <select id="new-vehicle-fuel">
                                        <option value="">-- Selectionner --</option>
                                        <option value="Essence">Essence</option>
                                        <option value="Diesel">Diesel</option>
                                        <option value="Hybride">Hybride</option>
                                        <option value="Electrique">Electrique</option>
                                        <option value="GPL">GPL</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row full">
                                <div class="form-group">
                                    <label>Fiche technique (PDF, image)</label>
                                    <input 
                                        type="file" 
                                        id="new-vehicle-spec" 
                                        accept=".pdf,.jpg,.jpeg,.png"
                                    >
                                    <small style="color: var(--text-light); font-size: 0.85rem;">Max 5MB (PDF, JPG, PNG)</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <button type="button" class="btn-save" onclick="addVehicle()">Ajouter vehicule</button>
                                <button type="button" class="btn-cancel" onclick="toggleAddVehicleForm()">Annuler</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        function showForm(formId, element) {
            document.querySelectorAll(".form-section").forEach(f => f.classList.remove("open"));
            document.getElementById(formId).classList.add("open");
            document.querySelectorAll(".menu-item").forEach(item => item.classList.remove("active"));
            element.classList.add("active");
        }

        function previewPhoto(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoPreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }
        
        function formatCVV(input) {
            let value = input.value.replace(/\D/g, '');
            value = value.substring(0, 4);
            input.value = value;
            validateCVV(input);
        }
        
        function validateCVV(input) {
            const feedback = document.getElementById('cvv-feedback');
            const value = input.value;
            
            if (value.length === 0) {
                feedback.textContent = '';
                feedback.className = 'cvv-feedback';
                input.classList.remove('valid', 'invalid');
                return;
            }
            
            if (value.length < 3) {
                feedback.textContent = 'Le CVV doit contenir 3 ou 4 chiffres';
                feedback.className = 'cvv-feedback warning';
                input.classList.remove('valid');
                input.classList.add('invalid');
                return;
            }
            
            feedback.textContent = 'CVV valide';
            feedback.className = 'cvv-feedback success';
            input.classList.remove('invalid');
            input.classList.add('valid');
        }
                
        function formatCard(input) {
            let value = input.value.replace(/[\s-]/g, '');
            value = value.replace(/\D/g, '');
            value = value.substring(0, 19);
            
            let formatted = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formatted += ' ';
                }
                formatted += value[i];
            }
            
            input.value = formatted;
            validateCardInput(input);
        }
        
        function validateCardInput(input) {
            const feedback = document.getElementById('card-feedback');
            const value = input.value.replace(/[\s-]/g, '');
            
            if (value.length === 0) {
                feedback.textContent = '';
                feedback.className = 'card-feedback';
                input.classList.remove('valid', 'invalid');
                return;
            }
            
            if (!/^\d+$/.test(value)) {
                feedback.textContent = 'La carte doit contenir uniquement des chiffres';
                feedback.className = 'card-feedback error';
                input.classList.remove('valid');
                input.classList.add('invalid');
                return;
            }
            
            if (value.length < 13) {
                feedback.textContent = 'La carte doit contenir au moins 13 chiffres (' + value.length + '/13)';
                feedback.className = 'card-feedback warning';
                input.classList.remove('valid');
                input.classList.add('invalid');
                return;
            }
            
            if (value.length > 19) {
                feedback.textContent = 'La carte ne peut pas depasser 19 chiffres';
                feedback.className = 'card-feedback error';
                input.classList.remove('valid');
                input.classList.add('invalid');
                return;
            }
            
            let sum = 0;
            let parity = value.length % 2;
            
            for (let i = 0; i < value.length; i++) {
                let digit = parseInt(value[i]);
                
                if (i % 2 === parity) {
                    digit *= 2;
                    if (digit > 9) {
                        digit -= 9;
                    }
                }
                
                sum += digit;
            }
            
            if (sum % 10 !== 0) {
                feedback.textContent = 'Numero de carte invalide (verification Luhn echouee)';
                feedback.className = 'card-feedback error';
                input.classList.remove('valid');
                input.classList.add('invalid');
                return;
            }
            
            let cardType = 'Carte valide';
            if (/^4/.test(value)) {
                cardType = 'Visa valide';
            } else if (/^5[1-5]/.test(value)) {
                cardType = 'MasterCard valide';
            } else if (/^3[47]/.test(value)) {
                cardType = 'American Express valide';
            } else if (/^6(?:011|5)/.test(value)) {
                cardType = 'Discover valide';
            }
            
            feedback.textContent = cardType;
            feedback.className = 'card-feedback success';
            input.classList.remove('invalid');
            input.classList.add('valid');
        }
        
        function formatRIB(input) {
            let value = input.value.replace(/[\s-]/g, '').toUpperCase();
            value = value.substring(0, 23);
            
            let formatted = '';
            if (value.length > 0) formatted += value.substring(0, 5);
            if (value.length > 5) formatted += ' ' + value.substring(5, 10);
            if (value.length > 10) formatted += ' ' + value.substring(10, 21);
            if (value.length > 21) formatted += ' ' + value.substring(21, 23);
            
            input.value = formatted;
            validateRIBInput(input);
        }
        
        function validateRIBInput(input) {
            const feedback = document.getElementById('rib-feedback');
            const value = input.value.replace(/[\s-]/g, '').toUpperCase();
            
            if (value.length === 0) {
                feedback.textContent = '';
                feedback.className = 'rib-feedback';
                input.classList.remove('valid', 'invalid');
                return;
            }
            
            if (value.length !== 23) {
                feedback.textContent = 'Le RIB doit contenir 23 caracteres (' + value.length + '/23)';
                feedback.className = 'rib-feedback warning';
                input.classList.remove('valid');
                input.classList.add('invalid');
                return;
            }
            
            const codeBanque = value.substring(0, 5);
            const codeGuichet = value.substring(5, 10);
            const numeroCompte = value.substring(10, 21);
            const cleRIB = value.substring(21, 23);
            
            if (!/^\d{5}$/.test(codeBanque) || !/^\d{5}$/.test(codeGuichet)) {
                feedback.textContent = 'Code banque et guichet doivent etre numeriques';
                feedback.className = 'rib-feedback error';
                input.classList.remove('valid');
                input.classList.add('invalid');
                return;
            }
            
            if (!/^\d{2}$/.test(cleRIB)) {
                feedback.textContent = 'La cle RIB doit etre numerique';
                feedback.className = 'rib-feedback error';
                input.classList.remove('valid');
                input.classList.add('invalid');
                return;
            }
            
            let numeroCompteConverti = '';
            for (let i = 0; i < numeroCompte.length; i++) {
                const char = numeroCompte[i];
                if (/[A-Z]/.test(char)) {
                    numeroCompteConverti += (char.charCodeAt(0) - 'A'.charCodeAt(0) + 1).toString();
                } else {
                    numeroCompteConverti += char;
                }
            }
            
            const base = codeBanque + codeGuichet + numeroCompteConverti;
            const cleCalculee = 97 - (BigInt(base) % 97n);
            const cleCalculeeStr = cleCalculee.toString().padStart(2, '0');
            
            if (cleCalculeeStr !== cleRIB) {
                feedback.textContent = 'Cle RIB incorrecte (attendue: ' + cleCalculeeStr + ')';
                feedback.className = 'rib-feedback error';
                input.classList.remove('valid');
                input.classList.add('invalid');
                return;
            }
            
            feedback.textContent = 'RIB valide';
            feedback.className = 'rib-feedback success';
            input.classList.remove('invalid');
            input.classList.add('valid');
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const ribInput = document.querySelector('input[name="rib"]');
            if (ribInput && ribInput.value) {
                validateRIBInput(ribInput);
            }
            
            loadCards();
            loadVehicles();
        });
        
        function loadVehicles() {
            fetch('/DriveUs/Outils/vehicle_handler.php?action=get_vehicles')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('vehicles-list');
                    
                    if (!data.success || data.vehicles.length === 0) {
                        container.innerHTML = '<div class="no-vehicles">Aucun vehicule enregistre</div>';
                        return;
                    }
                    
                    let html = '';
                    data.vehicles.forEach(vehicle => {
                        const fuelIcons = {
                            'Essence': 'G',
                            'Diesel': 'D',
                            'Hybride': 'H',
                            'Electrique': 'E',
                            'GPL': 'P'
                        };
                        const fuelIcon = fuelIcons[vehicle.fuel_type] || 'V';
                        const hasSpec = vehicle.spec_file && vehicle.spec_file !== '';
                        const specButtonClass = hasSpec ? 'btn-view-spec' : 'btn-no-spec';
                        const specButtonText = hasSpec ? 'Fiche' : 'Aucune';
                        
                        html += `
                            <div class="vehicle-item">
                                <div class="vehicle-icon">${fuelIcon}</div>
                                <div class="vehicle-info">
                                    <div class="vehicle-model">${vehicle.model}</div>
                                    <div class="vehicle-plate">${vehicle.plate}</div>
                                    <div class="vehicle-details">${vehicle.year} - ${vehicle.fuel_type} - ${vehicle.seats} places</div>
                                </div>
                                <div class="vehicle-actions">
                                    <button type="button" class="${specButtonClass}" ${hasSpec ? `onclick="viewVehicleSpec(${vehicle.id}, '${vehicle.spec_file}')"` : 'disabled'}>
                                        ${specButtonText}
                                    </button>
                                    <button type="button" class="btn-delete-vehicle" onclick="deleteVehicle(${vehicle.id})">Supprimer</button>
                                </div>
                            </div>
                        `;
                    });
                    
                    container.innerHTML = html;
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    document.getElementById('vehicles-list').innerHTML = '<div class="error-vehicles">Erreur de chargement</div>';
                });
        }
        
        function toggleAddVehicleForm() {
            const form = document.getElementById('add-vehicle-form');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            
            if (form.style.display === 'none') {
                document.getElementById('new-vehicle-model').value = '';
                document.getElementById('new-vehicle-plate').value = '';
                document.getElementById('new-vehicle-year').value = '';
                document.getElementById('new-vehicle-seats').value = '4';
                document.getElementById('new-vehicle-fuel').value = '';
                document.getElementById('new-vehicle-spec').value = '';
            }
        }
        
        function addVehicle() {
            const model = document.getElementById('new-vehicle-model').value.trim();
            const plate = document.getElementById('new-vehicle-plate').value.trim();
            const year = document.getElementById('new-vehicle-year').value;
            const seats = document.getElementById('new-vehicle-seats').value;
            const fuel = document.getElementById('new-vehicle-fuel').value;
            const specFile = document.getElementById('new-vehicle-spec').files[0];
            
            if (!model || !plate || !fuel) {
                alert('Veuillez remplir tous les champs obligatoires');
                return;
            }
            
            if (specFile && specFile.size > 5 * 1024 * 1024) {
                alert('La fiche technique ne doit pas depasser 5MB');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'add_vehicle');
            formData.append('model', model);
            formData.append('plate', plate);
            formData.append('year', year);
            formData.append('seats', seats);
            formData.append('fuel_type', fuel);
            if (specFile) {
                formData.append('spec_file', specFile);
            }
            
            fetch('/DriveUs/Outils/vehicle_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Vehicule ajoute avec succes');
                    toggleAddVehicleForm();
                    loadVehicles();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'ajout du vehicule');
            });
        }
        
        function viewVehicleSpec(vehicleId, specFile) {
            window.open('/DriveUs/Outils/Permis/vehicles/' + specFile, '_blank');
        }
        
        function deleteVehicle(vehicleId) {
            if (!confirm('Etes-vous sur de vouloir supprimer ce vehicule ?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_vehicle');
            formData.append('vehicle_id', vehicleId);
            
            fetch('/DriveUs/Outils/vehicle_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadVehicles();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la suppression');
            });
        }
        
        function loadCards() {
            fetch('/DriveUs/Outils/payment_handler.php?action=get_cards')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('cards-list');
                    
                    if (!data.success || data.cards.length === 0) {
                        container.innerHTML = '<div class="no-cards">Aucune carte enregistree</div>';
                        return;
                    }
                    
                    let html = '';
                    data.cards.forEach(card => {
                        const isDefault = card.is_default == 1;
                        const cardIcon = getCardIcon(card.card_brand);
                        html += `
                            <div class="card-item ${isDefault ? 'default' : ''}">
                                <div class="card-info">
                                    <span class="card-icon">${cardIcon}</span>
                                    <div class="card-details">
                                        <div class="card-brand">${card.card_brand}</div>
                                        <div class="card-number">**** **** **** ${card.last4}</div>
                                        <div class="card-expiry">Expire: ${String(card.exp_month).padStart(2, '0')}/${card.exp_year}</div>
                                    </div>
                                </div>
                                <div class="card-actions">
                                    ${isDefault ? 
                                        '<span class="badge-default">Par defaut</span>' : 
                                        `<button type="button" class="btn-set-default" onclick="setDefaultCard(${card.id})">Definir par defaut</button>`
                                    }
                                    <button type="button" class="btn-delete-card" onclick="deleteCard(${card.id})">Supprimer</button>
                                </div>
                            </div>
                        `;
                    });
                    
                    container.innerHTML = html;
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    document.getElementById('cards-list').innerHTML = '<div class="error-cards">Erreur de chargement</div>';
                });
        }
        
        function getCardIcon(brand) {
            const icons = {
                'Visa': 'V',
                'MasterCard': 'M',
                'Amex': 'A',
                'Discover': 'D',
                'Unknown': 'C'
            };
            return icons[brand] || 'C';
        }
        
        function toggleAddCardForm() {
            const form = document.getElementById('add-card-form');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            
            if (form.style.display === 'none') {
                document.getElementById('new-card-number').value = '';
                document.getElementById('new-card-month').value = '';
                document.getElementById('new-card-year').value = '';
                document.getElementById('new-card-cvv').value = '';
                document.getElementById('card-feedback').textContent = '';
                document.getElementById('card-feedback').className = 'card-feedback';
                document.getElementById('cvv-feedback').textContent = '';
                document.getElementById('cvv-feedback').className = 'cvv-feedback';
            }
        }
        
        function addCard() {
            const cardNumber = document.getElementById('new-card-number').value.replace(/[\s-]/g, '');
            const expMonth = document.getElementById('new-card-month').value;
            const expYear = document.getElementById('new-card-year').value;
            const cvv = document.getElementById('new-card-cvv').value;
            
            if (!cardNumber || cardNumber.length < 13 || cardNumber.length > 19) {
                alert('Veuillez entrer un numero de carte valide');
                return;
            }
            
            if (!expMonth || !expYear) {
                alert('Veuillez selectionner la date d\'expiration');
                return;
            }
            
            if (!cvv || cvv.length < 3 || cvv.length > 4) {
                alert('Veuillez entrer un CVV valide (3 ou 4 chiffres)');
                return;
            }
            
            const now = new Date();
            const expDate = new Date(expYear, expMonth - 1);
            if (expDate < now) {
                alert('Cette carte est expiree');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'add_card');
            formData.append('card_number', cardNumber);
            formData.append('exp_month', expMonth);
            formData.append('exp_year', expYear);
            formData.append('cvv', cvv);
            
            fetch('/DriveUs/Outils/payment_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Carte ajoutee avec succes');
                    toggleAddCardForm();
                    loadCards();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'ajout de la carte');
            });
        }
        
        function deleteCard(cardId) {
            if (!confirm('Etes-vous sur de vouloir supprimer cette carte ?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_card');
            formData.append('card_id', cardId);
            
            fetch('/DriveUs/Outils/payment_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadCards();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la suppression');
            });
        }
        
        function setDefaultCard(cardId) {
            const formData = new FormData();
            formData.append('action', 'set_default');
            formData.append('card_id', cardId);
            
            fetch('/DriveUs/Outils/payment_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadCards();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la mise a jour');
            });
        }
    </script>

    <?php include 'Outils/views/footer.php'; ?>
</body>
</html>

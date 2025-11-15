<?php
// reservation.php
session_start();
include 'includes/header.php';
include 'config/database.php';

// Taux de conversion EUR vers FCFA
$taux_fcfa = 655;

// Fonction pour obtenir des valeurs sécurisées
function getChambreValue($chambre, $key, $default = '') {
    return isset($chambre[$key]) ? $chambre[$key] : $default;
}

// Vérifier la structure de la table reservations
try {
    $stmt = $pdo->query("DESCRIBE reservations");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $columns = [];
}

// Récupérer les chambres disponibles
try {
    $stmt = $pdo->query("SELECT * FROM chambres WHERE disponibilite = 1 ORDER BY prix_nuit ASC");
    $chambres = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $chambres = [];
    $_SESSION['error'] = "Erreur lors du chargement des chambres: " . $e->getMessage();
}

// Traitement du formulaire de réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_client = htmlspecialchars(trim($_POST['nom_client']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $telephone = htmlspecialchars(trim($_POST['telephone']));
    $type_reservation = $_POST['type_reservation'] ?? 'nuit';
    $type_chambre = $_POST['type_chambre'];
    $nombre_personnes = $_POST['nombre_personnes'];
    $message_special = htmlspecialchars(trim($_POST['message_special'] ?? ''));
    
    // Variables pour les dates et heures
    $date_arrivee = $_POST['date_arrivee'];
    $date_depart = $_POST['date_depart'] ?? $date_arrivee;
    $heure_arrivee = isset($_POST['heure_arrivee']) ? $_POST['heure_arrivee'] . ':00' : '14:00:00';
    $heure_depart = isset($_POST['heure_depart']) ? $_POST['heure_depart'] . ':00' : '12:00:00';
    
    // Validation des données
    $errors = [];
    
    if (empty($nom_client)) $errors[] = "Le nom complet est requis";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'email est invalide";
    if (empty($telephone)) $errors[] = "Le téléphone est requis";
    if (empty($date_arrivee)) $errors[] = "La date d'arrivée est requise";
    if (empty($type_chambre)) $errors[] = "Le type de chambre est requis";
    
    if (empty($errors)) {
        // Trouver la chambre sélectionnée
        $chambre_selectionnee = null;
        foreach($chambres as $chambre) {
            if ($chambre['id'] == $type_chambre) {
                $chambre_selectionnee = $chambre;
                break;
            }
        }
        
        if ($chambre_selectionnee) {
            // Calcul du prix total
            $prix_total = 0;
            $nuits = 1;
            
            if ($type_reservation === 'nuit') {
                // Calcul pour nuitée complète
                if (!empty($date_depart) && $date_depart != $date_arrivee) {
                    $date1 = new DateTime($date_arrivee);
                    $date2 = new DateTime($date_depart);
                    $interval = $date1->diff($date2);
                    $nuits = $interval->days;
                }
                
                $prix_nuit = getChambreValue($chambre_selectionnee, 'prix_nuit', 0);
                $prix_total = $nuits * $prix_nuit * $taux_fcfa;
            } else if ($type_reservation === 'jour') {
                // Calcul pour réservation journée
                $prix_jour = getChambreValue($chambre_selectionnee, 'prix_jour', getChambreValue($chambre_selectionnee, 'prix_nuit', 0) * 0.6);
                $prix_total = $prix_jour * $taux_fcfa;
                $date_depart = $date_arrivee;
            }
            
            if ($prix_total > 0) {
                try {
                    // Préparer la requête d'insertion en fonction des colonnes disponibles
                    $columns_available = [
                        'nom_client', 'email', 'telephone', 'date_arrivee', 'date_depart', 
                        'type_chambre', 'nombre_personnes', 'prix_total'
                    ];
                    
                    // Ajouter les colonnes optionnelles si elles existent
                    if (in_array('type_reservation', $columns)) {
                        $columns_available[] = 'type_reservation';
                    }
                    if (in_array('heure_arrivee', $columns)) {
                        $columns_available[] = 'heure_arrivee';
                    }
                    if (in_array('heure_depart', $columns)) {
                        $columns_available[] = 'heure_depart';
                    }
                    if (in_array('message_special', $columns)) {
                        $columns_available[] = 'message_special';
                    }
                    
                    $placeholders = str_repeat('?,', count($columns_available) - 1) . '?';
                    $sql = "INSERT INTO reservations (" . implode(', ', $columns_available) . ") VALUES ($placeholders)";
                    
                    $stmt = $pdo->prepare($sql);
                    
                    // Préparer les valeurs
                    $values = [
                        $nom_client, $email, $telephone, $date_arrivee, $date_depart,
                        $chambre_selectionnee['type_chambre'], $nombre_personnes, $prix_total
                    ];
                    
                    // Ajouter les valeurs optionnelles
                    if (in_array('type_reservation', $columns)) {
                        $values[] = $type_reservation;
                    }
                    if (in_array('heure_arrivee', $columns)) {
                        $values[] = $heure_arrivee;
                    }
                    if (in_array('heure_depart', $columns)) {
                        $values[] = $heure_depart;
                    }
                    if (in_array('message_special', $columns)) {
                        $values[] = $message_special;
                    }
                    
                    if ($stmt->execute($values)) {
                        $reservation_id = $pdo->lastInsertId();
                        $_SESSION['success'] = "
                            <h5 class='mb-3'><i class='fas fa-check-circle text-success me-2'></i>Réservation confirmée !</h5>
                            <p class='mb-2'>Votre réservation a été enregistrée avec succès.</p>
                            <p class='mb-2'><strong>Référence :</strong> RES-" . str_pad($reservation_id, 6, '0', STR_PAD_LEFT) . "</p>
                            <p class='mb-0'>Nous vous contacterons dans les plus brefs délais pour confirmation.</p>
                        ";
                        
                        // Redirection pour éviter le re-soumission
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    } else {
                        $_SESSION['error'] = "Une erreur s'est produite lors de l'enregistrement de votre réservation.";
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    }
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }
            } else {
                $_SESSION['error'] = "Veuillez vérifier les dates sélectionnées.";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $_SESSION['error'] = "Chambre sélectionnée non valide.";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
        $_SESSION['form_data'] = $_POST;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Récupérer les données du formulaire depuis la session si elles existent
$nom_client = $_SESSION['form_data']['nom_client'] ?? '';
$email = $_SESSION['form_data']['email'] ?? '';
$telephone = $_SESSION['form_data']['telephone'] ?? '';
$type_reservation = $_SESSION['form_data']['type_reservation'] ?? 'nuit';
$type_chambre = $_SESSION['form_data']['type_chambre'] ?? '';
$nombre_personnes = $_SESSION['form_data']['nombre_personnes'] ?? '';
$message_special = $_SESSION['form_data']['message_special'] ?? '';
$date_arrivee = $_SESSION['form_data']['date_arrivee'] ?? '';
$date_depart = $_SESSION['form_data']['date_depart'] ?? '';
$heure_arrivee = $_SESSION['form_data']['heure_arrivee'] ?? '14:00';
$heure_depart = $_SESSION['form_data']['heure_depart'] ?? '12:00';

// Nettoyer les données de session après utilisation
unset($_SESSION['form_data']);

// Pré-remplir le formulaire si une chambre est spécifiée dans l'URL
$chambre_pre_selectionnee = isset($_GET['chambre']) ? intval($_GET['chambre']) : null;
if ($chambre_pre_selectionnee && empty($type_chambre)) {
    $type_chambre = $chambre_pre_selectionnee;
}
?>

<section class="reservation-section">
    <!-- Hero Section Réservation -->
    <div class="reservation-hero bg-primary text-white">
        <div class="container">
            <div class="row align-items-center min-vh-50">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="hero-title display-4 fw-bold mb-4">Réserver Votre Séjour</h1>
                    <p class="hero-subtitle lead mb-4">Remplissez le formulaire ci-dessous pour réserver votre chambre chez Hôtel Deluxe</p>
                    
                    <!-- Étapes de réservation -->
                    <div class="reservation-steps">
                        <div class="steps-container">
                            <div class="step active">
                                <div class="step-number">1</div>
                                <div class="step-label">Informations</div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div class="step-label">Paiement</div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div class="step-label">Confirmation</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Vague décorative -->
        <div class="wave-divider">
            <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25" class="shape-fill"></path>
                <path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" opacity=".5" class="shape-fill"></path>
                <path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" class="shape-fill"></path>
            </svg>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Alertes -->
                <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show mt-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle fa-2x me-3"></i>
                        <div><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mt-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="reservation-card mt-4">
                    <form method="POST" action="reservation.php" class="needs-validation" novalidate>
                        <div class="card-body p-4">
                            <!-- Section Informations Personnelles -->
                            <div class="reservation-section-card mb-5">
                                <div class="section-header mb-4">
                                    <h4 class="section-title">
                                        <i class="fas fa-user-circle me-2 text-primary"></i>
                                        Informations Personnelles
                                    </h4>
                                    <p class="section-subtitle">Vos coordonnées pour la réservation</p>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nom_client" class="form-label">
                                                <i class="fas fa-user me-2 text-muted"></i>Nom complet *
                                            </label>
                                            <input type="text" class="form-control form-control-lg" id="nom_client" name="nom_client" 
                                                   value="<?php echo htmlspecialchars($nom_client); ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                Veuillez entrer votre nom complet.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email" class="form-label">
                                                <i class="fas fa-envelope me-2 text-muted"></i>Email *
                                            </label>
                                            <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($email); ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                Veuillez entrer une adresse email valide.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="telephone" class="form-label">
                                                <i class="fas fa-phone me-2 text-muted"></i>Téléphone *
                                            </label>
                                            <input type="tel" class="form-control form-control-lg" id="telephone" name="telephone" 
                                                   value="<?php echo htmlspecialchars($telephone); ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                Veuillez entrer votre numéro de téléphone.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nombre_personnes" class="form-label">
                                                <i class="fas fa-users me-2 text-muted"></i>Nombre de personnes *
                                            </label>
                                            <select class="form-select form-select-lg" id="nombre_personnes" name="nombre_personnes" required>
                                                <option value="">Sélectionnez...</option>
                                                <option value="1" <?php echo ($nombre_personnes == '1') ? 'selected' : ''; ?>>1 personne</option>
                                                <option value="2" <?php echo ($nombre_personnes == '2') ? 'selected' : ''; ?>>2 personnes</option>
                                                <option value="3" <?php echo ($nombre_personnes == '3') ? 'selected' : ''; ?>>3 personnes</option>
                                                <option value="4" <?php echo ($nombre_personnes == '4') ? 'selected' : ''; ?>>4 personnes</option>
                                            </select>
                                            <div class="invalid-feedback">
                                                Veuillez sélectionner le nombre de personnes.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section Détails de la Réservation -->
                            <div class="reservation-section-card mb-5">
                                <div class="section-header mb-4">
                                    <h4 class="section-title">
                                        <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                        Détails de la Réservation
                                    </h4>
                                    <p class="section-subtitle">Choisissez vos dates et type de séjour</p>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="type_reservation" class="form-label">
                                                <i class="fas fa-moon me-2 text-muted"></i>Type de réservation *
                                            </label>
                                            <select class="form-select form-select-lg" id="type_reservation" name="type_reservation" required>
                                                <option value="">Sélectionnez...</option>
                                                <option value="nuit" <?php echo ($type_reservation == 'nuit') ? 'selected' : 'selected'; ?>>Nuitée complète</option>
                                                <option value="jour" <?php echo ($type_reservation == 'jour') ? 'selected' : ''; ?>>Réservation journée</option>
                                            </select>
                                            <div class="invalid-feedback">
                                                Veuillez sélectionner le type de réservation.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="type_chambre" class="form-label">
                                                <i class="fas fa-bed me-2 text-muted"></i>Type de chambre *
                                            </label>
                                            <select class="form-select form-select-lg" id="type_chambre" name="type_chambre" required>
                                                <option value="">Sélectionnez une chambre...</option>
                                                <?php foreach($chambres as $chambre): 
                                                    $prix_nuit = getChambreValue($chambre, 'prix_nuit', 0);
                                                    $prix_jour = getChambreValue($chambre, 'prix_jour', $prix_nuit * 0.6);
                                                    $prix_nuit_fcfa = $prix_nuit * $taux_fcfa;
                                                    $prix_jour_fcfa = $prix_jour * $taux_fcfa;
                                                ?>
                                                <option value="<?php echo $chambre['id']; ?>" 
                                                        data-prix-nuit="<?php echo $prix_nuit_fcfa; ?>" 
                                                        data-prix-jour="<?php echo $prix_jour_fcfa; ?>"
                                                        <?php echo ($type_chambre == $chambre['id'] || $chambre_pre_selectionnee == $chambre['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($chambre['type_chambre']); ?> - 
                                                    <?php echo number_format($prix_nuit_fcfa, 0, ',', ' '); ?> FCFA/nuit
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="invalid-feedback">
                                                Veuillez sélectionner un type de chambre.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Champs dynamiques selon le type de réservation -->
                                <div id="nuit-fields" class="reservation-fields mt-4">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="date_arrivee" class="form-label">
                                                    <i class="fas fa-sign-in-alt me-2 text-muted"></i>Date d'arrivée *
                                                </label>
                                                <input type="date" class="form-control form-control-lg" id="date_arrivee" name="date_arrivee" 
                                                       value="<?php echo htmlspecialchars($date_arrivee); ?>"
                                                       min="<?php echo date('Y-m-d'); ?>">
                                                <div class="invalid-feedback">
                                                    Veuillez sélectionner une date d'arrivée.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="date_depart" class="form-label">
                                                    <i class="fas fa-sign-out-alt me-2 text-muted"></i>Date de départ *
                                                </label>
                                                <input type="date" class="form-control form-control-lg" id="date_depart" name="date_depart" 
                                                       value="<?php echo htmlspecialchars($date_depart); ?>"
                                                       min="<?php echo date('Y-m-d'); ?>">
                                                <div class="invalid-feedback">
                                                    Veuillez sélectionner une date de départ.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="jour-fields" class="reservation-fields mt-4" style="display: none;">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="date_jour" class="form-label">
                                                    <i class="fas fa-calendar-day me-2 text-muted"></i>Date *
                                                </label>
                                                <input type="date" class="form-control form-control-lg" id="date_jour" name="date_arrivee" 
                                                       value="<?php echo htmlspecialchars($date_arrivee); ?>"
                                                       min="<?php echo date('Y-m-d'); ?>">
                                                <div class="invalid-feedback">
                                                    Veuillez sélectionner une date.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="heure_arrivee" class="form-label">
                                                    <i class="fas fa-clock me-2 text-muted"></i>Heure d'arrivée *
                                                </label>
                                                <input type="time" class="form-control form-control-lg" id="heure_arrivee" name="heure_arrivee" 
                                                       min="08:00" max="18:00" 
                                                       value="<?php echo htmlspecialchars($heure_arrivee); ?>">
                                                <div class="invalid-feedback">
                                                    Veuillez sélectionner une heure d'arrivée entre 08:00 et 18:00.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="heure_depart" class="form-label">
                                                    <i class="fas fa-clock me-2 text-muted"></i>Heure de départ *
                                                </label>
                                                <input type="time" class="form-control form-control-lg" id="heure_depart" name="heure_depart" 
                                                       min="10:00" max="22:00" 
                                                       value="<?php echo htmlspecialchars($heure_depart); ?>">
                                                <div class="invalid-feedback">
                                                    Veuillez sélectionner une heure de départ entre 10:00 et 22:00.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section Demandes Spéciales -->
                            <div class="reservation-section-card mb-5">
                                <div class="section-header mb-4">
                                    <h4 class="section-title">
                                        <i class="fas fa-comment-dots me-2 text-primary"></i>
                                        Demandes Spéciales
                                    </h4>
                                    <p class="section-subtitle">Précisez vos besoins particuliers (optionnel)</p>
                                </div>
                                
                                <div class="form-group">
                                    <label for="message_special" class="form-label">
                                        <i class="fas fa-edit me-2 text-muted"></i>Demandes particulières
                                    </label>
                                    <textarea class="form-control form-control-lg" id="message_special" name="message_special" 
                                              rows="4" placeholder="Précisez ici vos demandes particulières (régime alimentaire, anniversaire, préférences spéciales, etc.)"><?php echo htmlspecialchars($message_special); ?></textarea>
                                </div>
                            </div>

                            <!-- Résumé de la Réservation -->
                            <div class="reservation-section-card mb-5">
                                <div class="section-header mb-4">
                                    <h4 class="section-title">
                                        <i class="fas fa-receipt me-2 text-primary"></i>
                                        Récapitulatif de la Réservation
                                    </h4>
                                </div>
                                
                                <div id="resume-reservation" class="reservation-summary">
                                    <div class="summary-placeholder text-center py-5">
                                        <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Votre réservation s'affichera ici</p>
                                    </div>
                                    <div id="details-reservation" class="summary-details" style="display: none;"></div>
                                    <div id="prix-total" class="summary-total" style="display: none;"></div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="reservation-actions">
                                <div class="row">
                                    <div class="col-md-6">
                                        <button type="reset" class="btn btn-outline-secondary btn-lg w-100">
                                            <i class="fas fa-redo me-2"></i>Réinitialiser
                                        </button>
                                    </div>
                                    <div class="col-md-6">
                                        <button type="submit" class="btn btn-primary btn-lg w-100">
                                            <i class="fas fa-paper-plane me-2"></i>Confirmer la Réservation
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Informations importantes -->
                            <div class="reservation-info mt-4">
                                <div class="alert alert-info">
                                    <h6 class="alert-heading mb-3">
                                        <i class="fas fa-info-circle me-2"></i>Informations importantes
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <ul class="mb-0">
                                                <li>Check-in à partir de 14h00</li>
                                                <li>Check-out avant 12h00</li>
                                                <li>Annulation gratuite jusqu'à 48h avant l'arrivée</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <ul class="mb-0">
                                                <li>Paiement sécurisé lors de votre séjour</li>
                                                <li>Wi-Fi gratuit dans tout l'hôtel</li>
                                                <li>Parking sécurisé disponible</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.reservation-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
}

.reservation-hero {
    position: relative;
    padding: 100px 0 150px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.min-vh-50 {
    min-height: 50vh;
}

.hero-title {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
}

.hero-subtitle {
    font-size: 1.25rem;
    opacity: 0.9;
    line-height: 1.6;
}

.reservation-steps {
    margin-top: 3rem;
}

.steps-container {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 2rem;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.step-number {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.2rem;
    border: 2px solid rgba(255,255,255,0.3);
}

.step.active .step-number {
    background: #ffd700;
    color: #2c3e50;
    border-color: #ffd700;
}

.step-label {
    color: rgba(255,255,255,0.9);
    font-size: 0.9rem;
    font-weight: 500;
}

.step.active .step-label {
    color: #ffd700;
    font-weight: 600;
}

.wave-divider {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    overflow: hidden;
    line-height: 0;
}

.wave-divider svg {
    position: relative;
    display: block;
    width: calc(100% + 1.3px);
    height: 120px;
}

.wave-divider .shape-fill {
    fill: #f8f9fa;
}

.reservation-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.1);
    margin-bottom: 3rem;
}

.reservation-section-card {
    padding: 2rem;
    border: 2px solid #f8f9fa;
    border-radius: 15px;
    background: #fff;
    transition: all 0.3s ease;
}

.reservation-section-card:hover {
    border-color: #667eea;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
}

.section-header {
    border-bottom: 2px solid #f8f9fa;
    padding-bottom: 1rem;
}

.section-title {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.section-subtitle {
    color: #6c757d;
    margin-bottom: 0;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.form-control-lg, .form-select-lg {
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.form-control-lg:focus, .form-select-lg:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.1);
}

.reservation-fields {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 10px;
    border-left: 4px solid #667eea;
}

.reservation-summary {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 2rem;
    border: 2px dashed #dee2e6;
}

.summary-placeholder {
    color: #6c757d;
}

.summary-details {
    margin-bottom: 1.5rem;
}

.summary-total {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    border-left: 4px solid #667eea;
    font-size: 1.5rem;
    font-weight: 700;
    color: #667eea;
    text-align: center;
}

.reservation-actions {
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 15px;
}

.btn-lg {
    padding: 15px 30px;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .reservation-hero {
        padding: 80px 0 120px;
    }
    
    .hero-title {
        font-size: 2.5rem;
    }
    
    .hero-subtitle {
        font-size: 1.1rem;
    }
    
    .steps-container {
        gap: 1rem;
    }
    
    .step-number {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .step-label {
        font-size: 0.8rem;
    }
    
    .reservation-section-card {
        padding: 1.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du type de réservation
    const typeReservationSelect = document.getElementById('type_reservation');
    const nuitFields = document.getElementById('nuit-fields');
    const jourFields = document.getElementById('jour-fields');
    const dateArriveeNuit = document.getElementById('date_arrivee');
    const dateDepartNuit = document.getElementById('date_depart');
    const dateJour = document.getElementById('date_jour');
    const heureArrivee = document.getElementById('heure_arrivee');
    const heureDepart = document.getElementById('heure_depart');

    function toggleReservationFields() {
        const typeReservation = typeReservationSelect.value;
        
        if (typeReservation === 'nuit') {
            nuitFields.style.display = 'block';
            jourFields.style.display = 'none';
            // Rendre les champs nuit obligatoires
            if (dateArriveeNuit) dateArriveeNuit.required = true;
            if (dateDepartNuit) dateDepartNuit.required = true;
            if (dateJour) dateJour.required = false;
            if (heureArrivee) heureArrivee.required = false;
            if (heureDepart) heureDepart.required = false;
        } else if (typeReservation === 'jour') {
            nuitFields.style.display = 'none';
            jourFields.style.display = 'block';
            // Rendre les champs jour obligatoires
            if (dateArriveeNuit) dateArriveeNuit.required = false;
            if (dateDepartNuit) dateDepartNuit.required = false;
            if (dateJour) dateJour.required = true;
            if (heureArrivee) heureArrivee.required = true;
            if (heureDepart) heureDepart.required = true;
            
            // Synchroniser la date jour avec date arrivee nuit
            if (dateArriveeNuit && dateArriveeNuit.value) {
                dateJour.value = dateArriveeNuit.value;
            }
        }
        calculerPrix();
    }

    // Synchronisation des dates
    if (dateArriveeNuit && dateJour) {
        dateArriveeNuit.addEventListener('change', function() {
            if (typeReservationSelect.value === 'jour') {
                dateJour.value = this.value;
            }
            calculerPrix();
        });
        
        dateJour.addEventListener('change', function() {
            if (typeReservationSelect.value === 'nuit') {
                dateArriveeNuit.value = this.value;
            }
            calculerPrix();
        });
    }

    if (typeReservationSelect) {
        typeReservationSelect.addEventListener('change', toggleReservationFields);
        toggleReservationFields(); // Initial call
    }

    // Calcul du prix
    function calculerPrix() {
        const typeChambreSelect = document.getElementById('type_chambre');
        const resumeReservation = document.getElementById('resume-reservation');
        const detailsReservation = document.getElementById('details-reservation');
        const prixTotal = document.getElementById('prix-total');
        const placeholder = resumeReservation.querySelector('.summary-placeholder');

        if (typeChambreSelect.value && typeReservationSelect.value) {
            let total = 0;
            let details = '';
            const selectedOption = typeChambreSelect.selectedOptions[0];
            const nomChambre = selectedOption.text.split(' - ')[0];

            if (typeReservationSelect.value === 'nuit') {
                if (dateArriveeNuit.value && dateDepartNuit.value) {
                    const dateArrivee = new Date(dateArriveeNuit.value);
                    const dateDepart = new Date(dateDepartNuit.value);
                    const differenceTemps = dateDepart.getTime() - dateArrivee.getTime();
                    const nuits = Math.ceil(differenceTemps / (1000 * 3600 * 24));
                    
                    if (nuits > 0) {
                        const prixNuit = parseFloat(selectedOption.dataset.prixNuit);
                        total = nuits * prixNuit;
                        details = `
                            <div class="row">
                                <div class="col-6"><strong>Chambre:</strong></div>
                                <div class="col-6">${nomChambre}</div>
                            </div>
                            <div class="row">
                                <div class="col-6"><strong>Type:</strong></div>
                                <div class="col-6">Nuitée complète</div>
                            </div>
                            <div class="row">
                                <div class="col-6"><strong>Durée:</strong></div>
                                <div class="col-6">${nuits} nuit(s)</div>
                            </div>
                            <div class="row">
                                <div class="col-6"><strong>Arrivée:</strong></div>
                                <div class="col-6">${new Date(dateArriveeNuit.value).toLocaleDateString('fr-FR')}</div>
                            </div>
                            <div class="row">
                                <div class="col-6"><strong>Départ:</strong></div>
                                <div class="col-6">${new Date(dateDepartNuit.value).toLocaleDateString('fr-FR')}</div>
                            </div>
                        `;
                    }
                }
            } else if (typeReservationSelect.value === 'jour') {
                if (dateJour.value && heureArrivee.value && heureDepart.value) {
                    const prixJour = parseFloat(selectedOption.dataset.prixJour);
                    total = prixJour;
                    details = `
                        <div class="row">
                            <div class="col-6"><strong>Chambre:</strong></div>
                            <div class="col-6">${nomChambre}</div>
                        </div>
                        <div class="row">
                            <div class="col-6"><strong>Type:</strong></div>
                            <div class="col-6">Réservation journée</div>
                        </div>
                        <div class="row">
                            <div class="col-6"><strong>Date:</strong></div>
                            <div class="col-6">${new Date(dateJour.value).toLocaleDateString('fr-FR')}</div>
                        </div>
                        <div class="row">
                            <div class="col-6"><strong>Heure arrivée:</strong></div>
                            <div class="col-6">${heureArrivee.value}</div>
                        </div>
                        <div class="row">
                            <div class="col-6"><strong>Heure départ:</strong></div>
                            <div class="col-6">${heureDepart.value}</div>
                        </div>
                    `;
                }
            }
            
            if (total > 0) {
                detailsReservation.innerHTML = details;
                prixTotal.textContent = `Total: ${total.toLocaleString('fr-FR')} FCFA`;
                detailsReservation.style.display = 'block';
                prixTotal.style.display = 'block';
                if (placeholder) placeholder.style.display = 'none';
            } else {
                detailsReservation.style.display = 'none';
                prixTotal.style.display = 'none';
                if (placeholder) placeholder.style.display = 'block';
            }
        } else {
            detailsReservation.style.display = 'none';
            prixTotal.style.display = 'none';
            if (placeholder) placeholder.style.display = 'block';
        }
    }

    // Événements pour le calcul du prix
    const elements = [typeChambreSelect, typeReservationSelect, dateArriveeNuit, dateDepartNuit, dateJour, heureArrivee, heureDepart];
    elements.forEach(element => {
        if (element) {
            element.addEventListener('change', calculerPrix);
        }
    });

    // Définir la date minimale pour les champs de date
    const today = new Date().toISOString().split('T')[0];
    [dateArriveeNuit, dateDepartNuit, dateJour].forEach(dateInput => {
        if (dateInput) {
            dateInput.min = today;
        }
    });

    // Empêcher la sélection de dates de départ antérieures aux dates d'arrivée
    if (dateArriveeNuit && dateDepartNuit) {
        dateArriveeNuit.addEventListener('change', function() {
            if (dateDepartNuit) {
                dateDepartNuit.min = this.value;
                if (dateDepartNuit.value && dateDepartNuit.value < this.value) {
                    dateDepartNuit.value = '';
                }
            }
            calculerPrix();
        });
    }

    // Validation des heures pour réservation journée
    if (heureArrivee && heureDepart) {
        heureArrivee.addEventListener('change', function() {
            if (this.value < '08:00') this.value = '08:00';
            if (this.value > '18:00') this.value = '18:00';
            
            // S'assurer que l'heure de départ est après l'heure d'arrivée
            const heureArriveeValue = this.value;
            if (heureDepart.value && heureDepart.value <= heureArriveeValue) {
                const [hours, minutes] = heureArriveeValue.split(':');
                const newDepartTime = `${String(parseInt(hours) + 2).padStart(2, '0')}:${minutes}`;
                heureDepart.value = newDepartTime > '22:00' ? '22:00' : newDepartTime;
            }
            calculerPrix();
        });

        heureDepart.addEventListener('change', function() {
            if (this.value < '10:00') this.value = '10:00';
            if (this.value > '22:00') this.value = '22:00';
            
            // S'assurer que l'heure d'arrivée est avant l'heure de départ
            const heureDepartValue = this.value;
            if (heureArrivee.value && heureArrivee.value >= heureDepartValue) {
                const [hours, minutes] = heureDepartValue.split(':');
                const newArriveeTime = `${String(parseInt(hours) - 2).padStart(2, '0')}:${minutes}`;
                heureArrivee.value = newArriveeTime < '08:00' ? '08:00' : newArriveeTime;
            }
            calculerPrix();
        });
    }

    // Calcul initial si des valeurs sont pré-remplies
    calculerPrix();
});
</script>

<?php include 'includes/footer.php'; ?>
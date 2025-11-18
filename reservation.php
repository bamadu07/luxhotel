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

// Vérifier et créer les colonnes manquantes si nécessaire
function verifierColonnesPrix($pdo) {
    $colonnes_requises = [
        'prix_jour_4h' => 'DECIMAL(10,2)',
        'prix_jour_6h' => 'DECIMAL(10,2)',
        'prix_jour_8h' => 'DECIMAL(10,2)',
        'prix_weekend' => 'DECIMAL(10,2)',
        'prix_semaine' => 'DECIMAL(10,2)',
        'prix_mois' => 'DECIMAL(10,2)'
    ];
    
    try {
        $stmt = $pdo->query("DESCRIBE chambres");
        $colonnes_existantes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($colonnes_requises as $colonne => $type) {
            if (!in_array($colonne, $colonnes_existantes)) {
                $pdo->exec("ALTER TABLE chambres ADD COLUMN $colonne $type");
                error_log("Colonne $colonne ajoutée à la table chambres");
            }
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la vérification des colonnes: " . $e->getMessage());
    }
}

// Vérifier et créer les colonnes manquantes
verifierColonnesPrix($pdo);

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

// Ajouter les chambres de journée manuellement si elles n'existent pas dans la base
$chambres_journee = [
    [
        'id' => 1001,
        'type_chambre' => 'Chambre Journée Standard',
        'prix_nuit' => 40000 / $taux_fcfa,
        'prix_jour' => 20000 / $taux_fcfa,
        'prix_jour_4h' => 15000 / $taux_fcfa,
        'prix_jour_6h' => 17000 / $taux_fcfa,
        'prix_jour_8h' => 19000 / $taux_fcfa,
        'capacite' => 2,
        'superficie' => 25,
        'equipements' => 'WiFi, TV, Climatisation, Salle de bain privée',
        'description' => 'Chambre confortable parfaite pour une réservation journée'
    ],
    [
        'id' => 1002,
        'type_chambre' => 'Chambre Journée Confort',
        'prix_nuit' => 45000 / $taux_fcfa,
        'prix_jour' => 25000 / $taux_fcfa,
        'prix_jour_4h' => 18000 / $taux_fcfa,
        'prix_jour_6h' => 21000 / $taux_fcfa,
        'prix_jour_8h' => 23000 / $taux_fcfa,
        'capacite' => 2,
        'superficie' => 28,
        'equipements' => 'WiFi, TV écran plat, Climatisation, Mini-bar, Salle de bain privée',
        'description' => 'Chambre spacieuse avec équipements premium pour votre confort'
    ],
    [
        'id' => 1003,
        'type_chambre' => 'Chambre Journée Familiale',
        'prix_nuit' => 50000 / $taux_fcfa,
        'prix_jour' => 30000 / $taux_fcfa,
        'prix_jour_4h' => 22000 / $taux_fcfa,
        'prix_jour_6h' => 26000 / $taux_fcfa,
        'prix_jour_8h' => 28000 / $taux_fcfa,
        'capacite' => 4,
        'superficie' => 35,
        'equipements' => 'WiFi, 2 TVs, Climatisation, Espace détente, Salle de bain privée',
        'description' => 'Chambre familiale spacieuse idéale pour les groupes'
    ],
    [
        'id' => 1004,
        'type_chambre' => 'Suite Journée Luxe',
        'prix_nuit' => 60000 / $taux_fcfa,
        'prix_jour' => 35000 / $taux_fcfa,
        'prix_jour_4h' => 25000 / $taux_fcfa,
        'prix_jour_6h' => 30000 / $taux_fcfa,
        'prix_jour_8h' => 33000 / $taux_fcfa,
        'capacite' => 2,
        'superficie' => 40,
        'equipements' => 'WiFi haut débit, TV 4K, Climatisation, Mini-bar premium, Jacuzzi, Vue panoramique',
        'description' => 'Suite de luxe avec jacuzzi pour une expérience exceptionnelle'
    ]
];

// Fusionner les chambres de la base de données avec les chambres de journée
$chambres = array_merge($chambres, $chambres_journee);

// Mettre à jour les prix des chambres existantes pour la nuit (35,000 à 50,000 FCFA)
foreach ($chambres as &$chambre) {
    // Si c'est une chambre normale (pas une chambre journée), ajuster les prix
    if ($chambre['id'] < 1000) {
        $prix_nuit_fcfa = rand(35000, 50000); // Prix aléatoire entre 35,000 et 50,000 FCFA
        $chambre['prix_nuit'] = $prix_nuit_fcfa / $taux_fcfa;
        $chambre['prix_jour'] = $chambre['prix_nuit'] * 0.6; // 60% du prix nuit
        $chambre['prix_jour_4h'] = $chambre['prix_nuit'] * 0.3; // 30% du prix nuit
        $chambre['prix_jour_6h'] = $chambre['prix_nuit'] * 0.4; // 40% du prix nuit
        $chambre['prix_jour_8h'] = $chambre['prix_nuit'] * 0.5; // 50% du prix nuit
    }
}
unset($chambre); // Déréférencer pour éviter les effets de bord

// Images par défaut pour les chambres
$images_chambres = [
    'default' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=500&h=300&fit=crop',
    'simple' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=500&h=300&fit=crop',
    'double' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=500&h=300&fit=crop',
    'suite' => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=500&h=300&fit=crop',
    'journee' => 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=500&h=300&fit=crop'
];

// Fonction pour obtenir l'image de la chambre
function getRoomImage($roomType, $images) {
    $type = strtolower($roomType);
    
    if (strpos($type, 'journée') !== false || strpos($type, 'journee') !== false) return $images['journee'];
    if (strpos($type, 'simple') !== false || strpos($type, 'standard') !== false) return $images['simple'];
    if (strpos($type, 'double') !== false || strpos($type, 'twin') !== false) return $images['double'];
    if (strpos($type, 'suite') !== false || strpos($type, 'présidentielle') !== false || strpos($type, 'luxe') !== false) return $images['suite'];
    
    return $images['default'];
}

// Fonction pour calculer le prix selon la période
function calculerPrixSelonPeriode($date_arrivee, $date_depart, $prix_nuit, $prix_weekend, $prix_semaine) {
    $total = 0;
    $date_debut = new DateTime($date_arrivee);
    $date_fin = new DateTime($date_depart);
    
    // Parcourir chaque jour de la réservation
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($date_debut, $interval, $date_fin);
    
    foreach ($period as $date) {
        $jour_semaine = $date->format('N'); // 1 (lundi) à 7 (dimanche)
        $est_weekend = ($jour_semaine >= 6); // Samedi (6) et dimanche (7)
        
        if ($est_weekend) {
            $total += $prix_weekend;
        } else {
            $total += $prix_nuit;
        }
    }
    
    return $total;
}

// Fonction pour calculer le prix journée selon la durée
function calculerPrixJournee($heure_arrivee, $heure_depart, $prix_jour, $prix_jour_4h, $prix_jour_6h, $prix_jour_8h) {
    // Convertir les heures en minutes pour faciliter les calculs
    list($heure_a, $minute_a) = explode(':', $heure_arrivee);
    list($heure_d, $minute_d) = explode(':', $heure_depart);
    
    $minutes_arrivee = intval($heure_a) * 60 + intval($minute_a);
    $minutes_depart = intval($heure_d) * 60 + intval($minute_d);
    
    $duree_minutes = $minutes_depart - $minutes_arrivee;
    $duree_heures = $duree_minutes / 60;
    
    // Déterminer le forfait selon la durée
    if ($duree_heures <= 4) {
        return $prix_jour_4h; // Forfait 4 heures
    } elseif ($duree_heures <= 6) {
        return $prix_jour_6h; // Forfait 6 heures
    } elseif ($duree_heures <= 8) {
        return $prix_jour_8h; // Forfait 8 heures
    } else {
        return $prix_jour; // Forfait journée complète
    }
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
    $date_arrivee = $_POST['date_arrivee'] ?? '';
    $date_depart = $_POST['date_depart'] ?? $date_arrivee;
    $heure_arrivee = $_POST['heure_arrivee'] ?? '14:00';
    $heure_depart = $_POST['heure_depart'] ?? '12:00';
    
    // Validation des données
    $errors = [];
    
    if (empty($nom_client)) $errors[] = "Le nom complet est requis";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'email est invalide";
    if (empty($telephone)) $errors[] = "Le téléphone est requis";
    if (empty($type_chambre)) $errors[] = "Le type de chambre est requis";
    
    // Validation des dates BASÉE sur le type de réservation
    if ($type_reservation === 'nuit') {
        if (empty($date_arrivee)) $errors[] = "La date d'arrivée est requise pour une nuitée";
        if (empty($date_depart)) $errors[] = "La date de départ est requise pour une nuitée";
        
        if (!empty($date_arrivee) && !empty($date_depart)) {
            $date1 = new DateTime($date_arrivee);
            $date2 = new DateTime($date_depart);
            if ($date1 >= $date2) {
                $errors[] = "La date de départ doit être après la date d'arrivée";
            }
        }
    } else if ($type_reservation === 'jour') {
        if (empty($date_arrivee)) $errors[] = "La date est requise pour une réservation journée";
        if (empty($heure_arrivee)) $errors[] = "L'heure d'arrivée est requise";
        if (empty($heure_depart)) $errors[] = "L'heure de départ est requise";
        
        if (!empty($heure_arrivee) && !empty($heure_depart)) {
            if ($heure_arrivee >= $heure_depart) {
                $errors[] = "L'heure de départ doit être après l'heure d'arrivée";
            }
            
            // Vérifier la durée minimale (2 heures)
            $duree = strtotime($heure_depart) - strtotime($heure_arrivee);
            if ($duree < 7200) { // 2 heures en secondes
                $errors[] = "La durée minimale pour une réservation journée est de 2 heures";
            }
        }
    }
    
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
            $details_prix = '';
            
            if ($type_reservation === 'nuit') {
                // Calcul pour nuitée complète
                $date1 = new DateTime($date_arrivee);
                $date2 = new DateTime($date_depart);
                $interval = $date1->diff($date2);
                $nuits = $interval->days;
                
                $prix_nuit = getChambreValue($chambre_selectionnee, 'prix_nuit', 0);
                $prix_weekend = getChambreValue($chambre_selectionnee, 'prix_weekend', $prix_nuit * 1.2);
                
                // Calcul détaillé par jour
                $prix_total = calculerPrixSelonPeriode($date_arrivee, $date_depart, $prix_nuit, $prix_weekend, 0);
                $prix_total *= $taux_fcfa;
                
            } else if ($type_reservation === 'jour') {
                // Calcul pour réservation journée
                $prix_jour = getChambreValue($chambre_selectionnee, 'prix_jour', getChambreValue($chambre_selectionnee, 'prix_nuit', 0) * 0.6);
                $prix_jour_4h = getChambreValue($chambre_selectionnee, 'prix_jour_4h', getChambreValue($chambre_selectionnee, 'prix_nuit', 0) * 0.3);
                $prix_jour_6h = getChambreValue($chambre_selectionnee, 'prix_jour_6h', getChambreValue($chambre_selectionnee, 'prix_nuit', 0) * 0.4);
                $prix_jour_8h = getChambreValue($chambre_selectionnee, 'prix_jour_8h', getChambreValue($chambre_selectionnee, 'prix_nuit', 0) * 0.5);
                
                $prix_total = calculerPrixJournee($heure_arrivee, $heure_depart, $prix_jour, $prix_jour_4h, $prix_jour_6h, $prix_jour_8h);
                $prix_total *= $taux_fcfa;
                $date_depart = $date_arrivee; // Même date pour réservation journée
            }
            
            if ($prix_total > 0) {
                try {
                    // Préparer la requête d'insertion
                    $columns_available = [
                        'nom_client', 'email', 'telephone', 'date_arrivee', 'date_depart', 
                        'type_chambre', 'nombre_personnes', 'prix_total', 'type_reservation'
                    ];
                    
                    // Ajouter les colonnes optionnelles si elles existent
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
                        $chambre_selectionnee['type_chambre'], $nombre_personnes, $prix_total, $type_reservation
                    ];
                    
                    // Ajouter les valeurs optionnelles
                    if (in_array('heure_arrivee', $columns)) {
                        $values[] = $type_reservation === 'jour' ? $heure_arrivee . ':00' : '14:00:00';
                    }
                    if (in_array('heure_depart', $columns)) {
                        $values[] = $type_reservation === 'jour' ? $heure_depart . ':00' : '12:00:00';
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
                            <p class='mb-2'><strong>Montant :</strong> " . number_format($prix_total, 0, ',', ' ') . " FCFA</p>
                            <p class='mb-0'>Nous vous contacterons dans les plus brefs délais pour confirmation.</p>
                        ";
                        
                        // Redirection pour éviter le re-soumission
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    }
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
                }
            } else {
                $_SESSION['error'] = "Erreur dans le calcul du prix. Veuillez vérifier les dates sélectionnées.";
            }
        } else {
            $_SESSION['error'] = "Chambre sélectionnée non valide.";
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
        $_SESSION['form_data'] = $_POST;
    }
    
    // Redirection en cas d'erreur
    if (isset($_SESSION['error'])) {
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
$nombre_personnes = $_SESSION['form_data']['nombre_personnes'] ?? '2';
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

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation - Hôtel Deluxe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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

        .summary-price-details {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border-left: 4px solid #28a745;
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

        /* Styles pour les détails de la chambre */
        .room-details-card .card {
            border-radius: 15px;
            overflow: hidden;
        }

        .room-image-container {
            position: relative;
        }

        .room-details-overlay {
            position: absolute;
            top: 15px;
            right: 15px;
        }

        .room-badge {
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .room-features-details {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .room-pricing-details {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }

        .pricing-item {
            padding: 0.5rem 0;
        }

        .equipment-items {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .equipment-tag {
            background: #e9ecef;
            color: #495057;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            border: 1px solid #dee2e6;
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
</head>
<body>
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
        <div class="row">
            <!-- Colonne principale du formulaire -->
            <div class="col-lg-8">
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
                                                
                                                <!-- Chambres pour nuitée -->
                                                <optgroup label="🛌 Chambres Nuitée (35,000 - 50,000 FCFA)">
                                                <?php foreach($chambres as $chambre): 
                                                    if ($chambre['id'] < 1000) { // Chambres normales
                                                        $prix_nuit = getChambreValue($chambre, 'prix_nuit', 0);
                                                        $prix_jour = getChambreValue($chambre, 'prix_jour', $prix_nuit * 0.6);
                                                        $prix_jour_4h = getChambreValue($chambre, 'prix_jour_4h', $prix_nuit * 0.3);
                                                        $prix_jour_6h = getChambreValue($chambre, 'prix_jour_6h', $prix_nuit * 0.4);
                                                        $prix_jour_8h = getChambreValue($chambre, 'prix_jour_8h', $prix_nuit * 0.5);
                                                        $prix_nuit_fcfa = $prix_nuit * $taux_fcfa;
                                                        $prix_jour_fcfa = $prix_jour * $taux_fcfa;
                                                        $prix_jour_4h_fcfa = $prix_jour_4h * $taux_fcfa;
                                                        $prix_jour_6h_fcfa = $prix_jour_6h * $taux_fcfa;
                                                        $prix_jour_8h_fcfa = $prix_jour_8h * $taux_fcfa;
                                                ?>
                                                <option value="<?php echo $chambre['id']; ?>" 
                                                        data-prix-nuit="<?php echo $prix_nuit_fcfa; ?>" 
                                                        data-prix-jour="<?php echo $prix_jour_fcfa; ?>"
                                                        data-prix-jour-4h="<?php echo $prix_jour_4h_fcfa; ?>"
                                                        data-prix-jour-6h="<?php echo $prix_jour_6h_fcfa; ?>"
                                                        data-prix-jour-8h="<?php echo $prix_jour_8h_fcfa; ?>"
                                                        data-capacite="<?php echo $chambre['capacite']; ?>"
                                                        data-superficie="<?php echo getChambreValue($chambre, 'superficie', 25); ?>"
                                                        data-equipements="<?php echo htmlspecialchars(getChambreValue($chambre, 'equipements', 'WiFi, TV, Climatisation')); ?>"
                                                        data-description="<?php echo htmlspecialchars(getChambreValue($chambre, 'description', '')); ?>"
                                                        data-image="<?php echo getRoomImage($chambre['type_chambre'], $images_chambres); ?>"
                                                        <?php echo ($type_chambre == $chambre['id'] || $chambre_pre_selectionnee == $chambre['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($chambre['type_chambre']); ?> - 
                                                    <?php echo number_format($prix_nuit_fcfa, 0, ',', ' '); ?> FCFA/nuit
                                                </option>
                                                <?php } endforeach; ?>
                                                </optgroup>
                                                
                                                <!-- Chambres pour journée -->
                                                <optgroup label="☀️ Chambres Journée (20,000 - 35,000 FCFA)">
                                                <?php foreach($chambres as $chambre): 
                                                    if ($chambre['id'] >= 1000) { // Chambres journée
                                                        $prix_nuit = getChambreValue($chambre, 'prix_nuit', 0);
                                                        $prix_jour = getChambreValue($chambre, 'prix_jour', $prix_nuit * 0.6);
                                                        $prix_jour_4h = getChambreValue($chambre, 'prix_jour_4h', $prix_nuit * 0.3);
                                                        $prix_jour_6h = getChambreValue($chambre, 'prix_jour_6h', $prix_nuit * 0.4);
                                                        $prix_jour_8h = getChambreValue($chambre, 'prix_jour_8h', $prix_nuit * 0.5);
                                                        $prix_nuit_fcfa = $prix_nuit * $taux_fcfa;
                                                        $prix_jour_fcfa = $prix_jour * $taux_fcfa;
                                                        $prix_jour_4h_fcfa = $prix_jour_4h * $taux_fcfa;
                                                        $prix_jour_6h_fcfa = $prix_jour_6h * $taux_fcfa;
                                                        $prix_jour_8h_fcfa = $prix_jour_8h * $taux_fcfa;
                                                ?>
                                                <option value="<?php echo $chambre['id']; ?>" 
                                                        data-prix-nuit="<?php echo $prix_nuit_fcfa; ?>" 
                                                        data-prix-jour="<?php echo $prix_jour_fcfa; ?>"
                                                        data-prix-jour-4h="<?php echo $prix_jour_4h_fcfa; ?>"
                                                        data-prix-jour-6h="<?php echo $prix_jour_6h_fcfa; ?>"
                                                        data-prix-jour-8h="<?php echo $prix_jour_8h_fcfa; ?>"
                                                        data-capacite="<?php echo $chambre['capacite']; ?>"
                                                        data-superficie="<?php echo getChambreValue($chambre, 'superficie', 25); ?>"
                                                        data-equipements="<?php echo htmlspecialchars(getChambreValue($chambre, 'equipements', 'WiFi, TV, Climatisation')); ?>"
                                                        data-description="<?php echo htmlspecialchars(getChambreValue($chambre, 'description', '')); ?>"
                                                        data-image="<?php echo getRoomImage($chambre['type_chambre'], $images_chambres); ?>"
                                                        <?php echo ($type_chambre == $chambre['id'] || $chambre_pre_selectionnee == $chambre['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($chambre['type_chambre']); ?> - 
                                                    <?php echo number_format($prix_jour_fcfa, 0, ',', ' '); ?> FCFA/jour
                                                </option>
                                                <?php } endforeach; ?>
                                                </optgroup>
                                            </select>
                                            <div class="invalid-feedback">
                                                Veuillez sélectionner un type de chambre.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Champs pour NUITÉE -->
                                <div id="nuit-fields" class="reservation-fields mt-4">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="date_arrivee_nuit" class="form-label">
                                                    <i class="fas fa-sign-in-alt me-2 text-muted"></i>Date d'arrivée *
                                                </label>
                                                <input type="date" class="form-control form-control-lg date-nuit" id="date_arrivee_nuit" name="date_arrivee" 
                                                       value="<?php echo htmlspecialchars($date_arrivee); ?>"
                                                       min="<?php echo date('Y-m-d'); ?>" required>
                                                <div class="invalid-feedback">
                                                    Veuillez sélectionner une date d'arrivée.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="date_depart_nuit" class="form-label">
                                                    <i class="fas fa-sign-out-alt me-2 text-muted"></i>Date de départ *
                                                </label>
                                                <input type="date" class="form-control form-control-lg date-nuit" id="date_depart_nuit" name="date_depart" 
                                                       value="<?php echo htmlspecialchars($date_depart); ?>"
                                                       min="<?php echo date('Y-m-d'); ?>" required>
                                                <div class="invalid-feedback">
                                                    Veuillez sélectionner une date de départ.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Check-in à partir de 14h00 • Check-out avant 12h00
                                        </small>
                                    </div>
                                </div>

                                <!-- Champs pour JOURNÉE -->
                                <div id="jour-fields" class="reservation-fields mt-4" style="display: none;">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="date_jour" class="form-label">
                                                    <i class="fas fa-calendar-day me-2 text-muted"></i>Date *
                                                </label>
                                                <input type="date" class="form-control form-control-lg date-jour" id="date_jour" name="date_arrivee" 
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
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Réservation journée : 8h-22h • Minimum 2 heures • Forfaits disponibles : 4h, 6h, 8h, journée complète
                                        </small>
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
                                    <div id="details-prix" class="summary-price-details" style="display: none;"></div>
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
                        </div>
                    </form>
                </div>
            </div>

            <!-- Colonne latérale avec détails de la chambre -->
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 100px;">
                    <!-- Détails de la chambre sélectionnée -->
                    <div class="room-details-card mb-4">
                        <div class="card shadow-sm border-0">
                            <div id="room-details-image" class="room-image-container">
                                <img src="<?php echo $images_chambres['default']; ?>" 
                                     alt="Chambre" 
                                     class="card-img-top room-details-img"
                                     style="height: 200px; object-fit: cover;">
                                <div class="room-details-overlay">
                                    <span class="room-badge" id="room-category">Standard</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <h5 id="room-type" class="card-title fw-bold mb-3">Sélectionnez une chambre</h5>
                                
                                <div id="room-features" class="room-features-details">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><i class="fas fa-user-friends me-2 text-primary"></i> Capacité:</span>
                                        <span id="room-capacity" class="fw-semibold">-</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><i class="fas fa-ruler-combined me-2 text-primary"></i> Superficie:</span>
                                        <span id="room-size" class="fw-semibold">- m²</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span><i class="fas fa-bed me-2 text-primary"></i> Type:</span>
                                        <span id="room-bed" class="fw-semibold">-</span>
                                    </div>
                                </div>

                                <!-- Tarifs Nuitée -->
                                <div id="room-pricing-night" class="room-pricing-details mb-3">
                                    <h6 class="fw-semibold mb-2 text-primary">
                                        <i class="fas fa-moon me-2"></i>Tarifs Nuitée
                                    </h6>
                                    <div class="pricing-item d-flex justify-content-between mb-1">
                                        <small>Par nuit (semaine):</small>
                                        <small id="price-night" class="fw-bold">- FCFA</small>
                                    </div>
                                    <div class="pricing-item d-flex justify-content-between mb-1">
                                        <small>Par nuit (weekend):</small>
                                        <small id="price-weekend" class="fw-bold">- FCFA</small>
                                    </div>
                                </div>

                                <!-- Tarifs Journée -->
                                <div id="room-pricing-day" class="room-pricing-details">
                                    <h6 class="fw-semibold mb-2 text-success">
                                        <i class="fas fa-sun me-2"></i>Tarifs Journée
                                    </h6>
                                    <div class="pricing-item d-flex justify-content-between mb-1">
                                        <small>Forfait 4 heures:</small>
                                        <small id="price-day-4h" class="fw-bold">- FCFA</small>
                                    </div>
                                    <div class="pricing-item d-flex justify-content-between mb-1">
                                        <small>Forfait 6 heures:</small>
                                        <small id="price-day-6h" class="fw-bold">- FCFA</small>
                                    </div>
                                    <div class="pricing-item d-flex justify-content-between mb-1">
                                        <small>Forfait 8 heures:</small>
                                        <small id="price-day-8h" class="fw-bold">- FCFA</small>
                                    </div>
                                    <div class="pricing-item d-flex justify-content-between">
                                        <small>Journée complète:</small>
                                        <small id="price-day-full" class="fw-bold">- FCFA</small>
                                    </div>
                                </div>

                                <hr>

                                <div id="room-equipments" class="room-equipments">
                                    <h6 class="fw-semibold mb-3">
                                        <i class="fas fa-tv me-2 text-muted"></i>Équipements
                                    </h6>
                                    <div id="equipment-list" class="equipment-items">
                                        <p class="text-muted text-center">Sélectionnez une chambre pour voir les équipements</p>
                                    </div>
                                </div>

                                <div id="room-description" class="room-description mt-3" style="display: none;">
                                    <h6 class="fw-semibold mb-2">
                                        <i class="fas fa-info-circle me-2 text-muted"></i>Description
                                    </h6>
                                    <p id="room-desc-text" class="text-muted small"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informations importantes -->
                    <div class="reservation-info-card">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Informations importantes
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="info-item d-flex mb-3">
                                    <i class="fas fa-clock text-primary me-3 mt-1"></i>
                                    <div>
                                        <small class="fw-semibold d-block">Horaires</small>
                                        <small class="text-muted">Check-in: 14h00 • Check-out: 12h00</small>
                                    </div>
                                </div>
                                <div class="info-item d-flex mb-3">
                                    <i class="fas fa-credit-card text-primary me-3 mt-1"></i>
                                    <div>
                                        <small class="fw-semibold d-block">Paiement</small>
                                        <small class="text-muted">Paiement sécurisé lors de votre séjour</small>
                                    </div>
                                </div>
                                <div class="info-item d-flex mb-3">
                                    <i class="fas fa-wifi text-primary me-3 mt-1"></i>
                                    <div>
                                        <small class="fw-semibold d-block">Services inclus</small>
                                        <small class="text-muted">Wi-Fi gratuit • Parking • Petit-déjeuner</small>
                                    </div>
                                </div>
                                <div class="info-item d-flex">
                                    <i class="fas fa-ban text-primary me-3 mt-1"></i>
                                    <div>
                                        <small class="fw-semibold d-block">Annulation</small>
                                        <small class="text-muted">Gratuite jusqu'à 48h avant l'arrivée</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assistance -->
                    <div class="assistance-card mt-4">
                        <div class="card border-0 bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-headset fa-2x text-primary mb-3"></i>
                                <h6 class="fw-semibold">Besoin d'aide ?</h6>
                                <p class="text-muted small mb-3">Notre équipe est à votre disposition</p>
                                <div class="d-grid gap-2">
                                    <a href="tel:+221338640303" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-phone me-2"></i>Appeler
                                    </a>
                                    <a href="https://wa.me/221338640303" class="btn btn-outline-success btn-sm">
                                        <i class="fab fa-whatsapp me-2"></i>WhatsApp
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du type de réservation
    const typeReservationSelect = document.getElementById('type_reservation');
    const nuitFields = document.getElementById('nuit-fields');
    const jourFields = document.getElementById('jour-fields');
    const dateArriveeNuit = document.getElementById('date_arrivee_nuit');
    const dateDepartNuit = document.getElementById('date_depart_nuit');
    const dateJour = document.getElementById('date_jour');
    const heureArrivee = document.getElementById('heure_arrivee');
    const heureDepart = document.getElementById('heure_depart');
    const typeChambreSelect = document.getElementById('type_chambre');

    // Éléments pour les détails de la chambre
    const roomTypeElement = document.getElementById('room-type');
    const roomCapacityElement = document.getElementById('room-capacity');
    const roomSizeElement = document.getElementById('room-size');
    const roomBedElement = document.getElementById('room-bed');
    const priceNightElement = document.getElementById('price-night');
    const priceWeekendElement = document.getElementById('price-weekend');
    const priceDay4hElement = document.getElementById('price-day-4h');
    const priceDay6hElement = document.getElementById('price-day-6h');
    const priceDay8hElement = document.getElementById('price-day-8h');
    const priceDayFullElement = document.getElementById('price-day-full');
    const equipmentListElement = document.getElementById('equipment-list');
    const roomDescElement = document.getElementById('room-desc-text');
    const roomDescriptionContainer = document.getElementById('room-description');
    const roomImageElement = document.getElementById('room-details-image').querySelector('img');
    const roomCategoryElement = document.getElementById('room-category');

    function toggleReservationFields() {
        const typeReservation = typeReservationSelect.value;
        
        if (typeReservation === 'nuit') {
            nuitFields.style.display = 'block';
            jourFields.style.display = 'none';
            
            // Rendre les champs nuit obligatoires
            dateArriveeNuit.required = true;
            dateDepartNuit.required = true;
            dateJour.required = false;
            if (heureArrivee) heureArrivee.required = false;
            if (heureDepart) heureDepart.required = false;
            
        } else if (typeReservation === 'jour') {
            nuitFields.style.display = 'none';
            jourFields.style.display = 'block';
            
            // Rendre les champs jour obligatoires
            dateArriveeNuit.required = false;
            dateDepartNuit.required = false;
            dateJour.required = true;
            if (heureArrivee) heureArrivee.required = true;
            if (heureDepart) heureDepart.required = true;
            
            // Synchroniser la date
            if (dateArriveeNuit.value) {
                dateJour.value = dateArriveeNuit.value;
            }
        }
        calculerPrix();
    }

    // Mettre à jour les détails de la chambre
    function updateRoomDetails() {
        if (typeChambreSelect.value) {
            const selectedOption = typeChambreSelect.selectedOptions[0];
            
            // Mettre à jour les informations de base
            roomTypeElement.textContent = selectedOption.text.split(' - ')[0];
            roomCapacityElement.textContent = selectedOption.dataset.capacite + ' personnes';
            roomSizeElement.textContent = selectedOption.dataset.superficie + ' m²';
            roomBedElement.textContent = selectedOption.text.split(' - ')[0];
            
            // Mettre à jour les prix nuitée
            priceNightElement.textContent = parseFloat(selectedOption.dataset.prixNuit).toLocaleString('fr-FR') + ' FCFA';
            // Calcul du prix weekend (20% de plus)
            const prixWeekend = parseFloat(selectedOption.dataset.prixNuit) * 1.2;
            priceWeekendElement.textContent = prixWeekend.toLocaleString('fr-FR') + ' FCFA';
            
            // Mettre à jour les prix journée
            priceDay4hElement.textContent = parseFloat(selectedOption.dataset.prixJour4h).toLocaleString('fr-FR') + ' FCFA';
            priceDay6hElement.textContent = parseFloat(selectedOption.dataset.prixJour6h).toLocaleString('fr-FR') + ' FCFA';
            priceDay8hElement.textContent = parseFloat(selectedOption.dataset.prixJour8h).toLocaleString('fr-FR') + ' FCFA';
            priceDayFullElement.textContent = parseFloat(selectedOption.dataset.prixJour).toLocaleString('fr-FR') + ' FCFA';
            
            // Mettre à jour l'image
            roomImageElement.src = selectedOption.dataset.image;
            roomImageElement.alt = selectedOption.text.split(' - ')[0];
            
            // Mettre à jour la catégorie
            const roomType = selectedOption.text.split(' - ')[0].toLowerCase();
            if (roomType.includes('suite') || roomType.includes('luxe') || roomType.includes('présidentielle')) {
                roomCategoryElement.textContent = 'Luxe';
            } else if (roomType.includes('double')) {
                roomCategoryElement.textContent = 'Double';
            } else if (roomType.includes('journée') || roomType.includes('journee')) {
                roomCategoryElement.textContent = 'Journée';
            } else {
                roomCategoryElement.textContent = 'Standard';
            }
            
            // Mettre à jour les équipements
            const equipments = selectedOption.dataset.equipements.split(',');
            equipmentListElement.innerHTML = '';
            equipments.forEach(equipment => {
                if (equipment.trim()) {
                    const tag = document.createElement('span');
                    tag.className = 'equipment-tag';
                    tag.textContent = equipment.trim();
                    equipmentListElement.appendChild(tag);
                }
            });
            
            // Mettre à jour la description
            if (selectedOption.dataset.description) {
                roomDescElement.textContent = selectedOption.dataset.description;
                roomDescriptionContainer.style.display = 'block';
            } else {
                roomDescriptionContainer.style.display = 'none';
            }
        } else {
            // Réinitialiser les détails si aucune chambre sélectionnée
            roomTypeElement.textContent = 'Sélectionnez une chambre';
            roomCapacityElement.textContent = '-';
            roomSizeElement.textContent = '- m²';
            roomBedElement.textContent = '-';
            priceNightElement.textContent = '- FCFA';
            priceWeekendElement.textContent = '- FCFA';
            priceDay4hElement.textContent = '- FCFA';
            priceDay6hElement.textContent = '- FCFA';
            priceDay8hElement.textContent = '- FCFA';
            priceDayFullElement.textContent = '- FCFA';
            equipmentListElement.innerHTML = '<p class="text-muted text-center">Sélectionnez une chambre pour voir les équipements</p>';
            roomDescriptionContainer.style.display = 'none';
        }
    }

    // Synchronisation des dates
    if (dateArriveeNuit && dateJour) {
        dateArriveeNuit.addEventListener('change', function() {
            dateJour.value = this.value;
            if (dateDepartNuit && !dateDepartNuit.value) {
                // Définir la date de départ par défaut (1 jour après)
                const nextDay = new Date(this.value);
                nextDay.setDate(nextDay.getDate() + 1);
                dateDepartNuit.value = nextDay.toISOString().split('T')[0];
            }
            calculerPrix();
        });
        
        dateJour.addEventListener('change', function() {
            dateArriveeNuit.value = this.value;
            calculerPrix();
        });
    }

    if (typeReservationSelect) {
        typeReservationSelect.addEventListener('change', toggleReservationFields);
        toggleReservationFields(); // Initial call
    }

    if (typeChambreSelect) {
        typeChambreSelect.addEventListener('change', function() {
            updateRoomDetails();
            calculerPrix();
        });
        updateRoomDetails(); // Initial call
    }

    // Calcul du prix
    function calculerPrix() {
        const resumeReservation = document.getElementById('resume-reservation');
        const detailsReservation = document.getElementById('details-reservation');
        const detailsPrix = document.getElementById('details-prix');
        const prixTotal = document.getElementById('prix-total');
        const placeholder = resumeReservation.querySelector('.summary-placeholder');

        if (typeChambreSelect.value && typeReservationSelect.value) {
            let total = 0;
            let details = '';
            let detailsPrixHtml = '';
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
                        const prixWeekend = prixNuit * 1.2;
                        
                        // Calcul détaillé par jour
                        let prixDetaille = 0;
                        let detailsJours = '';
                        let joursSemaine = 0;
                        let joursWeekend = 0;
                        
                        const dateDebut = new Date(dateArriveeNuit.value);
                        const dateFin = new Date(dateDepartNuit.value);
                        
                        for (let d = new Date(dateDebut); d < dateFin; d.setDate(d.getDate() + 1)) {
                            const jourSemaine = d.getDay();
                            const estWeekend = (jourSemaine === 0 || jourSemaine === 6);
                            
                            if (estWeekend) {
                                prixDetaille += prixWeekend;
                                joursWeekend++;
                                detailsJours += `<div class="d-flex justify-content-between small">
                                    <span>${d.toLocaleDateString('fr-FR')} (Weekend):</span>
                                    <span>${prixWeekend.toLocaleString('fr-FR')} FCFA</span>
                                </div>`;
                            } else {
                                prixDetaille += prixNuit;
                                joursSemaine++;
                                detailsJours += `<div class="d-flex justify-content-between small">
                                    <span>${d.toLocaleDateString('fr-FR')} (Semaine):</span>
                                    <span>${prixNuit.toLocaleString('fr-FR')} FCFA</span>
                                </div>`;
                            }
                        }
                        
                        total = prixDetaille;
                        
                        details = `
                            <div class="row mb-2">
                                <div class="col-6"><strong>Chambre:</strong></div>
                                <div class="col-6">${nomChambre}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6"><strong>Type:</strong></div>
                                <div class="col-6">Nuitée complète</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6"><strong>Durée:</strong></div>
                                <div class="col-6">${nuits} nuit(s) - ${joursSemaine} jour(s) semaine + ${joursWeekend} jour(s) weekend</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6"><strong>Arrivée:</strong></div>
                                <div class="col-6">${new Date(dateArriveeNuit.value).toLocaleDateString('fr-FR')} à 14h00</div>
                            </div>
                            <div class="row">
                                <div class="col-6"><strong>Départ:</strong></div>
                                <div class="col-6">${new Date(dateDepartNuit.value).toLocaleDateString('fr-FR')} avant 12h00</div>
                            </div>
                        `;
                        
                        detailsPrixHtml = `
                            <h6 class="fw-semibold mb-2">Détail du prix:</h6>
                            ${detailsJours}
                            <hr class="my-2">
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total:</span>
                                <span>${total.toLocaleString('fr-FR')} FCFA</span>
                            </div>
                        `;
                    }
                }
            } else if (typeReservationSelect.value === 'jour') {
                if (dateJour.value && heureArrivee.value && heureDepart.value) {
                    const prixJour = parseFloat(selectedOption.dataset.prixJour);
                    const prixJour4h = parseFloat(selectedOption.dataset.prixJour4h);
                    const prixJour6h = parseFloat(selectedOption.dataset.prixJour6h);
                    const prixJour8h = parseFloat(selectedOption.dataset.prixJour8h);
                    
                    // Calcul de la durée
                    const [heureA, minuteA] = heureArrivee.value.split(':');
                    const [heureD, minuteD] = heureDepart.value.split(':');
                    const minutesArrivee = parseInt(heureA) * 60 + parseInt(minuteA);
                    const minutesDepart = parseInt(heureD) * 60 + parseInt(minuteD);
                    const dureeMinutes = minutesDepart - minutesArrivee;
                    const dureeHeures = dureeMinutes / 60;
                    
                    // Déterminer le forfait
                    let forfait = '';
                    if (dureeHeures <= 4) {
                        total = prixJour4h;
                        forfait = 'Forfait 4 heures';
                    } else if (dureeHeures <= 6) {
                        total = prixJour6h;
                        forfait = 'Forfait 6 heures';
                    } else if (dureeHeures <= 8) {
                        total = prixJour8h;
                        forfait = 'Forfait 8 heures';
                    } else {
                        total = prixJour;
                        forfait = 'Forfait journée complète';
                    }
                    
                    details = `
                        <div class="row mb-2">
                            <div class="col-6"><strong>Chambre:</strong></div>
                            <div class="col-6">${nomChambre}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6"><strong>Type:</strong></div>
                            <div class="col-6">Réservation journée</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6"><strong>Forfait:</strong></div>
                            <div class="col-6">${forfait}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6"><strong>Date:</strong></div>
                            <div class="col-6">${new Date(dateJour.value).toLocaleDateString('fr-FR')}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6"><strong>Heure arrivée:</strong></div>
                            <div class="col-6">${heureArrivee.value}</div>
                        </div>
                        <div class="row">
                            <div class="col-6"><strong>Heure départ:</strong></div>
                            <div class="col-6">${heureDepart.value}</div>
                        </div>
                    `;
                    
                    detailsPrixHtml = `
                        <h6 class="fw-semibold mb-2">Détail du prix:</h6>
                        <div class="d-flex justify-content-between small mb-2">
                            <span>${forfait}:</span>
                            <span>${total.toLocaleString('fr-FR')} FCFA</span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Durée:</span>
                            <span>${dureeHeures.toFixed(1)} heures</span>
                        </div>
                    `;
                }
            }
            
            if (total > 0) {
                detailsReservation.innerHTML = details;
                detailsPrix.innerHTML = detailsPrixHtml;
                prixTotal.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Total:</span>
                        <span class="fs-4 fw-bold">${total.toLocaleString('fr-FR')} FCFA</span>
                    </div>
                `;
                detailsReservation.style.display = 'block';
                detailsPrix.style.display = 'block';
                prixTotal.style.display = 'block';
                if (placeholder) placeholder.style.display = 'none';
            } else {
                detailsReservation.style.display = 'none';
                detailsPrix.style.display = 'none';
                prixTotal.style.display = 'none';
                if (placeholder) placeholder.style.display = 'block';
            }
        } else {
            detailsReservation.style.display = 'none';
            detailsPrix.style.display = 'none';
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
</body>
</html>
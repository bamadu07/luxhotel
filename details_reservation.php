<?php
// details_reservation.php
session_start();
include 'includes/header.php';
include 'config/database.php';

// Vérifier si l'ID de réservation est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Aucune réservation spécifiée.";
    header('Location: reservations.php');
    exit;
}

$reservation_id = intval($_GET['id']);
$taux_fcfa = 655;

// Récupérer les détails de la réservation
try {
    $stmt = $pdo->prepare("
        SELECT r.*, 
               c.type_chambre, 
               c.description,
               c.equipements,
               c.superficie,
               c.capacite
        FROM reservations r 
        LEFT JOIN chambres c ON r.type_chambre = c.type_chambre 
        WHERE r.id = ?
    ");
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        $_SESSION['error'] = "Réservation non trouvée.";
        header('Location: reservations.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des détails: " . $e->getMessage();
    header('Location: reservations.php');
    exit;
}

// Images par défaut pour les chambres
$images_chambres = [
    'default' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=500&h=300&fit=crop',
    'simple' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=500&h=300&fit=crop',
    'double' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=500&h=300&fit=crop',
    'suite' => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=500&h=300&fit=crop'
];

// Fonction pour obtenir l'image de la chambre
function getRoomImage($roomType, $images) {
    $type = strtolower($roomType);
    
    if (strpos($type, 'simple') !== false || strpos($type, 'standard') !== false) return $images['simple'];
    if (strpos($type, 'double') !== false || strpos($type, 'twin') !== false) return $images['double'];
    if (strpos($type, 'suite') !== false || strpos($type, 'présidentielle') !== false || strpos($type, 'luxe') !== false) return $images['suite'];
    
    return $images['default'];
}

// Fonction pour obtenir le badge de statut
function getStatusBadge($status) {
    // Définir les statuts possibles avec leurs classes Bootstrap
    $statuses = [
        'confirmée' => 'success',
        'confirmee' => 'success',
        'confirmed' => 'success',
        'en_attente' => 'warning',
        'en attente' => 'warning',
        'pending' => 'warning',
        'annulée' => 'danger',
        'annulee' => 'danger',
        'cancelled' => 'danger',
        'terminée' => 'info',
        'terminee' => 'info',
        'completed' => 'info',
        'checkin' => 'primary',
        'check-out' => 'secondary',
        'checkout' => 'secondary'
    ];
    
    // Nettoyer et normaliser le statut
    $status = strtolower(trim($status));
    $badge_class = $statuses[$status] ?? 'secondary';
    
    // Texte affiché avec première lettre en majuscule
    $status_text = ucfirst(str_replace('_', ' ', $status));
    
    return "<span class='badge bg-$badge_class'>$status_text</span>";
}

// Type de réservation
function getReservationType($type) {
    $types = [
        'nuit' => 'Nuitée complète',
        'jour' => 'Réservation journée',
        'night' => 'Nuitée complète',
        'day' => 'Réservation journée'
    ];
    
    return $types[$type] ?? ucfirst($type);
}

// Calculer la durée du séjour
$date_arrivee = new DateTime($reservation['date_arrivee']);
$date_depart = new DateTime($reservation['date_depart']);
$duree_sejour = $date_arrivee->diff($date_depart)->days;

// Formater les dates
$date_arrivee_formatted = $date_arrivee->format('d/m/Y');
$date_depart_formatted = $date_depart->format('d/m/Y');

// Déterminer le statut (avec valeur par défaut si non défini)
$statut = $reservation['statut'] ?? 'confirmée';
?>

<section class="reservation-details-section">
    <!-- Hero Section -->
    <div class="reservation-hero bg-primary text-white">
        <div class="container">
            <div class="row align-items-center min-vh-40">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="hero-title display-4 fw-bold mb-4">Détails de la Réservation</h1>
                    <p class="hero-subtitle lead mb-4">Référence: <strong>RES-<?php echo str_pad($reservation_id, 6, '0', STR_PAD_LEFT); ?></strong></p>
                    
                    <!-- Statut et actions -->
                    <div class="reservation-status">
                        <?php echo getStatusBadge($statut); ?>
                        <div class="mt-3">
                            <!-- AJOUT: Lien vers la page de réservation -->
                            <a href="reservation.php" class="btn btn-light me-2">
                                <i class="fas fa-plus me-2"></i>Nouvelle Réservation
                            </a>
                            <a href="reservations.php" class="btn btn-outline-light me-2">
                                <i class="fas fa-arrow-left me-2"></i>Retour aux réservations
                            </a>
                            <button class="btn btn-outline-light" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Imprimer
                            </button>
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

                <div class="reservation-details-content mt-4">
                    <div class="row">
                        <!-- Colonne principale -->
                        <div class="col-lg-8">
                            <!-- Carte Informations Réservation -->
                            <div class="detail-card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h4 class="mb-0">
                                        <i class="fas fa-calendar-check me-2"></i>
                                        Informations de la Réservation
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="info-item mb-3">
                                                <label class="fw-semibold text-muted">Référence:</label>
                                                <div class="fs-5 fw-bold text-primary">RES-<?php echo str_pad($reservation_id, 6, '0', STR_PAD_LEFT); ?></div>
                                            </div>
                                            <div class="info-item mb-3">
                                                <label class="fw-semibold text-muted">Type de réservation:</label>
                                                <div><?php echo getReservationType($reservation['type_reservation'] ?? 'nuit'); ?></div>
                                            </div>
                                            <div class="info-item mb-3">
                                                <label class="fw-semibold text-muted">Date de création:</label>
                                                <div><?php echo date('d/m/Y à H:i', strtotime($reservation['date_creation'] ?? $reservation['created_at'] ?? 'now')); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-item mb-3">
                                                <label class="fw-semibold text-muted">Statut:</label>
                                                <div><?php echo getStatusBadge($statut); ?></div>
                                            </div>
                                            <div class="info-item mb-3">
                                                <label class="fw-semibold text-muted">Nombre de personnes:</label>
                                                <div><?php echo $reservation['nombre_personnes']; ?> personne(s)</div>
                                            </div>
                                            <div class="info-item mb-3">
                                                <label class="fw-semibold text-muted">Prix total:</label>
                                                <div class="fs-5 fw-bold text-success"><?php echo number_format($reservation['prix_total'], 0, ',', ' '); ?> FCFA</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Carte Détails du Séjour -->
                            <div class="detail-card mb-4">
                                <div class="card-header bg-info text-white">
                                    <h4 class="mb-0">
                                        <i class="fas fa-clock me-2"></i>
                                        Détails du Séjour
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="info-item mb-3">
                                                <label class="fw-semibold text-muted">Date d'arrivée:</label>
                                                <div class="fw-bold"><?php echo $date_arrivee_formatted; ?></div>
                                                <?php if(isset($reservation['heure_arrivee'])): ?>
                                                <small class="text-muted">à <?php echo substr($reservation['heure_arrivee'], 0, 5); ?></small>
                                                <?php else: ?>
                                                <small class="text-muted">à partir de 14h00</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-item mb-3">
                                                <label class="fw-semibold text-muted">Date de départ:</label>
                                                <div class="fw-bold"><?php echo $date_depart_formatted; ?></div>
                                                <?php if(isset($reservation['heure_depart'])): ?>
                                                <small class="text-muted">avant <?php echo substr($reservation['heure_depart'], 0, 5); ?></small>
                                                <?php else: ?>
                                                <small class="text-muted">avant 12h00</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="fw-semibold text-muted">Durée du séjour:</label>
                                        <div class="fw-bold">
                                            <?php 
                                            if (($reservation['type_reservation'] ?? 'nuit') === 'jour') {
                                                echo "1 journée";
                                            } else {
                                                echo $duree_sejour . " nuit" . ($duree_sejour > 1 ? 's' : '');
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Carte Informations Client -->
                            <div class="detail-card mb-4">
                                <div class="card-header bg-success text-white">
                                    <h4 class="mb-0">
                                        <i class="fas fa-user me-2"></i>
                                        Informations Client
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="info-item mb-3">
                                                <label class="fw-semibold text-muted">Nom complet:</label>
                                                <div class="fw-bold"><?php echo htmlspecialchars($reservation['nom_client']); ?></div>
                                            </div>
                                            <div class="info-item mb-3">
                                                <label class="fw-semibold text-muted">Email:</label>
                                                <div>
                                                    <a href="mailto:<?php echo htmlspecialchars($reservation['email']); ?>">
                                                        <?php echo htmlspecialchars($reservation['email']); ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-item mb-3">
                                                <label class="fw-semibold text-muted">Téléphone:</label>
                                                <div>
                                                    <a href="tel:<?php echo htmlspecialchars($reservation['telephone']); ?>">
                                                        <?php echo htmlspecialchars($reservation['telephone']); ?>
                                                    </a>
                                                </div>
                                            </div>
                                            <?php if(isset($reservation['message_special']) && !empty($reservation['message_special'])): ?>
                                            <div class="info-item">
                                                <label class="fw-semibold text-muted">Demandes spéciales:</label>
                                                <div class="alert alert-info mt-2">
                                                    <?php echo nl2br(htmlspecialchars($reservation['message_special'])); ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Colonne latérale -->
                        <div class="col-lg-4">
                            <!-- Carte Chambre Réservée -->
                            <div class="detail-card mb-4">
                                <div class="card-header bg-warning text-dark">
                                    <h4 class="mb-0">
                                        <i class="fas fa-bed me-2"></i>
                                        Chambre Réservée
                                    </h4>
                                </div>
                                <div class="card-body p-0">
                                    <div class="room-image-container">
                                        <img src="<?php echo getRoomImage($reservation['type_chambre'], $images_chambres); ?>" 
                                             alt="<?php echo htmlspecialchars($reservation['type_chambre']); ?>" 
                                             class="room-image w-100"
                                             style="height: 200px; object-fit: cover;">
                                        <div class="room-overlay p-3">
                                            <h5 class="text-white mb-2"><?php echo htmlspecialchars($reservation['type_chambre']); ?></h5>
                                        </div>
                                    </div>
                                    <div class="p-3">
                                        <div class="room-features">
                                            <?php if(isset($reservation['capacite'])): ?>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span><i class="fas fa-user-friends me-2 text-muted"></i> Capacité:</span>
                                                <span class="fw-semibold"><?php echo $reservation['capacite']; ?> personnes</span>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if(isset($reservation['superficie'])): ?>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span><i class="fas fa-ruler-combined me-2 text-muted"></i> Superficie:</span>
                                                <span class="fw-semibold"><?php echo $reservation['superficie']; ?> m²</span>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if(isset($reservation['equipements'])): ?>
                                            <div class="equipements mt-3">
                                                <label class="fw-semibold text-muted mb-2">Équipements:</label>
                                                <div class="equipment-tags">
                                                    <?php 
                                                    $equipements = explode(',', $reservation['equipements']);
                                                    foreach($equipements as $equipement): 
                                                        if(trim($equipement)):
                                                    ?>
                                                    <span class="equipment-tag"><?php echo trim($equipement); ?></span>
                                                    <?php 
                                                        endif;
                                                    endforeach; 
                                                    ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Carte Actions Rapides -->
                            <div class="detail-card mb-4">
                                <div class="card-header bg-secondary text-white">
                                    <h4 class="mb-0">
                                        <i class="fas fa-cogs me-2"></i>
                                        Actions
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <!-- AJOUT: Lien vers nouvelle réservation -->
                                        <a href="reservation.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Nouvelle Réservation
                                        </a>
                                        <button class="btn btn-outline-primary" onclick="window.print()">
                                            <i class="fas fa-print me-2"></i>Imprimer
                                        </button>
                                        <a href="reservations.php" class="btn btn-outline-info">
                                            <i class="fas fa-list me-2"></i>Mes Réservations
                                        </a>
                                        <a href="contact.php" class="btn btn-outline-dark">
                                            <i class="fas fa-envelope me-2"></i>Contacter l'hôtel
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Carte Informations Hôtel -->
                            <div class="detail-card">
                                <div class="card-header bg-dark text-white">
                                    <h4 class="mb-0">
                                        <i class="fas fa-hotel me-2"></i>
                                        Informations Hôtel
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <div class="hotel-info">
                                        <div class="info-item mb-3">
                                            <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                            <span>123 Avenue de l'Hôtel, 75001 Paris</span>
                                        </div>
                                        <div class="info-item mb-3">
                                            <i class="fas fa-phone text-primary me-2"></i>
                                            <span>+33 1 23 45 67 89</span>
                                        </div>
                                        <div class="info-item mb-3">
                                            <i class="fas fa-envelope text-primary me-2"></i>
                                            <span>contact@hoteldeluxe.com</span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-clock text-primary me-2"></i>
                                            <span>Réception 24h/24</span>
                                        </div>
                                        <!-- AJOUT: Lien rapide vers réservation -->
                                        <div class="mt-3 pt-3 border-top">
                                            <a href="reservation.php" class="btn btn-outline-primary btn-sm w-100">
                                                <i class="fas fa-calendar-plus me-2"></i>Réserver une chambre
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Répartition du Prix -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="detail-card">
                                <div class="card-header bg-dark text-white">
                                    <h4 class="mb-0">
                                        <i class="fas fa-receipt me-2"></i>
                                        Détail du Prix
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="price-breakdown">
                                                <?php if(($reservation['type_reservation'] ?? 'nuit') === 'jour'): ?>
                                                <div class="price-item d-flex justify-content-between mb-3">
                                                    <span>Réservation journée - <?php echo htmlspecialchars($reservation['type_chambre']); ?></span>
                                                    <span class="fw-semibold"><?php echo number_format($reservation['prix_total'], 0, ',', ' '); ?> FCFA</span>
                                                </div>
                                                <?php else: ?>
                                                <div class="price-item d-flex justify-content-between mb-3">
                                                    <span><?php echo $duree_sejour; ?> nuit(s) - <?php echo htmlspecialchars($reservation['type_chambre']); ?></span>
                                                    <span class="fw-semibold"><?php echo number_format($reservation['prix_total'], 0, ',', ' '); ?> FCFA</span>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <div class="price-item d-flex justify-content-between mb-3">
                                                    <span>Taxes et services inclus</span>
                                                    <span class="text-success">Inclus</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="total-price text-center p-4 bg-light rounded">
                                                <div class="text-muted mb-2">Total à payer</div>
                                                <div class="fs-3 fw-bold text-primary"><?php echo number_format($reservation['prix_total'], 0, ',', ' '); ?> FCFA</div>
                                                <small class="text-muted">TVA incluse</small>
                                                <!-- AJOUT: Lien pour nouvelle réservation -->
                                                <div class="mt-3">
                                                    <a href="reservation.php" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-plus me-1"></i>Nouvelle réservation
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Appel à l'action -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="detail-card bg-primary text-white">
                                <div class="card-body text-center py-5">
                                    <h3 class="mb-3">Satisfait de votre séjour ?</h3>
                                    <p class="mb-4 opacity-75">Réservez à nouveau chez nous et bénéficiez de nos meilleurs tarifs</p>
                                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                                        <a href="reservation.php" class="btn btn-light btn-lg">
                                            <i class="fas fa-calendar-plus me-2"></i>Nouvelle Réservation
                                        </a>
                                        <a href="chambres.php" class="btn btn-outline-light btn-lg">
                                            <i class="fas fa-bed me-2"></i>Voir nos Chambres
                                        </a>
                                        <a href="contact.php" class="btn btn-outline-light btn-lg">
                                            <i class="fas fa-envelope me-2"></i>Nous Contacter
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.reservation-details-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
}

.reservation-hero {
    position: relative;
    padding: 80px 0 120px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.min-vh-40 {
    min-height: 40vh;
}

.hero-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
}

.hero-subtitle {
    font-size: 1.25rem;
    opacity: 0.9;
    line-height: 1.6;
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

.detail-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    border: none;
    transition: all 0.3s ease;
}

.detail-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.card-header {
    border-radius: 15px 15px 0 0 !important;
    padding: 1.25rem 1.5rem;
}

.card-body {
    padding: 1.5rem;
}

.info-item {
    padding: 0.5rem 0;
}

.room-image-container {
    position: relative;
    overflow: hidden;
}

.room-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.7));
}

.equipment-tags {
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

.hotel-info .info-item {
    display: flex;
    align-items: center;
    padding: 0.5rem 0;
}

.price-breakdown {
    border-right: 2px solid #e9ecef;
    padding-right: 2rem;
}

.total-price {
    border-left: 4px solid #667eea;
}

/* Badges */
.badge {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
}

/* Section Appel à l'action */
.bg-primary .btn-light:hover {
    background: #f8f9fa;
    color: #667eea;
}

/* Responsive */
@media (max-width: 768px) {
    .reservation-hero {
        padding: 60px 0 100px;
    }
    
    .hero-title {
        font-size: 2.2rem;
    }
    
    .price-breakdown {
        border-right: none;
        border-bottom: 2px solid #e9ecef;
        padding-right: 0;
        padding-bottom: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .card-body {
        padding: 1.25rem;
    }
    
    .reservation-status .btn {
        margin-bottom: 0.5rem;
        display: block;
        width: 100%;
    }
}

/* Impression */
@media print {
    .reservation-hero {
        background: #667eea !important;
        padding: 40px 0 60px !important;
    }
    
    .btn {
        display: none !important;
    }
    
    .detail-card {
        box-shadow: none !important;
        border: 1px solid #dee2e6 !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation des cartes au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Appliquer l'animation à toutes les cartes
    document.querySelectorAll('.detail-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'all 0.6s ease';
        observer.observe(card);
    });

    // Confirmation avant impression
    document.querySelector('button[onclick="window.print()"]').addEventListener('click', function() {
        setTimeout(() => {
            alert('Votre réservation a été envoyée à l\'imprimante.');
        }, 500);
    });

    // Tracking des clics sur les liens de réservation
    document.querySelectorAll('a[href="reservation.php"]').forEach(link => {
        link.addEventListener('click', function() {
            // Vous pouvez ajouter du tracking Google Analytics ici
            console.log('Clic sur le lien de réservation depuis la page détails');
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
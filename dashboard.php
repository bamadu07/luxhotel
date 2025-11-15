<?php
// dashboard.php
include 'includes/header.php';
include 'config/database.php';

// Vérifier si l'admin est connecté
if(!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Traitement de la confirmation/annulation des réservations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['reservation_id'])) {
        $reservation_id = (int)$_POST['reservation_id'];
        $action = $_POST['action'];
        
        try {
            if ($action === 'confirmer') {
                $stmt = $pdo->prepare("UPDATE reservations SET statut = 'confirmee' WHERE id = ?");
                $stmt->execute([$reservation_id]);
                $success = "Réservation #$reservation_id confirmée avec succès!";
            } elseif ($action === 'annuler') {
                $stmt = $pdo->prepare("UPDATE reservations SET statut = 'annulee' WHERE id = ?");
                $stmt->execute([$reservation_id]);
                $success = "Réservation #$reservation_id annulée avec succès!";
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour: " . $e->getMessage();
        }
    }
}

// Récupérer les statistiques
$reservations_total = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$reservations_confirmees = $pdo->query("SELECT COUNT(*) FROM reservations WHERE statut = 'confirmee'")->fetchColumn();
$reservations_en_attente = $pdo->query("SELECT COUNT(*) FROM reservations WHERE statut = 'en_attente'")->fetchColumn();
$reservations_annulees = $pdo->query("SELECT COUNT(*) FROM reservations WHERE statut = 'annulee'")->fetchColumn();
$revenu_total = $pdo->query("SELECT SUM(prix_total) FROM reservations WHERE statut = 'confirmee'")->fetchColumn();
$revenu_total = $revenu_total ? $revenu_total : 0;

// Récupérer les réservations récentes avec heures écoulées
$stmt = $pdo->query("
    SELECT r.*, 
           TIMESTAMPDIFF(HOUR, r.date_reservation, NOW()) as heures_ecoulees
    FROM reservations r 
    ORDER BY r.date_reservation DESC 
    LIMIT 5
");
$reservations_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les messages récents
$stmt = $pdo->query("SELECT * FROM contacts ORDER BY date_envoi DESC LIMIT 5");
$messages_recents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="dashboard-section py-5">
    <div class="container">
        <!-- Messages d'alerte -->
        <?php if(isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <h2 class="mb-4">Tableau de Bord Administrateur</h2>
        
        <!-- Statistiques -->
        <div class="row mb-5">
            <div class="col-md-2 col-6 mb-3">
                <div class="card bg-primary text-white text-center p-3">
                    <h4><?php echo $reservations_total; ?></h4>
                    <p>Total Réservations</p>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="card bg-success text-white text-center p-3">
                    <h4><?php echo $reservations_confirmees; ?></h4>
                    <p>Confirmées</p>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="card bg-warning text-white text-center p-3">
                    <h4><?php echo $reservations_en_attente; ?></h4>
                    <p>En Attente</p>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="card bg-danger text-white text-center p-3">
                    <h4><?php echo $reservations_annulees; ?></h4>
                    <p>Annulées</p>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="card bg-info text-white text-center p-3">
                    <h4><?php echo number_format($revenu_total, 0, ',', ' '); ?></h4>
                    <p>Revenu (FCFA)</p>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="card bg-secondary text-white text-center p-3">
                    <h4><?php echo count($messages_recents); ?></h4>
                    <p>Messages</p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Réservations récentes -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list-alt me-2"></i>Réservations Récentes
                            <span class="badge bg-primary ms-2"><?php echo count($reservations_recentes); ?></span>
                        </h5>
                        <button class="btn btn-sm btn-outline-light" onclick="refreshReservations()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <?php if(count($reservations_recentes) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Référence</th>
                                        <th>Client</th>
                                        <th>Chambre</th>
                                        <th>Dates & Prix</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="reservationsTable">
                                    <?php foreach($reservations_recentes as $reservation): 
                                        $heures_ecoulees = $reservation['heures_ecoulees'];
                                    ?>
                                    <tr class="<?php echo $heures_ecoulees < 24 ? 'table-info' : ''; ?>" 
                                        id="reservation-<?php echo $reservation['id']; ?>">
                                        <td>
                                            <strong class="text-primary">RES-<?php echo str_pad($reservation['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($reservation['date_reservation'])); ?>
                                            </small>
                                            <?php if($heures_ecoulees < 24): ?>
                                            <br>
                                            <span class="badge bg-success">Nouvelle</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="client-info">
                                                <strong><?php echo htmlspecialchars($reservation['nom_client']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($reservation['email']); ?>
                                                    <br>
                                                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($reservation['telephone']); ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark"><?php echo $reservation['type_chambre']; ?></span>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo $reservation['type_reservation'] === 'jour' ? 'Journée' : 'Nuitée'; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="date-info">
                                                <small><strong>Arrivée:</strong> <?php echo date('d/m/Y', strtotime($reservation['date_arrivee'])); ?></small>
                                                <br>
                                                <small><strong>Départ:</strong> <?php echo date('d/m/Y', strtotime($reservation['date_depart'])); ?></small>
                                                <br>
                                                <strong class="text-success"><?php echo number_format($reservation['prix_total'], 0, ',', ' '); ?> FCFA</strong>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $badge_class = [
                                                'en_attente' => 'bg-warning',
                                                'confirmee' => 'bg-success',
                                                'annulee' => 'bg-danger'
                                            ];
                                            $statut_text = [
                                                'en_attente' => 'En attente',
                                                'confirmee' => 'Confirmée',
                                                'annulee' => 'Annulée'
                                            ];
                                            ?>
                                            <span class="badge <?php echo $badge_class[$reservation['statut']]; ?> statut-badge">
                                                <?php echo $statut_text[$reservation['statut']]; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" 
                                                        data-bs-toggle="tooltip" 
                                                        title="Voir les détails"
                                                        onclick="voirDetails(<?php echo $reservation['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if($reservation['statut'] === 'en_attente'): ?>
                                                <button class="btn btn-outline-success confirm-btn" 
                                                        data-bs-toggle="tooltip" 
                                                        title="Confirmer cette réservation"
                                                        onclick="confirmerReservation(<?php echo $reservation['id']; ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <?php if($reservation['statut'] !== 'annulee'): ?>
                                                <button class="btn btn-outline-danger cancel-btn" 
                                                        data-bs-toggle="tooltip" 
                                                        title="Annuler cette réservation"
                                                        onclick="annulerReservation(<?php echo $reservation['id']; ?>)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Aucune réservation récente</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Messages récents et Actions rapides -->
            <div class="col-md-4 mb-4">
                <!-- Messages récents -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-envelope me-2"></i>Messages Récents
                            <span class="badge bg-primary ms-2"><?php echo count($messages_recents); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if(count($messages_recents) > 0): ?>
                        <div class="list-group">
                            <?php foreach($messages_recents as $message): 
                                $date_message = new DateTime($message['date_envoi']);
                                $now = new DateTime();
                                $interval = $date_message->diff($now);
                                $is_recent = $interval->days < 1;
                            ?>
                            <div class="list-group-item <?php echo $is_recent ? 'bg-light' : ''; ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($message['sujet']); ?></h6>
                                    <small><?php echo $date_message->format('d/m H:i'); ?></small>
                                </div>
                                <p class="mb-1 text-truncate"><?php echo htmlspecialchars($message['message']); ?></p>
                                <small>De: <?php echo htmlspecialchars($message['nom']); ?></small>
                                <div class="mt-2">
                                    <a href="gestion_contacts.php?id=<?php echo $message['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-reply me-1"></i>Répondre
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-center text-muted">Aucun message récent.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Actions rapides -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Actions Rapides</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="gestion_reservations.php" class="btn btn-primary text-start">
                                <i class="fas fa-list me-2"></i>Gérer les Réservations
                            </a>
                            <a href="gestion_chambres.php" class="btn btn-success text-start">
                                <i class="fas fa-bed me-2"></i>Gérer les Chambres
                            </a>
                            <a href="gestion_contacts.php" class="btn btn-info text-start">
                                <i class="fas fa-envelope me-2"></i>Voir les Messages
                            </a>
                            <button class="btn btn-outline-secondary text-start" onclick="refreshDashboard()">
                                <i class="fas fa-sync-alt me-2"></i>Actualiser le Dashboard
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal de confirmation -->
<div class="modal fade" id="confirmationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Confirmer l'action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody">
                Êtes-vous sûr de vouloir confirmer cette réservation ?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="confirmAction">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<style>
.client-info {
    max-width: 200px;
}

.confirm-btn:hover {
    background-color: #198754;
    border-color: #198754;
    color: white;
}

.cancel-btn:hover {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}

.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
}

/* Animation pour les nouvelles réservations */
@keyframes highlightNew {
    0% { background-color: #e7f1ff; }
    100% { background-color: transparent; }
}

.table-info {
    animation: highlightNew 2s ease;
}
</style>

<script>
let currentReservationId = null;
let currentAction = null;

// Initialiser les tooltips Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Fonction pour rafraîchir le dashboard
function refreshDashboard() {
    window.location.reload();
}

// Fonction pour rafraîchir les réservations
function refreshReservations() {
    const refreshBtn = event.target.closest('button');
    const originalHtml = refreshBtn.innerHTML;
    
    // Afficher l'indicateur de chargement
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    refreshBtn.disabled = true;
    
    // Rafraîchir la page
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

// Fonction pour confirmer une réservation
function confirmerReservation(reservationId) {
    currentReservationId = reservationId;
    currentAction = 'confirmer';
    
    // Mettre à jour le modal
    document.getElementById('modalTitle').textContent = 'Confirmer la Réservation';
    document.getElementById('modalBody').innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Êtes-vous sûr de vouloir confirmer cette réservation ?
        </div>
        <p class="mb-0">Cette action enverra un email de confirmation au client.</p>
    `;
    document.getElementById('confirmAction').textContent = 'Confirmer';
    document.getElementById('confirmAction').className = 'btn btn-success';
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    modal.show();
}

// Fonction pour annuler une réservation
function annulerReservation(reservationId) {
    currentReservationId = reservationId;
    currentAction = 'annuler';
    
    // Mettre à jour le modal
    document.getElementById('modalTitle').textContent = 'Annuler la Réservation';
    document.getElementById('modalBody').innerHTML = `
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Êtes-vous sûr de vouloir annuler cette réservation ?
        </div>
        <p class="mb-0">Cette action est irréversible et enverra un email d'annulation au client.</p>
    `;
    document.getElementById('confirmAction').textContent = 'Annuler';
    document.getElementById('confirmAction').className = 'btn btn-danger';
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    modal.show();
}

// Gérer la confirmation d'action
document.getElementById('confirmAction').addEventListener('click', function() {
    if (currentReservationId && currentAction) {
        // Créer un formulaire dynamique pour soumettre l'action
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = currentAction;
        
        const reservationInput = document.createElement('input');
        reservationInput.type = 'hidden';
        reservationInput.name = 'reservation_id';
        reservationInput.value = currentReservationId;
        
        form.appendChild(actionInput);
        form.appendChild(reservationInput);
        document.body.appendChild(form);
        
        // Soumettre le formulaire
        form.submit();
    }
});

// Fonction pour voir les détails d'une réservation
function voirDetails(reservationId) {
    // Rediriger vers la page de détails
    window.location.href = `details_reservation.php?id=${reservationId}`;
}

// Raccourcis clavier
document.addEventListener('keydown', function(e) {
    // Ctrl + R pour rafraîchir
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        refreshDashboard();
    }
    
    // Échap pour fermer les modals
    if (e.key === 'Escape') {
        const modal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
        if (modal) {
            modal.hide();
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
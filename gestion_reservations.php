<?php
// gestion_reservations.php
include 'includes/header.php';
include 'config/database.php';

// Vérifier si l'admin est connecté
if(!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Traitement des actions
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
            } elseif ($action === 'supprimer') {
                $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
                $stmt->execute([$reservation_id]);
                $success = "Réservation #$reservation_id supprimée avec succès!";
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de l'opération: " . $e->getMessage();
        }
    }
}

// Récupérer les paramètres de filtrage
$statut_filter = $_GET['statut'] ?? 'tous';
$search_term = $_GET['search'] ?? '';

// Construire la requête avec filtres
$sql = "SELECT r.*, 
               DATEDIFF(r.date_depart, r.date_arrivee) as nuits,
               TIMESTAMPDIFF(HOUR, r.date_reservation, NOW()) as heures_ecoulees
        FROM reservations r 
        WHERE 1=1";

$params = [];

// Filtre par statut
if ($statut_filter !== 'tous') {
    $sql .= " AND r.statut = ?";
    $params[] = $statut_filter;
}

// Filtre de recherche
if (!empty($search_term)) {
    $sql .= " AND (r.nom_client LIKE ? OR r.email LIKE ? OR r.telephone LIKE ? OR r.type_chambre LIKE ?)";
    $search_like = "%$search_term%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
}

$sql .= " ORDER BY r.date_reservation DESC";

// Exécuter la requête
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques pour les filtres
$stats_total = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$stats_confirmees = $pdo->query("SELECT COUNT(*) FROM reservations WHERE statut = 'confirmee'")->fetchColumn();
$stats_attente = $pdo->query("SELECT COUNT(*) FROM reservations WHERE statut = 'en_attente'")->fetchColumn();
$stats_annulees = $pdo->query("SELECT COUNT(*) FROM reservations WHERE statut = 'annulee'")->fetchColumn();
?>

<section class="gestion-reservations py-5">
    <div class="container">
        <!-- En-tête -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="text-primary">
                        <i class="fas fa-list-alt me-3"></i>Gestion des Réservations
                    </h1>
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Retour au Dashboard
                    </a>
                </div>
                <p class="text-muted">Gérez et suivez toutes les réservations de l'hôtel</p>
            </div>
        </div>

        <!-- Messages d'alerte -->
        <?php if(isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle fa-2x me-3"></i>
                <div><?php echo $success; ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                <div><?php echo $error; ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Cartes de statistiques -->
        <div class="row mb-4">
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stats-card bg-primary text-white" onclick="setFilter('tous')" style="cursor: pointer;">
                    <div class="stats-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo $stats_total; ?></div>
                        <div class="stats-label">Total</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stats-card bg-success text-white" onclick="setFilter('confirmee')" style="cursor: pointer;">
                    <div class="stats-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo $stats_confirmees; ?></div>
                        <div class="stats-label">Confirmées</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stats-card bg-warning text-white" onclick="setFilter('en_attente')" style="cursor: pointer;">
                    <div class="stats-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo $stats_attente; ?></div>
                        <div class="stats-label">En Attente</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stats-card bg-danger text-white" onclick="setFilter('annulee')" style="cursor: pointer;">
                    <div class="stats-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo $stats_annulees; ?></div>
                        <div class="stats-label">Annulées</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stats-card bg-info text-white">
                    <div class="stats-icon">
                        <i class="fas fa-filter"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo count($reservations); ?></div>
                        <div class="stats-label">Filtrées</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stats-card bg-secondary text-white" onclick="refreshPage()" style="cursor: pointer;">
                    <div class="stats-icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><i class="fas fa-redo"></i></div>
                        <div class="stats-label">Actualiser</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres et recherche -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filtrer par statut:</label>
                        <select name="statut" class="form-select" onchange="this.form.submit()">
                            <option value="tous" <?php echo $statut_filter === 'tous' ? 'selected' : ''; ?>>Toutes les réservations</option>
                            <option value="en_attente" <?php echo $statut_filter === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                            <option value="confirmee" <?php echo $statut_filter === 'confirmee' ? 'selected' : ''; ?>>Confirmées</option>
                            <option value="annulee" <?php echo $statut_filter === 'annulee' ? 'selected' : ''; ?>>Annulées</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Rechercher:</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Nom, email, téléphone ou type de chambre..." value="<?php echo htmlspecialchars($search_term); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <a href="gestion_reservations.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times"></i> Effacer
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tableau des réservations -->
        <div class="card">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2"></i>Liste des Réservations
                    <span class="badge bg-primary ms-2"><?php echo count($reservations); ?></span>
                </h5>
                <div class="header-actions">
                    <button class="btn btn-sm btn-outline-light" onclick="exportReservations()">
                        <i class="fas fa-download me-1"></i>Exporter
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if(count($reservations) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Référence</th>
                                <th>Client</th>
                                <th>Chambre</th>
                                <th>Dates & Séjour</th>
                                <th>Prix</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reservations as $reservation): 
                                $heures_ecoulees = $reservation['heures_ecoulees'];
                                $nuits = $reservation['nuits'] ?: 1;
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
                                    <?php if($reservation['message_special']): ?>
                                    <br>
                                    <small class="text-info" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($reservation['message_special']); ?>">
                                        <i class="fas fa-comment"></i> Note spéciale
                                    </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="date-info">
                                        <small><strong>Arrivée:</strong> <?php echo date('d/m/Y', strtotime($reservation['date_arrivee'])); ?></small>
                                        <br>
                                        <small><strong>Départ:</strong> <?php echo date('d/m/Y', strtotime($reservation['date_depart'])); ?></small>
                                        <br>
                                        <span class="badge bg-secondary">
                                            <?php echo $nuits; ?> nuit<?php echo $nuits > 1 ? 's' : ''; ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <strong class="text-success"><?php echo number_format($reservation['prix_total'], 0, ',', ' '); ?> FCFA</strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo number_format($reservation['prix_total'] / $nuits, 0, ',', ' '); ?> FCFA/nuit
                                    </small>
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
                                        
                                        <button class="btn btn-outline-dark delete-btn" 
                                                data-bs-toggle="tooltip" 
                                                title="Supprimer cette réservation"
                                                onclick="supprimerReservation(<?php echo $reservation['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-4x mb-3"></i>
                    <h4>Aucune réservation trouvée</h4>
                    <p>Essayez de modifier vos critères de recherche ou de filtrage.</p>
                    <a href="gestion_reservations.php" class="btn btn-primary">
                        <i class="fas fa-times me-2"></i>Effacer les filtres
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pagination (optionnelle) -->
        <?php if(count($reservations) > 0): ?>
        <div class="row mt-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Affichage de <strong><?php echo count($reservations); ?></strong> réservation(s)
                </div>
                <nav>
                    <ul class="pagination mb-0">
                        <li class="page-item disabled">
                            <a class="page-link" href="#">Précédent</a>
                        </li>
                        <li class="page-item active">
                            <a class="page-link" href="#">1</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="#">Suivant</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>
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
                Êtes-vous sûr de vouloir effectuer cette action ?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="confirmAction">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<style>
.gestion-reservations {
    background: #f8f9fa;
    min-height: 100vh;
}

.stats-card {
    border-radius: 15px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    height: 100%;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.stats-icon {
    width: 60px;
    height: 60px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.stats-number {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stats-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.client-info {
    max-width: 200px;
}

.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
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

.delete-btn:hover {
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
}

/* Responsive */
@media (max-width: 768px) {
    .stats-card {
        padding: 1rem;
        flex-direction: column;
        text-align: center;
    }
    
    .stats-number {
        font-size: 1.5rem;
    }
    
    .stats-icon {
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }
    
    .btn-group-sm {
        flex-direction: column;
    }
    
    .btn-group-sm .btn {
        margin-bottom: 2px;
    }
}
</style>

<script>
let currentReservationId = null;
let currentAction = null;

// Initialiser les tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Fonctions de filtrage
function setFilter(statut) {
    const url = new URL(window.location);
    url.searchParams.set('statut', statut);
    window.location.href = url.toString();
}

function refreshPage() {
    window.location.reload();
}

// Fonctions d'action
function confirmerReservation(reservationId) {
    currentReservationId = reservationId;
    currentAction = 'confirmer';
    
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
    
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    modal.show();
}

function annulerReservation(reservationId) {
    currentReservationId = reservationId;
    currentAction = 'annuler';
    
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
    
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    modal.show();
}

function supprimerReservation(reservationId) {
    currentReservationId = reservationId;
    currentAction = 'supprimer';
    
    document.getElementById('modalTitle').textContent = 'Supprimer la Réservation';
    document.getElementById('modalBody').innerHTML = `
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Êtes-vous sûr de vouloir supprimer définitivement cette réservation ?
        </div>
        <p class="mb-0"><strong>Cette action est irréversible !</strong> Toutes les données de cette réservation seront perdues.</p>
    `;
    document.getElementById('confirmAction').textContent = 'Supprimer';
    document.getElementById('confirmAction').className = 'btn btn-danger';
    
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    modal.show();
}

// Gérer la confirmation d'action
document.getElementById('confirmAction').addEventListener('click', function() {
    if (currentReservationId && currentAction) {
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
        
        form.submit();
    }
});

function voirDetails(reservationId) {
    window.location.href = `details_reservation.php?id=${reservationId}`;
}

function exportReservations() {
    // Simuler l'export (à implémenter)
    alert('Fonction d\'export à implémenter');
}

// Raccourcis clavier
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        refreshPage();
    }
    
    if (e.key === 'Escape') {
        const modal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
        if (modal) {
            modal.hide();
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
<?php
// gestion_contacts.php
include 'includes/header.php';
include 'config/database.php';

// Vérifier si l'admin est connecté
if(!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Fonction pour créer ou mettre à jour la table contacts
function initialiserTableContacts($pdo) {
    // Vérifier si la table existe
    try {
        $pdo->query("SELECT 1 FROM contacts LIMIT 1");
    } catch (PDOException $e) {
        // Table n'existe pas, la créer
        $pdo->exec("
            CREATE TABLE contacts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                sujet VARCHAR(200) NOT NULL,
                message TEXT NOT NULL,
                statut ENUM('non_lu', 'lu', 'repondu') DEFAULT 'non_lu',
                date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                date_reponse TIMESTAMP NULL,
                reponse TEXT
            )
        ");
        return true;
    }
    
    // Vérifier et ajouter les colonnes manquantes
    $colonnes_requises = [
        'statut' => "ALTER TABLE contacts ADD COLUMN statut ENUM('non_lu', 'lu', 'repondu') DEFAULT 'non_lu' AFTER message",
        'date_reponse' => "ALTER TABLE contacts ADD COLUMN date_reponse TIMESTAMP NULL AFTER date_envoi",
        'reponse' => "ALTER TABLE contacts ADD COLUMN reponse TEXT AFTER date_reponse"
    ];
    
    $modifications = false;
    foreach ($colonnes_requises as $colonne => $sql) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM contacts LIKE '$colonne'");
            if (!$stmt->fetch()) {
                $pdo->exec($sql);
                $modifications = true;
            }
        } catch (PDOException $e) {
            // Ignorer les erreurs pour continuer avec les autres colonnes
        }
    }
    
    return $modifications;
}

// Initialiser la table
$table_modifiee = initialiserTableContacts($pdo);

// Si la table a été modifiée ou créée, insérer des données d'exemple
if ($table_modifiee) {
    $messages_exemple = [
        ['Jean Dupont', 'jean@email.com', 'Demande information chambres', 'Bonjour, je souhaiterais avoir des informations sur vos chambres doubles. Quels sont les équipements inclus ?', 'non_lu'],
        ['Marie Martin', 'marie@email.com', 'Réservation groupe', 'Nous souhaitons réserver plusieurs chambres pour un séminaire en mars. Pouvez-vous me faire parvenir vos tarifs groupe ?', 'lu'],
        ['Pierre Durand', 'pierre@email.com', 'Problème Wi-Fi', 'Bonjour, lors de mon dernier séjour, le Wi-Fi fonctionnait mal dans ma chambre. Y a-t-il eu des améliorations depuis ?', 'repondu'],
        ['Sophie Lambert', 'sophie@email.com', 'Demande partenariat', 'Je représente une agence de voyage et souhaiterais discuter d\'un éventuel partenariat.', 'non_lu'],
        ['Thomas Moreau', 'thomas@email.com', 'Question petit-déjeuner', 'Est-il possible d\'avoir le petit-déjeuner servi dans la chambre ? Quels sont les horaires ?', 'lu']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO contacts (nom, email, sujet, message, statut) VALUES (?, ?, ?, ?, ?)");
    foreach ($messages_exemple as $message) {
        try {
            $stmt->execute($message);
        } catch (PDOException $e) {
            // Ignorer les erreurs d'insertion
        }
    }
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        try {
            if ($action === 'marquer_lu') {
                $contact_id = $_POST['contact_id'];
                $stmt = $pdo->prepare("UPDATE contacts SET statut = 'lu' WHERE id = ?");
                $stmt->execute([$contact_id]);
                $success = "Message marqué comme lu!";
                
            } elseif ($action === 'marquer_non_lu') {
                $contact_id = $_POST['contact_id'];
                $stmt = $pdo->prepare("UPDATE contacts SET statut = 'non_lu' WHERE id = ?");
                $stmt->execute([$contact_id]);
                $success = "Message marqué comme non lu!";
                
            } elseif ($action === 'repondre') {
                $contact_id = $_POST['contact_id'];
                $reponse = $_POST['reponse'];
                
                $stmt = $pdo->prepare("UPDATE contacts SET statut = 'repondu', reponse = ?, date_reponse = NOW() WHERE id = ?");
                $stmt->execute([$reponse, $contact_id]);
                $success = "Réponse envoyée avec succès!";
                
            } elseif ($action === 'supprimer') {
                $contact_id = $_POST['contact_id'];
                $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
                $stmt->execute([$contact_id]);
                $success = "Message supprimé avec succès!";
                
            } elseif ($action === 'marquer_tous_lus') {
                $stmt = $pdo->prepare("UPDATE contacts SET statut = 'lu' WHERE statut = 'non_lu'");
                $stmt->execute();
                $success = "Tous les messages ont été marqués comme lus!";
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
$sql = "SELECT * FROM contacts WHERE 1=1";
$params = [];

// Filtre par statut
if ($statut_filter !== 'tous') {
    $sql .= " AND statut = ?";
    $params[] = $statut_filter;
}

// Filtre de recherche
if (!empty($search_term)) {
    $sql .= " AND (nom LIKE ? OR email LIKE ? OR sujet LIKE ? OR message LIKE ?)";
    $search_like = "%$search_term%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
}

$sql .= " ORDER BY date_envoi DESC";

// Exécuter la requête avec gestion d'erreur
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $contacts = [];
    $error = "Erreur lors du chargement des messages: " . $e->getMessage();
}

// Statistiques avec gestion d'erreur et valeurs par défaut
try {
    $total_messages = $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
} catch (PDOException $e) {
    $total_messages = 0;
}

try {
    $messages_non_lus = $pdo->query("SELECT COUNT(*) FROM contacts WHERE statut = 'non_lu'")->fetchColumn();
} catch (PDOException $e) {
    $messages_non_lus = 0;
}

try {
    $messages_lus = $pdo->query("SELECT COUNT(*) FROM contacts WHERE statut = 'lu'")->fetchColumn();
} catch (PDOException $e) {
    $messages_lus = 0;
}

try {
    $messages_repondus = $pdo->query("SELECT COUNT(*) FROM contacts WHERE statut = 'repondu'")->fetchColumn();
} catch (PDOException $e) {
    $messages_repondus = 0;
}

// Calculer le temps écoulé pour chaque message et assurer les valeurs par défaut
foreach ($contacts as &$contact) {
    // Assurer que toutes les colonnes existent
    $contact['statut'] = $contact['statut'] ?? 'non_lu';
    $contact['date_envoi'] = $contact['date_envoi'] ?? date('Y-m-d H:i:s');
    $contact['reponse'] = $contact['reponse'] ?? '';
    $contact['date_reponse'] = $contact['date_reponse'] ?? null;
    
    $date_envoi = new DateTime($contact['date_envoi']);
    $now = new DateTime();
    $interval = $date_envoi->diff($now);
    
    if ($interval->days > 0) {
        $contact['temps_ecoule'] = $interval->days . ' jour' . ($interval->days > 1 ? 's' : '');
    } elseif ($interval->h > 0) {
        $contact['temps_ecoule'] = $interval->h . ' heure' . ($interval->h > 1 ? 's' : '');
    } else {
        $contact['temps_ecoule'] = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '');
    }
    
    $contact['est_recent'] = $interval->days < 1 && $contact['statut'] === 'non_lu';
}
unset($contact);
?>

<section class="gestion-contacts py-5">
    <div class="container">
        <!-- En-tête -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="text-primary">
                        <i class="fas fa-envelope me-3"></i>Gestion des Messages
                    </h1>
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Retour au Dashboard
                    </a>
                </div>
                <p class="text-muted">Gérez et répondez aux messages des clients</p>
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
                <div class="stats-card bg-primary text-white">
                    <div class="stats-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo $total_messages; ?></div>
                        <div class="stats-label">Total Messages</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stats-card bg-warning text-white">
                    <div class="stats-icon">
                        <i class="fas fa-envelope-open"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo $messages_non_lus; ?></div>
                        <div class="stats-label">Non Lus</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stats-card bg-info text-white">
                    <div class="stats-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo $messages_lus; ?></div>
                        <div class="stats-label">Lus</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stats-card bg-success text-white">
                    <div class="stats-icon">
                        <i class="fas fa-reply"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo $messages_repondus; ?></div>
                        <div class="stats-label">Répondus</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stats-card bg-secondary text-white">
                    <div class="stats-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo $total_messages > 0 ? round((($messages_lus + $messages_repondus) / $total_messages) * 100) : 0; ?>%</div>
                        <div class="stats-label">Taux Traitement</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stats-card bg-dark text-white" onclick="refreshPage()" style="cursor: pointer;">
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
                            <option value="tous" <?php echo $statut_filter === 'tous' ? 'selected' : ''; ?>>Tous les messages</option>
                            <option value="non_lu" <?php echo $statut_filter === 'non_lu' ? 'selected' : ''; ?>>Non lus</option>
                            <option value="lu" <?php echo $statut_filter === 'lu' ? 'selected' : ''; ?>>Lus</option>
                            <option value="repondu" <?php echo $statut_filter === 'repondu' ? 'selected' : ''; ?>>Répondus</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Rechercher:</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Nom, email, sujet ou message..." value="<?php echo htmlspecialchars($search_term); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <a href="gestion_contacts.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times"></i> Effacer
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des messages -->
        <div class="card">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-inbox me-2"></i>Messages des Clients
                    <span class="badge bg-primary ms-2"><?php echo count($contacts); ?></span>
                </h5>
                <div class="header-actions">
                    <?php if($messages_non_lus > 0): ?>
                    <button class="btn btn-sm btn-outline-light" onclick="marquerTousLus()">
                        <i class="fas fa-check-double me-1"></i>Tout marquer lu
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if(empty($contacts)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-4x mb-3"></i>
                    <h4>Aucun message trouvé</h4>
                    <p>Essayez de modifier vos critères de recherche ou de filtrage.</p>
                    <a href="gestion_contacts.php" class="btn btn-primary">
                        <i class="fas fa-times me-2"></i>Effacer les filtres
                    </a>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach($contacts as $contact): 
                        $badge_class = [
                            'non_lu' => 'bg-warning',
                            'lu' => 'bg-info', 
                            'repondu' => 'bg-success'
                        ];
                        $statut_text = [
                            'non_lu' => 'Non lu',
                            'lu' => 'Lu',
                            'repondu' => 'Répondu'
                        ];
                    ?>
                    <div class="list-group-item <?php echo $contact['est_recent'] ? 'bg-light' : ''; ?> message-item" 
                         data-statut="<?php echo $contact['statut']; ?>"
                         id="message-<?php echo $contact['id']; ?>">
                        <div class="row align-items-center">
                            <!-- Statut et informations principales -->
                            <div class="col-md-8">
                                <div class="d-flex align-items-start mb-2">
                                    <div class="me-3">
                                        <?php if($contact['statut'] === 'non_lu'): ?>
                                        <span class="badge <?php echo $badge_class[$contact['statut']]; ?> me-2">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                        <?php else: ?>
                                        <span class="badge <?php echo $badge_class[$contact['statut']]; ?> me-2">
                                            <i class="fas fa-envelope-open"></i>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($contact['sujet']); ?></h6>
                                            <small class="text-muted"><?php echo $contact['temps_ecoule']; ?></small>
                                        </div>
                                        <p class="mb-1 text-truncate"><?php echo htmlspecialchars($contact['message']); ?></p>
                                        <div class="d-flex align-items-center text-muted small">
                                            <span class="me-3">
                                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($contact['nom']); ?>
                                            </span>
                                            <span>
                                                <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($contact['email']); ?>
                                            </span>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i><?php echo date('d/m/Y à H:i', strtotime($contact['date_envoi'])); ?>
                                        </small>
                                        <?php if($contact['date_reponse']): ?>
                                        <br>
                                        <small class="text-success">
                                            <i class="fas fa-reply me-1"></i>Répondu le <?php echo date('d/m/Y à H:i', strtotime($contact['date_reponse'])); ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="col-md-4">
                                <div class="btn-group btn-group-sm w-100">
                                    <button class="btn btn-outline-primary" 
                                            data-bs-toggle="tooltip" 
                                            title="Voir les détails"
                                            onclick="voirDetails(<?php echo $contact['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if($contact['statut'] === 'non_lu'): ?>
                                    <button class="btn btn-outline-success" 
                                            data-bs-toggle="tooltip" 
                                            title="Marquer comme lu"
                                            onclick="marquerLu(<?php echo $contact['id']; ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-outline-warning" 
                                            data-bs-toggle="tooltip" 
                                            title="Marquer comme non lu"
                                            onclick="marquerNonLu(<?php echo $contact['id']; ?>)">
                                        <i class="fas fa-envelope"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-outline-info" 
                                            data-bs-toggle="tooltip" 
                                            title="Répondre"
                                            onclick="repondreMessage(<?php echo $contact['id']; ?>)">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                    
                                    <button class="btn btn-outline-danger" 
                                            data-bs-toggle="tooltip" 
                                            title="Supprimer"
                                            onclick="supprimerMessage(<?php echo $contact['id']; ?>, '<?php echo addslashes($contact['sujet']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                
                                <!-- Statut actuel -->
                                <div class="mt-2 text-center">
                                    <span class="badge <?php echo $badge_class[$contact['statut']]; ?>">
                                        <?php echo $statut_text[$contact['statut']]; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Réponse (affichée si existante) -->
                        <?php if(!empty($contact['reponse'])): ?>
                        <div class="mt-3 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong class="text-success">
                                    <i class="fas fa-reply me-2"></i>Votre réponse
                                </strong>
                                <?php if($contact['date_reponse']): ?>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y à H:i', strtotime($contact['date_reponse'])); ?>
                                </small>
                                <?php endif; ?>
                            </div>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($contact['reponse'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pagination (optionnelle) -->
        <?php if(count($contacts) > 0): ?>
        <div class="row mt-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Affichage de <strong><?php echo count($contacts); ?></strong> message(s)
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

<!-- Modal Voir Détails -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>Détails du Message
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsBody">
                <!-- Contenu chargé dynamiquement -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="repondreMessageFromDetails()">
                    <i class="fas fa-reply me-2"></i>Répondre
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Répondre -->
<div class="modal fade" id="repondreModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-reply me-2"></i>Répondre au Message
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="repondre">
                    <input type="hidden" name="contact_id" id="repondre_contact_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Message original:</label>
                        <div class="border p-3 bg-light rounded" id="message_original">
                            <!-- Contenu chargé dynamiquement -->
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Votre réponse *</label>
                        <textarea class="form-control" name="reponse" rows="6" placeholder="Tapez votre réponse ici..." required></textarea>
                        <div class="form-text">Votre réponse sera envoyée par email au client.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane me-2"></i>Envoyer la réponse
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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
.gestion-contacts {
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

.message-item {
    transition: all 0.3s ease;
}

.message-item:hover {
    background-color: #f8f9fa !important;
}

.message-item.bg-light {
    border-left: 4px solid #ffc107;
}

/* Animation pour les nouveaux messages */
@keyframes pulse {
    0% { background-color: #fff3cd; }
    50% { background-color: #ffeaa7; }
    100% { background-color: #fff3cd; }
}

.message-item.bg-light {
    animation: pulse 2s infinite;
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
    
    .message-item .row > div {
        margin-bottom: 1rem;
    }
}
</style>

<script>
let currentContactId = null;
let currentAction = null;

// Initialiser les tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

function refreshPage() {
    window.location.reload();
}

// Fonctions d'action
function voirDetails(contactId) {
    // Simuler le chargement des détails (dans une vraie app, on ferait un appel AJAX)
    const message = document.querySelector(`#message-${contactId}`);
    const sujet = message.querySelector('h6').textContent;
    const nom = message.querySelector('.fa-user').parentNode.textContent.trim();
    const email = message.querySelector('.fa-envelope').parentNode.textContent.trim();
    const date = message.querySelector('.fa-clock').parentNode.textContent.trim();
    const messageText = message.querySelector('p').textContent;
    
    document.getElementById('detailsBody').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <strong>De:</strong> ${nom}
            </div>
            <div class="col-md-6">
                <strong>Email:</strong> ${email}
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Date:</strong> ${date}
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Sujet:</strong> ${sujet}
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <strong>Message:</strong>
                <div class="border p-3 bg-light rounded mt-2">
                    ${messageText}
                </div>
            </div>
        </div>
    `;
    
    currentContactId = contactId;
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    modal.show();
}

function repondreMessageFromDetails() {
    const detailsModal = bootstrap.Modal.getInstance(document.getElementById('detailsModal'));
    detailsModal.hide();
    repondreMessage(currentContactId);
}

function repondreMessage(contactId) {
    const message = document.querySelector(`#message-${contactId}`);
    const sujet = message.querySelector('h6').textContent;
    const nom = message.querySelector('.fa-user').parentNode.textContent.trim();
    const messageText = message.querySelector('p').textContent;
    
    document.getElementById('repondre_contact_id').value = contactId;
    document.getElementById('message_original').innerHTML = `
        <strong>De:</strong> ${nom}<br>
        <strong>Sujet:</strong> ${sujet}<br>
        <strong>Message:</strong><br>
        ${messageText}
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('repondreModal'));
    modal.show();
}

function marquerLu(contactId) {
    currentContactId = contactId;
    currentAction = 'marquer_lu';
    
    document.getElementById('modalTitle').textContent = 'Marquer comme lu';
    document.getElementById('modalBody').innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Êtes-vous sûr de vouloir marquer ce message comme lu ?
        </div>
    `;
    document.getElementById('confirmAction').textContent = 'Marquer lu';
    document.getElementById('confirmAction').className = 'btn btn-success';
    
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    modal.show();
}

function marquerNonLu(contactId) {
    currentContactId = contactId;
    currentAction = 'marquer_non_lu';
    
    document.getElementById('modalTitle').textContent = 'Marquer comme non lu';
    document.getElementById('modalBody').innerHTML = `
        <div class="alert alert-warning">
            <i class="fas fa-info-circle me-2"></i>
            Êtes-vous sûr de vouloir marquer ce message comme non lu ?
        </div>
    `;
    document.getElementById('confirmAction').textContent = 'Marquer non lu';
    document.getElementById('confirmAction').className = 'btn btn-warning';
    
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    modal.show();
}

function marquerTousLus() {
    if (confirm('Êtes-vous sûr de vouloir marquer tous les messages non lus comme lus ?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'marquer_tous_lus';
        
        form.appendChild(actionInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function supprimerMessage(contactId, sujet) {
    currentContactId = contactId;
    currentAction = 'supprimer';
    
    document.getElementById('modalTitle').textContent = 'Supprimer le Message';
    document.getElementById('modalBody').innerHTML = `
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Êtes-vous sûr de vouloir supprimer définitivement le message "<strong>${sujet}</strong>" ?
        </div>
        <p class="mb-0"><strong>Cette action est irréversible !</strong></p>
    `;
    document.getElementById('confirmAction').textContent = 'Supprimer';
    document.getElementById('confirmAction').className = 'btn btn-danger';
    
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    modal.show();
}

// Gérer la confirmation d'action
document.getElementById('confirmAction').addEventListener('click', function() {
    if (currentContactId && currentAction) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = currentAction;
        
        const contactInput = document.createElement('input');
        contactInput.type = 'hidden';
        contactInput.name = 'contact_id';
        contactInput.value = currentContactId;
        
        form.appendChild(actionInput);
        form.appendChild(contactInput);
        document.body.appendChild(form);
        
        form.submit();
    }
});

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
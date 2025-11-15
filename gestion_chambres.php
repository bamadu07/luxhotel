<?php
// gestion_chambres.php
include 'includes/header.php';
include 'config/database.php';

// Vérifier si l'admin est connecté
if(!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Fonction pour créer ou mettre à jour la table chambres
function initialiserTableChambres($pdo) {
    // Vérifier si la table existe
    try {
        $pdo->query("SELECT 1 FROM chambres LIMIT 1");
    } catch (PDOException $e) {
        // Table n'existe pas, la créer
        $pdo->exec("
            CREATE TABLE chambres (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(100) NOT NULL,
                type VARCHAR(50) NOT NULL,
                prix_nuit DECIMAL(10,2) NOT NULL,
                description TEXT,
                capacite INT NOT NULL,
                equipements TEXT,
                statut ENUM('disponible', 'occupee', 'maintenance') DEFAULT 'disponible',
                date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        return true;
    }
    
    // Vérifier et ajouter les colonnes manquantes
    $colonnes_requises = [
        'nom' => "ALTER TABLE chambres ADD COLUMN nom VARCHAR(100) NOT NULL AFTER id",
        'type' => "ALTER TABLE chambres ADD COLUMN type VARCHAR(50) NOT NULL AFTER nom", 
        'prix_nuit' => "ALTER TABLE chambres ADD COLUMN prix_nuit DECIMAL(10,2) NOT NULL AFTER type",
        'description' => "ALTER TABLE chambres ADD COLUMN description TEXT AFTER prix_nuit",
        'capacite' => "ALTER TABLE chambres ADD COLUMN capacite INT NOT NULL AFTER description",
        'equipements' => "ALTER TABLE chambres ADD COLUMN equipements TEXT AFTER capacite",
        'statut' => "ALTER TABLE chambres ADD COLUMN statut ENUM('disponible', 'occupee', 'maintenance') DEFAULT 'disponible' AFTER equipements",
        'date_creation' => "ALTER TABLE chambres ADD COLUMN date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER statut"
    ];
    
    $modifications = false;
    foreach ($colonnes_requises as $colonne => $sql) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM chambres LIKE '$colonne'");
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
$table_modifiee = initialiserTableChambres($pdo);

// Si la table a été modifiée ou créée, insérer des données d'exemple
if ($table_modifiee) {
    $chambres_exemple = [
        ['Chambre 101', 'Chambre Simple', 25000, 'Chambre confortable avec lit simple', 1, 'TV, WiFi, Salle de bain privée', 'disponible'],
        ['Chambre 102', 'Chambre Double', 35000, 'Chambre spacieuse avec lit double', 2, 'TV, WiFi, Climatisation, Salle de bain privée', 'disponible'],
        ['Chambre 201', 'Chambre Twin', 40000, 'Chambre avec deux lits simples', 2, 'TV, WiFi, Climatisation, Mini-bar', 'occupee'],
        ['Suite 301', 'Suite Junior', 75000, 'Suite luxueuse avec salon', 3, 'TV écran plat, WiFi, Climatisation, Mini-bar, Jacuzzi', 'disponible']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO chambres (nom, type, prix_nuit, description, capacite, equipements, statut) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($chambres_exemple as $chambre) {
        try {
            $stmt->execute($chambre);
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
            if ($action === 'ajouter') {
                $nom = $_POST['nom'];
                $type = $_POST['type'];
                $prix = $_POST['prix'];
                $description = $_POST['description'];
                $capacite = $_POST['capacite'];
                $equipements = $_POST['equipements'];
                
                $stmt = $pdo->prepare("INSERT INTO chambres (nom, type, prix_nuit, description, capacite, equipements, statut) VALUES (?, ?, ?, ?, ?, ?, 'disponible')");
                $stmt->execute([$nom, $type, $prix, $description, $capacite, $equipements]);
                $success = "Chambre ajoutée avec succès!";
                
            } elseif ($action === 'modifier') {
                $chambre_id = $_POST['chambre_id'];
                $nom = $_POST['nom'];
                $type = $_POST['type'];
                $prix = $_POST['prix'];
                $description = $_POST['description'];
                $capacite = $_POST['capacite'];
                $equipements = $_POST['equipements'];
                $statut = $_POST['statut'];
                
                $stmt = $pdo->prepare("UPDATE chambres SET nom = ?, type = ?, prix_nuit = ?, description = ?, capacite = ?, equipements = ?, statut = ? WHERE id = ?");
                $stmt->execute([$nom, $type, $prix, $description, $capacite, $equipements, $statut, $chambre_id]);
                $success = "Chambre modifiée avec succès!";
                
            } elseif ($action === 'changer_statut') {
                $chambre_id = $_POST['chambre_id'];
                $nouveau_statut = $_POST['nouveau_statut'];
                
                $stmt = $pdo->prepare("UPDATE chambres SET statut = ? WHERE id = ?");
                $stmt->execute([$nouveau_statut, $chambre_id]);
                $success = "Statut de la chambre mis à jour!";
                
            } elseif ($action === 'supprimer') {
                $chambre_id = $_POST['chambre_id'];
                
                // Vérifier s'il y a des réservations pour cette chambre
                $chambre_info = $pdo->prepare("SELECT nom FROM chambres WHERE id = ?");
                $chambre_info->execute([$chambre_id]);
                $chambre = $chambre_info->fetch();
                
                if ($chambre) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE type_chambre = ?");
                    $stmt->execute([$chambre['nom']]);
                    $reservations_existantes = $stmt->fetchColumn();
                    
                    if ($reservations_existantes > 0) {
                        $error = "Impossible de supprimer cette chambre : il y a des réservations associées.";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM chambres WHERE id = ?");
                        $stmt->execute([$chambre_id]);
                        $success = "Chambre supprimée avec succès!";
                    }
                }
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de l'opération: " . $e->getMessage();
        }
    }
}

// Récupérer les chambres avec gestion d'erreur
try {
    $stmt = $pdo->query("SELECT * FROM chambres ORDER BY nom");
    $chambres = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $chambres = [];
    $error = "Erreur lors du chargement des chambres: " . $e->getMessage();
}

// Compter les réservations pour chaque chambre avec valeurs par défaut
foreach ($chambres as &$chambre) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE type_chambre = ?");
        $stmt->execute([$chambre['nom']]);
        $chambre['nombre_reservations'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE type_chambre = ? AND date_arrivee >= CURDATE()");
        $stmt->execute([$chambre['nom']]);
        $chambre['reservations_futures'] = $stmt->fetchColumn();
    } catch (PDOException $e) {
        $chambre['nombre_reservations'] = 0;
        $chambre['reservations_futures'] = 0;
    }
    
    // Assurer que toutes les colonnes existent
    $chambre['nom'] = $chambre['nom'] ?? 'Chambre ' . $chambre['id'];
    $chambre['type'] = $chambre['type'] ?? 'Chambre Simple';
    $chambre['prix_nuit'] = $chambre['prix_nuit'] ?? 0;
    $chambre['description'] = $chambre['description'] ?? '';
    $chambre['capacite'] = $chambre['capacite'] ?? 2;
    $chambre['equipements'] = $chambre['equipements'] ?? '';
    $chambre['statut'] = $chambre['statut'] ?? 'disponible';
}
unset($chambre); // Détruire la référence

// Statistiques avec gestion d'erreur
try {
    $total_chambres = $pdo->query("SELECT COUNT(*) FROM chambres")->fetchColumn();
    $chambres_disponibles = $pdo->query("SELECT COUNT(*) FROM chambres WHERE statut = 'disponible'")->fetchColumn();
    $chambres_occupees = $pdo->query("SELECT COUNT(*) FROM chambres WHERE statut = 'occupee'")->fetchColumn();
    $chambres_maintenance = $pdo->query("SELECT COUNT(*) FROM chambres WHERE statut = 'maintenance'")->fetchColumn();
} catch (PDOException $e) {
    $total_chambres = count($chambres);
    $chambres_disponibles = 0;
    $chambres_occupees = 0;
    $chambres_maintenance = 0;
    foreach ($chambres as $chambre) {
        if ($chambre['statut'] === 'disponible') $chambres_disponibles++;
        elseif ($chambre['statut'] === 'occupee') $chambres_occupees++;
        elseif ($chambre['statut'] === 'maintenance') $chambres_maintenance++;
    }
}

// Types de chambres disponibles
$types_chambres = ['Chambre Simple', 'Chambre Double', 'Chambre Twin', 'Suite Junior', 'Suite Présidentielle', 'Chambre Familiale'];

// Préparer les données pour le modal de modification
$chambre_a_modifier = null;
if (isset($_GET['modifier'])) {
    $chambre_id = intval($_GET['modifier']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM chambres WHERE id = ?");
        $stmt->execute([$chambre_id]);
        $chambre_a_modifier = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Erreur lors du chargement de la chambre: " . $e->getMessage();
    }
}
?>

<section class="gestion-chambres py-5">
    <div class="container">
        <!-- En-tête -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="text-primary">
                        <i class="fas fa-bed me-3"></i>Gestion des Chambres
                    </h1>
                    <div>
                        <a href="dashboard.php" class="btn btn-outline-primary me-2">
                            <i class="fas fa-arrow-left me-2"></i>Dashboard
                        </a>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#ajouterChambreModal">
                            <i class="fas fa-plus me-2"></i>Nouvelle Chambre
                        </button>
                    </div>
                </div>
                <p class="text-muted">Gérez l'inventaire et la disponibilité des chambres</p>
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
                        <i class="fas fa-bed"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo $total_chambres; ?></div>
                        <div class="stats-label">Total Chambres</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stats-card bg-success text-white">
                    <div class="stats-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo $chambres_disponibles; ?></div>
                        <div class="stats-label">Disponibles</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stats-card bg-warning text-white">
                    <div class="stats-icon">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo $chambres_occupees; ?></div>
                        <div class="stats-label">Occupées</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stats-card bg-danger text-white">
                    <div class="stats-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo $chambres_maintenance; ?></div>
                        <div class="stats-label">Maintenance</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stats-card bg-info text-white">
                    <div class="stats-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo $total_chambres > 0 ? round(($chambres_disponibles / $total_chambres) * 100) : 0; ?>%</div>
                        <div class="stats-label">Taux Disponibilité</div>
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

        <!-- Filtres -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filtrer par statut:</label>
                        <select class="form-select" onchange="filtrerChambres(this.value)">
                            <option value="tous">Toutes les chambres</option>
                            <option value="disponible">Disponibles</option>
                            <option value="occupee">Occupées</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Filtrer par type:</label>
                        <select class="form-select" onchange="filtrerParType(this.value)">
                            <option value="tous">Tous les types</option>
                            <?php foreach($types_chambres as $type): ?>
                            <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Rechercher:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Nom de la chambre..." id="searchInput" onkeyup="rechercherChambres()">
                            <button class="btn btn-outline-secondary" type="button" onclick="effacerRecherche()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grille des chambres -->
        <div class="row" id="chambresGrid">
            <?php if(empty($chambres)): ?>
            <div class="col-12">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-bed fa-4x mb-3"></i>
                    <h4>Aucune chambre configurée</h4>
                    <p>Commencez par ajouter votre première chambre à l'hôtel.</p>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#ajouterChambreModal">
                        <i class="fas fa-plus me-2"></i>Ajouter une Chambre
                    </button>
                </div>
            </div>
            <?php else: ?>
            <?php foreach($chambres as $chambre): 
                $badge_class = [
                    'disponible' => 'bg-success',
                    'occupee' => 'bg-warning',
                    'maintenance' => 'bg-danger'
                ];
                $statut_text = [
                    'disponible' => 'Disponible',
                    'occupee' => 'Occupée',
                    'maintenance' => 'Maintenance'
                ];
            ?>
            <div class="col-xl-4 col-md-6 mb-4 chambre-card" 
                 data-statut="<?php echo $chambre['statut']; ?>"
                 data-type="<?php echo $chambre['type']; ?>"
                 data-nom="<?php echo strtolower($chambre['nom']); ?>">
                <div class="card h-100 chambre-card-inner border-<?php echo $chambre['statut'] === 'disponible' ? 'success' : ($chambre['statut'] === 'occupee' ? 'warning' : 'danger'); ?>">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-bed me-2"></i><?php echo $chambre['nom']; ?>
                        </h5>
                        <span class="badge <?php echo $badge_class[$chambre['statut']]; ?>">
                            <?php echo $statut_text[$chambre['statut']]; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted">Type</small>
                                <div class="fw-bold"><?php echo $chambre['type']; ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Capacité</small>
                                <div class="fw-bold">
                                    <i class="fas fa-user me-1"></i><?php echo $chambre['capacite']; ?> personne(s)
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">Prix par nuit</small>
                            <div class="h5 text-success mb-1"><?php echo number_format($chambre['prix_nuit'], 0, ',', ' '); ?> FCFA</div>
                        </div>
                        
                        <?php if(!empty($chambre['description'])): ?>
                        <div class="mb-3">
                            <small class="text-muted">Description</small>
                            <p class="mb-1 small"><?php echo $chambre['description']; ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($chambre['equipements'])): ?>
                        <div class="mb-3">
                            <small class="text-muted">Équipements</small>
                            <div class="equipements-list">
                                <?php 
                                $equipements = explode(',', $chambre['equipements']);
                                foreach($equipements as $equipement):
                                    if(trim($equipement)):
                                ?>
                                <span class="badge bg-light text-dark me-1 mb-1"><?php echo trim($equipement); ?></span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="stats-chambre">
                            <div class="row text-center">
                                <div class="col-6">
                                    <small class="text-muted">Réservations</small>
                                    <div class="fw-bold"><?php echo $chambre['nombre_reservations']; ?></div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Futures</small>
                                    <div class="fw-bold text-<?php echo $chambre['reservations_futures'] > 0 ? 'warning' : 'muted'; ?>">
                                        <?php echo $chambre['reservations_futures']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="btn-group w-100">
                            <a href="?modifier=<?php echo $chambre['id']; ?>" class="btn btn-outline-primary btn-sm" data-bs-toggle="tooltip" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>
                            
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                    type="button" 
                                    data-bs-toggle="dropdown"
                                    data-bs-toggle="tooltip" title="Changer statut">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item <?php echo $chambre['statut'] === 'disponible' ? 'active' : ''; ?>" 
                                       href="#" onclick="changerStatut(<?php echo $chambre['id']; ?>, 'disponible')">
                                        <i class="fas fa-check-circle text-success me-2"></i>Disponible
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?php echo $chambre['statut'] === 'occupee' ? 'active' : ''; ?>" 
                                       href="#" onclick="changerStatut(<?php echo $chambre['id']; ?>, 'occupee')">
                                        <i class="fas fa-user-clock text-warning me-2"></i>Occupée
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?php echo $chambre['statut'] === 'maintenance' ? 'active' : ''; ?>" 
                                       href="#" onclick="changerStatut(<?php echo $chambre['id']; ?>, 'maintenance')">
                                        <i class="fas fa-tools text-danger me-2"></i>Maintenance
                                    </a>
                                </li>
                            </ul>
                            
                            <button class="btn btn-outline-danger btn-sm" 
                                    onclick="supprimerChambre(<?php echo $chambre['id']; ?>, '<?php echo $chambre['nom']; ?>')"
                                    data-bs-toggle="tooltip" title="Supprimer"
                                    <?php echo $chambre['reservations_futures'] > 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Modal Ajouter Chambre -->
<div class="modal fade" id="ajouterChambreModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Ajouter une Nouvelle Chambre
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="ajouter">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom de la chambre *</label>
                            <input type="text" class="form-control" name="nom" required placeholder="Ex: Chambre 101">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type de chambre *</label>
                            <select class="form-select" name="type" required>
                                <option value="">Sélectionnez un type</option>
                                <?php foreach($types_chambres as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prix par nuit (FCFA) *</label>
                            <input type="number" class="form-control" name="prix" required min="0" step="1000" placeholder="50000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Capacité (personnes) *</label>
                            <input type="number" class="form-control" name="capacite" required min="1" max="10" value="2">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Description de la chambre..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Équipements</label>
                            <input type="text" class="form-control" name="equipements" placeholder="Climatisation, TV, WiFi, Mini-bar... (séparés par des virgules)">
                            <div class="form-text">Listez les équipements séparés par des virgules</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifier Chambre -->
<?php if($chambre_a_modifier): ?>
<div class="modal fade show" id="modifierChambreModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Modifier la Chambre
                    </h5>
                    <a href="?" class="btn-close"></a>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="modifier">
                    <input type="hidden" name="chambre_id" value="<?php echo $chambre_a_modifier['id']; ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom de la chambre *</label>
                            <input type="text" class="form-control" name="nom" value="<?php echo htmlspecialchars($chambre_a_modifier['nom']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type de chambre *</label>
                            <select class="form-select" name="type" required>
                                <option value="">Sélectionnez un type</option>
                                <?php foreach($types_chambres as $type): ?>
                                <option value="<?php echo $type; ?>" <?php echo $chambre_a_modifier['type'] === $type ? 'selected' : ''; ?>>
                                    <?php echo $type; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prix par nuit (FCFA) *</label>
                            <input type="number" class="form-control" name="prix" value="<?php echo $chambre_a_modifier['prix_nuit']; ?>" required min="0" step="1000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Capacité (personnes) *</label>
                            <input type="number" class="form-control" name="capacite" value="<?php echo $chambre_a_modifier['capacite']; ?>" required min="1" max="10">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Statut *</label>
                            <select class="form-select" name="statut" required>
                                <option value="disponible" <?php echo $chambre_a_modifier['statut'] === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                                <option value="occupee" <?php echo $chambre_a_modifier['statut'] === 'occupee' ? 'selected' : ''; ?>>Occupée</option>
                                <option value="maintenance" <?php echo $chambre_a_modifier['statut'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($chambre_a_modifier['description']); ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Équipements</label>
                            <input type="text" class="form-control" name="equipements" value="<?php echo htmlspecialchars($chambre_a_modifier['equipements']); ?>" placeholder="Climatisation, TV, WiFi, Mini-bar... (séparés par des virgules)">
                            <div class="form-text">Listez les équipements séparés par des virgules</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="?" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

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
.gestion-chambres {
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

.chambre-card-inner {
    transition: all 0.3s ease;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.chambre-card-inner:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.equipements-list {
    max-height: 80px;
    overflow-y: auto;
}

.stats-chambre {
    border-top: 1px solid #e9ecef;
    padding-top: 1rem;
    margin-top: 1rem;
}

.btn-outline-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,123,255,0.3);
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
    
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        margin-bottom: 2px;
    }
}
</style>

<script>
let currentChambreId = null;
let currentAction = null;

// Initialiser les tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Fonctions de filtrage
function filtrerChambres(statut) {
    const cards = document.querySelectorAll('.chambre-card');
    cards.forEach(card => {
        if (statut === 'tous' || card.getAttribute('data-statut') === statut) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function filtrerParType(type) {
    const cards = document.querySelectorAll('.chambre-card');
    cards.forEach(card => {
        if (type === 'tous' || card.getAttribute('data-type') === type) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function rechercherChambres() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.chambre-card');
    
    cards.forEach(card => {
        const nom = card.getAttribute('data-nom');
        if (nom.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function effacerRecherche() {
    document.getElementById('searchInput').value = '';
    rechercherChambres();
}

function refreshPage() {
    window.location.reload();
}

function changerStatut(chambreId, nouveauStatut) {
    currentChambreId = chambreId;
    currentAction = 'changer_statut';
    
    const statuts = {
        'disponible': 'Disponible',
        'occupee': 'Occupée', 
        'maintenance': 'Maintenance'
    };
    
    document.getElementById('modalTitle').textContent = 'Changer le Statut';
    document.getElementById('modalBody').innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Êtes-vous sûr de vouloir changer le statut de cette chambre en "<strong>${statuts[nouveauStatut]}</strong>" ?
        </div>
    `;
    document.getElementById('confirmAction').textContent = 'Changer';
    document.getElementById('confirmAction').className = 'btn btn-warning';
    
    // Stocker le nouveau statut dans un champ caché
    document.getElementById('confirmAction').setAttribute('data-nouveau-statut', nouveauStatut);
    
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    modal.show();
}

function supprimerChambre(chambreId, nomChambre) {
    currentChambreId = chambreId;
    currentAction = 'supprimer';
    
    document.getElementById('modalTitle').textContent = 'Supprimer la Chambre';
    document.getElementById('modalBody').innerHTML = `
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Êtes-vous sûr de vouloir supprimer définitivement la chambre "<strong>${nomChambre}</strong>" ?
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
    if (currentChambreId && currentAction) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = currentAction;
        
        const chambreInput = document.createElement('input');
        chambreInput.type = 'hidden';
        chambreInput.name = 'chambre_id';
        chambreInput.value = currentChambreId;
        
        form.appendChild(actionInput);
        form.appendChild(chambreInput);
        
        // Ajouter le nouveau statut si c'est un changement de statut
        if (currentAction === 'changer_statut') {
            const nouveauStatutInput = document.createElement('input');
            nouveauStatutInput.type = 'hidden';
            nouveauStatutInput.name = 'nouveau_statut';
            nouveauStatutInput.value = this.getAttribute('data-nouveau-statut');
            form.appendChild(nouveauStatutInput);
        }
        
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
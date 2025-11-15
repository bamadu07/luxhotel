<?php
// chambres.php
session_start();
include 'includes/header.php';
include 'config/database.php';

// Récupérer toutes les chambres
$stmt = $pdo->query("SELECT * FROM chambres WHERE disponibilite = 1 ORDER BY prix_nuit ASC");
$chambres = $stmt->fetchAll(PDO::FETCH_ASSOC);

$taux_fcfa = 655;

// Fonction pour obtenir des valeurs sécurisées avec valeurs par défaut
function getChambreValue($chambre, $key, $default = '') {
    return isset($chambre[$key]) ? $chambre[$key] : $default;
}

// Images par défaut pour les chambres
$images_chambres = [
    'default' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=500&h=300&fit=crop',
    'simple' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=500&h=300&fit=crop',
    'double' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=500&h=300&fit=crop',
    'suite' => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=500&h=300&fit=crop',
    'familiale' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=500&h=300&fit=crop',
    'luxe' => 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=500&h=300&fit=crop'
];

// Catégories disponibles pour les filtres
$categories = [];
$all_features = [];

foreach ($chambres as $chambre) {
    $categorie = getChambreValue($chambre, 'categorie', 'Standard');
    if (!in_array($categorie, $categories)) {
        $categories[] = $categorie;
    }
    
    // Collecter tous les équipements pour les filtres
    $equipements = getChambreValue($chambre, 'equipements', 'WiFi, TV écran plat, Climatisation, Salle de bain privée');
    $features = array_map('trim', explode(',', $equipements));
    $all_features = array_merge($all_features, $features);
}

$all_features = array_unique($all_features);
sort($all_features);
?>

<section class="chambres-page">
    <!-- Hero Section pour Chambres -->
    <div class="chambres-hero bg-primary text-white">
        <div class="container">
            <div class="row align-items-center min-vh-60">
                <div class="col-lg-12 text-center hero-content fade-in">
                    <h1 class="hero-title display-4 fw-bold mb-4">Nos Chambres & Suites</h1>
                    <p class="hero-subtitle lead mb-5 mx-auto" style="max-width: 600px;">
                        Découvrez notre collection exclusive de chambres et suites soigneusement conçues 
                        pour allier confort, élégance et modernité. Chaque espace a été pensé pour 
                        votre bien-être.
                    </p>
                    
                    <!-- Statistiques animées -->
                    <div class="hero-stats row justify-content-center">
                        <div class="col-4 col-md-3 text-center">
                            <div class="stat-circle">
                                <div class="stat-number" data-count="<?php echo count($chambres); ?>">0</div>
                                <div class="stat-label">Chambres</div>
                            </div>
                        </div>
                        <div class="col-4 col-md-3 text-center">
                            <div class="stat-circle">
                                <div class="stat-number" data-count="<?php echo count($categories); ?>">0</div>
                                <div class="stat-label">Catégories</div>
                            </div>
                        </div>
                        <div class="col-4 col-md-3 text-center">
                            <div class="stat-circle">
                                <div class="stat-number" data-count="24">0</div>
                                <div class="stat-label">Services</div>
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
        <!-- Filtres Avancés -->
        <div class="filters-section">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="filters-card">
                        <div class="filters-header">
                            <h3 class="text-center mb-0">
                                <i class="fas fa-search me-2"></i>
                                Trouvez la Chambre Parfaite
                            </h3>
                        </div>
                        
                        <div class="filters-body" id="filtersBody">
                            <div class="row g-4 justify-content-center">
                                <!-- Filtres Principaux -->
                                <div class="col-lg-3 col-md-6">
                                    <div class="filter-group">
                                        <label class="filter-label">
                                            <i class="fas fa-tag me-2"></i>Catégorie
                                        </label>
                                        <select class="filter-select" id="filterType">
                                            <option value="all">Toutes les catégories</option>
                                            <?php foreach($categories as $categorie): ?>
                                            <option value="<?php echo strtolower($categorie); ?>"><?php echo htmlspecialchars($categorie); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-lg-2 col-md-6">
                                    <div class="filter-group">
                                        <label class="filter-label">
                                            <i class="fas fa-users me-2"></i>Capacité
                                        </label>
                                        <select class="filter-select" id="filterCapacity">
                                            <option value="all">Toutes</option>
                                            <option value="1">1 personne</option>
                                            <option value="2">2 personnes</option>
                                            <option value="3">3 personnes</option>
                                            <option value="4">4+ personnes</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-lg-3 col-md-6">
                                    <div class="filter-group">
                                        <label class="filter-label">
                                            <i class="fas fa-money-bill-wave me-2"></i>Budget
                                        </label>
                                        <select class="filter-select" id="filterPrice">
                                            <option value="all">Tous les budgets</option>
                                            <option value="50000">Moins de 50.000 FCFA</option>
                                            <option value="100000">50.000 - 100.000 FCFA</option>
                                            <option value="150000">100.000 - 150.000 FCFA</option>
                                            <option value="200000">150.000 - 200.000 FCFA</option>
                                            <option value="300000">Plus de 200.000 FCFA</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-lg-2 col-md-6">
                                    <div class="filter-group">
                                        <label class="filter-label">
                                            <i class="fas fa-sort me-2"></i>Trier par
                                        </label>
                                        <select class="filter-select" id="sortRooms">
                                            <option value="price-asc">Prix croissant</option>
                                            <option value="price-desc">Prix décroissant</option>
                                            <option value="capacity">Capacité</option>
                                            <option value="size">Superficie</option>
                                            <option value="popular">Populaires</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-lg-2 col-md-12">
                                    <div class="filter-group">
                                        <label class="filter-label">&nbsp;</label>
                                        <button class="btn btn-primary w-100 filter-btn" onclick="filterRooms()">
                                            <i class="fas fa-filter me-2"></i>Appliquer
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Filtres Rapides -->
                            <div class="quick-filters mt-4 text-center">
                                <div class="row justify-content-center">
                                    <div class="col-12">
                                        <label class="filter-label mb-3">Suggestions rapides :</label>
                                        <div class="d-flex flex-wrap gap-2 justify-content-center">
                                            <button class="btn btn-outline-primary btn-sm filter-quick active" data-type="all">
                                                <i class="fas fa-star me-1"></i>Toutes
                                            </button>
                                            <button class="btn btn-outline-primary btn-sm filter-quick" data-type="suite">
                                                <i class="fas fa-crown me-1"></i>Suites Premium
                                            </button>
                                            <button class="btn btn-outline-primary btn-sm filter-quick" data-type="vue mer">
                                                <i class="fas fa-water me-1"></i>Vue Exceptionnelle
                                            </button>
                                            <button class="btn btn-outline-primary btn-sm filter-quick" data-type="familiale">
                                                <i class="fas fa-users me-1"></i>Familiales
                                            </button>
                                            <button class="btn btn-outline-secondary btn-sm" onclick="resetFilters()">
                                                <i class="fas fa-redo me-1"></i>Réinitialiser
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- En-tête des résultats -->
        <div class="results-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 id="resultsCount" class="mb-0 text-center text-md-start">
                        <span class="results-number"><?php echo count($chambres); ?></span> chambre(s) disponible(s)
                    </h4>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <div class="view-options">
                        <span class="view-label me-2">Vue :</span>
                        <div class="btn-group" role="group">
                            <button class="btn btn-outline-primary btn-sm active" id="gridView" data-bs-toggle="tooltip" title="Vue Grille">
                                <i class="fas fa-th-large"></i>
                            </button>
                            <button class="btn btn-outline-primary btn-sm" id="listView" data-bs-toggle="tooltip" title="Vue Liste">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grille des chambres -->
        <div class="rooms-grid-section">
            <div class="row justify-content-center" id="roomsGrid">
                <?php foreach($chambres as $index => $chambre): 
                    // Valeurs sécurisées avec fallback
                    $prix_nuit = getChambreValue($chambre, 'prix_nuit', 0);
                    $prix_jour = getChambreValue($chambre, 'prix_jour', $prix_nuit * 0.6);
                    $prix_fcfa = $prix_nuit * $taux_fcfa;
                    $prix_jour_fcfa = $prix_jour * $taux_fcfa;
                    $categorie = getChambreValue($chambre, 'categorie', 'Standard');
                    $description_courte = getChambreValue($chambre, 'description_courte', substr(getChambreValue($chambre, 'description', 'Chambre confortable'), 0, 100));
                    $superficie = getChambreValue($chambre, 'superficie', 25);
                    $equipements = getChambreValue($chambre, 'equipements', 'WiFi, TV écran plat, Climatisation, Salle de bain privée');
                    $features = array_map('trim', explode(',', $equipements));
                    $is_featured = getChambreValue($chambre, 'featured', 0);
                    
                    // Déterminer l'image en fonction du type de chambre
                    $type_chambre = strtolower($chambre['type_chambre']);
                    $image_url = $images_chambres['default'];
                    
                    if (strpos($type_chambre, 'simple') !== false) {
                        $image_url = $images_chambres['simple'];
                    } elseif (strpos($type_chambre, 'double') !== false || strpos($type_chambre, 'twin') !== false) {
                        $image_url = $images_chambres['double'];
                    } elseif (strpos($type_chambre, 'suite') !== false || strpos($type_chambre, 'présidentielle') !== false || strpos($type_chambre, 'luxe') !== false) {
                        $image_url = $images_chambres['suite'];
                    } elseif (strpos($type_chambre, 'familiale') !== false) {
                        $image_url = $images_chambres['familiale'];
                    }
                ?>
                <div class="col-lg-4 col-md-6 mb-4 room-item fade-in" 
                     data-type="<?php echo strtolower($categorie); ?>"
                     data-capacity="<?php echo $chambre['capacite']; ?>"
                     data-price="<?php echo $prix_fcfa; ?>"
                     data-size="<?php echo $superficie; ?>"
                     data-category="<?php echo strtolower($categorie); ?>"
                     data-featured="<?php echo $is_featured; ?>"
                     data-features="<?php echo htmlspecialchars(json_encode($features)); ?>">
                    
                    <!-- Carte Chambre -->
                    <div class="room-card shadow-sm h-100 <?php echo $is_featured ? 'featured-room' : ''; ?>">
                        <?php if($is_featured): ?>
                        <div class="featured-badge">
                            <i class="fas fa-crown me-1"></i>Populaire
                        </div>
                        <?php endif; ?>
                        
                        <div class="room-image-container">
                            <img src="<?php echo $image_url; ?>" 
                                 class="room-image w-100" 
                                 alt="<?php echo htmlspecialchars($chambre['type_chambre']); ?>"
                                 style="height: 250px; object-fit: cover;">
                            
                            <!-- Overlay d'actions -->
                            <div class="room-overlay">
                                <div class="overlay-actions">
                                    <button class="btn btn-light btn-round" data-bs-toggle="modal" data-bs-target="#roomModal<?php echo $chambre['id']; ?>">
                                        <i class="fas fa-expand"></i>
                                    </button>
                                    <button class="btn btn-light btn-round favorite-btn" data-room="<?php echo $chambre['id']; ?>">
                                        <i class="far fa-heart"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Badges -->
                            <div class="room-badges">
                                <span class="badge badge-capacity">
                                    <i class="fas fa-user-friends me-1"></i><?php echo $chambre['capacite']; ?>
                                </span>
                                <span class="badge badge-size">
                                    <i class="fas fa-ruler-combined me-1"></i><?php echo $superficie; ?> m²
                                </span>
                            </div>
                        </div>
                        
                        <div class="room-content">
                            <div class="room-header">
                                <h5 class="room-title fw-bold"><?php echo htmlspecialchars($chambre['type_chambre']); ?></h5>
                                <span class="room-category"><?php echo htmlspecialchars($categorie); ?></span>
                            </div>
                            
                            <p class="room-description text-muted"><?php echo htmlspecialchars($description_courte); ?>...</p>
                            
                            <div class="room-features">
                                <div class="features-grid">
                                    <?php foreach(array_slice($features, 0, 4) as $feature): ?>
                                    <span class="feature-item">
                                        <i class="fas fa-check text-success me-1"></i>
                                        <?php echo htmlspecialchars($feature); ?>
                                    </span>
                                    <?php endforeach; ?>
                                    <?php if(count($features) > 4): ?>
                                    <span class="feature-more" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars(implode(', ', array_slice($features, 4))); ?>">
                                        +<?php echo count($features) - 4; ?> plus
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="room-pricing">
                                <div class="price-main">
                                    <span class="price-amount text-primary fw-bold"><?php echo number_format($prix_fcfa, 0, ',', ' '); ?> FCFA</span>
                                    <span class="price-period text-muted">/nuit</span>
                                </div>
                                <div class="price-alternative">
                                    <span class="price-day text-success fw-bold"><?php echo number_format($prix_jour_fcfa, 0, ',', ' '); ?> FCFA</span>
                                    <span class="price-label text-muted">réservation journée</span>
                                </div>
                            </div>
                            
                            <div class="room-actions">
                                <a href="reservation.php?chambre=<?php echo $chambre['id']; ?>" class="btn btn-primary btn-book">
                                    <i class="fas fa-calendar-plus me-2"></i>Réserver
                                </a>
                                <button class="btn btn-outline-primary btn-details" data-bs-toggle="modal" data-bs-target="#roomModal<?php echo $chambre['id']; ?>">
                                    <i class="fas fa-info-circle me-2"></i>Détails
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Message aucun résultat -->
        <div id="noResults" class="no-results-section text-center py-5 d-none">
            <div class="no-results-content">
                <i class="fas fa-search fa-4x text-muted mb-4"></i>
                <h3 class="text-muted mb-3">Aucune chambre ne correspond à vos critères</h3>
                <p class="text-muted mb-4">Essayez de modifier vos filtres ou consultez nos suggestions</p>
                <button class="btn btn-primary btn-lg" onclick="resetFilters()">
                    <i class="fas fa-redo me-2"></i>Réinitialiser la recherche
                </button>
            </div>
        </div>

        <!-- Pagination -->
        <div class="pagination-section mt-5">
            <nav aria-label="Pagination des chambres">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</section>

<!-- Modals pour chaque chambre -->
<?php foreach($chambres as $chambre): 
    // Réutiliser les valeurs calculées plus haut
    $prix_nuit = getChambreValue($chambre, 'prix_nuit', 0);
    $prix_jour = getChambreValue($chambre, 'prix_jour', $prix_nuit * 0.6);
    $prix_fcfa = $prix_nuit * $taux_fcfa;
    $prix_jour_fcfa = $prix_jour * $taux_fcfa;
    $categorie = getChambreValue($chambre, 'categorie', 'Standard');
    $superficie = getChambreValue($chambre, 'superficie', 25);
    $equipements = getChambreValue($chambre, 'equipements', 'WiFi, TV écran plat, Climatisation, Salle de bain privée');
    $features = array_map('trim', explode(',', $equipements));
    
    // Déterminer l'image pour le modal
    $type_chambre = strtolower($chambre['type_chambre']);
    $image_url = $images_chambres['default'];
    
    if (strpos($type_chambre, 'simple') !== false) {
        $image_url = $images_chambres['simple'];
    } elseif (strpos($type_chambre, 'double') !== false || strpos($type_chambre, 'twin') !== false) {
        $image_url = $images_chambres['double'];
    } elseif (strpos($type_chambre, 'suite') !== false || strpos($type_chambre, 'présidentielle') !== false || strpos($type_chambre, 'luxe') !== false) {
        $image_url = $images_chambres['suite'];
    } elseif (strpos($type_chambre, 'familiale') !== false) {
        $image_url = $images_chambres['familiale'];
    }
?>
<div class="modal fade room-modal" id="roomModal<?php echo $chambre['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2 text-primary"></i>
                    <?php echo htmlspecialchars($chambre['type_chambre']); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="room-modal-content">
                    <div class="room-gallery">
                        <img src="<?php echo $image_url; ?>" class="img-fluid w-100" alt="<?php echo htmlspecialchars($chambre['type_chambre']); ?>" style="height: 500px; object-fit: cover;">
                    </div>
                    <div class="room-details">
                        <div class="details-header">
                            <h4><?php echo htmlspecialchars($chambre['type_chambre']); ?></h4>
                            <span class="room-category-badge"><?php echo htmlspecialchars($categorie); ?></span>
                        </div>
                        
                        <div class="details-pricing">
                            <div class="main-price">
                                <?php echo number_format($prix_fcfa, 0, ',', ' '); ?> FCFA
                                <small>/nuitée complète</small>
                            </div>
                            <div class="secondary-price">
                                <?php echo number_format($prix_jour_fcfa, 0, ',', ' '); ?> FCFA
                                <small>/réservation journée</small>
                            </div>
                        </div>
                        
                        <p class="room-full-description">
                            <?php echo htmlspecialchars(getChambreValue($chambre, 'description', $description_courte)); ?>
                        </p>
                        
                        <div class="room-specs">
                            <div class="specs-grid">
                                <div class="spec-item">
                                    <i class="fas fa-user-friends text-primary"></i>
                                    <span><?php echo $chambre['capacite']; ?> personnes</span>
                                </div>
                                <div class="spec-item">
                                    <i class="fas fa-ruler-combined text-primary"></i>
                                    <span><?php echo $superficie; ?> m²</span>
                                </div>
                                <div class="spec-item">
                                    <i class="fas fa-bed text-primary"></i>
                                    <span>Lit <?php echo $chambre['capacite'] > 2 ? 'King Size' : 'Queen Size'; ?></span>
                                </div>
                                <div class="spec-item">
                                    <i class="fas fa-eye text-primary"></i>
                                    <span>Vue <?php echo $categorie === 'Suite' ? 'Panoramique' : 'Jardin'; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="room-equipment">
                            <h6>Équipements & Services</h6>
                            <div class="equipment-grid">
                                <?php foreach($features as $feature): ?>
                                <div class="equipment-item">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <?php echo htmlspecialchars($feature); ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Fermer
                </button>
                <a href="reservation.php?chambre=<?php echo $chambre['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-calendar-plus me-2"></i>Réserver cette chambre
                </a>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Styles CSS améliorés -->
<style>
.chambres-page {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
}

/* Hero Section */
.chambres-hero {
    position: relative;
    padding: 100px 0 150px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.min-vh-60 {
    min-height: 60vh;
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

.hero-stats {
    margin-top: 3rem;
}

.stat-circle {
    text-align: center;
    padding: 2rem 1rem;
}

.stat-circle .stat-number {
    font-size: 3rem;
    font-weight: 700;
    color: #ffd700;
    margin-bottom: 0.5rem;
}

.stat-circle .stat-label {
    font-size: 1rem;
    color: rgba(255,255,255,0.9);
    text-transform: uppercase;
    letter-spacing: 1px;
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

/* Filtres */
.filters-section {
    margin-top: -80px;
    position: relative;
    z-index: 10;
}

.filters-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.1);
    overflow: hidden;
}

.filters-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    text-align: center;
}

.filters-body {
    padding: 2rem;
}

.filter-group {
    margin-bottom: 0;
}

.filter-label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    display: block;
}

.filter-select {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    background: white;
    transition: all 0.3s ease;
}

.filter-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.1);
}

.filter-btn {
    padding: 12px;
    border-radius: 10px;
    font-weight: 600;
}

/* Cartes Chambres */
.room-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    position: relative;
    height: 100%;
}

.room-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 50px rgba(0,0,0,0.15);
}

.room-card.featured-room {
    border: 2px solid #ffd700;
}

.featured-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    background: #ffd700;
    color: #2c3e50;
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 600;
    z-index: 2;
    font-size: 0.8rem;
}

.room-image-container {
    position: relative;
    overflow: hidden;
    height: 250px;
}

.room-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.room-card:hover .room-image {
    transform: scale(1.05);
}

.room-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.4);
    opacity: 0;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.room-card:hover .room-overlay {
    opacity: 1;
}

.overlay-actions {
    display: flex;
    gap: 10px;
}

.btn-round {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
}

.room-badges {
    position: absolute;
    bottom: 15px;
    left: 15px;
    display: flex;
    gap: 8px;
}

.badge-capacity, .badge-size {
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 0.8rem;
    backdrop-filter: blur(10px);
}

.room-content {
    padding: 1.5rem;
}

.room-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.room-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
}

.room-category {
    background: #667eea;
    color: white;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
}

.room-description {
    color: #6c757d;
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.room-features {
    margin-bottom: 1.5rem;
}

.features-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.feature-item {
    font-size: 0.85rem;
    color: #495057;
    display: flex;
    align-items: center;
}

.feature-more {
    font-size: 0.8rem;
    color: #667eea;
    cursor: pointer;
    grid-column: span 2;
    text-align: center;
    padding: 5px;
    border: 1px dashed #dee2e6;
    border-radius: 8px;
}

.room-pricing {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 10px;
}

.price-main {
    text-align: left;
}

.price-amount {
    font-size: 1.5rem;
    font-weight: 700;
    color: #667eea;
    display: block;
}

.price-period {
    font-size: 0.9rem;
    color: #6c757d;
}

.price-alternative {
    text-align: right;
}

.price-day {
    font-size: 1.1rem;
    font-weight: 600;
    color: #28a745;
    display: block;
}

.price-label {
    font-size: 0.8rem;
    color: #6c757d;
}

.room-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.btn-book, .btn-details {
    padding: 12px;
    border-radius: 10px;
    font-weight: 600;
}

/* Results Header */
.results-header {
    background: white;
    padding: 1.5rem 2rem;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    margin: 2rem 0;
}

.results-number {
    color: #667eea;
    font-weight: 700;
}

.view-options .btn-group {
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 10px;
}

/* Modal amélioré */
.room-modal-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 500px;
}

.room-gallery {
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
}

.room-gallery img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.room-details {
    padding: 2rem;
}

.details-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
}

.details-header h4 {
    margin: 0;
    color: #2c3e50;
}

.room-category-badge {
    background: #667eea;
    color: white;
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
}

.details-pricing {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 1.5rem;
}

.main-price {
    font-size: 2rem;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 0.5rem;
}

.main-price small {
    font-size: 1rem;
    color: #6c757d;
}

.secondary-price {
    font-size: 1.25rem;
    font-weight: 600;
    color: #28a745;
}

.secondary-price small {
    font-size: 0.9rem;
    color: #6c757d;
}

.room-full-description {
    color: #495057;
    line-height: 1.7;
    margin-bottom: 2rem;
}

.room-specs {
    margin-bottom: 2rem;
}

.specs-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.spec-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 10px;
}

.equipment-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 1rem;
}

.equipment-item {
    display: flex;
    align-items: center;
    padding: 8px 0;
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in {
    opacity: 0;
    transform: translateY(30px);
    animation: fadeInUp 0.8s ease forwards;
}

/* Animation delays */
.fade-in:nth-child(1) { animation-delay: 0.1s; }
.fade-in:nth-child(2) { animation-delay: 0.2s; }
.fade-in:nth-child(3) { animation-delay: 0.3s; }
.fade-in:nth-child(4) { animation-delay: 0.4s; }

/* Responsive */
@media (max-width: 768px) {
    .chambres-hero {
        padding: 80px 0 120px;
    }
    
    .hero-title {
        font-size: 2.5rem;
    }
    
    .hero-subtitle {
        font-size: 1.1rem;
    }
    
    .filters-section {
        margin-top: -60px;
    }
    
    .room-modal-content {
        grid-template-columns: 1fr;
    }
    
    .room-gallery {
        height: 300px;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
    }
    
    .room-pricing {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .specs-grid, .equipment-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-circle .stat-number {
        font-size: 2rem;
    }
}
</style>

<script>
// Animation au scroll
document.addEventListener('DOMContentLoaded', function() {
    const fadeElements = document.querySelectorAll('.fade-in');
    
    const fadeInOnScroll = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
                fadeInOnScroll.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    fadeElements.forEach(element => {
        fadeInOnScroll.observe(element);
    });

    // Animations des statistiques
    const statNumbers = document.querySelectorAll('.stat-number[data-count]');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = entry.target;
                const finalValue = parseInt(target.getAttribute('data-count'));
                let currentValue = 0;
                const duration = 2000;
                const increment = finalValue / (duration / 16);
                
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        target.textContent = finalValue;
                        clearInterval(timer);
                    } else {
                        target.textContent = Math.floor(currentValue);
                    }
                }, 16);
                
                observer.unobserve(target);
            }
        });
    });
    
    statNumbers.forEach(stat => observer.observe(stat));

    // Initialisation des tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Gestion des vues
    const gridView = document.getElementById('gridView');
    const listView = document.getElementById('listView');
    
    [gridView, listView].forEach(btn => {
        btn.addEventListener('click', function() {
            [gridView, listView].forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Filtres rapides
    const quickFilters = document.querySelectorAll('.filter-quick');
    quickFilters.forEach(btn => {
        btn.addEventListener('click', function() {
            quickFilters.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });
});

// Fonctions de filtrage (simplifiées pour l'exemple)
function filterRooms() {
    console.log('Filtrage des chambres...');
    // Implémentation du filtrage
}

function resetFilters() {
    console.log('Réinitialisation des filtres...');
    // Implémentation de la réinitialisation
}
</script>

<?php include 'includes/footer.php'; ?>
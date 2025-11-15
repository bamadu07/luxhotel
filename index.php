<?php
// index.php (version sécurisée)
include 'includes/header.php';
include 'config/database.php';

// Vérifier la structure de la table et adapter la requête
try {
    // Essayer d'abord avec la colonne featured
    $stmt = $pdo->query("SELECT * FROM chambres WHERE disponibilite = 1 AND featured = 1 LIMIT 3");
    $chambres_featured = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si aucun résultat, utiliser une requête sans featured
    if (empty($chambres_featured)) {
        $stmt = $pdo->query("SELECT * FROM chambres WHERE disponibilite = 1 LIMIT 3");
        $chambres_featured = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // En cas d'erreur, utiliser la requête simple
    $stmt = $pdo->query("SELECT * FROM chambres WHERE disponibilite = 1 LIMIT 3");
    $chambres_featured = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Taux de conversion EUR vers FCFA
$taux_fcfa = 655;

// Fonction pour obtenir des valeurs sécurisées
function getChambreValue($chambre, $key, $default = '') {
    return isset($chambre[$key]) ? $chambre[$key] : $default;
}

// Images par défaut pour les chambres
$images_chambres = [
    'default' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=500&h=300&fit=crop',
    'simple' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=500&h=300&fit=crop',
    'double' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=500&h=300&fit=crop',
    'suite' => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=500&h=300&fit=crop'
];
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center min-vh-80">
            <div class="col-lg-12 text-center hero-content fade-in">
                <h1 class="hero-title display-4 fw-bold mb-4">L'Excellence Réinventée</h1>
                <p class="hero-subtitle lead mb-5 mx-auto" style="max-width: 600px;">
                    Découvrez un sanctuaire de luxe où l'élégance rencontre le confort moderne. 
                    Une expérience hôtelière inoubliable vous attend au cœur de la ville.
                </p>
                <div class="d-flex flex-wrap gap-3 justify-content-center mt-4">
                    <a href="reservation.php" class="btn btn-hero btn-lg">
                        <i class="fas fa-calendar-check me-2"></i>Réserver Maintenant
                    </a>
                    <a href="chambres.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-eye me-2"></i>Découvrir nos Chambres
                    </a>
                </div>
                
                <div class="row mt-5 pt-4 justify-content-center">
                    <div class="col-4 col-md-3 text-center">
                        <div class="stat-number">50+</div>
                        <div class="stat-label">Chambres Luxueuses</div>
                    </div>
                    <div class="col-4 col-md-3 text-center">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Service Concierge</div>
                    </div>
                    <div class="col-4 col-md-3 text-center">
                        <div class="stat-number">5★</div>
                        <div class="stat-label">Avis Clients</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 fade-in">
                <div class="position-relative">
                    <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&h=500&fit=crop" 
                         alt="Hôtel Prestige" class="img-fluid rounded-3 shadow-lg hero-image">
                    <div class="position-absolute bottom-0 start-0 m-4">
                        <div class="bg-white rounded-pill px-3 py-2 shadow-sm">
                            <small class="text-primary fw-bold">À partir de 75.000 FCFA/nuit</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="section-padding">
    <div class="container">
        <h2 class="section-title text-center">Nos Services Exclusifs</h2>
        
        <div class="row justify-content-center">
            <div class="col-md-4 mb-4 fade-in">
                <div class="service-card text-center p-4 h-100">
                    <div class="service-icon">
                        <i class="fas fa-concierge-bell"></i>
                    </div>
                    <h4 class="mt-4 mb-3">Service Concierge 24/7</h4>
                    <p class="text-muted">Notre équipe dévouée est à votre service jour et nuit pour répondre à tous vos besoins.</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4 fade-in">
                <div class="service-card text-center p-4 h-100">
                    <div class="service-icon">
                        <i class="fas fa-spa"></i>
                    </div>
                    <h4 class="mt-4 mb-3">Spa & Bien-être</h4>
                    <p class="text-muted">Détendez-vous dans notre spa luxueux avec des traitements personnalisés.</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4 fade-in">
                <div class="service-card text-center p-4 h-100">
                    <div class="service-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h4 class="mt-4 mb-3">Restaurant Gastronomique</h4>
                    <p class="text-muted">Savourez une cuisine raffinée préparée par nos chefs avec des produits frais.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Chambres Section -->
<section class="section-padding bg-light">
    <div class="container">
        <h2 class="section-title text-center">Nos Chambres Populaires</h2>

        <div class="row justify-content-center">
            <?php foreach($chambres_featured as $chambre): 
                $prix_nuit = getChambreValue($chambre, 'prix_nuit', 100);
                $prix_jour = getChambreValue($chambre, 'prix_jour', $prix_nuit * 0.6);
                $prix_fcfa = $prix_nuit * $taux_fcfa;
                $prix_jour_fcfa = $prix_jour * $taux_fcfa;
                $categorie = getChambreValue($chambre, 'categorie', 'Standard');
                $description_courte = getChambreValue($chambre, 'description_courte', substr(getChambreValue($chambre, 'description', ''), 0, 100));
                $superficie = getChambreValue($chambre, 'superficie', 25);
                $equipements = getChambreValue($chambre, 'equipements', 'WiFi, TV, Climatisation');
                
                // Déterminer l'image en fonction du type de chambre
                $type_chambre = strtolower($chambre['type_chambre']);
                $image_url = $images_chambres['default'];
                
                if (strpos($type_chambre, 'simple') !== false) {
                    $image_url = $images_chambres['simple'];
                } elseif (strpos($type_chambre, 'double') !== false || strpos($type_chambre, 'twin') !== false) {
                    $image_url = $images_chambres['double'];
                } elseif (strpos($type_chambre, 'suite') !== false || strpos($type_chambre, 'présidentielle') !== false) {
                    $image_url = $images_chambres['suite'];
                }
            ?>
            <div class="col-lg-4 col-md-6 mb-4 fade-in">
                <div class="room-card shadow-sm h-100">
                    <div class="position-relative overflow-hidden">
                        <img src="<?php echo $image_url; ?>" 
                             class="room-image w-100" 
                             alt="<?php echo htmlspecialchars($chambre['type_chambre']); ?>"
                             style="height: 250px; object-fit: cover;">
                        <span class="room-badge"><?php echo $categorie; ?></span>
                        <div class="room-overlay">
                            <div class="room-actions">
                                <a href="chambres.php" class="btn btn-sm btn-light">
                                    <i class="fas fa-expand"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold"><?php echo htmlspecialchars($chambre['type_chambre']); ?></h5>
                        <p class="card-text text-muted mb-3"><?php echo htmlspecialchars($description_courte); ?>...</p>
                        
                        <div class="room-features mb-3">
                            <div class="d-flex justify-content-between text-sm mb-2">
                                <span><i class="fas fa-user-friends me-1 text-primary"></i> <?php echo $chambre['capacite']; ?> personnes</span>
                                <span><i class="fas fa-ruler-combined me-1 text-primary"></i> <?php echo $superficie; ?> m²</span>
                            </div>
                            <div class="equipements-small">
                                <small class="text-muted">
                                    <i class="fas fa-wifi me-1"></i>
                                    <?php 
                                    $equip_list = explode(',', $equipements);
                                    echo implode(' • ', array_slice($equip_list, 0, 3));
                                    ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="room-price text-primary fw-bold"><?php echo number_format($prix_fcfa, 0, ',', ' '); ?> FCFA</div>
                                <small class="text-muted">nuitée complète</small>
                            </div>
                            <div class="text-end">
                                <div class="text-success fw-bold"><?php echo number_format($prix_jour_fcfa, 0, ',', ' '); ?> FCFA</div>
                                <small class="text-muted">réservation journée</small>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="reservation.php?chambre=<?php echo $chambre['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-calendar-plus me-2"></i>Réserver Maintenant
                            </a>
                            <a href="chambres.php#chambre-<?php echo $chambre['id']; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-info-circle me-2"></i>Voir Détails
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5 fade-in">
            <a href="chambres.php" class="btn btn-primary btn-lg">
                <i class="fas fa-bed me-2"></i>Voir Toutes les Chambres
            </a>
        </div>
    </div>
</section>

<!-- Section Témoignages -->
<section class="section-padding">
    <div class="container">
        <h2 class="section-title text-center">Ce Que Disent Nos Clients</h2>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="row">
                    <div class="col-md-6 mb-4 fade-in">
                        <div class="testimonial-card p-4 h-100">
                            <div class="d-flex align-items-center mb-3">
                                <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?w=100&h=100&fit=crop&crop=face" 
                                     alt="Marie D." class="rounded-circle me-3" width="60" height="60">
                                <div>
                                    <h5 class="mb-1">Marie D.</h5>
                                    <div class="text-warning">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="text-muted mb-0">
                                "Un séjour exceptionnel ! Le service est impeccable et les chambres sont magnifiques. 
                                Je recommande vivement cet hôtel pour un séjour de luxe."
                            </p>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4 fade-in">
                        <div class="testimonial-card p-4 h-100">
                            <div class="d-flex align-items-center mb-3">
                                <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face" 
                                     alt="Pierre M." class="rounded-circle me-3" width="60" height="60">
                                <div>
                                    <h5 class="mb-1">Pierre M.</h5>
                                    <div class="text-warning">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="text-muted mb-0">
                                "Le spa est incroyable et le restaurant gastronomique est à couper le souffle. 
                                Une expérience 5 étoiles du début à la fin."
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="section-padding bg-primary text-white">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-6 fade-in">
                <h2 class="mb-3">Restez Informé</h2>
                <p class="mb-4 opacity-75">
                    Inscrivez-vous à notre newsletter pour recevoir nos offres exclusives 
                    et les dernières actualités de l'hôtel.
                </p>
                <form class="newsletter-form">
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Votre email" required>
                        <button class="btn btn-light" type="submit">
                            <i class="fas fa-paper-plane me-2"></i>S'inscrire
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<style>
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 100px 0 50px;
}

.min-vh-80 {
    min-height: 80vh;
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

.btn-hero {
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 12px 30px;
    border-radius: 50px;
    transition: all 0.3s ease;
}

.btn-hero:hover {
    background: white;
    color: #667eea;
    transform: translateY(-2px);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.8;
}

.section-padding {
    padding: 80px 0;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 3rem;
    color: #2c3e50;
}

.service-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.service-card:hover {
    transform: translateY(-10px);
}

.service-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 2rem;
    color: white;
}

.room-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: none;
}

.room-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.room-image {
    transition: transform 0.3s ease;
}

.room-card:hover .room-image {
    transform: scale(1.05);
}

.room-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #667eea;
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.room-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.room-card:hover .room-overlay {
    opacity: 1;
}

.room-price {
    font-size: 1.5rem;
    font-weight: 700;
}

.testimonial-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    border-left: 4px solid #667eea;
}

.newsletter-form .form-control {
    border: none;
    border-radius: 50px 0 0 50px;
    padding: 15px 20px;
}

.newsletter-form .btn {
    border-radius: 0 50px 50px 0;
    padding: 15px 25px;
}

.fade-in {
    opacity: 0;
    transform: translateY(30px);
    animation: fadeInUp 0.8s ease forwards;
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Animation delays */
.fade-in:nth-child(1) { animation-delay: 0.1s; }
.fade-in:nth-child(2) { animation-delay: 0.2s; }
.fade-in:nth-child(3) { animation-delay: 0.3s; }
.fade-in:nth-child(4) { animation-delay: 0.4s; }

@media (max-width: 768px) {
    .hero-title {
        font-size: 2.5rem;
    }
    
    .hero-subtitle {
        font-size: 1.1rem;
    }
    
    .section-title {
        font-size: 2rem;
    }
    
    .stat-number {
        font-size: 1.5rem;
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
});
</script>

<?php include 'includes/footer.php'; ?>
<?php
// contact.php
session_start();
include 'includes/header.php';
include 'config/database.php';

// Configuration email
$admin_email = "contact@hoteldeluxe.com";
$site_name = "Hôtel Deluxe";

// Traitement du formulaire de contact
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = htmlspecialchars(trim($_POST['nom']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $sujet = htmlspecialchars(trim($_POST['sujet']));
    $message = htmlspecialchars(trim($_POST['message']));
    
    // Validation des données
    $errors = [];
    
    if (empty($nom)) {
        $errors[] = "Le nom complet est requis.";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Une adresse email valide est requise.";
    }
    
    if (empty($sujet)) {
        $errors[] = "Le sujet est requis.";
    }
    
    if (empty($message)) {
        $errors[] = "Le message est requis.";
    }
    
    if (strlen($message) < 10) {
        $errors[] = "Le message doit contenir au moins 10 caractères.";
    }
    
    // Si pas d'erreurs, procéder à l'enregistrement et l'envoi d'email
    if (empty($errors)) {
        try {
            // Commencer une transaction
            $pdo->beginTransaction();
            
            // Insérer le message dans la base de données
            $stmt = $pdo->prepare("INSERT INTO contacts (nom, email, sujet, message, date_creation) VALUES (?, ?, ?, ?, NOW())");
            $db_success = $stmt->execute([$nom, $email, $sujet, $message]);
            
            if ($db_success) {
                // Préparer et envoyer l'email
                $email_subject = "Nouveau message de contact: " . $sujet;
                
                $email_body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                        .content { padding: 20px; background: #f9f9f9; }
                        .field { margin-bottom: 15px; }
                        .label { font-weight: bold; color: #007bff; }
                        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Nouveau Message de Contact</h2>
                            <p>Site: $site_name</p>
                        </div>
                        <div class='content'>
                            <div class='field'>
                                <span class='label'>Nom:</span> $nom
                            </div>
                            <div class='field'>
                                <span class='label'>Email:</span> $email
                            </div>
                            <div class='field'>
                                <span class='label'>Sujet:</span> $sujet
                            </div>
                            <div class='field'>
                                <span class='label'>Message:</span><br>
                                " . nl2br($message) . "
                            </div>
                            <div class='field'>
                                <span class='label'>Date:</span> " . date('d/m/Y à H:i') . "
                            </div>
                        </div>
                        <div class='footer'>
                            <p>Cet email a été envoyé automatiquement depuis le formulaire de contact de $site_name</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                // Headers pour l'email HTML
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: $site_name <noreply@hoteldeluxe.com>" . "\r\n";
                $headers .= "Reply-To: $email" . "\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();
                
                // Envoyer l'email
                $email_sent = mail($admin_email, $email_subject, $email_body, $headers);
                
                // Envoyer un email de confirmation à l'utilisateur
                $user_subject = "Confirmation de réception de votre message - $site_name";
                
                $user_body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #28a745; color: white; padding: 20px; text-align: center; }
                        .content { padding: 20px; background: #f9f9f9; }
                        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Confirmation de Réception</h2>
                        </div>
                        <div class='content'>
                            <p>Bonjour $nom,</p>
                            <p>Nous avons bien reçu votre message et vous en remercions.</p>
                            <p><strong>Sujet:</strong> $sujet</p>
                            <p>Nous traitons votre demande dans les plus brefs délais et nous vous répondrons très prochainement.</p>
                            <p><em>Voici le message que vous nous avez envoyé :</em></p>
                            <div style='background: white; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0;'>
                                " . nl2br($message) . "
                            </div>
                            <p>Cordialement,<br>L'équipe de $site_name</p>
                        </div>
                        <div class='footer'>
                            <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                $user_headers = "MIME-Version: 1.0" . "\r\n";
                $user_headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $user_headers .= "From: $site_name <noreply@hoteldeluxe.com>" . "\r\n";
                
                $confirmation_sent = mail($email, $user_subject, $user_body, $user_headers);
                
                // Valider la transaction
                $pdo->commit();
                
                $_SESSION['success'] = "Votre message a été envoyé avec succès! Nous vous avons envoyé un email de confirmation. Nous vous répondrons dans les plus brefs délais.";
                
                // Redirection pour éviter le re-soumission du formulaire
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
                
            } else {
                throw new Exception("Erreur lors de l'enregistrement en base de données");
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Une erreur s'est produite lors de l'envoi de votre message. Veuillez réessayer. Erreur: " . $e->getMessage();
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
        $_SESSION['form_data'] = [
            'nom' => $nom,
            'email' => $email,
            'sujet' => $sujet,
            'message' => $message
        ];
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Récupérer les données du formulaire depuis la session si elles existent
$nom = $_SESSION['form_data']['nom'] ?? '';
$email = $_SESSION['form_data']['email'] ?? '';
$sujet = $_SESSION['form_data']['sujet'] ?? '';
$message = $_SESSION['form_data']['message'] ?? '';

// Nettoyer les données de session après utilisation
unset($_SESSION['form_data']);
?>

<section class="contact-section">
    <!-- Hero Section Contact -->
    <div class="contact-hero bg-primary text-white">
        <div class="container">
            <div class="row align-items-center min-vh-50">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="hero-title display-4 fw-bold mb-4">Contactez-Nous</h1>
                    <p class="hero-subtitle lead mb-4">Nous sommes à votre écoute 24h/24 et 7j/7 pour répondre à toutes vos questions</p>
                    
                    <!-- Statistiques de contact -->
                    <div class="contact-stats row justify-content-center">
                        <div class="col-4 col-md-3 text-center">
                            <div class="stat-circle">
                                <div class="stat-number" data-count="24">0</div>
                                <div class="stat-label">Heures</div>
                            </div>
                        </div>
                        <div class="col-4 col-md-3 text-center">
                            <div class="stat-circle">
                                <div class="stat-number" data-count="7">0</div>
                                <div class="stat-label">Jours</div>
                            </div>
                        </div>
                        <div class="col-4 col-md-3 text-center">
                            <div class="stat-circle">
                                <div class="stat-number" data-count="30">0</div>
                                <div class="stat-label">Minutes</div>
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

                <div class="contact-content mt-4">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="contact-card shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h4 class="mb-0">
                                        <i class="fas fa-paper-plane me-2"></i>
                                        Envoyez-nous un message
                                    </h4>
                                </div>
                                <div class="card-body p-4">
                                    <form method="POST" action="contact.php" class="needs-validation" novalidate id="contactForm">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="form-group">
                                                    <label for="nom" class="form-label">
                                                        <i class="fas fa-user me-2 text-muted"></i>Nom complet <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="text" class="form-control form-control-lg" id="nom" name="nom" value="<?php echo htmlspecialchars($nom); ?>" required>
                                                    <div class="invalid-feedback">
                                                        Veuillez entrer votre nom complet.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="form-group">
                                                    <label for="email" class="form-label">
                                                        <i class="fas fa-envelope me-2 text-muted"></i>Email <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="email" class="form-control form-control-lg" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                                    <div class="invalid-feedback">
                                                        Veuillez entrer une adresse email valide.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-group">
                                                <label for="sujet" class="form-label">
                                                    <i class="fas fa-tag me-2 text-muted"></i>Sujet <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control form-control-lg" id="sujet" name="sujet" value="<?php echo htmlspecialchars($sujet); ?>" required>
                                                <div class="invalid-feedback">
                                                    Veuillez entrer un sujet.
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-group">
                                                <label for="message" class="form-label">
                                                    <i class="fas fa-edit me-2 text-muted"></i>Message <span class="text-danger">*</span>
                                                </label>
                                                <textarea class="form-control form-control-lg" id="message" name="message" rows="6" required placeholder="Décrivez-nous votre demande en détail..."><?php echo htmlspecialchars($message); ?></textarea>
                                                <div class="invalid-feedback">
                                                    Veuillez entrer votre message (minimum 10 caractères).
                                                </div>
                                                <div class="form-text">
                                                    <span id="charCount"><?php echo strlen($message); ?></span> caractères
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center">
                                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                                <i class="fas fa-paper-plane me-2"></i>Envoyer le Message
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4 mt-4 mt-lg-0">
                            <div class="contact-info-card shadow-sm">
                                <div class="card-header bg-secondary text-white">
                                    <h4 class="mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Informations de contact
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <div class="contact-info-item text-center mb-4">
                                        <div class="icon-wrapper bg-primary text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="fas fa-map-marker-alt fa-lg"></i>
                                        </div>
                                        <h5>Adresse</h5>
                                        <p class="mb-0">123 Avenue de l'Hôtel<br>75001 Paris, France</p>
                                    </div>
                                    
                                    <div class="contact-info-item text-center mb-4">
                                        <div class="icon-wrapper bg-success text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="fas fa-phone fa-lg"></i>
                                        </div>
                                        <h5>Téléphone</h5>
                                        <p class="mb-0">+33 1 23 45 67 89</p>
                                        <small class="text-muted">24h/24 et 7j/7</small>
                                    </div>
                                    
                                    <div class="contact-info-item text-center mb-4">
                                        <div class="icon-wrapper bg-info text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="fas fa-envelope fa-lg"></i>
                                        </div>
                                        <h5>Email</h5>
                                        <p class="mb-0">contact@hoteldeluxe.com</p>
                                        <small class="text-muted">Réponse sous 24h</small>
                                    </div>
                                    
                                    <div class="contact-info-item text-center">
                                        <div class="icon-wrapper bg-warning text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="fas fa-clock fa-lg"></i>
                                        </div>
                                        <h5>Horaires</h5>
                                        <p class="mb-0">Réception ouverte<br>24h/24 et 7j/7</p>
                                    </div>

                                    <!-- Réseaux sociaux -->
                                    <div class="social-links text-center mt-4 pt-4 border-top">
                                        <h6 class="mb-3">Suivez-nous</h6>
                                        <div class="d-flex justify-content-center gap-3">
                                            <a href="#" class="social-link facebook">
                                                <i class="fab fa-facebook-f"></i>
                                            </a>
                                            <a href="#" class="social-link twitter">
                                                <i class="fab fa-twitter"></i>
                                            </a>
                                            <a href="#" class="social-link instagram">
                                                <i class="fab fa-instagram"></i>
                                            </a>
                                            <a href="#" class="social-link linkedin">
                                                <i class="fab fa-linkedin-in"></i>
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
    </div>
</section>

<style>
.contact-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
}

.contact-hero {
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

.contact-stats {
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

.contact-card, .contact-info-card {
    background: white;
    border-radius: 20px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    height: 100%;
}

.contact-card:hover, .contact-info-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 50px rgba(0,0,0,0.15);
}

.card-header {
    border-radius: 20px 20px 0 0 !important;
    padding: 1.5rem 2rem;
}

.card-body {
    padding: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.form-control-lg {
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.form-control-lg:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.1);
}

.icon-wrapper {
    transition: transform 0.3s ease;
}

.contact-info-item:hover .icon-wrapper {
    transform: scale(1.1);
}

.social-links {
    margin-top: 2rem;
}

.social-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
}

.social-link.facebook { background: #3b5998; }
.social-link.twitter { background: #1da1f2; }
.social-link.instagram { background: #e4405f; }
.social-link.linkedin { background: #0077b5; }

.social-link:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 25px;
    padding: 15px 40px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.alert {
    border-radius: 15px;
    border: none;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .contact-hero {
        padding: 80px 0 120px;
    }
    
    .hero-title {
        font-size: 2.5rem;
    }
    
    .hero-subtitle {
        font-size: 1.1rem;
    }
    
    .stat-circle .stat-number {
        font-size: 2rem;
    }
    
    .card-body {
        padding: 1.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    const messageTextarea = document.getElementById('message');
    const charCount = document.getElementById('charCount');
    
    // Compteur de caractères
    messageTextarea.addEventListener('input', function() {
        charCount.textContent = this.value.length;
    });
    
    // Validation Bootstrap
    contactForm.addEventListener('submit', function(event) {
        if (!contactForm.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        contactForm.classList.add('was-validated');
    });
    
    // Animation des statistiques
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

    // Animation des champs
    const inputs = contactForm.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (this.value === '') {
                this.parentElement.classList.remove('focused');
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
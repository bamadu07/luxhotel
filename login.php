<?php
// login.php
include 'includes/header.php';
include 'config/database.php';

// Rediriger si déjà connecté
if(isset($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars(trim($_POST['username']));
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);
    
    // Validation
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Le nom d'utilisateur est requis";
    }
    
    if (empty($password)) {
        $errors[] = "Le mot de passe est requis";
    }
    
    if (empty($errors)) {
        try {
            // Vérifier les identifiants
            $stmt = $pdo->prepare("SELECT * FROM administrateurs WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
                
                // Remember me functionality
                if ($remember_me) {
                    $token = bin2hex(random_bytes(32));
                    $expiry = time() + (30 * 24 * 60 * 60); // 30 jours
                    
                    setcookie('admin_remember', $token, $expiry, '/');
                    
                    // Stocker le token en base de données
                    $stmt = $pdo->prepare("UPDATE administrateurs SET remember_token = ?, token_expiry = ? WHERE id = ?");
                    $stmt->execute([$token, date('Y-m-d H:i:s', $expiry), $admin['id']]);
                }
                
                // Journalisation de la connexion
                $stmt = $pdo->prepare("UPDATE administrateurs SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$admin['id']]);
                
                // Redirection sécurisée
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "Nom d'utilisateur ou mot de passe incorrect.";
                
                // Journalisation des tentatives échouées
                error_log("Tentative de connexion échouée pour l'utilisateur: " . $username);
            }
        } catch (PDOException $e) {
            $error = "Erreur de connexion. Veuillez réessayer.";
            error_log("Erreur de connexion admin: " . $e->getMessage());
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Vérifier le cookie remember me
if (isset($_COOKIE['admin_remember']) && !isset($_SESSION['admin_logged_in'])) {
    try {
        $token = $_COOKIE['admin_remember'];
        $stmt = $pdo->prepare("SELECT * FROM administrateurs WHERE remember_token = ? AND token_expiry > NOW()");
        $stmt->execute([$token]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
            
            header('Location: dashboard.php');
            exit;
        }
    } catch (PDOException $e) {
        // Ignorer l'erreur et continuer avec la connexion normale
    }
}
?>

<section class="admin-login-section">
    <!-- Hero Section Connexion -->
    <div class="login-hero bg-primary text-white">
        <div class="container">
            <div class="row align-items-center min-vh-50">
                <div class="col-lg-6 mx-auto text-center">
                    <h1 class="hero-title mb-3">Espace Administrateur</h1>
                    <p class="hero-subtitle">Accédez au tableau de bord de gestion de l'hôtel</p>
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
            <div class="col-lg-5 col-md-7">
                <!-- Carte de connexion -->
                <div class="login-card">
                    <div class="card-header text-center">
                        <div class="login-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h3 class="login-title">Connexion Admin</h3>
                        <p class="login-subtitle">Accédez à votre espace d'administration</p>
                    </div>
                    
                    <div class="card-body">
                        <?php if(isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <div><?php echo $error; ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(isset($_GET['expired'])): ?>
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-clock me-2"></i>
                                <div>Votre session a expiré. Veuillez vous reconnecter.</div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(isset($_GET['logout'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle me-2"></i>
                                <div>Vous avez été déconnecté avec succès.</div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="login.php" class="login-form" id="loginForm">
                            <div class="form-group">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2"></i>Nom d'utilisateur
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                           placeholder="Entrez votre nom d'utilisateur" required>
                                </div>
                                <div class="invalid-feedback">
                                    Veuillez entrer votre nom d'utilisateur.
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="password" class="form-label">
                                    <i class="fas fa-key me-2"></i>Mot de passe
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Entrez votre mot de passe" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Veuillez entrer votre mot de passe.
                                </div>
                            </div>
                            
                            <div class="form-options">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                                    <label class="form-check-label" for="remember_me">
                                        Se souvenir de moi
                                    </label>
                                </div>
                                <a href="forgot-password.php" class="forgot-password">
                                    Mot de passe oublié ?
                                </a>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-login w-100" id="loginButton">
                                    <span class="btn-text">Se connecter</span>
                                    <div class="btn-loader d-none">
                                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                        Connexion...
                                    </div>
                                </button>
                            </div>
                        </form>
                        
                        <!-- Informations de sécurité -->
                        <div class="security-info">
                            <div class="security-alert">
                                <i class="fas fa-shield-alt me-2"></i>
                                <small>Cet espace est réservé au personnel autorisé. Toute activité non autorisée sera journalisée.</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Informations supplémentaires -->
                <div class="login-info text-center mt-4">
                    <div class="info-card">
                        <h6><i class="fas fa-info-circle me-2"></i>Informations</h6>
                        <p class="mb-2">Problèmes de connexion ?</p>
                        <p class="mb-0">
                            <small>
                                Contactez le <a href="mailto:support@hotelprestige.com" class="text-primary">support technique</a>
                            </small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.admin-login-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
}

.login-hero {
    padding: 80px 0 120px;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
    position: relative;
}

.min-vh-50 {
    min-height: 50vh;
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

.login-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.1);
    margin-top: -80px;
    position: relative;
    z-index: 10;
    border: none;
    overflow: hidden;
}

.login-card .card-header {
    background: linear-gradient(135deg, #2C5530 0%, #4a7c59 100%);
    color: white;
    padding: 2.5rem 2rem 2rem;
    border: none;
}

.login-icon {
    width: 80px;
    height: 80px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 2rem;
    backdrop-filter: blur(10px);
}

.login-title {
    font-weight: 700;
    margin-bottom: 0.5rem;
    font-size: 1.8rem;
}

.login-subtitle {
    opacity: 0.9;
    margin-bottom: 0;
}

.login-card .card-body {
    padding: 2.5rem 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    font-weight: 600;
    color: var(--dark-text);
    margin-bottom: 0.5rem;
    display: block;
}

.input-group {
    border-radius: 10px;
    overflow: hidden;
}

.input-group-text {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-right: none;
    color: #6c757d;
}

.form-control {
    border: 2px solid #e9ecef;
    border-left: none;
    border-right: none;
    padding: 12px 15px;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: none;
}

.input-group .btn-outline-secondary {
    border: 2px solid #e9ecef;
    border-left: none;
    color: #6c757d;
    transition: all 0.3s ease;
}

.input-group .btn-outline-secondary:hover {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.form-check-input:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.forgot-password {
    color: var(--primary-color);
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.3s ease;
}

.forgot-password:hover {
    color: var(--accent-color);
    text-decoration: underline;
}

.btn-login {
    padding: 15px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 1.1rem;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.btn-text, .btn-loader {
    transition: opacity 0.3s ease;
}

.security-info {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e9ecef;
}

.security-alert {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 10px;
    border-left: 4px solid var(--secondary-color);
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.security-alert i {
    color: var(--secondary-color);
    margin-top: 0.1rem;
}

.login-info {
    margin-top: 2rem;
}

.info-card {
    background: white;
    padding: 1.5rem;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
}

.info-card h6 {
    color: var(--primary-color);
    margin-bottom: 1rem;
    font-weight: 600;
}

/* Animations */
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.shake {
    animation: shake 0.5s ease-in-out;
}

/* Responsive */
@media (max-width: 768px) {
    .login-hero {
        padding: 60px 0 100px;
    }
    
    .login-card {
        margin-top: -60px;
    }
    
    .login-card .card-header {
        padding: 2rem 1.5rem 1.5rem;
    }
    
    .login-card .card-body {
        padding: 2rem 1.5rem;
    }
    
    .form-options {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
}

/* Loader personnalisé */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

/* États de validation */
.was-validated .form-control:valid {
    border-color: #198754;
}

.was-validated .form-control:invalid {
    border-color: #dc3545;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const loginButton = document.getElementById('loginButton');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    // Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });
    
    // Gestion de la soumission du formulaire
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validation basique
        if (!this.checkValidity()) {
            this.classList.add('was-validated');
            return;
        }
        
        // Afficher le loader
        const btnText = loginButton.querySelector('.btn-text');
        const btnLoader = loginButton.querySelector('.btn-loader');
        
        btnText.classList.add('d-none');
        btnLoader.classList.remove('d-none');
        loginButton.disabled = true;
        
        // Simuler un délai de traitement
        setTimeout(() => {
            this.submit();
        }, 1000);
    });
    
    // Effet de shake en cas d'erreur
    <?php if(isset($error)): ?>
    setTimeout(() => {
        loginForm.classList.add('shake');
        setTimeout(() => {
            loginForm.classList.remove('shake');
        }, 500);
    }, 100);
    <?php endif; ?>
    
    // Auto-focus sur le champ username
    document.getElementById('username').focus();
    
    // Prévention de la soumission multiple
    let formSubmitted = false;
    loginForm.addEventListener('submit', function() {
        if (formSubmitted) {
            e.preventDefault();
            return false;
        }
        formSubmitted = true;
        return true;
    });
    
    // Raccourci clavier Entrée
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !loginButton.disabled) {
            loginForm.dispatchEvent(new Event('submit'));
        }
    });
    
    // Animation d'entrée
    const loginCard = document.querySelector('.login-card');
    loginCard.style.opacity = '0';
    loginCard.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        loginCard.style.transition = 'all 0.6s ease';
        loginCard.style.opacity = '1';
        loginCard.style.transform = 'translateY(0)';
    }, 100);
});

// Fonction pour afficher/cacher le mot de passe
function togglePasswordVisibility(inputId, buttonId) {
    const passwordInput = document.getElementById(inputId);
    const toggleButton = document.getElementById(buttonId);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleButton.innerHTML = '<i class="fas fa-eye-slash"></i>';
    } else {
        passwordInput.type = 'password';
        toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
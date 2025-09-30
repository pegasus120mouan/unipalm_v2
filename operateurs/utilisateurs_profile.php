<?php
//session_start();
require_once '../inc/functions/connexion.php';

// Récupération de l'ID utilisateur depuis l'URL
$id_utilisateur = $_GET['id'] ?? null;

if (!$id_utilisateur) {
    header('Location: utilisateurs.php');
    exit();
}

// Requête pour récupérer les informations utilisateur
$sql = "SELECT id, nom, prenoms, CONCAT(nom, ' ', prenoms) AS nom_complet, 
 contact, login, avatar, password, role
FROM utilisateurs 
WHERE id = :id_utilisateur";
$requete = $conn->prepare($sql);
$requete->bindParam(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
$requete->execute();
$vueUtilisateur = $requete->fetch(PDO::FETCH_ASSOC);

// Vérifier si l'utilisateur existe
if (!$vueUtilisateur) {
    header('Location: utilisateurs.php');
    exit();
}

// Inclure le header après les vérifications
include('header_operateurs.php');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Utilisateur - UniPalm</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        :root {
            --primary-color: #2E8B57;
            --secondary-color: #3CB371;
            --accent-color: #20B2AA;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --gradient-primary: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            --gradient-accent: linear-gradient(135deg, var(--accent-color) 0%, var(--info-color) 100%);
            --shadow-soft: 0 10px 30px rgba(0, 0, 0, 0.1);
            --shadow-medium: 0 15px 35px rgba(0, 0, 0, 0.15);
            --border-radius: 15px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px 0;
        }

        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .profile-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-soft);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .profile-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 2.5rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .breadcrumb-modern {
            background: none;
            padding: 0;
            margin: 0;
        }

        .breadcrumb-modern .breadcrumb-item {
            color: #6c757d;
            font-weight: 500;
        }

        .breadcrumb-modern .breadcrumb-item.active {
            color: var(--primary-color);
            font-weight: 600;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: var(--shadow-soft);
            transition: all 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid rgba(255, 255, 255, 0.3);
            box-shadow: var(--shadow-soft);
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .profile-avatar:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-medium);
        }

        .profile-info-card {
            padding: 30px;
            text-align: center;
        }

        .profile-name {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.5rem;
            color: var(--dark-color);
            margin: 20px 0 10px;
        }

        .profile-role {
            color: var(--primary-color);
            font-weight: 500;
            font-size: 1rem;
            margin-bottom: 20px;
        }

        .info-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-value {
            color: var(--primary-color);
            font-weight: 500;
        }

        .modern-tabs {
            border: none;
            margin-bottom: 30px;
        }

        .modern-tab {
            background: none;
            border: none;
            padding: 15px 25px;
            font-weight: 500;
            color: #6c757d;
            border-radius: 10px;
            margin-right: 10px;
            transition: all 0.3s ease;
        }

        .modern-tab.active {
            background: var(--gradient-primary);
            color: white;
            box-shadow: var(--shadow-soft);
        }

        .modern-tab:hover {
            background: rgba(46, 139, 87, 0.1);
            color: var(--primary-color);
        }

        .form-modern {
            padding: 30px;
        }

        .form-group-modern {
            margin-bottom: 25px;
        }

        .form-label-modern {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control-modern {
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            padding: 15px 20px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }

        .form-control-modern:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.25);
            background: white;
        }

        .password-input-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 5px;
            z-index: 10;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .password-toggle:focus {
            outline: none;
            color: var(--primary-color);
        }

        .btn-modern {
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary-modern {
            background: var(--gradient-primary);
            color: white;
            box-shadow: var(--shadow-soft);
        }

        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .btn-danger-modern {
            background: linear-gradient(135deg, var(--danger-color) 0%, #fd79a8 100%);
            color: white;
            box-shadow: var(--shadow-soft);
        }

        .btn-danger-modern:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .btn-info-modern {
            background: var(--gradient-accent);
            color: white;
            box-shadow: var(--shadow-soft);
        }

        .btn-info-modern:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .upload-area {
            border: 2px dashed rgba(46, 139, 87, 0.3);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            background: rgba(46, 139, 87, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(46, 139, 87, 0.1);
        }

        .upload-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .animate-fade-in {
            animation: fadeInUp 0.6s ease-out;
        }

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

        .success-message {
            background: linear-gradient(135deg, var(--success-color) 0%, #20c997 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-message {
            background: linear-gradient(135deg, var(--danger-color) 0%, #fd79a8 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .profile-title {
                font-size: 2rem;
            }
            
            .profile-container {
                padding: 0 15px;
            }
            
            .profile-header {
                padding: 20px;
            }
            
            .form-modern {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <!-- Header Section -->
        <div class="profile-header animate-fade-in">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1 class="profile-title">Profil de <?php echo htmlspecialchars($vueUtilisateur['nom_complet']); ?></h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb breadcrumb-modern">
                            <li class="breadcrumb-item">
                                <a href="#" class="text-decoration-none">
                                    <i class="fas fa-users me-2"></i>Agents
                                </a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                <?php echo htmlspecialchars($vueUtilisateur['nom_complet']); ?>
                            </li>
                        </ol>
                    </nav>
                </div>
                <div class="text-end">
                    <span class="badge bg-success fs-6 px-3 py-2">
                        <i class="fas fa-user-check me-2"></i>Profil Actif
                    </span>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row g-4">
            <!-- Profile Info Sidebar -->
            <div class="col-lg-4">
                <div class="glass-card profile-info-card animate-fade-in">
                    <!-- Avatar Section -->
                    <div class="mb-4">
                        <img src="../dossiers_images/<?php echo htmlspecialchars($vueUtilisateur['avatar']); ?>" 
                             alt="Avatar" class="profile-avatar mx-auto d-block">
                        <h2 class="profile-name"><?php echo htmlspecialchars($vueUtilisateur['nom_complet']); ?></h2>
                        <p class="profile-role">
                            <i class="fas fa-user-tie me-2"></i><?php echo ucfirst(htmlspecialchars($vueUtilisateur['role'])); ?>
                        </p>
                    </div>

                    <!-- Upload Photo Form -->
                    <div class="upload-area mb-4">
                        <form action="traitement_utilisateurs_images.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?php echo $vueUtilisateur['id']; ?>">
                            <div class="upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <h5 class="mb-3">Changer la photo de profil</h5>
                            <input type="file" class="form-control form-control-modern mb-3" name="photo" accept="image/*">
                            <button type="submit" class="btn btn-info-modern btn-modern">
                                <i class="fas fa-upload me-2"></i>Mettre à jour
                            </button>
                        </form>
                    </div>

                    <!-- User Info -->
                    <ul class="info-list">
                        <li class="info-item">
                            <span class="info-label">
                                <i class="fas fa-user"></i>Nom
                            </span>
                            <span class="info-value"><?php echo htmlspecialchars($vueUtilisateur['nom']); ?></span>
                        </li>
                        <li class="info-item">
                            <span class="info-label">
                                <i class="fas fa-id-badge"></i>Prénoms
                            </span>
                            <span class="info-value"><?php echo htmlspecialchars($vueUtilisateur['prenoms']); ?></span>
                        </li>
                        <li class="info-item">
                            <span class="info-label">
                                <i class="fas fa-phone"></i>Contact
                            </span>
                            <span class="info-value"><?php echo htmlspecialchars($vueUtilisateur['contact']); ?></span>
                        </li>
                        <li class="info-item">
                            <span class="info-label">
                                <i class="fas fa-at"></i>Login
                            </span>
                            <span class="info-value"><?php echo htmlspecialchars($vueUtilisateur['login']); ?></span>
                        </li>
                    </ul>

                    <!-- Action Button -->
                    <div class="mt-4">
                        <a href="#" class="btn btn-primary-modern btn-modern w-100">
                            <i class="fas fa-ticket-alt me-2"></i>Mes Tickets
                        </a>
                    </div>
                </div>
            </div>
            <!-- Forms Section -->
            <div class="col-lg-8">
                <div class="glass-card animate-fade-in">
                    <!-- Tabs Navigation -->
                    <div class="modern-tabs">
                        <ul class="nav nav-pills" id="profileTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="modern-tab active" id="profile-tab" data-bs-toggle="pill" 
                                        data-bs-target="#profile" type="button" role="tab" 
                                        aria-controls="profile" aria-selected="true">
                                    <i class="fas fa-user-edit me-2"></i>Modifier mon profil
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="modern-tab" id="password-tab" data-bs-toggle="pill" 
                                        data-bs-target="#password" type="button" role="tab" 
                                        aria-controls="password" aria-selected="false">
                                    <i class="fas fa-key me-2"></i>Changer mot de passe
                                </button>
                            </li>
                        </ul>
                    </div>
                    <!-- Tab Content -->
                    <div class="tab-content" id="profileTabsContent">
                        <!-- Profile Edit Tab -->
                        <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                            <div class="form-modern">
                                <h4 class="mb-4">
                                    <i class="fas fa-user-edit me-2 text-primary"></i>Modifier les informations personnelles
                                </h4>
                                
                                <?php if (isset($_SESSION['success'])): ?>
                                    <div class="success-message">
                                        <i class="fas fa-check-circle"></i>
                                        <div><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($_SESSION['error'])): ?>
                                    <div class="error-message">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <div><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="post" action="enregistrement/save_utilisateur_profile.php">
                                    <input type="hidden" name="id" value="<?php echo $vueUtilisateur['id']; ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label for="nom" class="form-label-modern">
                                                    <i class="fas fa-user"></i>Nom
                                                </label>
                                                <input type="text" class="form-control form-control-modern" 
                                                       id="nom" name="nom" 
                                                       value="<?php echo htmlspecialchars($vueUtilisateur['nom']); ?>" 
                                                       required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group-modern">
                                                <label for="prenoms" class="form-label-modern">
                                                    <i class="fas fa-id-badge"></i>Prénoms
                                                </label>
                                                <input type="text" class="form-control form-control-modern" 
                                                       id="prenoms" name="prenoms" 
                                                       value="<?php echo htmlspecialchars($vueUtilisateur['prenoms']); ?>" 
                                                       required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group-modern">
                                        <label for="contact" class="form-label-modern">
                                            <i class="fas fa-phone"></i>Contact
                                        </label>
                                        <input type="tel" class="form-control form-control-modern" 
                                               id="contact" name="contact" 
                                               value="<?php echo htmlspecialchars($vueUtilisateur['contact']); ?>" 
                                               required>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end gap-3 mt-4">
                                        <button type="reset" class="btn btn-secondary">
                                            <i class="fas fa-undo me-2"></i>Réinitialiser
                                        </button>
                                        <button type="submit" class="btn btn-primary-modern btn-modern">
                                            <i class="fas fa-save me-2"></i>Enregistrer les modifications
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Password Change Tab -->
                        <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
                            <div class="form-modern">
                                <h4 class="mb-4">
                                    <i class="fas fa-key me-2 text-danger"></i>Changer le mot de passe
                                </h4>
                                
                                <?php if (isset($_SESSION['error'])): ?>
                                    <div class="error-message">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <div><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="alert alert-info d-flex align-items-center mb-4">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <div>
                                        <strong>Sécurité :</strong> Choisissez un mot de passe fort avec au moins 8 caractères, incluant des lettres, chiffres et symboles.
                                    </div>
                                </div>
                                
                                <form method="post" action="enregistrement/save_utilisateur_update_password.php" id="passwordForm">
                                    <input type="hidden" name="id" value="<?php echo $vueUtilisateur['id']; ?>">
                                    
                                    <div class="form-group-modern">
                                        <label for="old_password" class="form-label-modern">
                                            <i class="fas fa-lock"></i>Ancien mot de passe
                                        </label>
                                        <div class="password-input-group">
                                            <input type="password" class="form-control form-control-modern" 
                                                   id="old_password" name="old_password" 
                                                   placeholder="Entrez votre ancien mot de passe" 
                                                   required>
                                            <button type="button" class="password-toggle" onclick="togglePassword('old_password')">
                                                <i class="fas fa-eye" id="old_password_icon"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group-modern">
                                        <label for="new_password" class="form-label-modern">
                                            <i class="fas fa-key"></i>Nouveau mot de passe
                                        </label>
                                        <div class="password-input-group">
                                            <input type="password" class="form-control form-control-modern" 
                                                   id="new_password" name="new_password" 
                                                   placeholder="Entrez votre nouveau mot de passe" 
                                                   minlength="8" required>
                                            <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                                <i class="fas fa-eye" id="new_password_icon"></i>
                                            </button>
                                        </div>
                                        <div class="form-text mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-shield-alt me-1"></i>Minimum 8 caractères recommandés
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group-modern">
                                        <label for="check_password" class="form-label-modern">
                                            <i class="fas fa-check-double"></i>Confirmer le mot de passe
                                        </label>
                                        <div class="password-input-group">
                                            <input type="password" class="form-control form-control-modern" 
                                                   id="check_password" name="check_password" 
                                                   placeholder="Confirmez votre nouveau mot de passe" 
                                                   required>
                                            <button type="button" class="password-toggle" onclick="togglePassword('check_password')">
                                                <i class="fas fa-eye" id="check_password_icon"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end gap-3 mt-4">
                                        <button type="reset" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>Annuler
                                        </button>
                                        <button type="submit" class="btn btn-danger-modern btn-modern">
                                            <i class="fas fa-key me-2"></i>Changer le mot de passe
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);">
                <div class="modal-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title" id="successModalLabel">
                        <i class="fas fa-check-circle me-2"></i>Succès
                    </h5>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="text-success mb-3">Mot de passe changé avec succès !</h4>
                    <p class="text-muted">Votre mot de passe a été mis à jour avec succès.</p>
                </div>
                <div class="modal-footer justify-content-center" style="border: none;">
                    <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">
                        <i class="fas fa-check me-2"></i>OK
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- Custom JavaScript -->
    <script>
        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + '_icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Redirect to users page
        function redirectToUsers() {
            window.location.href = 'utilisateurs.php';
        }


        // Check for messages and switch tabs accordingly
        document.addEventListener('DOMContentLoaded', function() {
            // If there's an error message, switch to password tab
            <?php if (isset($_SESSION['error'])): ?>
                const passwordTab = new bootstrap.Tab(document.getElementById('password-tab'));
                passwordTab.show();
            <?php endif; ?>
            
            // If there's a success message, show modal and switch to password tab
            <?php if (isset($_SESSION['success'])): ?>
                const passwordTab = new bootstrap.Tab(document.getElementById('password-tab'));
                passwordTab.show();
                
                // Show success modal after tab switch
                setTimeout(() => {
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                }, 300);
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
        });

        // Password confirmation validation
        document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('check_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas!');
                return false;
            }
        });
        
        // Real-time password confirmation check
        document.getElementById('check_password')?.addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('Les mots de passe ne correspondent pas');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                if (confirmPassword && newPassword === confirmPassword) {
                    this.classList.add('is-valid');
                }
            }
        });
        
        // File upload preview
        document.querySelector('input[type="file"]')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-avatar').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Add loading states to buttons
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement...';
                    submitBtn.disabled = true;
                }
            });
        });
        
        // Smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            // Add staggered animation delays
            document.querySelectorAll('.animate-fade-in').forEach((el, index) => {
                el.style.animationDelay = (index * 0.1) + 's';
            });
        });
    </script>
</body>
</html>
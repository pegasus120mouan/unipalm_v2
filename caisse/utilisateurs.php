<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_utilisateurs.php';
include('header_caisse.php');

// Gestion des filtres de recherche
$search_login = trim($_GET['search_login'] ?? '');
$search_nom = trim($_GET['search_nom'] ?? '');
$search_role = trim($_GET['search_role'] ?? '');

// Récupérer les utilisateurs selon les filtres
$utilisateurs = [];
if (!empty($search_login) || !empty($search_nom) || !empty($search_role)) {
    // Filtrage personnalisé
    $all_users = getUtilisateurs($conn);
    $utilisateurs = array_filter($all_users, function($user) use ($search_login, $search_nom, $search_role) {
        $match_login = empty($search_login) || stripos($user['login'], $search_login) !== false;
        $match_nom = empty($search_nom) || stripos($user['nom'] . ' ' . $user['prenoms'], $search_nom) !== false;
        $match_role = empty($search_role) || stripos($user['role'], $search_role) !== false;
        
        return $match_login && $match_nom && $match_role;
    });
} else {
    $utilisateurs = getUtilisateurs($conn);
}

// Statistiques
$total_users = count(getUtilisateurs($conn));
$active_users = count(array_filter(getUtilisateurs($conn), function($user) { return $user['statut_compte'] == 1; }));
$inactive_users = $total_users - $active_users;
$filtered_count = count($utilisateurs);
?>

<style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --success-color: #4facfe;
            --warning-color: #f6d365;
            --danger-color: #ff6b6b;
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: rgba(255, 255, 255, 0.18);
            --shadow-light: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            --text-primary: #2c3e50;
            --text-secondary: #7f8c8d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            overflow-x: hidden;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .main-container {
            position: relative;
            z-index: 2;
            padding: 2rem;
            min-height: 100vh;
        }

        /* Header */
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            animation: slideInDown 0.8s ease-out;
        }

        .page-title {
            font-family: 'Poppins', sans-serif;
            font-size: 3rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            text-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .page-subtitle {
            color: #34495e;
            font-size: 1.2rem;
            font-weight: 500;
        }

        /* Breadcrumb */
        .breadcrumb-container {
            margin-bottom: 2rem;
            animation: slideInLeft 0.6s ease-out;
        }

        .breadcrumb-modern {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            padding: 1rem 1.5rem;
            box-shadow: var(--shadow-light);
        }

        .breadcrumb-modern .breadcrumb {
            margin: 0;
            background: none;
            font-weight: 500;
        }

        .breadcrumb-modern .breadcrumb-item a {
            color: #667eea;
            text-decoration: none;
            transition: color 0.3s ease;
            font-weight: 500;
        }

        .breadcrumb-modern .breadcrumb-item a:hover {
            color: #764ba2;
        }

        .breadcrumb-modern .breadcrumb-item.active {
            color: #2c3e50;
            font-weight: 600;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
            animation: slideInUp 0.6s ease-out;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--shadow-light);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.5);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 20px 20px 0 0;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            color: white;
            animation: float 3s ease-in-out infinite;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-family: 'Poppins', sans-serif;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stat-label {
            color: #34495e;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        /* Action buttons */
        .actions-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            justify-content: center;
            animation: slideInUp 0.8s ease-out;
        }

        .btn-modern {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
        }

        .btn-modern:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-modern:hover::before {
            left: 100%;
        }

        .btn-danger-modern {
            background: linear-gradient(135deg, var(--danger-color), #ff8a80);
        }

        .btn-success-modern {
            background: linear-gradient(135deg, var(--success-color), #00d2ff);
        }

        .btn-warning-modern {
            background: linear-gradient(135deg, var(--warning-color), #ffa726);
        }

        /* Search container */
        .search-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: var(--shadow-light);
            animation: slideInUp 0.6s ease-out;
        }

        .search-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .search-field {
            display: flex;
            flex-direction: column;
        }

        .search-label {
            color: #2c3e50;
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-family: 'Poppins', sans-serif;
        }

        .search-input {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            color: #2c3e50;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            background: rgba(255, 255, 255, 0.95);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            outline: none;
            transform: translateY(-2px);
        }

        .search-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-search {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }

        .btn-search:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-reset {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }

        .btn-reset:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(108, 117, 125, 0.4);
            color: white;
        }

        .search-results {
            background: rgba(79, 172, 254, 0.1);
            border: 1px solid rgba(79, 172, 254, 0.3);
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }

        .results-info {
            color: #2c3e50;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }

        .filter-label {
            color: #2c3e50;
            font-weight: 600;
            margin-right: 0.5rem;
        }

        .filter-badge {
            background: linear-gradient(135deg, var(--accent-color), #ff9a9e);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Table container */
        .table-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-light);
            margin-bottom: 2rem;
            animation: slideInUp 1s ease-out;
        }

        .table-modern {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .table-modern thead th {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-weight: 600;
            padding: 1rem;
            text-align: left;
            border: none;
            font-family: 'Poppins', sans-serif;
        }

        .table-modern thead th:first-child {
            border-radius: 15px 0 0 0;
        }

        .table-modern thead th:last-child {
            border-radius: 0 15px 0 0;
        }

        .table-modern tbody tr {
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }

        .table-modern tbody tr:hover {
            background: rgba(255, 255, 255, 0.95);
            transform: scale(1.01);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .table-modern tbody td {
            padding: 1rem;
            border: none;
            color: #2c3e50;
            vertical-align: middle;
            font-weight: 500;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .user-avatar:hover {
            transform: scale(1.1);
            border-color: var(--primary-color);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .status-active {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .status-inactive {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            background: linear-gradient(135deg, var(--accent-color), #ff9a9e);
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .action-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin: 0 0.25rem;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-edit {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .btn-delete {
            background: linear-gradient(135deg, var(--danger-color), #ff8a80);
        }

        /* Modal styling */
        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            box-shadow: var(--shadow-light);
        }

        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 20px 20px 0 0;
        }

        .modal-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }

        .modal-body {
            color: #2c3e50;
        }

        .form-label {
            color: #2c3e50;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            color: #2c3e50;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.95);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        /* Animations */
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .table-responsive {
                border-radius: 15px;
                overflow-x: auto;
            }
            
            .actions-container {
                flex-direction: column;
                align-items: center;
            }
            
            .search-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Ripple effect */
        .ripple {
            position: relative;
            overflow: hidden;
        }

        .ripple::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .ripple:active::before {
            width: 300px;
            height: 300px;
        }

        /* Loading animation */
        .loading {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
</style>

<!-- Additional CSS for modern design -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">

    <!-- Floating particles -->
    <div class="particles"></div>

    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-users me-3"></i>Gestion des Utilisateurs
            </h1>
            <p class="page-subtitle">Gérez les comptes utilisateurs et leurs permissions</p>
        </div>

        <!-- Breadcrumb -->
        <div class="breadcrumb-container">
            <div class="breadcrumb-modern">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard.php"><i class="fas fa-home"></i> Accueil</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Utilisateurs</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?= $total_users ?></div>
                <div class="stat-label">Total Utilisateurs</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--success-color), #00d2ff);">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-number"><?= $active_users ?></div>
                <div class="stat-label">Comptes Actifs</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--danger-color), #ff8a80);">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="stat-number"><?= $inactive_users ?></div>
                <div class="stat-label">Comptes Inactifs</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning-color), #ffa726);">
                    <i class="fas fa-filter"></i>
                </div>
                <div class="stat-number"><?= $filtered_count ?></div>
                <div class="stat-label">Utilisateurs Affichés</div>
            </div>
        </div>

        <!-- Action buttons -->
        <div class="actions-container">
            <button type="button" class="btn-modern ripple" data-bs-toggle="modal" data-bs-target="#add-user">
                <i class="fas fa-user-plus"></i>
                Enregistrer un utilisateur
            </button>

            <button type="button" class="btn-modern btn-danger-modern ripple" onclick="window.location.href='export_utilisateurs.php'">
                <i class="fas fa-file-export"></i>
                Exporter la liste
            </button>

            <button type="button" class="btn-modern btn-success-modern ripple" onclick="window.location.href='impression_utilisateurs.php'">
                <i class="fas fa-print"></i>
                Imprimer la liste
            </button>
        </div>

        <!-- Search Filters -->
        <div class="search-container">
            <h3 style="color: #2c3e50; margin-bottom: 1.5rem; font-family: 'Poppins', sans-serif;">
                <i class="fas fa-filter"></i> Filtres de Recherche
            </h3>
            
            <form method="GET" class="search-form">
                <div class="search-grid">
                    <div class="search-field">
                        <label for="search_login" class="search-label">
                            <i class="fas fa-user me-2"></i>Login
                        </label>
                        <input type="text" 
                               id="search_login" 
                               name="search_login" 
                               class="search-input" 
                               placeholder="Rechercher par login..."
                               value="<?= htmlspecialchars($search_login) ?>">
                    </div>
                    
                    <div class="search-field">
                        <label for="search_nom" class="search-label">
                            <i class="fas fa-user me-2"></i>Nom/Prénom
                        </label>
                        <input type="text" 
                               id="search_nom" 
                               name="search_nom" 
                               class="search-input" 
                               placeholder="Rechercher par nom ou prénom..."
                               value="<?= htmlspecialchars($search_nom) ?>">
                    </div>
                    
                    <div class="search-field">
                        <label for="search_role" class="search-label">
                            <i class="fas fa-user-tag me-2"></i>Rôle
                        </label>
                        <input type="text" 
                               id="search_role" 
                               name="search_role" 
                               class="search-input" 
                               placeholder="Rechercher par rôle..."
                               value="<?= htmlspecialchars($search_role) ?>">
                    </div>
                </div>
                
                <div class="search-actions">
                    <button type="submit" class="btn-search ripple">
                        <i class="fas fa-search me-2"></i>Rechercher
                    </button>
                    <a href="utilisateurs.php" class="btn-reset ripple">
                        <i class="fas fa-times me-2"></i>Réinitialiser
                    </a>
                </div>
            </form>
            
            <!-- Search Results -->
            <?php if (!empty($search_login) || !empty($search_nom) || !empty($search_role)): ?>
                <div class="search-results">
                    <div class="results-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong><?= $filtered_count ?></strong> utilisateur(s) trouvé(s) sur <?= $total_users ?> au total
                    </div>
                    
                    <div class="active-filters">
                        <span class="filter-label">Filtres actifs :</span>
                        <?php if (!empty($search_login)): ?>
                            <span class="filter-badge">Login: <?= htmlspecialchars($search_login) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($search_nom)): ?>
                            <span class="filter-badge">Nom: <?= htmlspecialchars($search_nom) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($search_role)): ?>
                            <span class="filter-badge">Rôle: <?= htmlspecialchars($search_role) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Users table -->
        <div class="table-container">
            <h3 style="color: #2c3e50; margin-bottom: 1.5rem; font-family: 'Poppins', sans-serif;">
                <i class="fas fa-list"></i> Liste des Utilisateurs
            </h3>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user"></i> Nom</th>
                            <th><i class="fas fa-user"></i> Prénoms</th>
                            <th><i class="fas fa-phone"></i> Contact</th>
                            <th><i class="fas fa-user-tag"></i> Rôle</th>
                            <th><i class="fas fa-sign-in-alt"></i> Login</th>
                            <th><i class="fas fa-image"></i> Avatar</th>
                            <th><i class="fas fa-cogs"></i> Actions</th>
                            <th><i class="fas fa-toggle-on"></i> Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utilisateurs as $utilisateur): ?>
                        <tr>
                            <td><?= htmlspecialchars($utilisateur['nom']) ?></td>
                            <td><?= htmlspecialchars($utilisateur['prenoms']) ?></td>
                            <td>
                                <span class="status-badge">
                                    <i class="fas fa-phone me-1"></i>
                                    <?= htmlspecialchars($utilisateur['contact']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="role-badge">
                                    <?= htmlspecialchars($utilisateur['role']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?= $utilisateur['statut_compte'] == 1 ? 'status-active' : 'status-inactive' ?>">
                                    <?= htmlspecialchars($utilisateur['login']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="utilisateurs_profile.php?id=<?= $utilisateur['id'] ?>">
                                    <img src="../dossiers_images/<?= htmlspecialchars($utilisateur['avatar']) ?>" 
                                         alt="Avatar" 
                                         class="user-avatar">
                                </a>
                            </td>
                            <td>
                                <a href="utilisateurs_update.php?id=<?= $utilisateur['id'] ?>" 
                                   class="action-btn btn-edit" 
                                   title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="action-btn btn-delete" 
                                        onclick="confirmDelete(<?= $utilisateur['id'] ?>)" 
                                        title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                            <td>
                                <form method="post" action="traitement_statut_compte.php">
                                    <input type="hidden" name="user_id" value="<?= $utilisateur['id'] ?>">
                                    <input type="hidden" name="statut_compte" value="<?= ($utilisateur['statut_compte'] == 0) ? 1 : 0 ?>">
                                    <input type="checkbox" 
                                           name="statut_compte_display" 
                                           data-toggle="toggle" 
                                           data-on="Actif" 
                                           data-off="Inactif" 
                                           data-onstyle="success" 
                                           data-offstyle="danger" 
                                           <?= ($utilisateur['statut_compte'] == 1) ? 'checked' : '' ?> 
                                           onchange="submitForm(this)">
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="add-user" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">
                        <i class="fas fa-user-plus me-2"></i>Enregistrer un utilisateur
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form class="forms-sample" method="post" action="traitement_utilisateurs.php">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nom" class="form-label">
                                        <i class="fas fa-user me-2"></i>Nom
                                    </label>
                                    <input type="text" class="form-control" id="nom" name="nom" placeholder="Nom" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="prenoms" class="form-label">
                                        <i class="fas fa-user me-2"></i>Prénoms
                                    </label>
                                    <input type="text" class="form-control" id="prenoms" name="prenoms" placeholder="Prénoms" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contact" class="form-label">
                                        <i class="fas fa-phone me-2"></i>Contact
                                    </label>
                                    <input type="text" class="form-control" id="contact" name="contact" placeholder="Contact" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="login" class="form-label">
                                        <i class="fas fa-sign-in-alt me-2"></i>Login
                                    </label>
                                    <input type="text" class="form-control" id="login" name="login" placeholder="Login" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock me-2"></i>Mot de passe
                                    </label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="retype_password" class="form-label">
                                        <i class="fas fa-lock me-2"></i>Confirmation
                                    </label>
                                    <input type="password" class="form-control" id="retype_password" name="retype_password" placeholder="Confirmer le mot de passe" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">
                                <i class="fas fa-user-tag me-2"></i>Rôle
                            </label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="">Sélectionner un rôle</option>
                                <option value="admin">Administrateur</option>
                                <option value="operateur">Opérateur</option>
                                <option value="validateur">Validateur</option>
                                <option value="caissiere">Caissière</option>
                                <option value="directeur">Directeur</option>
                            </select>
                        </div>

                        <div class="modal-footer border-0">
                            <button type="submit" class="btn-modern ripple" name="signup">
                                <i class="fas fa-save me-2"></i>Enregistrer
                            </button>
                            <button type="button" class="btn-modern btn-danger-modern ripple" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>

    <script>
        // Generate floating particles
        function createParticles() {
            const particles = document.querySelector('.particles');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.width = Math.random() * 10 + 5 + 'px';
                particle.style.height = particle.style.width;
                particle.style.animationDelay = Math.random() * 6 + 's';
                particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
                particles.appendChild(particle);
            }
        }

        // Initialize particles on page load
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            
            // Animate elements on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe all animated elements
            document.querySelectorAll('.stat-card, .table-container, .search-container').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(el);
            });
        });

        // Enhanced delete confirmation
        function confirmDelete(id) {
            Swal.fire({
                title: 'Êtes-vous sûr ?',
                text: "Cette action est irréversible !",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff6b6b',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-trash me-2"></i>Oui, supprimer',
                cancelButtonText: '<i class="fas fa-times me-2"></i>Annuler',
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: 'rgba(0, 0, 0, 0.8)',
                customClass: {
                    popup: 'border-0 shadow-lg',
                    title: 'text-dark fw-bold',
                    content: 'text-dark'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Suppression en cours...',
                        html: '<div class="loading"></div>',
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        background: 'rgba(255, 255, 255, 0.95)',
                        customClass: {
                            popup: 'border-0 shadow-lg'
                        }
                    });
                    
                    // Redirect to delete
                    setTimeout(() => {
                        window.location.href = 'traitement_utilisateurs.php?action=delete&id=' + id;
                    }, 1000);
                }
            });
        }

        // Form validation enhancement
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<div class="loading me-2"></div>Traitement...';
                    submitBtn.disabled = true;
                }
            });
        });

        // Submit form for status toggle
        function submitForm(checkbox) {
            const form = checkbox.closest('form');
            if (form) {
                // Show loading state
                const submitBtn = document.createElement('button');
                submitBtn.type = 'submit';
                submitBtn.style.display = 'none';
                form.appendChild(submitBtn);
                submitBtn.click();
            }
        }

        // Ripple effect for buttons
        document.querySelectorAll('.ripple').forEach(button => {
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple-effect');
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Add ripple effect CSS
        const style = document.createElement('style');
        style.textContent = `
            .ripple-effect {
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.6);
                transform: scale(0);
                animation: ripple-animation 0.6s linear;
                pointer-events: none;
            }
            
            @keyframes ripple-animation {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>

    <!-- Success/Error Messages -->
    <?php if(isset($_SESSION['popup']) && $_SESSION['popup'] == true): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Succès !',
            text: 'Utilisateur créé avec succès.',
            background: 'rgba(255, 255, 255, 0.95)',
            color: '#2c3e50',
            customClass: {
                popup: 'border-0 shadow-lg'
            }
        });
    </script>
    <?php $_SESSION['popup'] = false; endif; ?>

    <?php if(isset($_SESSION['delete_pop']) && $_SESSION['delete_pop'] == true): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Erreur !',
            text: 'Erreur lors de la création de l\'utilisateur.',
            background: 'rgba(255, 255, 255, 0.95)',
            color: '#2c3e50',
            customClass: {
                popup: 'border-0 shadow-lg'
            }
        });
    </script>
    <?php $_SESSION['delete_pop'] = false; endif; ?>

</body>
</html>

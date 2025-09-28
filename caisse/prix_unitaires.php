<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_tickets.php';
require_once '../inc/functions/requete/requete_usines.php';
require_once '../inc/functions/requete/requete_chef_equipes.php';
require_once '../inc/functions/requete/requete_vehicules.php';
require_once '../inc/functions/requete/requete_agents.php';

include('header_caisse.php');

$id_user = $_SESSION['user_id'];

$limit = $_GET['limit'] ?? 15; // Nombre d'éléments par page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Page actuelle

// Récupérer les paramètres de filtrage
$usine_id = $_GET['usine_id'] ?? null;
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$prix_min = $_GET['prix_min'] ?? '';
$prix_max = $_GET['prix_max'] ?? '';

// Requête SQL pour compter le nombre total d'éléments
$sql_count = "SELECT COUNT(*) as total FROM prix_unitaires
              INNER JOIN usines ON prix_unitaires.id_usine = usines.id_usine
              WHERE 1=1";

// Requête SQL pour récupérer les prix unitaires avec filtres
$sql = "SELECT
    usines.nom_usine,
    usines.id_usine,
    prix_unitaires.prix,
    prix_unitaires.date_debut,
    prix_unitaires.date_fin,
    prix_unitaires.id
FROM
    prix_unitaires
INNER JOIN
    usines ON prix_unitaires.id_usine = usines.id_usine
WHERE 1=1";

// Ajouter les conditions de filtrage aux deux requêtes
if ($usine_id) {
    $condition = " AND prix_unitaires.id_usine = :usine_id";
    $sql .= $condition;
    $sql_count .= $condition;
}
if ($date_debut) {
    $condition = " AND prix_unitaires.date_debut >= :date_debut";
    $sql .= $condition;
    $sql_count .= $condition;
}
if ($date_fin) {
    $condition = " AND prix_unitaires.date_fin <= :date_fin";
    $sql .= $condition;
    $sql_count .= $condition;
}
if ($prix_min) {
    $condition = " AND prix_unitaires.prix >= :prix_min";
    $sql .= $condition;
    $sql_count .= $condition;
}
if ($prix_max) {
    $condition = " AND prix_unitaires.prix <= :prix_max";
    $sql .= $condition;
    $sql_count .= $condition;
}

// Ajouter l'ordre et la pagination à la requête principale
$sql .= " ORDER BY prix_unitaires.date_debut DESC LIMIT :offset, :limit";

// Préparer et exécuter la requête de comptage
$stmt_count = $conn->prepare($sql_count);
if ($usine_id) $stmt_count->bindParam(':usine_id', $usine_id);
if ($date_debut) $stmt_count->bindParam(':date_debut', $date_debut);
if ($date_fin) $stmt_count->bindParam(':date_fin', $date_fin);
if ($prix_min) $stmt_count->bindParam(':prix_min', $prix_min);
if ($prix_max) $stmt_count->bindParam(':prix_max', $prix_max);
$stmt_count->execute();
$total_items = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];

// Calculer la pagination
$total_pages = ceil($total_items / $limit);
$page = max(1, min($page, $total_pages));
$offset = ($page - 1) * $limit;

// Préparer et exécuter la requête principale
$stmt = $conn->prepare($sql);
if ($usine_id) $stmt->bindParam(':usine_id', $usine_id);
if ($date_debut) $stmt->bindParam(':date_debut', $date_debut);
if ($date_fin) $stmt->bindParam(':date_fin', $date_fin);
if ($prix_min) $stmt->bindParam(':prix_min', $prix_min);
if ($prix_max) $stmt->bindParam(':prix_max', $prix_max);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$prix_unitaires_list = $stmt->fetchAll();

$usines = getUsines($conn);
$chefs_equipes=getChefEquipes($conn);
$vehicules=getVehicules($conn);
$agents=getAgents($conn);

// Vérifiez si des tickets existent avant de procéder
if (!empty($tickets)) {
    $total_tickets = count($tickets);
    $total_pages = ceil($total_tickets / $limit);
    $page = max(1, min($page, $total_pages));
    $offset = ($page - 1) * $limit;
    $tickets_list = array_slice($tickets, $offset, $limit);
} else {
    $tickets_list = [];
    $total_pages = 1;
}

?>

<!-- Main row -->
<style>
    /* ===== STYLES ULTRA-PROFESSIONNELS POUR PRIX UNITAIRES ===== */
    
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
        --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --glass-bg: rgba(255, 255, 255, 0.95);
        --glass-border: rgba(255, 255, 255, 0.3);
        --shadow-light: 0 8px 32px rgba(31, 38, 135, 0.15);
        --shadow-heavy: 0 15px 35px rgba(31, 38, 135, 0.25);
        --border-radius: 20px;
        --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Page Header */
    .page-header {
        background: var(--primary-gradient);
        color: white;
        padding: 2rem 1.5rem;
        border-radius: var(--border-radius);
        margin-bottom: 2rem;
        box-shadow: var(--shadow-heavy);
        position: relative;
        overflow: hidden;
    }

    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transform: translate(50%, -50%);
    }

    .page-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .page-header p {
        font-size: 1.1rem;
        margin: 0.5rem 0 0 0;
        opacity: 0.9;
    }

    /* Action Buttons Container */
    .action-buttons-container {
        background: var(--glass-bg);
        backdrop-filter: blur(15px);
        border: 1px solid var(--glass-border);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-light);
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
    }

    .btn-professional {
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: var(--transition);
        border: none;
        position: relative;
        overflow: hidden;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-professional:disabled {
        background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%) !important;
        color: rgba(255, 255, 255, 0.7) !important;
        cursor: not-allowed !important;
        opacity: 0.6 !important;
        transform: none !important;
        box-shadow: none !important;
    }

    .btn-professional:not(:disabled):hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }

    .btn-add {
        background: var(--success-gradient);
        color: white;
        box-shadow: 0 4px 15px rgba(86, 171, 47, 0.3);
    }

    .btn-print {
        background: var(--info-gradient);
        color: white;
        box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
    }

    /* Search Container */
    .search-container {
        background: var(--glass-bg);
        backdrop-filter: blur(15px);
        border: 1px solid var(--glass-border);
        border-radius: var(--border-radius);
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-light);
    }

    .search-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-control-modern {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 12px 16px;
        font-size: 0.95rem;
        transition: var(--transition);
        background: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .form-control-modern:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }

    .btn-search {
        background: var(--primary-gradient);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 12px 24px;
        font-weight: 600;
        transition: var(--transition);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .btn-search:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        color: white;
    }

    .btn-reset {
        background: transparent;
        color: #e74c3c;
        border: 2px solid #e74c3c;
        border-radius: 12px;
        padding: 10px 22px;
        font-weight: 600;
        transition: var(--transition);
    }

    .btn-reset:hover {
        background: #e74c3c;
        color: white;
        transform: translateY(-2px);
    }

    /* Active Filters */
    .active-filters {
        background: rgba(102, 126, 234, 0.05);
        border-radius: 12px;
        padding: 1rem;
        border-left: 4px solid #667eea;
    }

    .badge-filter {
        background: var(--primary-gradient);
        color: white;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 500;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .badge-filter a {
        color: white;
        opacity: 0.8;
        transition: opacity 0.3s;
    }

    .badge-filter a:hover {
        opacity: 1;
        text-decoration: none;
    }

    /* Table Professional */
    .table-container {
        background: var(--glass-bg);
        backdrop-filter: blur(15px);
        border: 1px solid var(--glass-border);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-light);
        overflow: hidden;
    }

    .table-professional {
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .table-professional thead th {
        background: var(--primary-gradient);
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 1rem;
        border: none;
        font-size: 0.9rem;
    }

    .table-professional tbody tr {
        background: white;
        transition: var(--transition);
    }

    .table-professional tbody tr:hover {
        background: rgba(102, 126, 234, 0.05);
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .table-professional tbody td {
        padding: 1rem;
        border-bottom: 1px solid #f8f9fa;
        font-size: 0.95rem;
        vertical-align: middle;
    }

    .table-professional tbody tr:last-child td {
        border-bottom: none;
    }

    /* Action Buttons in Table */
    .action-btn {
        width: 35px;
        height: 35px;
        border-radius: 8px;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 0 2px;
        transition: var(--transition);
        cursor: pointer;
    }

    .action-btn-edit {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3);
    }

    .action-btn-edit:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.4);
    }

    .action-btn-delete {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(231, 76, 60, 0.3);
    }

    .action-btn-delete:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
    }

    /* Pagination Professional */
    .pagination-container {
        background: var(--glass-bg);
        backdrop-filter: blur(15px);
        border: 1px solid var(--glass-border);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--shadow-light);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .pagination-nav {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .pagination-btn {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        border: none;
        background: var(--primary-gradient);
        color: white;
        font-weight: 600;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    .pagination-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        color: white;
        text-decoration: none;
    }

    .pagination-info {
        background: rgba(102, 126, 234, 0.1);
        color: #667eea;
        padding: 8px 16px;
        border-radius: 10px;
        font-weight: 600;
        margin: 0 1rem;
    }

    .items-per-page-form {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(102, 126, 234, 0.05);
        padding: 8px 12px;
        border-radius: 10px;
    }

    .items-per-page-select {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 6px 12px;
        font-size: 0.9rem;
        background: white;
        transition: var(--transition);
    }

    .items-per-page-select:focus {
        border-color: #667eea;
        outline: none;
    }

    .submit-button {
        background: var(--primary-gradient);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 6px 12px;
        font-weight: 600;
        transition: var(--transition);
        cursor: pointer;
    }

    .submit-button:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .page-header h1 {
            font-size: 2rem;
        }
        
        .action-buttons-container {
            flex-direction: column;
            align-items: stretch;
        }
        
        .btn-professional {
            justify-content: center;
        }
        
        .table-container {
            padding: 1rem;
            overflow-x: auto;
        }
        
        .pagination-container {
            flex-direction: column;
            text-align: center;
        }
        
        .pagination-nav {
            justify-content: center;
        }
    }

    @media (max-width: 576px) {
        .table-professional thead {
            display: none;
        }
        
        .table-professional tbody tr {
            display: block;
            margin-bottom: 1rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 1rem;
        }
        
        .table-professional tbody td {
            display: block;
            text-align: left;
            border: none;
            padding: 0.5rem 0;
        }
        
        .table-professional tbody td::before {
            content: attr(data-label) ": ";
            font-weight: 700;
            color: #667eea;
            display: inline-block;
            width: 120px;
        }
    }

    /* Status Indicators */
    .status-active {
        background: var(--success-gradient);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .status-expired {
        background: var(--warning-gradient);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    /* Price Display */
    .price-display {
        font-weight: 700;
        color: #27ae60;
        font-size: 1.1rem;
    }

    /* Animation for loading states */
    @keyframes shimmer {
        0% { background-position: -200px 0; }
        100% { background-position: calc(200px + 100%) 0; }
    }

    .loading-shimmer {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200px 100%;
        animation: shimmer 1.5s infinite;
    }
</style>


<!-- Page Header -->
<div class="page-header">
    <h1><i class="fas fa-tags mr-3"></i>Gestion des Prix Unitaires</h1>
    <p>Gérez et consultez les prix unitaires par usine avec des outils de recherche avancés</p>
</div>

<!-- Action Buttons -->
<div class="action-buttons-container">
    <div class="d-flex gap-3">
        <button type="button" class="btn-professional btn-add" disabled>
            <i class="fas fa-plus"></i>
            Enregistrer un Prix Unitaire
        </button>
        <button type="button" class="btn-professional btn-print" disabled>
            <i class="fas fa-print"></i>
            Imprimer la liste
        </button>
    </div>
    <div class="d-flex align-items-center">
        <span class="badge badge-info">
            <i class="fas fa-database mr-1"></i>
            <?= $total_items ?> prix unitaires
        </span>
    </div>
</div>

<!-- Search and Filters -->
<div class="search-container">
    <div class="search-title">
        <i class="fas fa-search"></i>
        Recherche et Filtres Avancés
    </div>
    
    <form id="filterForm" method="GET">
        <div class="row">
            <!-- Recherche par usine -->
            <div class="col-md-3 mb-3">
                <label class="form-label text-muted mb-2">
                    <i class="fas fa-industry mr-1"></i>Usine
                </label>
                <select class="form-control-modern" name="usine_id" id="usine_select">
                    <option value="">Toutes les usines</option>
                    <?php foreach($usines as $usine): ?>
                        <option value="<?= $usine['id_usine'] ?>" <?= ($usine_id == $usine['id_usine']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($usine['nom_usine']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Prix minimum -->
            <div class="col-md-3 mb-3">
                <label class="form-label text-muted mb-2">
                    <i class="fas fa-money-bill-wave mr-1"></i>Prix minimum
                </label>
                <input type="number" 
                       class="form-control-modern" 
                       name="prix_min" 
                       id="prix_min"
                       placeholder="0" 
                       value="<?= htmlspecialchars($prix_min) ?>">
            </div>

            <!-- Prix maximum -->
            <div class="col-md-3 mb-3">
                <label class="form-label text-muted mb-2">
                    <i class="fas fa-money-bill-wave mr-1"></i>Prix maximum
                </label>
                <input type="number" 
                       class="form-control-modern" 
                       name="prix_max" 
                       id="prix_max"
                       placeholder="999999" 
                       value="<?= htmlspecialchars($prix_max) ?>">
            </div>

            <!-- Date de début -->
            <div class="col-md-3 mb-3">
                <label class="form-label text-muted mb-2">
                    <i class="fas fa-calendar-alt mr-1"></i>Date de début
                </label>
                <input type="date" 
                       class="form-control-modern" 
                       name="date_debut" 
                       id="date_debut"
                       value="<?= htmlspecialchars($date_debut) ?>">
            </div>

            <!-- Date de fin -->
            <div class="col-md-3 mb-3">
                <label class="form-label text-muted mb-2">
                    <i class="fas fa-calendar-alt mr-1"></i>Date de fin
                </label>
                <input type="date" 
                       class="form-control-modern" 
                       name="date_fin" 
                       id="date_fin"
                       value="<?= htmlspecialchars($date_fin) ?>">
            </div>

            <!-- Boutons -->
            <div class="col-12 text-center mt-3">
                <button type="submit" class="btn-search mr-3">
                    <i class="fas fa-search mr-2"></i>Rechercher
                </button>
                <a href="prix_unitaires.php" class="btn-reset">
                    <i class="fas fa-times mr-2"></i>Réinitialiser
                </a>
            </div>
        </div>
    </form>
            
    
    <!-- Filtres actifs -->
    <?php if($usine_id || $date_debut || $date_fin || $prix_min || $prix_max): ?>
    <div class="active-filters mt-4">
        <div class="d-flex align-items-center flex-wrap">
            <strong class="text-muted mr-3">
                <i class="fas fa-filter mr-1"></i>Filtres actifs :
            </strong>
            <?php if($usine_id): ?>
                <?php 
                $usine_name = '';
                foreach($usines as $usine) {
                    if($usine['id_usine'] == $usine_id) {
                        $usine_name = $usine['nom_usine'];
                        break;
                    }
                }
                ?>
                <span class="badge-filter">
                    <i class="fas fa-industry"></i>
                    Usine: <?= htmlspecialchars($usine_name) ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['usine_id' => null])) ?>">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
            <?php endif; ?>
            <?php if($prix_min): ?>
                <span class="badge-filter">
                    <i class="fas fa-money-bill-wave"></i>
                    Prix min: <?= number_format($prix_min, 0, ',', ' ') ?> FCFA
                    <a href="?<?= http_build_query(array_merge($_GET, ['prix_min' => null])) ?>">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
            <?php endif; ?>
            <?php if($prix_max): ?>
                <span class="badge-filter">
                    <i class="fas fa-money-bill-wave"></i>
                    Prix max: <?= number_format($prix_max, 0, ',', ' ') ?> FCFA
                    <a href="?<?= http_build_query(array_merge($_GET, ['prix_max' => null])) ?>">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
            <?php endif; ?>
            <?php if($date_debut): ?>
                <span class="badge-filter">
                    <i class="fas fa-calendar-alt"></i>
                    Depuis: <?= date('d/m/Y', strtotime($date_debut)) ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['date_debut' => null])) ?>">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
            <?php endif; ?>
            <?php if($date_fin): ?>
                <span class="badge-filter">
                    <i class="fas fa-calendar-alt"></i>
                    Jusqu'au: <?= date('d/m/Y', strtotime($date_fin)) ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['date_fin' => null])) ?>">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Table Container -->
<div class="table-container">
    <table class="table-professional w-100">
        <thead>
            <tr>
                <th><i class="fas fa-industry mr-2"></i>Usine</th>
                <th><i class="fas fa-money-bill-wave mr-2"></i>Prix Unitaire</th>
                <th><i class="fas fa-calendar-alt mr-2"></i>Date Début</th>
                <th><i class="fas fa-calendar-check mr-2"></i>Date Fin</th>
              <!-- <th><i class="fas fa-cogs mr-2"></i>Actions</th> -->
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($prix_unitaires_list)): ?>
                <?php foreach ($prix_unitaires_list as $prix) : ?>
                    <tr>
                        <td data-label="Usine">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-industry text-primary mr-2"></i>
                                <strong><?= htmlspecialchars($prix['nom_usine']) ?></strong>
                            </div>
                        </td>
                        <td data-label="Prix Unitaire">
                            <span class="price-display">
                                <?= number_format($prix['prix'], 0, ',', ' ') ?> FCFA
                            </span>
                        </td>
                        <td data-label="Date Début">
                            <span class="text-muted">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                <?= date('d/m/Y', strtotime($prix['date_debut'])) ?>
                            </span>
                        </td>
                        <td data-label="Date Fin">
                            <?php if ($prix['date_fin']): ?>
                                <span class="status-expired">
                                    <i class="fas fa-calendar-times mr-1"></i>
                                    <?= date('d/m/Y', strtotime($prix['date_fin'])) ?>
                                </span>
                            <?php else: ?>
                                <span class="status-active">
                                    <i class="fas fa-infinity mr-1"></i>
                                    En cours
                                </span>
                            <?php endif; ?>
                        </td>
                       <!-- <td data-label="Actions">
                            <div class="d-flex justify-content-center">
                                <button class="action-btn action-btn-edit" 
                                        data-toggle="modal" 
                                        data-target="#editModal<?= $prix['id'] ?>"
                                        title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn action-btn-delete" 
                                        data-toggle="modal" 
                                        data-target="#deleteModal<?= $prix['id'] ?>"
                                        title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>-->
                    </tr>

        <!-- Modal Modification -->


                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <i class="fas fa-search text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                            <h5 class="text-muted mb-2">Aucun prix unitaire trouvé</h5>
                            <p class="text-muted mb-3">Essayez de modifier vos critères de recherche</p>
                            <a href="prix_unitaires.php" class="btn-reset">
                                <i class="fas fa-refresh mr-2"></i>Réinitialiser les filtres
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination Professional -->
<?php if ($total_pages > 1): ?>
<div class="pagination-container">
    <div class="pagination-nav">
        <?php if($page > 1): ?>
            <a href="?page=<?= $page - 1 ?><?= $usine_id ? '&usine_id='.$usine_id : '' ?><?= $date_debut ? '&date_debut='.$date_debut : '' ?><?= $date_fin ? '&date_fin='.$date_fin : '' ?><?= $prix_min ? '&prix_min='.$prix_min : '' ?><?= $prix_max ? '&prix_max='.$prix_max : '' ?><?= isset($_GET['limit']) ? '&limit='.$_GET['limit'] : '' ?>" 
               class="pagination-btn" 
               title="Page précédente">
                <i class="fas fa-chevron-left"></i>
            </a>
        <?php endif; ?>
        
        <div class="pagination-info">
            Page <?= $page ?> sur <?= $total_pages ?>
        </div>

        <?php if($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?><?= $usine_id ? '&usine_id='.$usine_id : '' ?><?= $date_debut ? '&date_debut='.$date_debut : '' ?><?= $date_fin ? '&date_fin='.$date_fin : '' ?><?= $prix_min ? '&prix_min='.$prix_min : '' ?><?= $prix_max ? '&prix_max='.$prix_max : '' ?><?= isset($_GET['limit']) ? '&limit='.$_GET['limit'] : '' ?>" 
               class="pagination-btn"
               title="Page suivante">
                <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
    
    <form action="" method="get" class="items-per-page-form">
        <?php if($usine_id): ?>
            <input type="hidden" name="usine_id" value="<?= htmlspecialchars($usine_id) ?>">
        <?php endif; ?>
        <?php if($date_debut): ?>
            <input type="hidden" name="date_debut" value="<?= htmlspecialchars($date_debut) ?>">
        <?php endif; ?>
        <?php if($date_fin): ?>
            <input type="hidden" name="date_fin" value="<?= htmlspecialchars($date_fin) ?>">
        <?php endif; ?>
        <?php if($prix_min): ?>
            <input type="hidden" name="prix_min" value="<?= htmlspecialchars($prix_min) ?>">
        <?php endif; ?>
        <?php if($prix_max): ?>
            <input type="hidden" name="prix_max" value="<?= htmlspecialchars($prix_max) ?>">
        <?php endif; ?>
        
        <label for="limit" class="text-muted mr-2">
            <i class="fas fa-list mr-1"></i>Afficher :
        </label>
        <select name="limit" id="limit" class="items-per-page-select">
            <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>5 éléments</option>
            <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10 éléments</option>
            <option value="15" <?= $limit == 15 ? 'selected' : '' ?>>15 éléments</option>
            <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25 éléments</option>
        </select>
        <button type="submit" class="submit-button ml-2">
            <i class="fas fa-check mr-1"></i>Appliquer
        </button>
    </form>
</div>
<?php endif; ?>



  <div class="modal fade" id="add-ticket" tabindex="-1" role="dialog" aria-labelledby="addTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title" id="addTicketModalLabel">Enregistrer un Prix Unitaire</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form class="forms-sample" method="post" action="traitement_prix_unitaires.php">
            <div class="card-body">
              <div class="form-group">
                  <label>Sélection Usine</label>
                  <select id="select" name="id_usine" class="form-control" required>
                      <?php
                      if (!empty($usines)) {
                          foreach ($usines as $usine) {
                              echo '<option value="' . htmlspecialchars($usine['id_usine']) . '">' . htmlspecialchars($usine['nom_usine']) . '</option>';
                          }
                      } else {
                          echo '<option value="">Aucune usine disponible</option>';
                      }
                      ?>
                  </select>
              </div>

              <div class="form-group">
                <label>Prix Unitaire</label>
                <input type="number" step="0.01" class="form-control" placeholder="Prix unitaire" name="prix" required>
              </div>

              <div class="form-group">
                <label>Date de début</label>
                <input type="date" class="form-control" name="date_debut" required>
              </div>

              <div class="form-group">
                <label>Date de fin</label>
                <input type="date" class="form-control" name="date_fin">
                <small class="form-text text-muted">Laissez vide si le prix unitaire est toujours en cours</small>
              </div>

              <button type="submit" class="btn btn-primary mr-2" name="savePrixUnitaire">Enregistrer</button>
              <button type="button" class="btn btn-light" data-dismiss="modal">Annuler</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="print-bordereau">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Impression bordereau</h4>
        </div>
        <div class="modal-body">
          <form class="forms-sample" method="post" action="print_bordereau.php" target="_blank">
            <div class="card-body">
              <div class="form-group">
                  <label>Chargé de Mission</label>
                  <select id="select" name="id_agent" class="form-control">
                      <?php
                      // Vérifier si des usines existent
                      if (!empty($agents)) {
                          foreach ($agents as $agent) {
                              echo '<option value="' . htmlspecialchars($agent['id_agent']) . '">' . htmlspecialchars($agent['nom_complet_agent']) . '</option>';
                          }
                      } else {
                          echo '<option value="">Aucune chef eéuipe disponible</option>';
                      }
                      ?>
                  </select>
              </div>
              <div class="form-group">
                <label for="exampleInputPassword1">Date de debut</label>
                <input type="date" class="form-control" id="exampleInputPassword1" placeholder="Poids" name="date_debut">
              </div>
              <div class="form-group">
                <label for="exampleInputPassword1">Date Fin</label>
                <input type="date" class="form-control" id="exampleInputPassword1" placeholder="Poids" name="date_fin">
              </div>

              <button type="submit" class="btn btn-primary mr-2" name="saveCommande">Imprimer</button>
              <button type="button" class="btn btn-light" data-dismiss="modal">Annuler</button>
            </div>
          </form>
        </div>
      </div>
      <!-- /.modal-content -->
    </div>


    <!-- /.modal-dialog -->
  </div>

<!-- Recherche par tickets-->
<div class="modal fade" id="search_ticket">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-search mr-2"></i>Rechercher un ticket
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-column">
                    <button type="button" class="btn btn-primary btn-block mb-3" data-toggle="modal" data-target="#searchByAgentModal" data-dismiss="modal">
                        <i class="fas fa-user-tie mr-2"></i>Recherche par chargé de Mission
                    </button>
                    
                    <button type="button" class="btn btn-primary btn-block mb-3" data-toggle="modal" data-target="#searchByUsineModal" data-dismiss="modal">
                        <i class="fas fa-industry mr-2"></i>Recherche par Usine
                    </button>
                    
                    <button type="button" class="btn btn-primary btn-block mb-3" data-toggle="modal" data-target="#searchByDateModal" data-dismiss="modal">
                        <i class="fas fa-calendar-alt mr-2"></i>Recherche par Date
                    </button>

                    <button type="button" class="btn btn-primary btn-block mb-3" data-toggle="modal" data-target="#searchByBetweendateModal" data-dismiss="modal">
                        <i class="fas fa-calendar-alt mr-2"></i>Recherche entre 2 dates
                    </button>
                    
                    <button type="button" class="btn btn-primary btn-block mb-3" data-toggle="modal" data-target="#searchByVehiculeModal" data-dismiss="modal">
                        <i class="fas fa-truck mr-2"></i>Recherche par Véhicule
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Recherche par Agent -->
<div class="modal fade" id="searchByAgentModal" tabindex="-1" role="dialog" aria-labelledby="searchByAgentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchByAgentModalLabel">
                    <i class="fas fa-user-tie mr-2"></i>Recherche par chargé de Mission
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="searchByAgentForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="agent_id">Sélectionner un chargé de Mission</label>
                        <select class="form-control" name="agent_id" id="agent_id" required>
                            <option value="">Choisir un chargé de Mission</option>
                            <?php foreach ($agents as $agent): ?>
                                <option value="<?= $agent['id_agent'] ?>">
                                    <?= $agent['nom_complet_agent'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Recherche par Usine -->
<div class="modal fade" id="searchByUsineModal" tabindex="-1" role="dialog" aria-labelledby="searchByUsineModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchByUsineModalLabel">
                    <i class="fas fa-industry mr-2"></i>Recherche par Usine
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="searchByUsineForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="usine">Sélectionner une Usine</label>
                        <select class="form-control" name="usine" id="usine" required>
                            <option value="">Choisir une usine</option>
                            <?php foreach ($usines as $usine): ?>
                                <option value="<?= $usine['id_usine'] ?>">
                                    <?= $usine['nom_usine'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Recherche par Date -->
<div class="modal fade" id="searchByDateModal" tabindex="-1" role="dialog" aria-labelledby="searchByDateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchByDateModalLabel">
                    <i class="fas fa-calendar-alt mr-2"></i>Recherche par Date
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="searchByDateForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="date_creation">Sélectionner une Date</label>
                        <input type="date" class="form-control" id="date_creation" name="date_creation" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="searchByBetweendateModal" tabindex="-1" role="dialog" aria-labelledby="searchByDateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchByBetweendateModalLabel">
                    <i class="fas fa-calendar-alt mr-2"></i>Recherche entre 2 dates
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="searchByBetweendateForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="date_debut">Sélectionner date Début</label>
                        <input type="date" class="form-control" id="date_debut" name="date_debut" placeholder="date debut" required>
                    </div>
                    <div class="form-group">
                        <label for="date_fin">Sélectionner date de Fin</label>
                        <input type="date" class="form-control" id="date_fin" name="date_fin" placeholder="date fin" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Recherche par Véhicule -->
<div class="modal fade" id="searchByVehiculeModal" tabindex="-1" role="dialog" aria-labelledby="searchByVehiculeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchByVehiculeModalLabel">
                    <i class="fas fa-truck mr-2"></i>Recherche par Véhicule
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="searchByVehiculeForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="chauffeur">Sélectionner un Véhicule</label>
                        <select class="form-control" name="chauffeur" id="chauffeur" required>
                            <option value="">Choisir un véhicule</option>
                            <?php foreach ($vehicules as $vehicule): ?>
                                <option value="<?= $vehicule['vehicules_id'] ?>">
                                    <?= $vehicule['matricule_vehicule'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Gestionnaire pour le formulaire de recherche par usine
document.getElementById('searchByUsineForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const usineId = document.getElementById('usine').value;
    if (usineId) {
        window.location.href = 'tickets.php?usine=' + usineId;
    }
});

// Gestionnaire pour le formulaire de recherche par date
document.getElementById('searchByDateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const date = document.getElementById('date_creation').value;
    if (date) {
        window.location.href = 'tickets.php?date_creation=' + date;
    }
});

// Gestionnaire pour le formulaire de recherche par véhicule
document.getElementById('searchByVehiculeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const vehiculeId = document.getElementById('chauffeur').value;
    if (vehiculeId) {
        window.location.href = 'tickets.php?chauffeur=' + vehiculeId;
    }
});

// Gestionnaire pour le formulaire de recherche entre deux dates
document.getElementById('searchByBetweendateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const date_debut = document.getElementById('date_debut').value;
    const date_fin = document.getElementById('date_fin').value;
    if (date_debut && date_fin) {
        window.location.href = 'tickets.php?date_debut=' + date_debut + '&date_fin=' + date_fin;
    }
});
</script>

<?php foreach ($tickets_list as $ticket) : ?>
  <div class="modal fade" id="ticketModal<?= $ticket['id_ticket'] ?>" tabindex="-1" role="dialog" aria-labelledby="ticketModalLabel<?= $ticket['id_ticket'] ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="ticketModalLabel<?= $ticket['id_ticket'] ?>">
            <i class="fas fa-ticket-alt mr-2"></i>Détails du Ticket #<?= $ticket['numero_ticket'] ?>
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row mb-4">
            <div class="col-md-6">
              <div class="info-group">
                <label class="text-muted">Date du ticket:</label>
                <p class="font-weight-bold"><?= date('d/m/Y', strtotime($ticket['date_ticket'])) ?></p>
              </div>
              <div class="info-group">
                <label class="text-muted">Usine:</label>
                <p class="font-weight-bold"><?= $ticket['nom_usine'] ?></p>
              </div>
              <div class="info-group">
                <label class="text-muted">Agent:</label>
                <p class="font-weight-bold"><?= $ticket['agent_nom_complet'] ?></p>
              </div>
              <div class="info-group">
                <label class="text-muted">Véhicule:</label>
                <p class="font-weight-bold"><?= $ticket['matricule_vehicule'] ?></p>
              </div>
              <div class="info-group">
                <label class="text-muted">Poids ticket:</label>
                <p class="font-weight-bold"><?= $ticket['poids'] ?> kg</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="info-group">
                <label class="text-muted">Prix unitaire:</label>
                <p class="font-weight-bold"><?= number_format($ticket['prix_unitaire'], 2, ',', ' ') ?> FCFA</p>
              </div>
              <div class="info-group">
                <label class="text-muted">Montant à payer:</label>
                <p class="font-weight-bold text-primary"><?= number_format($ticket['montant_paie'], 2, ',', ' ') ?> FCFA</p>
              </div>
              <div class="info-group">
                <label class="text-muted">Montant payé:</label>
                <p class="font-weight-bold text-success"><?= number_format($ticket['montant_payer'] ?? 0, 2, ',', ' ') ?> FCFA</p>
              </div>
              <div class="info-group">
                <label class="text-muted">Reste à payer:</label>
                <p class="font-weight-bold <?= ($ticket['montant_reste'] == 0) ? 'text-success' : 'text-danger' ?>">
                  <?= number_format($ticket['montant_reste'] ?? $ticket['montant_paie'], 2, ',', ' ') ?> FCFA
                </p>
              </div>
            </div>
          </div>
          <div class="border-top pt-3">
            <div class="info-group">
              <label class="text-muted">Créé par:</label>
              <p class="font-weight-bold"><?= $ticket['utilisateur_nom_complet'] ?></p>
            </div>
            <div class="info-group">
              <label class="text-muted">Date de création:</label>
              <p class="font-weight-bold"><?= date('d/m/Y', strtotime($ticket['created_at'])) ?></p>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
        </div>
      </div>
    </div>
  </div>

  <style>
  .info-group {
    margin-bottom: 15px;
  }
  .info-group label {
    display: block;
    font-size: 0.9em;
    margin-bottom: 2px;
  }
  .info-group p {
    margin-bottom: 0;
  }
  .modal-header .close {
    padding: 1rem;
    margin: -1rem -1rem -1rem auto;
  }
  </style>
<?php endforeach; ?>

</body>

</html>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation de tous les modals
    $('.modal').modal({
        keyboard: false,
        backdrop: 'static',
        show: false
    });

    // Gestionnaire spécifique pour le modal d'ajout
    $('#add-ticket').on('show.bs.modal', function (e) {
        console.log('Modal add-ticket en cours d\'ouverture');
    });
});
</script>
<script src="../../plugins/jquery/jquery.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<script src="../../plugins/jquery-ui/jquery-ui.min.js"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<!-- <script>
  $.widget.bridge('uibutton', $.ui.button)
</script>-->
<!-- Bootstrap 4 -->
<script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- ChartJS -->
<script src="../../plugins/chart.js/Chart.min.js"></script>
<!-- Sparkline -->
<script src="../../plugins/sparklines/sparkline.js"></script>
<!-- JQVMap -->
<script src="../../plugins/jqvmap/jquery.vmap.min.js"></script>
<script src="../../plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
<!-- jQuery Knob Chart -->
<script src="../../plugins/jquery-knob/jquery.knob.min.js"></script>
<!-- daterangepicker -->
<script src="../../plugins/moment/moment.min.js"></script>
<script src="../../plugins/daterangepicker/daterangepicker.js"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="../../plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<!-- Summernote -->
<script src="../../plugins/summernote/summernote-bs4.min.js"></script>
<!-- overlayScrollbars -->
<script src="../../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="../../dist/js/adminlte.js"></script>
<?php

if (isset($_SESSION['popup']) && $_SESSION['popup'] ==  true) {
  ?>
    <script>
      var audio = new Audio("../inc/sons/notification.mp3");
      audio.volume = 1.0; // Assurez-vous que le volume n'est pas à zéro
      audio.play().then(() => {
        // Lecture réussie
        var Toast = Swal.mixin({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000
        });
  
        Toast.fire({
          icon: 'success',
          title: 'Action effectuée avec succès.'
        });
      }).catch((error) => {
        console.error('Erreur de lecture audio :', error);
      });
    </script>
  <?php
    $_SESSION['popup'] = false;
  }
  ?>



<!------- Delete Pop--->
<?php

if (isset($_SESSION['delete_pop']) && $_SESSION['delete_pop'] ==  true) {
?>
  <script>
    var Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000
    });

    Toast.fire({
      icon: 'error',
      title: 'Action échouée.'
    })
  </script>

<?php
  $_SESSION['delete_pop'] = false;
}
?>
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<!--<script src="dist/js/pages/dashboard.js"></script>-->
<script>
function showSearchModal(modalId) {
  // Hide all modals
  document.querySelectorAll('.modal').forEach(modal => {
    $(modal).modal('hide');
  });

  // Show the selected modal
  $('#' + modalId).modal('show');
}
</script>
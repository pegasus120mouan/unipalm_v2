<?php
//session_start();
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_tickets.php';
require_once '../inc/functions/requete/requete_bordereaux.php';
require_once '../inc/functions/requete/requete_usines.php';
require_once '../inc/functions/requete/requete_chef_equipes.php';
require_once '../inc/functions/requete/requete_vehicules.php';
require_once '../inc/functions/requete/requete_agents.php';

if(isset($_GET['action']) && $_GET['action'] == 'delete') {
    $id_bordereau = $_GET['id'];
    $numero_bordereau = $_GET['numero_bordereau'];  
    deleteBordereau($conn, $id_bordereau, $numero_bordereau);
    header('Location: bordereaux.php');
    exit();
}

// Traitement du formulaire avant tout affichage HTML
if (isset($_POST['saveBordereau'])) {
    $id_agent = $_POST['id_agent'];
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];

   // echo $id_agent;
    //echo $date_debut;
   // echo $date_fin;

    $result = saveBordereau($conn, $id_agent, $date_debut, $date_fin);
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }
    header('Location: bordereaux.php');
    exit();
}

// Traitement de la suppression du bordereau
if (isset($_POST['delete_bordereau'])) {
    $id_bordereau = $_POST['id_bordereau'];
    
    try {
        // Vérifier si le bordereau existe
        $check_sql = "SELECT id_bordereau FROM bordereau WHERE id_bordereau = :id_bordereau";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindParam(':id_bordereau', $id_bordereau);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            // Supprimer le bordereau
            $delete_sql = "DELETE FROM bordereau WHERE id_bordereau = :id_bordereau";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bindParam(':id_bordereau', $id_bordereau);
            $delete_stmt->execute();
            
            $_SESSION['success'] = "Bordereau supprimé avec succès";
        } else {
            $_SESSION['error'] = "Bordereau introuvable";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la suppression du bordereau: " . $e->getMessage();
    }
    
    header('Location: bordereaux.php');
    exit();
}

include('header_caisse.php');

//$_SESSION['user_id'] = $user['id'];
 $id_user=$_SESSION['user_id'];
 //echo $id_user;

////$stmt = $conn->prepare("SELECT * FROM users");
//$stmt->execute();
//$users = $stmt->fetchAll();
//foreach($users as $user)

$limit = $_GET['limit'] ?? 15; // Nombre de tickets par page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Page actuelle

// Récupérer les paramètres de recherche
$search_usine = $_GET['usine'] ?? null;
$search_date = $_GET['date_creation'] ?? null;
$search_chauffeur = $_GET['chauffeur'] ?? null;
$search_agent = $_GET['agent'] ?? null;
$search_date_debut = $_GET['date_debut'] ?? null;
$search_date_fin = $_GET['date_fin'] ?? null;

// Récupérer les données (functions)
/*if ($search_usine || $search_date || $search_chauffeur || $search_agent) {
    $tickets = searchTickets($conn, $search_usine, $search_date, $search_chauffeur, $search_agent);
} else {
    $tickets = getTickets($conn);
}*/

// Vérifiez si des tickets existent avant de procéder
/*if (!empty($tickets)) {
    $total_tickets = count($tickets);
    $total_pages = ceil($total_tickets / $limit);
    $page = max(1, min($page, $total_pages));
    $offset = ($page - 1) * $limit;
    $tickets_list = array_slice($tickets, $offset, $limit);
} else {
    $tickets_list = [];
    $total_pages = 1;
}*/

$usines = getUsines($conn);
$chefs_equipes=getChefEquipes($conn);
$vehicules=getVehicules($conn);
$agents=getAgents($conn);

// Récupération des bordereaux avec pagination et filtres
$result = getBordereaux($conn, $page, $limit, [
    'usine' => $search_usine,
    'date' => $search_date,
    'chauffeur' => $search_chauffeur,
    'agent' => $search_agent,
    'date_debut' => $search_date_debut,
    'date_fin' => $search_date_fin
]);

$bordereaux = $result['data'];
$total_pages = $result['total_pages'];
$current_page = $page;



// Vérifiez si des tickets existent avant de procéder
//if (!empty($tickets)) {
//    $ticket_pages = array_chunk($tickets, $limit); // Divise les tickets en pages
//    $tickets_list = $ticket_pages[$page - 1] ?? []; // Tickets pour la page actuelle
//} else {
//    $tickets_list = []; // Aucun ticket à afficher
//}


?>


<!-- Main row -->
<style>
    /* ===== STYLES ULTRA-PROFESSIONNELS POUR BORDEREAUX ===== */
    
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
        --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --danger-gradient: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
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
        text-decoration: none;
        cursor: pointer;
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
        text-decoration: none;
        color: white;
    }

    .btn-generate {
        background: var(--danger-gradient);
        color: white;
        box-shadow: 0 4px 15px rgba(255, 65, 108, 0.3);
    }

    .btn-search {
        background: var(--success-gradient);
        color: white;
        box-shadow: 0 4px 15px rgba(86, 171, 47, 0.3);
    }

    .btn-export {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(44, 62, 80, 0.3);
    }

    .btn-add {
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
        padding: 1rem 0.75rem;
        border: none;
        font-size: 0.85rem;
        text-align: center;
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
        padding: 1rem 0.75rem;
        border-bottom: 1px solid #f8f9fa;
        font-size: 0.9rem;
        vertical-align: middle;
        text-align: center;
    }

    .table-professional tbody tr:last-child td {
        border-bottom: none;
    }

    /* Status Badges */
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-solde {
        background: var(--success-gradient);
        color: white;
    }

    .status-pending {
        background: var(--warning-gradient);
        color: white;
    }

    .status-validated {
        background: var(--success-gradient);
        color: white;
    }

    .status-not-validated {
        background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
        color: white;
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
        text-decoration: none;
    }

    .action-btn:hover {
        transform: translateY(-2px);
        text-decoration: none;
    }

    .action-btn-validate {
        background: var(--success-gradient);
        color: white;
        box-shadow: 0 2px 8px rgba(86, 171, 47, 0.3);
    }

    .action-btn-validate:hover {
        box-shadow: 0 4px 12px rgba(86, 171, 47, 0.4);
        color: white;
    }

    .action-btn-delete {
        background: var(--danger-gradient);
        color: white;
        box-shadow: 0 2px 8px rgba(255, 65, 108, 0.3);
    }

    .action-btn-delete:hover {
        box-shadow: 0 4px 12px rgba(255, 65, 108, 0.4);
        color: white;
    }

    .action-btn-print {
        background: var(--info-gradient);
        color: white;
        box-shadow: 0 2px 8px rgba(79, 172, 254, 0.3);
    }

    .action-btn-print:hover {
        box-shadow: 0 4px 12px rgba(79, 172, 254, 0.4);
        color: white;
    }

    .action-btn-associate {
        background: var(--warning-gradient);
        color: white;
        box-shadow: 0 2px 8px rgba(240, 147, 251, 0.3);
        padding: 8px 12px;
        width: auto;
        font-size: 0.8rem;
    }

    .action-btn-associate:hover {
        box-shadow: 0 4px 12px rgba(240, 147, 251, 0.4);
        color: white;
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
        justify-content: center;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 2rem;
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
        cursor: pointer;
    }

    .pagination-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        color: white;
        text-decoration: none;
    }

    .pagination-btn.active {
        background: var(--success-gradient);
        transform: scale(1.1);
    }

    .pagination-info {
        background: rgba(102, 126, 234, 0.1);
        color: #667eea;
        padding: 8px 16px;
        border-radius: 10px;
        font-weight: 600;
        margin: 0 1rem;
    }

    /* Loading Animation */
    .loading-container {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 3rem;
        background: var(--glass-bg);
        border-radius: var(--border-radius);
        margin-bottom: 2rem;
    }

    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 4px solid rgba(102, 126, 234, 0.1);
        border-left: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Price Display */
    .price-display {
        font-weight: 700;
        color: #27ae60;
        font-size: 1rem;
    }

    .price-negative {
        color: #e74c3c;
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
            margin-bottom: 0.5rem;
        }
        
        .table-container {
            padding: 1rem;
            overflow-x: auto;
        }
        
        .pagination-container {
            flex-direction: column;
            text-align: center;
            gap: 1rem;
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

    /* Alert Improvements */
    .alert-professional {
        border: none;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        margin-bottom: 1rem;
        box-shadow: var(--shadow-light);
        backdrop-filter: blur(15px);
    }

    .alert-success {
        background: rgba(86, 171, 47, 0.1);
        color: #2d5016;
        border-left: 4px solid #56ab2f;
    }

    .alert-danger {
        background: rgba(255, 65, 108, 0.1);
        color: #8b1538;
        border-left: 4px solid #ff416c;
    }

    .alert-warning {
        background: rgba(240, 147, 251, 0.1);
        color: #7d1a7d;
        border-left: 4px solid #f093fb;
    }

    /* Styles pour les filtres avancés */
    .btn-search {
        background: var(--primary-gradient);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 12px 24px;
        font-weight: 600;
        transition: var(--transition);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }

    .btn-search:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        color: white;
        text-decoration: none;
    }

    .btn-reset {
        background: transparent;
        color: #e74c3c;
        border: 2px solid #e74c3c;
        border-radius: 12px;
        padding: 10px 22px;
        font-weight: 600;
        transition: var(--transition);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }

    .btn-reset:hover {
        background: #e74c3c;
        color: white;
        transform: translateY(-2px);
        text-decoration: none;
    }

    /* Animation pour les badges de filtres */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .badge-filter {
        animation: fadeInUp 0.5s ease-out;
    }

    /* Validation des champs */
    .is-invalid {
        border-color: #e74c3c !important;
        box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1) !important;
    }

    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #e74c3c;
    }

    /* Amélioration des select */
    .form-control-modern option {
        padding: 8px 12px;
        background: white;
        color: #2c3e50;
    }

    .form-control-modern option:hover {
        background: rgba(102, 126, 234, 0.1);
    }

    /* Responsive pour les filtres */
    @media (max-width: 768px) {
        .search-container .row .col-md-3 {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 1rem;
        }
        
        .btn-search, .btn-reset {
            width: 100%;
            justify-content: center;
            margin-bottom: 0.5rem;
        }
    }
</style>

<link rel="stylesheet" href="../../plugins/jquery-ui/jquery-ui.min.css">
<style>
.ui-autocomplete {
    max-height: 200px;
    overflow-y: auto;
    overflow-x: hidden;
    z-index: 9999;
}
.ui-menu-item {
    padding: 5px 10px;
    cursor: pointer;
}
.ui-menu-item:hover {
    background-color: #f8f9fa;
}
</style>

<!-- Page Header -->
<div class="page-header">
    <h1><i class="fas fa-file-invoice mr-3"></i>Gestion des Bordereaux</h1>
    <p>Gérez, consultez et validez les bordereaux de livraison avec des outils de recherche avancés</p>
</div>

<!-- Alerts Professional -->
<?php if (isset($_SESSION['warning'])): ?>
    <div class="alert alert-warning alert-professional alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <?= $_SESSION['warning'] ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php unset($_SESSION['warning']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['popup'])): ?>
    <div class="alert alert-success alert-professional alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-2"></i>
        Ticket enregistré avec succès
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php unset($_SESSION['popup']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['delete_pop'])): ?>
    <div class="alert alert-danger alert-professional alert-dismissible fade show" role="alert">
        <i class="fas fa-times-circle mr-2"></i>
        Une erreur s'est produite
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php unset($_SESSION['delete_pop']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-professional alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-2"></i>
        <?= $_SESSION['success'] ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-professional alert-dismissible fade show" role="alert">
        <i class="fas fa-times-circle mr-2"></i>
        <?= $_SESSION['error'] ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Action Buttons -->
<div class="action-buttons-container">
    <div class="d-flex gap-3 flex-wrap">
        <button type="button" class="btn-professional btn-generate" disabled style="cursor: not-allowed; opacity: 0.6;">
            <i class="fas fa-file-pdf"></i>
            Générer un bordereau
        </button>

        <button type="button" class="btn-professional btn-search" disabled style="cursor: not-allowed; opacity: 0.6;">
            <i class="fas fa-search"></i>
            Rechercher un ticket
        </button>

        <button type="button" class="btn-professional btn-export" disabled style="cursor: not-allowed; opacity: 0.6;">
            <i class="fas fa-download"></i>
            Exporter la liste
        </button>

        <button type="button" class="btn-professional btn-add" disabled style="cursor: not-allowed; opacity: 0.6;">
            <i class="fas fa-plus"></i>
            Nouveau bordereau
        </button>
    </div>
    <div class="d-flex align-items-center">
        <span class="badge badge-info">
            <i class="fas fa-database mr-1"></i>
            <?= count($bordereaux) ?> bordereaux
        </span>
    </div>
</div>

<!-- Advanced Filters Container -->
<div class="search-container">
    <div class="search-title">
        <i class="fas fa-filter"></i>
        Filtres Avancés de Recherche
    </div>
    
    <form id="advancedFilterForm" method="GET" action="">
        <div class="row">
            <!-- Filtre par Agent -->
            <div class="col-md-3 mb-3">
                <label class="form-label text-muted mb-2">
                    <i class="fas fa-user-tie mr-1"></i>Agent / Chargé de Mission
                </label>
                <select class="form-control-modern" name="agent_id" id="agent_filter">
                    <option value="">Tous les agents</option>
                    <?php foreach($agents as $agent): ?>
                        <option value="<?= $agent['id_agent'] ?>" 
                                <?= (isset($_GET['agent_id']) && $_GET['agent_id'] == $agent['id_agent']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($agent['nom_complet_agent']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filtre par Statut -->
            <div class="col-md-3 mb-3">
                <label class="form-label text-muted mb-2">
                    <i class="fas fa-info-circle mr-1"></i>Statut du Bordereau
                </label>
                <select class="form-control-modern" name="statut" id="statut_filter">
                    <option value="">Tous les statuts</option>
                    <option value="en_attente" <?= (isset($_GET['statut']) && $_GET['statut'] == 'en_attente') ? 'selected' : '' ?>>
                        En Attente
                    </option>
                    <option value="soldé" <?= (isset($_GET['statut']) && $_GET['statut'] == 'soldé') ? 'selected' : '' ?>>
                        Soldé
                    </option>
                </select>
            </div>

            <!-- Filtre par Date de Création -->
            <div class="col-md-3 mb-3">
                <label class="form-label text-muted mb-2">
                    <i class="fas fa-calendar-alt mr-1"></i>Date de Création
                </label>
                <input type="date" 
                       class="form-control-modern" 
                       name="date_creation" 
                       id="date_creation_filter"
                       value="<?= htmlspecialchars($_GET['date_creation'] ?? '') ?>">
            </div>

            <!-- Filtre par Période -->
            <div class="col-md-3 mb-3">
                <label class="form-label text-muted mb-2">
                    <i class="fas fa-calendar-week mr-1"></i>Période Prédéfinie
                </label>
                <select class="form-control-modern" name="periode" id="periode_filter">
                    <option value="">Toutes les périodes</option>
                    <option value="aujourd_hui" <?= (isset($_GET['periode']) && $_GET['periode'] == 'aujourd_hui') ? 'selected' : '' ?>>
                        Aujourd'hui
                    </option>
                    <option value="cette_semaine" <?= (isset($_GET['periode']) && $_GET['periode'] == 'cette_semaine') ? 'selected' : '' ?>>
                        Cette semaine
                    </option>
                    <option value="ce_mois" <?= (isset($_GET['periode']) && $_GET['periode'] == 'ce_mois') ? 'selected' : '' ?>>
                        Ce mois
                    </option>
                    <option value="mois_dernier" <?= (isset($_GET['periode']) && $_GET['periode'] == 'mois_dernier') ? 'selected' : '' ?>>
                        Mois dernier
                    </option>
                </select>
            </div>

            <!-- Filtre par Montant Minimum -->
            <div class="col-md-3 mb-3">
                <label class="form-label text-muted mb-2">
                    <i class="fas fa-money-bill-wave mr-1"></i>Montant Minimum
                </label>
                <input type="number" 
                       class="form-control-modern" 
                       name="montant_min" 
                       id="montant_min_filter"
                       placeholder="0"
                       value="<?= htmlspecialchars($_GET['montant_min'] ?? '') ?>">
            </div>

            <!-- Filtre par Montant Maximum -->
            <div class="col-md-3 mb-3">
                <label class="form-label text-muted mb-2">
                    <i class="fas fa-money-bill-wave mr-1"></i>Montant Maximum
                </label>
                <input type="number" 
                       class="form-control-modern" 
                       name="montant_max" 
                       id="montant_max_filter"
                       placeholder="999999999"
                       value="<?= htmlspecialchars($_GET['montant_max'] ?? '') ?>">
            </div>

            <!-- Filtre par Nombre de Tickets -->
            <div class="col-md-3 mb-3">
                <label class="form-label text-muted mb-2">
                    <i class="fas fa-ticket-alt mr-1"></i>Nombre de Tickets Min
                </label>
                <input type="number" 
                       class="form-control-modern" 
                       name="tickets_min" 
                       id="tickets_min_filter"
                       placeholder="1"
                       min="1"
                       value="<?= htmlspecialchars($_GET['tickets_min'] ?? '') ?>">
            </div>

            <!-- Recherche par Numéro -->
            <div class="col-md-3 mb-3">
                <label class="form-label text-muted mb-2">
                    <i class="fas fa-hashtag mr-1"></i>Numéro de Bordereau
                </label>
                <input type="text" 
                       class="form-control-modern" 
                       name="numero" 
                       id="numero_filter"
                       placeholder="Rechercher par numéro..."
                       value="<?= htmlspecialchars($_GET['numero'] ?? '') ?>">
            </div>
        </div>

        <!-- Boutons d'Action -->
        <div class="row mt-3">
            <div class="col-12 text-center">
                <button type="submit" class="btn-search mr-3">
                    <i class="fas fa-search mr-2"></i>Appliquer les Filtres
                </button>
                <a href="bordereaux.php" class="btn-reset mr-3">
                    <i class="fas fa-times mr-2"></i>Réinitialiser
                </a>
                <button type="button" class="btn btn-outline-info" id="toggleAdvanced">
                    <i class="fas fa-cog mr-2"></i>Options Avancées
                </button>
            </div>
        </div>
    </form>
    
    <!-- Filtres Actifs -->
    <?php 
    $active_filters = array_filter([
        'agent_id' => $_GET['agent_id'] ?? null,
        'statut' => $_GET['statut'] ?? null,
        'date_creation' => $_GET['date_creation'] ?? null,
        'periode' => $_GET['periode'] ?? null,
        'montant_min' => $_GET['montant_min'] ?? null,
        'montant_max' => $_GET['montant_max'] ?? null,
        'tickets_min' => $_GET['tickets_min'] ?? null,
        'numero' => $_GET['numero'] ?? null
    ]);
    ?>
    
    <?php if (!empty($active_filters)): ?>
    <div class="active-filters mt-4">
        <div class="d-flex align-items-center flex-wrap">
            <strong class="text-muted mr-3">
                <i class="fas fa-filter mr-1"></i>Filtres actifs :
            </strong>
            
            <?php if(isset($_GET['agent_id']) && $_GET['agent_id']): ?>
                <?php 
                $agent_name = '';
                foreach($agents as $agent) {
                    if($agent['id_agent'] == $_GET['agent_id']) {
                        $agent_name = $agent['nom_complet_agent'];
                        break;
                    }
                }
                ?>
                <span class="badge-filter">
                    <i class="fas fa-user-tie"></i>
                    Agent: <?= htmlspecialchars($agent_name) ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['agent_id' => null])) ?>">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
            <?php endif; ?>
            
            <?php if(isset($_GET['statut']) && $_GET['statut']): ?>
                <span class="badge-filter">
                    <i class="fas fa-info-circle"></i>
                    Statut: <?= ucfirst($_GET['statut']) ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['statut' => null])) ?>">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
            <?php endif; ?>
            
            <?php if(isset($_GET['date_creation']) && $_GET['date_creation']): ?>
                <span class="badge-filter">
                    <i class="fas fa-calendar-alt"></i>
                    Date: <?= date('d/m/Y', strtotime($_GET['date_creation'])) ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['date_creation' => null])) ?>">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
            <?php endif; ?>
            
            <?php if(isset($_GET['periode']) && $_GET['periode']): ?>
                <span class="badge-filter">
                    <i class="fas fa-calendar-week"></i>
                    Période: <?= str_replace('_', ' ', ucfirst($_GET['periode'])) ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['periode' => null])) ?>">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
            <?php endif; ?>
            
            <?php if(isset($_GET['montant_min']) && $_GET['montant_min']): ?>
                <span class="badge-filter">
                    <i class="fas fa-money-bill-wave"></i>
                    Min: <?= number_format($_GET['montant_min'], 0, ',', ' ') ?> FCFA
                    <a href="?<?= http_build_query(array_merge($_GET, ['montant_min' => null])) ?>">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
            <?php endif; ?>
            
            <?php if(isset($_GET['montant_max']) && $_GET['montant_max']): ?>
                <span class="badge-filter">
                    <i class="fas fa-money-bill-wave"></i>
                    Max: <?= number_format($_GET['montant_max'], 0, ',', ' ') ?> FCFA
                    <a href="?<?= http_build_query(array_merge($_GET, ['montant_max' => null])) ?>">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
            <?php endif; ?>
            
            <?php if(isset($_GET['tickets_min']) && $_GET['tickets_min']): ?>
                <span class="badge-filter">
                    <i class="fas fa-ticket-alt"></i>
                    Tickets min: <?= $_GET['tickets_min'] ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['tickets_min' => null])) ?>">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
            <?php endif; ?>
            
            <?php if(isset($_GET['numero']) && $_GET['numero']): ?>
                <span class="badge-filter">
                    <i class="fas fa-hashtag"></i>
                    Numéro: <?= htmlspecialchars($_GET['numero']) ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['numero' => null])) ?>">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

 <!-- <button type="button" class="btn btn-primary spacing" data-toggle="modal" data-target="#add-commande">
    Enregistrer une commande
  </button>


    <button type="button" class="btn btn-outline-secondary spacing" data-toggle="modal" data-target="#recherche-commande1">
        <i class="fas fa-print custom-icon"></i>
    </button>


  <a class="btn btn-outline-secondary" href="commandes_print.php"><i class="fa fa-print" style="font-size:24px;color:green"></i></a>


     Utilisation du formulaire Bootstrap avec ms-auto pour aligner à droite
<form action="page_recherche.php" method="GET" class="d-flex ml-auto">
    <input class="form-control me-2" type="search" name="recherche" style="width: 400px;" placeholder="Recherche..." aria-label="Search">
    <button class="btn btn-outline-primary spacing" style="margin-left: 15px;" type="submit">Rechercher</button>
</form>

-->

<!-- Loading Animation -->
<div id="loader" class="loading-container">
    <div class="loading-spinner"></div>
    <div class="ml-3">
        <h5 class="text-muted mb-0">Chargement des bordereaux...</h5>
        <p class="text-muted small mb-0">Veuillez patienter</p>
    </div>
</div>

<!-- Table Container -->
<div class="table-container" id="table-container" style="display: none;">
    <table class="table-professional w-100">
        <thead>
            <tr>
                <th><i class="fas fa-calendar mr-1"></i>Date</th>
                <th><i class="fas fa-hashtag mr-1"></i>Numéro</th>
                <th><i class="fas fa-ticket-alt mr-1"></i>Tickets</th>
                <th><i class="fas fa-calendar-alt mr-1"></i>Début</th>
                <th><i class="fas fa-calendar-check mr-1"></i>Fin</th>
                <th><i class="fas fa-weight mr-1"></i>Poids</th>
                <th><i class="fas fa-money-bill mr-1"></i>Total</th>
                <th><i class="fas fa-credit-card mr-1"></i>Payé</th>
                <th><i class="fas fa-exclamation-circle mr-1"></i>Reste</th>
                <th><i class="fas fa-info-circle mr-1"></i>Statut</th>
                <th><i class="fas fa-user-tie mr-1"></i>Agent</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($bordereaux)): ?>
                <?php foreach ($bordereaux as $bordereau) : ?>
                    <tr>
                        <td data-label="Date">
                            <span class="text-muted">
                                <i class="fas fa-calendar mr-1"></i>
                                <?= date('d/m/Y', strtotime($bordereau['date_creation_bordereau'])) ?>
                            </span>
                        </td>
                        <td data-label="Numéro">
                            <a href="view_bordereau.php?numero=<?= urlencode($bordereau['numero_bordereau']) ?>" 
                               class="text-primary font-weight-bold">
                                <i class="fas fa-file-invoice mr-1"></i>
                                <?= $bordereau['numero_bordereau'] ?>
                            </a>
                        </td>
                        <td data-label="Tickets">
                            <span class="status-badge status-validated">
                                <i class="fas fa-ticket-alt mr-1"></i>
                                <?= $bordereau['nombre_tickets'] ?>
                            </span>
                        </td>
                        <td data-label="Début">
                            <span class="text-muted">
                                <?= $bordereau['date_debut'] ? date('d/m/Y', strtotime($bordereau['date_debut'])) : '-' ?>
                            </span>
                        </td>
                        <td data-label="Fin">
                            <span class="text-muted">
                                <?= $bordereau['date_fin'] ? date('d/m/Y', strtotime($bordereau['date_fin'])) : '-' ?>
                            </span>
                        </td>
                        <td data-label="Poids">
                            <span class="font-weight-bold text-info">
                                <?= number_format($bordereau['poids_total'], 2, ',', ' ') ?> kg
                            </span>
                        </td>
                        <td data-label="Total">
                            <span class="price-display">
                                <?= number_format($bordereau['montant_total'], 0, ',', ' ') ?> FCFA
                            </span>
                        </td>
                        <td data-label="Payé">
                            <span class="price-display">
                                <?= number_format($bordereau['montant_payer'] ?? 0, 0, ',', ' ') ?> FCFA
                            </span>
                        </td>
                        <td data-label="Reste">
                            <span class="price-display <?= ($bordereau['montant_reste'] ?? $bordereau['montant_total']) > 0 ? 'price-negative' : '' ?>">
                                <?= number_format($bordereau['montant_reste'] ?? $bordereau['montant_total'], 0, ',', ' ') ?> FCFA
                            </span>
                        </td>
                        <td data-label="Statut">
                            <span class="status-badge <?= $bordereau['statut_bordereau'] === 'soldé' ? 'status-solde' : 'status-pending' ?>">
                                <i class="fas fa-<?= $bordereau['statut_bordereau'] === 'soldé' ? 'check-circle' : 'clock' ?> mr-1"></i>
                                <?= ucfirst($bordereau['statut_bordereau']) ?>
                            </span>
                        </td>
                        <td data-label="Agent">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-tie text-primary mr-2"></i>
                                <strong><?= $bordereau['nom_complet_agent'] ?></strong>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="11" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <i class="fas fa-search text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                            <h5 class="text-muted mb-2">Aucun bordereau trouvé</h5>
                            <p class="text-muted mb-3">Aucun bordereau ne correspond à vos critères de recherche</p>
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
    <!-- Navigation précédente -->
    <?php if($page > 1): ?>
        <a href="?page=<?= $page - 1 ?><?= isset($_GET['usine']) ? '&usine='.$_GET['usine'] : '' ?><?= isset($_GET['date_creation']) ? '&date_creation='.$_GET['date_creation'] : '' ?><?= isset($_GET['chauffeur']) ? '&chauffeur='.$_GET['chauffeur'] : '' ?><?= isset($_GET['agent_id']) ? '&agent_id='.$_GET['agent_id'] : '' ?>" 
           class="pagination-btn" 
           title="Page précédente">
            <i class="fas fa-chevron-left"></i>
        </a>
    <?php endif; ?>
    
    <?php
    // Afficher les numéros de page
    $start = max(1, $page - 2);
    $end = min($total_pages, $page + 2);
    
    // Afficher la première page si on n'y est pas
    if ($start > 1) {
        echo '<a href="?page=1' . 
            (isset($_GET['usine']) ? '&usine='.$_GET['usine'] : '') . 
            (isset($_GET['date_creation']) ? '&date_creation='.$_GET['date_creation'] : '') . 
            (isset($_GET['chauffeur']) ? '&chauffeur='.$_GET['chauffeur'] : '') . 
            (isset($_GET['agent_id']) ? '&agent_id='.$_GET['agent_id'] : '') . 
            '" class="pagination-btn">1</a>';
        if ($start > 2) {
            echo '<span class="pagination-info">...</span>';
        }
    }
    
    // Afficher les pages autour de la page courante
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $page) {
            echo '<span class="pagination-btn active">' . $i . '</span>';
        } else {
            echo '<a href="?page=' . $i . 
                (isset($_GET['usine']) ? '&usine='.$_GET['usine'] : '') . 
                (isset($_GET['date_creation']) ? '&date_creation='.$_GET['date_creation'] : '') . 
                (isset($_GET['chauffeur']) ? '&chauffeur='.$_GET['chauffeur'] : '') . 
                (isset($_GET['agent_id']) ? '&agent_id='.$_GET['agent_id'] : '') . 
                '" class="pagination-btn">' . $i . '</a>';
        }
    }
    
    // Afficher la dernière page si on n'y est pas
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            echo '<span class="pagination-info">...</span>';
        }
        echo '<a href="?page=' . $total_pages . 
            (isset($_GET['usine']) ? '&usine='.$_GET['usine'] : '') . 
            (isset($_GET['date_creation']) ? '&date_creation='.$_GET['date_creation'] : '') . 
            (isset($_GET['chauffeur']) ? '&chauffeur='.$_GET['chauffeur'] : '') . 
            (isset($_GET['agent_id']) ? '&agent_id='.$_GET['agent_id'] : '') . 
            '" class="pagination-btn">' . $total_pages . '</a>';
    }
    ?>
    
    <!-- Navigation suivante -->
    <?php if($page < $total_pages): ?>
        <a href="?page=<?= $page + 1 ?><?= isset($_GET['usine']) ? '&usine='.$_GET['usine'] : '' ?><?= isset($_GET['date_creation']) ? '&date_creation='.$_GET['date_creation'] : '' ?><?= isset($_GET['chauffeur']) ? '&chauffeur='.$_GET['chauffeur'] : '' ?><?= isset($_GET['agent_id']) ? '&agent_id='.$_GET['agent_id'] : '' ?>" 
           class="pagination-btn"
           title="Page suivante">
            <i class="fas fa-chevron-right"></i>
        </a>
    <?php endif; ?>
</div>
<?php endif; ?>
<!-- JavaScript pour améliorer l'expérience utilisateur -->
<script>
$(document).ready(function() {
    // Animation de chargement
    setTimeout(function() {
        $('#loader').fadeOut(500, function() {
            $('#table-container').fadeIn(500);
        });
    }, 1000);
    
    // Confirmation de suppression
    $('.action-btn-delete').on('click', function(e) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce bordereau ?')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Animation des boutons au survol
    $('.btn-professional').hover(
        function() {
            $(this).addClass('shadow-lg');
        },
        function() {
            $(this).removeClass('shadow-lg');
        }
    );
    
    // Gestion des filtres avancés
    let advancedVisible = false;
    const advancedFields = ['#montant_min_filter', '#montant_max_filter', '#tickets_min_filter', '#numero_filter'];
    
    // Masquer les champs avancés au démarrage
    advancedFields.forEach(field => {
        $(field).closest('.col-md-3').hide();
    });
    
    // Toggle des options avancées
    $('#toggleAdvanced').on('click', function() {
        advancedVisible = !advancedVisible;
        const $btn = $(this);
        
        if (advancedVisible) {
            advancedFields.forEach(field => {
                $(field).closest('.col-md-3').slideDown(300);
            });
            $btn.html('<i class="fas fa-eye-slash mr-2"></i>Masquer Options Avancées');
            $btn.removeClass('btn-outline-info').addClass('btn-outline-warning');
        } else {
            advancedFields.forEach(field => {
                $(field).closest('.col-md-3').slideUp(300);
            });
            $btn.html('<i class="fas fa-cog mr-2"></i>Options Avancées');
            $btn.removeClass('btn-outline-warning').addClass('btn-outline-info');
        }
    });
    
    // Gestion intelligente des périodes prédéfinies
    $('#periode_filter').on('change', function() {
        const periode = $(this).val();
        const today = new Date();
        let startDate = '';
        
        switch(periode) {
            case 'aujourd_hui':
                startDate = today.toISOString().split('T')[0];
                $('#date_creation_filter').val(startDate);
                break;
            case 'cette_semaine':
                const startOfWeek = new Date(today.setDate(today.getDate() - today.getDay()));
                startDate = startOfWeek.toISOString().split('T')[0];
                $('#date_creation_filter').val(startDate);
                break;
            case 'ce_mois':
                startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                $('#date_creation_filter').val(startDate);
                break;
            case 'mois_dernier':
                const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                startDate = lastMonth.toISOString().split('T')[0];
                $('#date_creation_filter').val(startDate);
                break;
            default:
                $('#date_creation_filter').val('');
        }
    });
    
    // Validation des montants
    $('#montant_min_filter, #montant_max_filter').on('input', function() {
        const min = parseInt($('#montant_min_filter').val()) || 0;
        const max = parseInt($('#montant_max_filter').val()) || 999999999;
        
        if (min > max) {
            $(this).addClass('is-invalid');
            $(this).after('<div class="invalid-feedback">Le montant minimum ne peut pas être supérieur au maximum</div>');
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });
    
    // Sauvegarde des filtres dans localStorage
    $('#advancedFilterForm').on('submit', function() {
        const formData = $(this).serialize();
        localStorage.setItem('bordereaux_filters', formData);
    });
    
    // Restauration des filtres depuis localStorage
    const savedFilters = localStorage.getItem('bordereaux_filters');
    if (savedFilters && window.location.search === '') {
        // Seulement si aucun paramètre GET n'est présent
        console.log('🔄 Filtres sauvegardés détectés');
    }
    
    // Animation des badges de filtres actifs
    $('.badge-filter').each(function(index) {
        $(this).css('animation-delay', (index * 0.1) + 's');
        $(this).addClass('animate__animated animate__fadeInUp');
    });
    
    // Compteur de résultats en temps réel
    function updateResultsCounter() {
        const totalResults = $('.table-professional tbody tr').length - 1; // -1 pour exclure la ligne "aucun résultat"
        $('.badge.badge-info').html('<i class="fas fa-database mr-1"></i>' + totalResults + ' bordereaux');
    }
    
    // Auto-submit sur changement de filtre (optionnel)
    $('.form-control-modern').on('change', function() {
        // Décommenter pour auto-submit
        // $('#advancedFilterForm').submit();
    });
    
    console.log('✅ Système de filtres avancés initialisé');
    console.log('🔍 ' + Object.keys(<?= json_encode($active_filters) ?>).length + ' filtres actifs détectés');
});
</script>

  <div class="modal fade" id="add-ticket" tabindex="-1" role="dialog" aria-labelledby="addTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title" id="addTicketModalLabel">Enregistrer un ticket</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form class="forms-sample" method="post" action="traitement_tickets.php">
            <div class="card-body">
            <div class="form-group">
                <label for="exampleInputEmail1">Date ticket</label>
                <input type="date" class="form-control" id="exampleInputEmail1" placeholder="date ticket" name="date_ticket">
              </div> 
              <div class="form-group">
                <label for="exampleInputEmail1">Numéro du Ticket</label>
                <input type="text" class="form-control" id="exampleInputEmail1" placeholder="Numero du ticket" name="numero_ticket">
              </div>
               <div class="form-group">
                  <label>Selection Usine</label>
                  <select id="select" name="usine" class="form-control">
                      <?php
                      // Vérifier si des usines existent
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
                  <label>Selection véhicules</label>
                  <select id="select" name="vehicule" class="form-control">
                      <?php
                      // Vérifier si des usines existent
                      if (!empty($vehicules)) {
                          foreach ($vehicules as $vehicule) {
                              echo '<option value="' . htmlspecialchars($vehicule['vehicules_id']) . '">' . htmlspecialchars($vehicule['matricule_vehicule']) . '</option>';
                          }
                      } else {
                          echo '<option value="">Aucun vehicule disponible</option>';
                      }
                      ?>
                  </select>
              </div>

              <div class="form-group">
                <label for="exampleInputPassword1">Poids</label>
                <input type="text" class="form-control" id="exampleInputPassword1" placeholder="Poids" name="poids">
              </div>

              <button type="submit" class="btn btn-primary mr-2" name="saveCommande">Enregister</button>
              <button class="btn btn-light">Annuler</button>
            </div>
          </form>
        </div>
      </div>
      <!-- /.modal-content -->
    </div>


    <!-- /.modal-dialog -->
  </div>

  <div class="modal fade" id="print-bordereau">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Impression bordereau</h4>
        </div>
        <div class="modal-body">
          <form class="forms-sample" method="post" action="print_visualisation_bordereau.php" target="_blank">
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
                          echo '<option value="">Aucune chef equipe disponible</option>';
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

  <!-- Modal d'ajout de bordereau -->
<div class="modal fade" id="add-bordereau" tabindex="-1" role="dialog" aria-labelledby="addBordereauLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="addBordereauLabel">Nouveau bordereau</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" action="save_bordereau.php">
                    <div class="form-group">
                        <label for="agent_search">Chargé de Mission</label>
                        <div class="autocomplete-container">
                            <input type="text" 
                                   class="form-control" 
                                   id="agent_search" 
                                   placeholder="Tapez le nom du chargé de mission..."
                                   autocomplete="off"
                                   required>
                            <input type="hidden" name="id_agent" id="id_agent" required>
                            <div id="agent_suggestions" class="autocomplete-suggestions"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="date_debut">Date de début</label>
                        <input type="date" class="form-control" id="date_debut" name="date_debut" required>
                    </div>
                    <div class="form-group">
                        <label for="date_fin">Date de fin</label>
                        <input type="date" class="form-control" id="date_fin" name="date_fin" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" name="saveBordereau">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
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

<?php foreach ($bordereaux as $bordereau) : ?>
    <!-- Modal pour l'association des tickets -->
    <div class="modal fade" id="ticketsAssociationBordereau<?= $bordereau['id_bordereau'] ?>" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Association des tickets au bordereau <?= $bordereau['numero_bordereau'] ?></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Informations du bordereau</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <th>ID Agent :</th>
                                            <td><?= $bordereau['id_agent'] ?></td>
                                            <th>N° Bordereau :</th>
                                            <td><?= $bordereau['numero_bordereau'] ?></td>
                                            <th>Date début :</th>
                                            <td><?= date('d/m/Y', strtotime($bordereau['date_debut'])) ?></td>
                                            <th>Date fin :</th>
                                            <td><?= date('d/m/Y', strtotime($bordereau['date_fin'])) ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <?php 
                            $tickets = getTicketsAssociation(
                                $conn, 
                                $bordereau['id_agent'],
                                $bordereau['date_debut'],
                                $bordereau['date_fin']
                            );
                            if (!empty($tickets)) : 
                                $has_available_tickets = false;
                                foreach ($tickets as $ticket) {
                                    if (empty($ticket['numero_bordereau'])) {
                                        $has_available_tickets = true;
                                        break;
                                    }
                                }
                            ?>
                            <form id="associationForm<?= $bordereau['id_bordereau'] ?>" action="associer_tickets.php" method="post">
                                <input type="hidden" name="id_agent" value="<?= $bordereau['id_agent'] ?>">
                                <input type="hidden" name="numero_bordereau" value="<?= $bordereau['numero_bordereau'] ?>">
                                <input type="hidden" name="date_debut" value="<?= $bordereau['date_debut'] ?>">
                                <input type="hidden" name="date_fin" value="<?= $bordereau['date_fin'] ?>">
                                
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th style="width: 40px">
                                                    <?php if ($has_available_tickets): ?>
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input select-all" id="selectAll<?= $bordereau['id_bordereau'] ?>">
                                                        <label class="custom-control-label" for="selectAll<?= $bordereau['id_bordereau'] ?>"></label>
                                                    </div>
                                                    <?php endif; ?>
                                                </th>
                                                <th>Date Réception</th>
                                                <th>Date Ticket</th>
                                                <th>Véhicule</th>
                                                <th>N° Ticket</th>
                                                <th>Poids (kg)</th>
                                                <th>Prix unitaire</th>
                                                <th>Montant total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_poids = 0;
                                            $total_montant_total = 0;
                                            foreach ($tickets as $ticket) : 
                                                $total_poids += $ticket['poids'];
                                                $total_montant_total += $ticket['montant_total'];
                                                $is_associated = !empty($ticket['numero_bordereau']);
                                            ?>
                                            <tr <?= $is_associated ? 'class="text-muted bg-light"' : '' ?>>
                                                <td>
                                                    <?php if (!$is_associated): ?>
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input ticket-checkbox" 
                                                               id="ticket<?= $ticket['id_ticket'] ?>" 
                                                               name="tickets[]" 
                                                               value="<?= $ticket['id_ticket'] ?>">
                                                        <label class="custom-control-label" for="ticket<?= $ticket['id_ticket'] ?>"></label>
                                                    </div>
                                                    <?php else: ?>
                                                    <i class="fas fa-link text-muted" title="Déjà associé au bordereau <?= htmlspecialchars($ticket['numero_bordereau']) ?>"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('d/m/y', strtotime($ticket['date_reception'])) ?></td>
                                                <td><?= date('d/m/y', strtotime($ticket['date_ticket'])) ?></td>
                                                <td><?= $ticket['vehicule'] ?></td>
                                                <td><?= $ticket['numero_ticket'] ?></td>
                                                <td class="text-right"><?= number_format($ticket['poids'], 0, ',', ' ') ?></td>
                                                <td class="text-right"><?= number_format($ticket['prix_unitaire'], 2, ',', ' ') ?></td>
                                                <td class="text-right"><?= number_format($ticket['montant_total'], 2, ',', ' ') ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <tr class="font-weight-bold">
                                                <td colspan="5" class="text-right">TOTAL GÉNÉRAL (<?= count($tickets) ?> tickets)</td>
                                                <td class="text-right"><?= number_format($total_poids, 0, ',', ' ') ?></td>
                                                <td colspan="2" class="text-right"><?= number_format($total_montant_total, 2, ',', ' ') ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                                    <?php if ($has_available_tickets): ?>
                                    <button type="submit" class="btn btn-primary" id="submitAssociation<?= $bordereau['id_bordereau'] ?>">
                                        <i class="fas fa-link"></i> Associer les tickets sélectionnés
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                            <?php else : ?>
                                <div class="alert alert-info">
                                    Aucun ticket disponible pour cette période et cet agent.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>


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

<!-- Script pour la gestion des tickets -->
<script>
function validateBordereau(bordereauId) {
    console.log('Validation du bordereau:', bordereauId);
    
    $.ajax({
        url: 'validate_bordereau.php',
        method: 'POST',
        data: { 
            id_bordereau: bordereauId,
            action: 'validate'
        },
        dataType: 'json',
        success: function(response) {
            console.log('Réponse reçue:', response);
            if (response.success) {
                location.reload();
            } else {
                alert('Erreur lors de la validation du bordereau');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erreur AJAX:', error);
            console.error('Status:', status);
            console.error('Réponse:', xhr.responseText);
            alert('Erreur lors de la validation du bordereau');
        }
    });
}

$(document).ready(function() {
    // Le reste de votre code JavaScript existant...
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Afficher le loader au démarrage
    document.getElementById('loader').style.display = 'block';
    document.getElementById('example1').style.display = 'none';
    
    // Cacher le loader et afficher la table après un court délai
    setTimeout(function() {
        document.getElementById('loader').style.display = 'none';
        document.getElementById('example1').style.display = 'table';
        
        // Initialiser DataTables après avoir affiché la table
        if($.fn.DataTable.isDataTable('#example1')) {
            $('#example1').DataTable().destroy();
        }
        $('#example1').DataTable({
            "responsive": true,
            "lengthChange": false,
            "autoWidth": false,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/French.json"
            }
        });
    }, 1000);
    
    // Gestion des soumissions de formulaire
    $('form').on('submit', function() {
        document.getElementById('loader').style.display = 'block';
    });

    // Gestion des requêtes AJAX
    $(document).ajaxStart(function() {
        document.getElementById('loader').style.display = 'block';
    }).ajaxStop(function() {
        document.getElementById('loader').style.display = 'none';
    });
    
    // Gestion des modals
    $('.modal').on('show.bs.modal', function() {
        document.getElementById('loader').style.display = 'none';
    });
});
</script>

<!-- CSS pour l'autocomplétion -->
<style>
.autocomplete-container {
    position: relative;
}

.autocomplete-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 4px 4px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1050;
    display: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.autocomplete-suggestion {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
}

.autocomplete-suggestion:hover,
.autocomplete-suggestion.selected {
    background-color: #f8f9fa;
}

.autocomplete-suggestion:last-child {
    border-bottom: none;
}

.autocomplete-suggestion .agent-name {
    font-weight: 500;
    color: #333;
}

.autocomplete-loading {
    padding: 10px 15px;
    text-align: center;
    color: #666;
    font-style: italic;
}

.autocomplete-no-results {
    padding: 10px 15px;
    text-align: center;
    color: #999;
    font-style: italic;
}
</style>

<!-- JavaScript pour l'autocomplétion -->
<script>
$(document).ready(function() {
    let searchTimeout;
    let selectedIndex = -1;
    
    // Fonction pour effectuer la recherche
    function searchAgents(query) {
        if (query.length < 2) {
            $('#agent_suggestions').hide().empty();
            return;
        }
        
        // Afficher le loader
        $('#agent_suggestions').show().html('<div class="autocomplete-loading">Recherche en cours...</div>');
        
        $.ajax({
            url: '../api/search_agents.php',
            method: 'GET',
            data: { q: query },
            dataType: 'json',
            success: function(data) {
                displaySuggestions(data);
            },
            error: function() {
                $('#agent_suggestions').html('<div class="autocomplete-no-results">Erreur lors de la recherche</div>');
            }
        });
    }
    
    // Fonction pour afficher les suggestions
    function displaySuggestions(agents) {
        const suggestionsDiv = $('#agent_suggestions');
        
        if (agents.length === 0) {
            suggestionsDiv.html('<div class="autocomplete-no-results">Aucun résultat trouvé</div>');
            return;
        }
        
        let html = '';
        agents.forEach(function(agent, index) {
            html += `<div class="autocomplete-suggestion" data-id="${agent.id}" data-index="${index}">
                        <div class="agent-name">${agent.text}</div>
                     </div>`;
        });
        
        suggestionsDiv.html(html);
        selectedIndex = -1;
    }
    
    // Événement de saisie dans le champ de recherche
    $('#agent_search').on('input', function() {
        const query = $(this).val().trim();
        
        // Réinitialiser la sélection
        $('#id_agent').val('');
        selectedIndex = -1;
        
        // Annuler la recherche précédente
        clearTimeout(searchTimeout);
        
        // Lancer une nouvelle recherche après un délai
        searchTimeout = setTimeout(function() {
            searchAgents(query);
        }, 300);
    });
    
    // Gestion des touches du clavier
    $('#agent_search').on('keydown', function(e) {
        const suggestions = $('.autocomplete-suggestion');
        
        if (suggestions.length === 0) return;
        
        switch(e.keyCode) {
            case 38: // Flèche haut
                e.preventDefault();
                selectedIndex = selectedIndex > 0 ? selectedIndex - 1 : suggestions.length - 1;
                updateSelection();
                break;
                
            case 40: // Flèche bas
                e.preventDefault();
                selectedIndex = selectedIndex < suggestions.length - 1 ? selectedIndex + 1 : 0;
                updateSelection();
                break;
                
            case 13: // Entrée
                e.preventDefault();
                if (selectedIndex >= 0) {
                    selectSuggestion(suggestions.eq(selectedIndex));
                }
                break;
                
            case 27: // Échap
                $('#agent_suggestions').hide();
                selectedIndex = -1;
                break;
        }
    });
    
    // Fonction pour mettre à jour la sélection visuelle
    function updateSelection() {
        $('.autocomplete-suggestion').removeClass('selected');
        if (selectedIndex >= 0) {
            $('.autocomplete-suggestion').eq(selectedIndex).addClass('selected');
        }
    }
    
    // Clic sur une suggestion
    $(document).on('click', '.autocomplete-suggestion', function() {
        selectSuggestion($(this));
    });
    
    // Fonction pour sélectionner une suggestion
    function selectSuggestion($suggestion) {
        const agentId = $suggestion.data('id');
        const agentName = $suggestion.find('.agent-name').text();
        
        $('#agent_search').val(agentName);
        $('#id_agent').val(agentId);
        $('#agent_suggestions').hide();
        selectedIndex = -1;
    }
    
    // Cacher les suggestions quand on clique ailleurs
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.autocomplete-container').length) {
            $('#agent_suggestions').hide();
            selectedIndex = -1;
        }
    });
    
    // Réinitialiser le formulaire quand le modal se ferme
    $('#add-bordereau').on('hidden.bs.modal', function() {
        $('#agent_search').val('');
        $('#id_agent').val('');
        $('#agent_suggestions').hide().empty();
        selectedIndex = -1;
    });
    
    // Focus sur le champ quand le modal s'ouvre
    $('#add-bordereau').on('shown.bs.modal', function() {
        $('#agent_search').focus();
    });
});
</script>
</body>
</html>
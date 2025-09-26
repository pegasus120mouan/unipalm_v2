<?php
include('header.php');
require_once('../inc/functions/connexion.php');
require_once('../inc/functions/requete/requete_tickets.php');
require_once('../inc/functions/requete/requete_usines.php');
require_once('../inc/functions/requete/requete_chef_equipes.php');
require_once('../inc/functions/requete/requete_vehicules.php');
require_once('../inc/functions/requete/requete_agents.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$id_user = $_SESSION['user_id'];

// Récupération des données pour les listes déroulantes
$usines = getUsines($conn);
$chefs_equipes = getChefEquipes($conn);
$vehicules = getVehicules($conn);
$agents = getAgents($conn);

// Paramètres de filtrage avec vérification
$agent_id = isset($_GET['agent_id']) && !empty($_GET['agent_id']) ? (int)$_GET['agent_id'] : null;
$usine_id = isset($_GET['usine_id']) && !empty($_GET['usine_id']) ? (int)$_GET['usine_id'] : null;
$date_debut = isset($_GET['date_debut']) ? trim($_GET['date_debut']) : '';
$date_fin = isset($_GET['date_fin']) ? trim($_GET['date_fin']) : '';
$numero_ticket = isset($_GET['numero_ticket']) ? trim($_GET['numero_ticket']) : '';

// Validation des dates
if (!empty($date_debut) && !strtotime($date_debut)) {
    $date_debut = '';
}
if (!empty($date_fin) && !strtotime($date_fin)) {
    $date_fin = '';
}

$limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 15;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Récupérer les données avec vérification d'erreurs
try {
    // Préparation des filtres
    $filters = [];
    if (!empty($agent_id)) {
        $filters['agent'] = $agent_id;
    }
    if (!empty($usine_id)) {
        $filters['usine'] = $usine_id;
    }
    if (!empty($date_debut)) {
        $filters['date_debut'] = $date_debut;
    }
    if (!empty($date_fin)) {
        $filters['date_fin'] = $date_fin;
    }

    if (!empty($numero_ticket)) {
        // Si un numéro de ticket est spécifié, on ne récupère que ce ticket
        $tickets = searchTickets($conn, null, null, null, null, $numero_ticket);
        $tickets_list = $tickets; // Pas besoin de pagination pour une recherche spécifique
        $total_pages = 1;
    } else {
        // Sinon on récupère tous les tickets selon les filtres
        $tickets = getTickets($conn, $filters);
        
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
    }

    // Vérification et initialisation des tableaux
    $tickets = is_array($tickets) ? $tickets : [];
    $usines = is_array($usines) ? $usines : [];
    $chefs_equipes = is_array($chefs_equipes) ? $chefs_equipes : [];
    $vehicules = is_array($vehicules) ? $vehicules : [];
    $agents = is_array($agents) ? $agents : [];

} catch (Exception $e) {
    error_log("Erreur lors de la récupération des données: " . $e->getMessage());
    $error_message = "Une erreur est survenue lors de la récupération des données.";
    $tickets = [];
    $usines = [];
    $chefs_equipes = [];
    $vehicules = [];
    $agents = [];
}

// Pagination sécurisée
$total_tickets = count($tickets);
$total_pages = max(1, ceil($total_tickets / $limit));
$page = min($page, $total_pages);
$offset = ($page - 1) * $limit;

$tickets_list = !empty($tickets) ? array_slice($tickets, $offset, $limit) : [];

// Préserver les paramètres de filtrage pour la pagination
$filter_params = [];
if (!empty($agent_id)) $filter_params['agent_id'] = $agent_id;
if (!empty($usine_id)) $filter_params['usine_id'] = $usine_id;
if (!empty($date_debut)) $filter_params['date_debut'] = $date_debut;
if (!empty($date_fin)) $filter_params['date_fin'] = $date_fin;
if (!empty($numero_ticket)) $filter_params['numero_ticket'] = $numero_ticket;
if ($limit !== 15) $filter_params['limit'] = $limit;

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifications Tickets - UniPalm</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body>

<style>
:root {
    --primary-color: #667eea;
    --secondary-color: #764ba2;
    --accent-color: #f093fb;
    --success-color: #4ade80;
    --warning-color: #fbbf24;
    --danger-color: #f87171;
    --info-color: #60a5fa;
    --dark-color: #1f2937;
    --light-color: #f8fafc;
    --glass-bg: rgba(255, 255, 255, 0.1);
    --glass-border: rgba(255, 255, 255, 0.2);
    --shadow-light: 0 8px 32px rgba(31, 38, 135, 0.37);
    --shadow-medium: 0 15px 35px rgba(31, 38, 135, 0.2);
    --shadow-heavy: 0 25px 50px rgba(31, 38, 135, 0.3);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #f5576c);
    background-size: 400% 400%;
    animation: gradientShift 15s ease infinite;
    min-height: 100vh;
    position: relative;
    overflow-x: hidden;
}

@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Floating particles animation */
body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.3) 0%, transparent 50%);
    animation: float 20s ease-in-out infinite;
    pointer-events: none;
    z-index: -1;
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    33% { transform: translateY(-30px) rotate(120deg); }
    66% { transform: translateY(30px) rotate(240deg); }
}

/* Glass morphism container */
.glass-container {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    box-shadow: var(--shadow-medium);
    padding: 2rem;
    margin: 1rem 0;
    transition: all 0.3s ease;
    animation: slideInUp 0.8s ease-out;
}

.glass-container:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-heavy);
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

/* Header styling */
.page-header {
    background: linear-gradient(135deg, var(--glass-bg), rgba(255, 255, 255, 0.05));
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    text-align: center;
    animation: fadeInDown 1s ease-out;
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.page-header h1 {
    font-family: 'Poppins', sans-serif;
    font-weight: 700;
    font-size: 2.5rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
    text-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.page-header .subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.1rem;
    font-weight: 400;
}

/* Loader styling */
#loader {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 3rem;
    text-align: center;
    color: white;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top: 4px solid var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
/* Form controls */
.form-label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.form-control, .form-select {
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    color: #2c3e50;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.form-control:focus, .form-select:focus {
    background: rgba(255, 255, 255, 0.95);
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    color: #2c3e50;
}

.form-control::placeholder {
    color: rgba(44, 62, 80, 0.6);
}

/* Button styling */
.btn {
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    border: none;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.btn:hover::before {
    left: 100%;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    box-shadow: var(--shadow-light);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
}

.btn-secondary {
    background: linear-gradient(135deg, #6b7280, #4b5563);
    color: white;
}

.btn-success {
    background: linear-gradient(135deg, var(--success-color), #22c55e);
}

.btn-warning {
    background: linear-gradient(135deg, var(--warning-color), #f59e0b);
}

.btn-danger {
    background: linear-gradient(135deg, var(--danger-color), #ef4444);
}

.btn-info {
    background: linear-gradient(135deg, var(--info-color), #3b82f6);
}

/* Table styling */
.table-container {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 1.5rem;
    margin: 2rem 0;
    animation: slideInRight 0.8s ease-out 0.4s both;
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.table {
    background: transparent;
    color: #2c3e50;
    border-radius: 15px;
    overflow: hidden;
}

.table thead th {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border: none;
    padding: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.9rem;
}

.table tbody tr {
    background: rgba(255, 255, 255, 0.05);
    border: none;
    transition: all 0.3s ease;
}

.table tbody tr:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.table tbody td {
    border: none;
    padding: 1rem;
    vertical-align: middle;
    color: #2c3e50;
}

/* Modern dropdown styling */
.dropdown-menu {
    border: none !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
    border-radius: 12px !important;
    padding: 8px 0 !important;
    min-width: 220px !important;
    backdrop-filter: blur(10px) !important;
    background: rgba(255, 255, 255, 0.95) !important;
    z-index: 1050 !important;
    position: absolute !important;
    display: none;
}

.dropdown-menu.show {
    display: block !important;
    animation: dropdownFadeIn 0.3s ease-out;
}

@keyframes dropdownFadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dropdown-item {
    padding: 12px 20px !important;
    transition: all 0.3s ease !important;
    border: none !important;
    background: none !important;
    color: #495057 !important;
    text-decoration: none !important;
    display: block !important;
    width: 100% !important;
    clear: both !important;
    font-weight: 400 !important;
    text-align: inherit !important;
    white-space: nowrap !important;
}

.dropdown-item:hover:not(.disabled) {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    transform: translateX(5px);
    text-decoration: none !important;
}

.dropdown-item:focus:not(.disabled) {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    text-decoration: none !important;
    outline: none !important;
}

.dropdown-item.disabled {
    opacity: 0.5 !important;
    cursor: not-allowed !important;
    background: transparent !important;
    color: #6c757d !important;
}

.dropdown-item i {
    width: 20px !important;
    text-align: center !important;
    margin-right: 8px !important;
}

.dropdown-divider {
    height: 0 !important;
    margin: 4px 0 !important;
    overflow: hidden !important;
    border-top: 1px solid #e9ecef !important;
}

/* Ensure btn-group positioning */
.btn-group {
    position: relative !important;
    z-index: 1 !important;
}

.btn-group .dropdown-menu {
    right: 0 !important;
    left: auto !important;
}

/* Table container to prevent overflow issues */
.table-responsive {
    overflow: visible !important;
}

tbody tr {
    position: relative;
    z-index: 1;
}

tbody tr:hover {
    z-index: 2;
}

/* Modal styling */
.modal-content {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    color: white;
}

.modal-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px 20px 0 0;
}

.modal-title {
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
}

/* Responsive design */
@media (max-width: 768px) {
    .page-header h1 {
        font-size: 2rem;
    }
    
    .glass-container {
        padding: 1rem;
        margin: 0.5rem 0;
    }
    
    .table-responsive {
        border-radius: 15px;
        overflow: hidden;
    }
    
    .table thead {
        display: none;
    }
    
    .table tbody tr {
        display: block;
        margin-bottom: 1rem;
        border-radius: 15px;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.1);
    }
    
    .table tbody td {
        display: block;
        text-align: right;
        padding: 0.5rem 0;
        border: none;
    }
    
    .table tbody td::before {
        content: attr(data-label) ": ";
        float: left;
        font-weight: 600;
        color: var(--primary-color);
    }
    
    .pagination-nav {
        flex-direction: column;
        gap: 10px;
    }
}
</style>

<!-- Loader -->
<div id="loader" class="text-center p-3">
    <div class="spinner"></div>
    <p class="mt-3"><i class="fas fa-clock me-2"></i>Chargement des données...</p>
</div>

<!-- Contenu principal initialement caché -->
<div id="mainContent" style="display: none;">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="animate__animated animate__fadeInDown">
                <i class="fas fa-edit me-3" style="color: var(--warning-color);"></i>
                Modifications Tickets
            </h1>
            <p class="subtitle animate__animated animate__fadeInUp animate__delay-1s">
                Gestion et modification des tickets - Interface professionnelle
            </p>
        </div>



        <!-- Search Container -->
        <div class="glass-container">
            <form method="GET" class="p-4">
                <div class="row">
                    <div class="col-lg-2 col-md-4 mb-3">
                        <label for="numero_ticket" class="form-label">
                            <i class="fas fa-ticket-alt me-2"></i>Numéro ticket
                        </label>
                        <input type="text" class="form-control modern-input" id="numero_ticket" name="numero_ticket" placeholder="Numéro ticket" value="<?= htmlspecialchars($numero_ticket) ?>">
                    </div>
                    <div class="col-lg-2 col-md-4 mb-3">
                        <label for="agent_select" class="form-label">
                            <i class="fas fa-user me-2"></i>Agent
                        </label>
                        <select class="form-select modern-select" id="agent_select" name="agent_id">
                            <option value="">Tous les agents</option>
                            <?php foreach($agents as $agent): ?>
                                <?php if(isset($agent['id_agent'], $agent['nom_complet_agent'])): ?>
                                    <option value="<?= htmlspecialchars($agent['id_agent']) ?>" <?= ($agent_id == $agent['id_agent']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($agent['nom_complet_agent']) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 mb-3">
                        <label for="usine_select" class="form-label">
                            <i class="fas fa-industry me-2"></i>Usine
                        </label>
                        <select class="form-select modern-select" id="usine_select" name="usine_id">
                            <option value="">Toutes les usines</option>
                            <?php foreach($usines as $usine): ?>
                                <?php if(isset($usine['id_usine'], $usine['nom_usine'])): ?>
                                    <option value="<?= htmlspecialchars($usine['id_usine']) ?>" <?= ($usine_id == $usine['id_usine']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($usine['nom_usine']) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 mb-3">
                        <label for="date_debut" class="form-label">
                            <i class="fas fa-calendar-alt me-2"></i>Date début
                        </label>
                        <input type="date" class="form-control modern-input" id="date_debut" name="date_debut" value="<?= htmlspecialchars($date_debut) ?>">
                    </div>
                    <div class="col-lg-2 col-md-4 mb-3">
                        <label for="date_fin" class="form-label">
                            <i class="fas fa-calendar-check me-2"></i>Date fin
                        </label>
                        <input type="date" class="form-control modern-input" id="date_fin" name="date_fin" value="<?= htmlspecialchars($date_fin) ?>">
                    </div>
                    <div class="col-lg-2 col-md-4 mb-3 d-flex flex-column">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary modern-btn">
                                <i class="fas fa-search me-1"></i> Filtrer
                            </button>
                            <a href="tickets_modifications.php" class="btn btn-secondary modern-btn">
                                <i class="fas fa-sync-alt me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
                <?php if (isset($_GET['limit'])): ?>
                    <input type="hidden" name="limit" value="<?= (int)$_GET['limit'] ?>">
                <?php endif; ?>
            </form>
        </div>

    <div class="table-container glass-container">
        <div class="table-responsive">
            <table id="example1" class="table modern-table" style="min-height: 30vh;">
                <thead>
                  <tr>
                    
                    <th><i class="fas fa-calendar me-2"></i>Date Ticket</th>
                    <th><i class="fas fa-clock me-2"></i>Date Création</th>
                    <th><i class="fas fa-ticket-alt me-2"></i>N° Ticket</th>
                    <th><i class="fas fa-industry me-2"></i>Usine</th>
                    <th><i class="fas fa-user me-2"></i>Agent</th>
                    <th><i class="fas fa-truck me-2"></i>Véhicule</th>
                    <th><i class="fas fa-weight me-2"></i>Poids</th>
                    <th><i class="fas fa-coins me-2"></i>Prix Unitaire</th>
                    <th><i class="fas fa-money-bill me-2"></i>Montant à Payer</th>
                    <th><i class="fas fa-cogs me-2"></i>Actions</th>
                  </tr>
                </thead>
                        <tbody>
                          <?php foreach ($tickets_list as $ticket) : ?>
                            <tr class="table-row animate__animated animate__fadeInUp" style="animation-delay: <?= array_search($ticket, $tickets_list) * 0.1 ?>s;">
                              <td class="fw-medium"><?= date('d/m/Y', strtotime($ticket['date_ticket'])) ?></td>
                              <td class="text-muted"><?= date('d/m/Y', strtotime($ticket['created_at'])) ?></td>
                              <td>
                                <span class="badge bg-primary bg-gradient px-3 py-2 rounded-pill">
                                  <i class="fas fa-ticket-alt me-1"></i>
                                  <?= htmlspecialchars($ticket['numero_ticket']) ?>
                                </span>
                              </td>
                              <td>
                                <div class="d-flex align-items-center">
                                  <i class="fas fa-industry text-primary me-2"></i>
                                  <span class="fw-medium"><?= htmlspecialchars($ticket['nom_usine']) ?></span>
                                </div>
                              </td>
                              <td>
                                <div class="d-flex align-items-center">
                                  <i class="fas fa-user text-success me-2"></i>
                                  <span><?= htmlspecialchars($ticket['nom_complet_agent']) ?></span>
                                </div>
                              </td>
                              <td>
                                <div class="d-flex align-items-center">
                                  <i class="fas fa-truck text-warning me-2"></i>
                                  <span class="font-monospace"><?= htmlspecialchars($ticket['matricule_vehicule']) ?></span>
                                </div>
                              </td>
                              <td>
                                <span class="badge bg-info bg-gradient px-3 py-2">
                                  <i class="fas fa-weight me-1"></i>
                                  <?= number_format($ticket['poids'], 0, ',', ' ') ?> kg
                                </span>
                              </td>
                              <td class="fw-bold text-success"><?= number_format($ticket['prix_unitaire'], 0, ',', ' ') ?></td>
                              <td class="fw-bold text-primary"><?= number_format($ticket['montant_paie'], 0, ',', ' ') ?></td>
                              <td>
                                <div class="btn-group">
                                  <button type="button" class="btn btn-info dropdown-toggle modern-btn" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-edit"></i> Modifications
                                  </button>
                                  <div class="dropdown-menu dropdown-menu-end shadow-lg modern-dropdown">
                                    <a class="dropdown-item <?= $ticket['date_paie'] !== null ? 'disabled' : '' ?>" href="#" data-bs-toggle="modal" data-bs-target="#editModalNumeroTicket<?= $ticket['id_ticket'] ?>">
                                        <i class="fas fa-hashtag text-primary me-2"></i> Changer N° Ticket
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item <?= $ticket['date_paie'] !== null ? 'disabled' : '' ?>" href="#" data-bs-toggle="modal" data-bs-target="#editModalUsine<?= $ticket['id_ticket'] ?>">
                                        <i class="fas fa-industry text-info me-2"></i> Changer Usine
                                    </a>
                                    <a class="dropdown-item <?= $ticket['date_paie'] !== null ? 'disabled' : '' ?>" href="#" data-bs-toggle="modal" data-bs-target="#editModalChefEquipe<?= $ticket['id_ticket'] ?>">
                                        <i class="fas fa-user text-success me-2"></i> Changer Chef Mission
                                    </a>
                                    <a class="dropdown-item <?= $ticket['date_paie'] !== null ? 'disabled' : '' ?>" href="#" data-bs-toggle="modal" data-bs-target="#editModalVehicule<?= $ticket['id_ticket'] ?>">
                                        <i class="fas fa-truck text-warning me-2"></i> Changer Véhicule
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item <?= $ticket['date_paie'] !== null ? 'disabled' : '' ?>" href="#" data-bs-toggle="modal" data-bs-target="#editModalPrixUnitaire<?= $ticket['id_ticket'] ?>">
                                        <i class="fas fa-money-bill text-success me-2"></i> Changer Prix Unitaire
                                    </a>
                                    <a class="dropdown-item <?= $ticket['date_paie'] !== null ? 'disabled' : '' ?>" href="#" data-bs-toggle="modal" data-bs-target="#editModalDateCreation<?= $ticket['id_ticket'] ?>">
                                        <i class="fas fa-calendar-alt text-danger me-2"></i> Changer Date Création
                                    </a>
                                  </div>
                                </div>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modern Pagination -->
        <div class="pagination-container glass-container mt-4 p-4 rounded-4 animate__animated animate__fadeInUp" style="animation-delay: 0.6s;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <!-- Pagination Navigation -->
                <div class="pagination-nav d-flex align-items-center gap-2">
                    <?php if($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&<?= http_build_query($filter_params) ?>" class="btn btn-outline-primary modern-btn">
                            <i class="fas fa-chevron-left me-1"></i> Précédent
                        </a>
                    <?php else: ?>
                        <button class="btn btn-outline-secondary modern-btn" disabled>
                            <i class="fas fa-chevron-left me-1"></i> Précédent
                        </button>
                    <?php endif; ?>
                    
                    <div class="pagination-info px-4 py-2 bg-light bg-opacity-50 rounded-pill">
                        <span class="fw-medium text-primary"><?= $page ?></span>
                        <span class="text-muted mx-2">sur</span>
                        <span class="fw-medium text-primary"><?= $total_pages ?></span>
                    </div>
                    
                    <?php if($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>&<?= http_build_query($filter_params) ?>" class="btn btn-outline-primary modern-btn">
                            Suivant <i class="fas fa-chevron-right ms-1"></i>
                        </a>
                    <?php else: ?>
                        <button class="btn btn-outline-secondary modern-btn" disabled>
                            Suivant <i class="fas fa-chevron-right ms-1"></i>
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- Items per page selector -->
                <form action="" method="get" class="items-per-page-form d-flex align-items-center gap-2">
                    <label for="limit" class="text-muted fw-medium mb-0">
                        <i class="fas fa-list me-1"></i> Afficher :
                    </label>
                    <select name="limit" id="limit" class="form-select form-select-sm modern-select" onchange="this.form.submit()" style="width: auto;">
                        <?php foreach([15, 25, 50] as $val): ?>
                            <option value="<?= $val ?>" <?= $limit == $val ? 'selected' : '' ?>><?= $val ?> éléments</option>
                        <?php endforeach; ?>
                    </select>
                    <?php 
                    // Préserver les paramètres de filtrage lors du changement de limite
                    foreach($filter_params as $key => $value):
                        if($key !== 'limit' && $key !== 'page'):
                    ?>
                        <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                    <?php 
                        endif;
                    endforeach;
                    ?>
                </form>
            </div>
        </div>

    <?php if(empty($tickets_list)): ?>
        <div class="alert alert-info text-center mt-4">
            <i class="fas fa-info-circle"></i> Aucun ticket ne correspond aux critères de recherche.
        </div>
    <?php endif; ?>

    <?php if(isset($error_message)): ?>
        <div class="alert alert-danger text-center mt-4">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- Modales pour chaque ticket -->
    <?php foreach ($tickets_list as $ticket) : ?>
      <!-- Modal pour modifier le numéro de ticket -->
      <div class="modal fade" id="editModalNumeroTicket<?= $ticket['id_ticket'] ?>">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Modifier le numéro de ticket</h5>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
              <form action="traitement_tickets.php" method="post">
                <input type="hidden" name="id_ticket" value="<?= $ticket['id_ticket'] ?>">
                <div class="form-group">
                  <label>Nouveau numéro de ticket</label>
                  <input type="text" name="numero_ticket" class="form-control" value="<?= $ticket['numero_ticket'] ?>" required>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-success" name="updateNumeroTicket">
                    <i class="fas fa-save"></i> Mettre à jour
                  </button>
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal pour modifier l'usine -->
      <div class="modal fade" id="editModalUsine<?= $ticket['id_ticket'] ?>">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Modifier l'usine</h5>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
              <form action="traitement_tickets.php" method="post">
                <input type="hidden" name="id_ticket" value="<?= $ticket['id_ticket'] ?>">
                <div class="form-group">
                  <label for="usine<?= $ticket['id_ticket'] ?>" class="font-weight-bold mb-2">Sélectionner la nouvelle usine</label>
                  <select name="usine" id="usine<?= $ticket['id_ticket'] ?>" class="form-control select2-usine" style="width: 100%;">
                    <option value="">Sélectionner une usine</option>
                    <?php foreach ($usines as $usine) : ?>
                      <option value="<?= htmlspecialchars($usine['id_usine']) ?>" <?= $usine['id_usine'] == $ticket['id_usine'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($usine['nom_usine']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-success" name="updateUsine">
                    <i class="fas fa-save"></i> Mettre à jour
                  </button>
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal pour modifier le chef de mission -->
      <div class="modal fade" id="editModalChefEquipe<?= $ticket['id_ticket'] ?>">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Modifier chargé de mission</h5>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
              <form action="traitement_tickets.php" method="post">
                <input type="hidden" name="id_ticket" value="<?= $ticket['id_ticket'] ?>">
                <div class="form-group">
                  <label for="chef_equipe<?= $ticket['id_ticket'] ?>" class="font-weight-bold mb-2">Sélectionner le nouveau chargé de mission</label>
                  <select name="chef_equipe" id="chef_equipe<?= $ticket['id_ticket'] ?>" class="form-control select2-chef-equipe" style="width: 100%;">
                    <option value="">Sélectionner un chargé de mission</option>
                    <?php foreach ($agents as $agent) : ?>
                      <option value="<?= htmlspecialchars($agent['id_agent']) ?>" <?= $agent['id_agent'] == $ticket['id_agent'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($agent['nom_complet_agent']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-success" name="updateChefEquipe">
                    <i class="fas fa-save"></i> Mettre à jour
                  </button>
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal pour modifier le véhicule -->
      <div class="modal fade" id="editModalVehicule<?= $ticket['id_ticket'] ?>">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Modifier le véhicule</h5>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
              <form action="traitement_tickets.php" method="post">
                <input type="hidden" name="id_ticket" value="<?= $ticket['id_ticket'] ?>">
                <div class="form-group">
                  <label for="vehicule<?= $ticket['id_ticket'] ?>" class="font-weight-bold mb-2">Sélectionner le nouveau véhicule</label>
                  <select name="vehicule" id="vehicule<?= $ticket['id_ticket'] ?>" class="form-control select2-vehicule" style="width: 100%;">
                    <option value="">Sélectionner un véhicule</option>
                    <?php foreach ($vehicules as $vehicule) : ?>
                      <option value="<?= htmlspecialchars($vehicule['vehicules_id']) ?>" <?= $vehicule['vehicules_id'] == $ticket['vehicule_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($vehicule['matricule_vehicule']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-success" name="updateVehicule">
                    <i class="fas fa-save"></i> Mettre à jour
                  </button>
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal pour modifier le prix unitaire -->
      <div class="modal fade" id="editModalPrixUnitaire<?= $ticket['id_ticket'] ?>">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Modifier le prix unitaire</h5>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
              <form action="traitement_tickets.php" method="post">
                <input type="hidden" name="id_ticket" value="<?= $ticket['id_ticket'] ?>">
                <input type="hidden" name="action" value="update_prix_unitaire">
                <div class="form-group">
                  <label for="prix_unitaire<?= $ticket['id_ticket'] ?>">Prix unitaire</label>
                  <div class="input-group">
                    <input type="number" 
                           class="form-control" 
                           id="prix_unitaire<?= $ticket['id_ticket'] ?>" 
                           value="<?= $ticket['prix_unitaire'] ?>" 
                           name="prix_unitaire"
                           min="1"
                           step="1"
                           required
                           data-poids="<?= $ticket['poids'] ?>"
                           data-montant-deja-paye="<?= $ticket['montant_payer'] ?>"
                           oninput="updateTotal(this, <?= $ticket['poids'] ?>)">
                    <div class="input-group-append">
                      <span class="input-group-text">FCFA</span>
                    </div>
                  </div>
                  <small class="form-text text-muted" id="total<?= $ticket['id_ticket'] ?>">
                    Le montant à payer sera automatiquement recalculé (Prix unitaire × <?= $ticket['poids'] ?> kg = <?= number_format($ticket['prix_unitaire'] * $ticket['poids'], 0, ',', ' ') ?> FCFA)
                  </small>
                  <?php if ($ticket['montant_payer'] > 0): ?>
                    <small class="form-text text-warning">
                      <i class="fas fa-exclamation-triangle"></i>
                      Attention : Ce ticket a déjà reçu <?= number_format($ticket['montant_payer'], 0, ',', ' ') ?> FCFA de paiement
                    </small>
                  <?php endif; ?>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Mettre à jour
                  </button>
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal pour modifier la date de création -->
      <div class="modal fade" id="editModalDateCreation<?= $ticket['id_ticket'] ?>">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Modifier la date de création</h5>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
              <form action="update_date_creation.php?id=<?= $ticket['id_ticket'] ?>" method="post">
                <input type="hidden" name="id_ticket" value="<?= $ticket['id_ticket'] ?>">
                <div class="form-group">
                  <label for="date_creation_<?= $ticket['id_ticket'] ?>">Date de création</label>
                  <div class="input-group">
                    <input type="date" 
                           class="form-control" 
                           id="date_creation_<?= $ticket['id_ticket'] ?>"
                           value="<?= date('Y-m-d', strtotime($ticket['created_at'])) ?>" 
                           name="date_creation" 
                           required>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Mettre à jour
                  </button>
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="modal fade" id="add-ticket">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">Enregistrer un ticket</h4>
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
                      <select id="select" name="usine" class="form-control select2-usine">
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
                      <select id="select" name="id_agent" class="form-control select2-agent">
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
                      <select id="select" name="vehicule" class="form-control select2-vehicule">
                          <?php
                          // Vérifier si des usines existent
                          if (!empty($vehicules)) {
                              foreach ($vehicules as $vehicule) {
                                  echo '<option value="' . htmlspecialchars($vehicule['vehicules_id']) . '">' . htmlspecialchars($vehicule['matricule_vehicule']) . '</option>';
                              }
                          } else {
                              echo '<option value="">Aucun véhicule disponible</option>';
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
              <form class="forms-sample" method="post" action="print_bordereau.php" target="_blank">
                <div class="card-body">
                  <div class="form-group">
                      <label>Chargé de Mission</label>
                      <select id="select" name="id_agent" class="form-control select2-agent">
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
                  <button class="btn btn-light">Annuler</button>
                </div>
              </form>
            </div>
          </div>
          <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>

    <!-- Recherche par Communes -->



  


    <!-- /.row (main row) -->
</div><!-- /.container-fluid -->
<!-- /.content -->
</div>
<!-- /.content-wrapper -->
<!-- <footer class="main-footer">
    <strong>Copyright &copy; 2014-2021 <a href="https://adminlte.io">AdminLTE.io</a>.</strong>
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
      <b>Version</b> 3.2.0
    </div>
  </footer>-->
<!-- Control Sidebar -->
<aside class="control-sidebar control-sidebar-dark">
  <!-- Control sidebar content goes here -->
</aside>
<!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Initialize Bootstrap components -->
<script>
$(document).ready(function() {
    // Force initialize all dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
    
    console.log('Dropdowns initialized:', dropdownList.length);
    
    // Debug dropdown events
    document.addEventListener('show.bs.dropdown', function (event) {
        console.log('Dropdown showing:', event.target);
    });
    
    document.addEventListener('shown.bs.dropdown', function (event) {
        console.log('Dropdown shown:', event.target);
    });
});
</script>

<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
<!-- Modern JavaScript Enhancements -->
<script>
// Modal management
function showSearchModal(modalId) {
    document.querySelectorAll('.modal').forEach(modal => {
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) bsModal.hide();
    });
    
    const targetModal = new bootstrap.Modal(document.getElementById(modalId));
    targetModal.show();
}

// Enhanced delete confirmation
function confirmDelete(ticketId, ticketNumber) {
    Swal.fire({
        title: 'Êtes-vous sûr?',
        text: `Voulez-vous vraiment supprimer le ticket ${ticketNumber}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Oui, supprimer!',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `tickets_delete.php?id=${ticketId}`;
        }
    });
}

// Smooth scroll animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe elements for scroll animations
document.querySelectorAll('.animate-on-scroll').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'all 0.6s ease';
    observer.observe(el);
});

// Add ripple effect to buttons
$('.modern-btn').on('click', function(e) {
    const button = $(this);
    const ripple = $('<span class="ripple"></span>');
    const rect = this.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = e.clientX - rect.left - size / 2;
    const y = e.clientY - rect.top - size / 2;
    
    ripple.css({
        width: size,
        height: size,
        left: x,
        top: y
    }).appendTo(button);
    
    setTimeout(() => ripple.remove(), 600);
});

// Enhanced form validation
$('.modern-form').on('submit', function(e) {
    const form = $(this);
    const requiredFields = form.find('[required]');
    let isValid = true;
    
    requiredFields.each(function() {
        const field = $(this);
        if (!field.val().trim()) {
            field.addClass('is-invalid');
            isValid = false;
        } else {
            field.removeClass('is-invalid').addClass('is-valid');
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Erreur de validation',
            text: 'Veuillez remplir tous les champs obligatoires.',
            confirmButtonColor: 'var(--primary-color)'
        });
    }
});

// Auto-hide alerts
$('.alert').each(function() {
    const alert = $(this);
    setTimeout(() => {
        alert.fadeOut(500);
    }, 5000);
});
</script>

<script>
function updateTotal(input, poids) {
    const prix = parseFloat(input.value) || 0;
    const total = prix * poids;
    const montantDejaPaye = parseFloat(input.dataset.montantDejaPaye) || 0;
    const ticketId = input.id.replace('prix_unitaire', '');
    const submitBtn = input.closest('form').querySelector('button[type="submit"]');
    
    document.getElementById('total' + ticketId).innerHTML = 
        `Le montant à payer sera automatiquement recalculé (Prix unitaire × ${poids} kg = ${total.toLocaleString('fr-FR')} FCFA)`;
    
    // Vérifier si le nouveau montant est valide
    if (total < montantDejaPaye) {
        input.setCustomValidity(`Le nouveau montant (${total.toLocaleString('fr-FR')} FCFA) ne peut pas être inférieur au montant déjà payé (${montantDejaPaye.toLocaleString('fr-FR')} FCFA)`);
        submitBtn.disabled = true;
    } else {
        input.setCustomValidity('');
        submitBtn.disabled = false;
    }
}

// Initialiser le calcul au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('input[id^="prix_unitaire"]');
    inputs.forEach(input => {
        const poids = parseFloat(input.dataset.poids);
        if (poids) updateTotal(input, poids);
    });
});
</script>

<!-- Select2 and Advanced Interactions -->
<script>
$(document).ready(function() {
    // Initialize Select2 with modern styling
    function initSelect2() {
        $('.select2-usine').select2({
            theme: 'bootstrap-5',
            placeholder: 'Sélectionner une usine',
            allowClear: true,
            width: '100%',
            language: {
                noResults: () => "Aucune usine trouvée",
                searching: () => "Recherche..."
            }
        });

        $('.select2-agent').select2({
            theme: 'bootstrap-5',
            placeholder: 'Sélectionner un chargé de mission',
            allowClear: true,
            width: '100%',
            language: {
                noResults: () => "Aucun chargé de mission trouvé",
                searching: () => "Recherche..."
            }
        });

        $('.select2-vehicule').select2({
            theme: 'bootstrap-5',
            placeholder: 'Sélectionner un véhicule',
            allowClear: true,
            width: '100%',
            language: {
                noResults: () => "Aucun véhicule trouvé",
                searching: () => "Recherche..."
            }
        });
    }
    
    // Initialize on page load
    initSelect2();
    
    // Reinitialize when modals are shown
    $('.modal').on('shown.bs.modal', function() {
        initSelect2();
    });
    
    // Reset forms when modals are hidden
    $('.modal').on('hidden.bs.modal', function() {
        $(this).find('form')[0]?.reset();
        $(this).find('select').val('').trigger('change');
        $(this).find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
    });
    
    // Enhanced table interactions
    $('.table-row').hover(
        function() {
            $(this).addClass('table-hover-effect');
        },
        function() {
            $(this).removeClass('table-hover-effect');
        }
    );
});
</script>
</body>

</html>

<style>
      .dropdown-item {
        padding: 0.5rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: background-color 0.2s;
      }
      
      .dropdown-item:hover {
        background-color: #f8f9fa;
      }
      
      .dropdown-item i {
        width: 20px;
      }
      
      .dropdown-item:disabled {
        color: #6c757d;
        opacity: 0.65;
        cursor: not-allowed;
      }
      
      .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
      }
      
      .btn-primary:hover {
        background-color: #0069d9;
        border-color: #0062cc;
      }
      
      .modal-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
      }
      
      .modal-footer {
        background-color: #f8f9fa;
        border-top: 1px solid #dee2e6;
      }

      .dropdown-menu {
        min-width: 200px;
      }
    </style>
<!-- Final Page Initialization -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show loader
    const loader = document.getElementById('loader');
    const mainContent = document.getElementById('mainContent');
    
    if (loader) loader.style.display = 'block';
    
    // Hide loader and show content with smooth transition
    setTimeout(function() {
        if (loader) {
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.style.display = 'none';
                if (mainContent) {
                    mainContent.style.display = 'block';
                    mainContent.style.opacity = '0';
                    setTimeout(() => {
                        mainContent.style.opacity = '1';
                    }, 50);
                }
            }, 300);
        }
    }, 800);
    
    // Initialize floating particles animation
    createFloatingParticles();
});

// Create floating particles for enhanced visual effect
function createFloatingParticles() {
    const particlesContainer = document.querySelector('.floating-particles');
    if (!particlesContainer) return;
    
    for (let i = 0; i < 15; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 20 + 's';
        particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
        particlesContainer.appendChild(particle);
    }
}
</script>
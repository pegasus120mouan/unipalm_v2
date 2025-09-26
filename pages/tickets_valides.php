<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_tickets.php';
require_once '../inc/functions/requete/requete_usines.php';
require_once '../inc/functions/requete/requete_chef_equipes.php';
require_once '../inc/functions/requete/requete_vehicules.php';
require_once '../inc/functions/requete/requete_agents.php';

include('header.php');

// Save memory about the professional transformation
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets Validés - UniPalm</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body>

<?php

$limit = $_GET['limit'] ?? 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Récupérer les paramètres de filtrage
$agent_id = $_GET['agent_id'] ?? null;
$usine_id = $_GET['usine_id'] ?? null;
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$search_agent = $_GET['search_agent'] ?? '';
$search_usine = $_GET['search_usine'] ?? '';

// Récupérer les tickets validés avec les filtres
$tickets = getTicketsValides($conn, $agent_id, $usine_id, $date_debut, $date_fin);

// Filtrer les tickets si un terme de recherche est présent
if (!empty($search_agent) || !empty($search_usine)) {
    $tickets = array_filter($tickets, function($ticket) use ($search_agent, $search_usine) {
        $match = true;
        if (!empty($search_agent)) {
            $match = $match && stripos($ticket['agent_nom_complet'], $search_agent) !== false;
        }
        if (!empty($search_usine)) {
            $match = $match && stripos($ticket['nom_usine'], $search_usine) !== false;
        }
        return $match;
    });
}

// Calculer la pagination
$total_tickets = count($tickets);
$total_pages = ceil($total_tickets / $limit);
$page = max(1, min($page, $total_pages));
$offset = ($page - 1) * $limit;

// Extraire les tickets pour la page courante
$tickets_list = array_slice($tickets, $offset, $limit);

// Récupérer les listes pour l'autocomplétion
$agents = getAgents($conn);
$usines = getUsines($conn);
$chefs_equipes=getChefEquipes($conn);
$vehicules=getVehicules($conn);

// Initialiser les variables de pagination
$total_tickets = is_array($tickets) ? count($tickets) : 0;
$total_pages = $total_tickets > 0 ? ceil($total_tickets / $limit) : 1;
$page = max(1, min($page, $total_pages));

// Paginer les résultats
$offset = ($page - 1) * $limit;
$tickets_list = is_array($tickets) ? array_slice($tickets, $offset, $limit) : [];

// Vérifiez si des tickets existent avant de procéder
if (!empty($tickets)) {
    $ticket_pages = array_chunk($tickets, $limit); // Divise les tickets en pages
    $tickets_list = $ticket_pages[$page - 1] ?? []; // Tickets pour la page actuelle
} else {
    $tickets_list = []; // Aucun ticket à afficher
}

?>


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

/* Search form styling */
.search-container {
    margin-bottom: 2rem;
}

.search-fieldset {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 2rem;
    position: relative;
    animation: slideInLeft 0.8s ease-out 0.2s both;
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

.search-legend {
    font-family: 'Poppins', sans-serif;
    font-size: 1.3rem;
    font-weight: 600;
    color: white;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    border: none;
    position: absolute;
    top: -15px;
    left: 20px;
    box-shadow: var(--shadow-light);
}

/* Form controls */
.form-label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.form-control, .form-select {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    color: #2c3e50;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.form-control:focus, .form-select:focus {
    background: rgba(255, 255, 255, 0.15);
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    color: #2c3e50;
}

.form-control::placeholder {
    color: rgba(44, 62, 80, 0.6);
}

.input-group-text {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    border-radius: 12px 0 0 12px;
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

/* Pagination styling */
.pagination-container {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 1.5rem;
    margin: 2rem 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    animation: slideInUp 0.8s ease-out 0.6s both;
}

.pagination-link {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border: none;
    border-radius: 10px;
    padding: 0.5rem 1rem;
    text-decoration: none;
    transition: all 0.3s ease;
    font-weight: 600;
}

.pagination-link:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-light);
    color: white;
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
}

/* Custom animations */
.animate-on-scroll {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.6s ease;
}

.animate-on-scroll.visible {
    opacity: 1;
    transform: translateY(0);
}

/* Status badges */
.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: none;
    cursor: default;
}

.status-success {
    background: linear-gradient(135deg, var(--success-color), #22c55e);
    color: white;
}

.status-warning {
    background: linear-gradient(135deg, var(--warning-color), #f59e0b);
    color: white;
}

.status-danger {
    background: linear-gradient(135deg, var(--danger-color), #ef4444);
    color: white;
}

.status-info {
    background: linear-gradient(135deg, var(--info-color), #3b82f6);
    color: white;
}

.status-dark {
    background: linear-gradient(135deg, var(--dark-color), #374151);
    color: white;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du formulaire de recherche
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        // Masquer la table et afficher le loader
        document.getElementById('example1').style.display = 'none';
        document.getElementById('loader').style.display = 'block';
    });

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
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "columnDefs": [
                { "orderable": false, "targets": [7, 8, 9, 10] }
            ],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json"
            }
        });
    }, 1000);

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

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="animate__animated animate__fadeInDown">
            <i class="fas fa-check-circle me-3" style="color: var(--success-color);"></i>
            Tickets Validés
        </h1>
        <p class="subtitle animate__animated animate__fadeInUp animate__delay-1s">
            Gestion et suivi des tickets validés - Total: <strong><?php echo $total_tickets; ?></strong> ticket(s)
        </p>
    </div>

    <!-- Search Container -->
    <div class="search-container">
        <div class="row justify-content-center">
            <div class="col-12">
                <fieldset class="search-fieldset">
                    <legend class="search-legend">
                        <i class="fas fa-search me-2"></i>Recherche Avancée
                    </legend>
                    <form id="filterForm" method="GET" class="p-4">
                    <div class="row">
                        <!-- Recherche par agent -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="agent_select" class="form-label">
                                <i class="fas fa-user me-2"></i>Agent
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <select class="form-select" name="agent_id" id="agent_select">
                                    <option value="">Sélectionner un agent</option>
                                    <?php foreach($agents as $agent): ?>
                                        <option value="<?= $agent['id_agent'] ?>" <?= ($agent_id == $agent['id_agent']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($agent['nom_complet_agent']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Recherche par usine -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="usine_select" class="form-label">
                                <i class="fas fa-industry me-2"></i>Usine
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-industry"></i>
                                </span>
                                <select class="form-select" name="usine_id" id="usine_select">
                                    <option value="">Sélectionner une usine</option>
                                    <?php foreach($usines as $usine): ?>
                                        <option value="<?= $usine['id_usine'] ?>" <?= ($usine_id == $usine['id_usine']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($usine['nom_usine']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Date de début -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="date_debut" class="form-label">
                                <i class="fas fa-calendar-alt me-2"></i>Date de début
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-calendar-alt"></i>
                                </span>
                                <input type="date" 
                                       class="form-control" 
                                       name="date_debut" 
                                       id="date_debut"
                                       value="<?= htmlspecialchars($date_debut) ?>">
                            </div>
                        </div>

                        <!-- Date de fin -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="date_fin" class="form-label">
                                <i class="fas fa-calendar-check me-2"></i>Date de fin
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-calendar-check"></i>
                                </span>
                                <input type="date" 
                                       class="form-control" 
                                       name="date_fin" 
                                       id="date_fin"
                                       value="<?= htmlspecialchars($date_fin) ?>">
                            </div>
                        </div>

                        <!-- Boutons -->
                        <div class="col-12 mb-3 d-flex flex-wrap gap-3 align-items-end justify-content-center">
                            <button type="submit" class="btn btn-primary btn-lg px-4">
                                <i class="fas fa-search me-2"></i>Rechercher
                            </button>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="btn btn-secondary btn-lg px-4">
                                <i class="fas fa-sync-alt me-2"></i>Réinitialiser
                            </a>
                            <button type="button" class="btn btn-success btn-lg px-4" data-bs-toggle="modal" data-bs-target="#add-ticket">
                                <i class="fas fa-plus me-2"></i>Nouveau Ticket
                            </button>
                        </div>
                    </div>
                </form>
            </fieldset>
        </div>
    </div>
</div>

    <!-- Table Container -->
    <div class="table-container">
        <!-- Loader -->
        <div id="loader" class="text-center p-3">
            <div class="spinner"></div>
            <p class="mt-3"><i class="fas fa-clock me-2"></i>Chargement des données...</p>
        </div>
        
        <!-- Table -->
        <div class="table-responsive">
            <table id="example1" class="table" style="display: none;">
                <thead>
                    <tr>
                        <th><i class="fas fa-calendar me-2"></i>Date Ticket</th>
                        <th><i class="fas fa-ticket-alt me-2"></i>Numéro</th>
                        <th><i class="fas fa-industry me-2"></i>Usine</th>
                        <th><i class="fas fa-user-tie me-2"></i>Chargé Mission</th>
                        <th><i class="fas fa-truck me-2"></i>Véhicule</th>
                        <th><i class="fas fa-weight me-2"></i>Poids</th>
                        <th><i class="fas fa-user-plus me-2"></i>Créé par</th>
                        <th><i class="fas fa-coins me-2"></i>Prix Unit.</th>
                        <th><i class="fas fa-check me-2"></i>Validation</th>
                        <th><i class="fas fa-money-bill me-2"></i>Montant</th>
                        <th><i class="fas fa-calendar-check me-2"></i>Date Paie</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tickets_list)) : ?>
                        <?php foreach ($tickets_list as $ticket) : ?>
                            <tr>
                                <td data-label="Date Ticket"><?= $ticket['date_ticket'] ?></td>
                                <td data-label="Numéro">
                                    <span class="badge bg-primary"><?= $ticket['numero_ticket'] ?></span>
                                </td>
                                <td data-label="Usine"><?= $ticket['nom_usine'] ?></td>
                                <td data-label="Chargé Mission"><?= $ticket['agent_nom_complet'] ?></td>
                                <td data-label="Véhicule">
                                    <span class="badge bg-info"><?= $ticket['matricule_vehicule'] ?></span>
                                </td>
                                <td data-label="Poids">
                                    <strong><?= $ticket['poids'] ?> kg</strong>
                                </td>
                                <td data-label="Créé par"><?= $ticket['utilisateur_nom_complet'] ?></td>

                                <td data-label="Prix Unitaire">
                                    <?php if ($ticket['prix_unitaire'] === null || $ticket['prix_unitaire'] == 0.00): ?>
                                        <span class="status-badge status-danger">
                                            <i class="fas fa-clock me-1"></i>En Attente
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-dark">
                                            <?= $ticket['prix_unitaire'] ?>
                                        </span>
                                    <?php endif; ?>
                                </td>




                                <td data-label="Validation">
                                    <?php if ($ticket['date_validation_boss'] === null): ?>
                                        <span class="status-badge status-warning">
                                            <i class="fas fa-hourglass-half me-1"></i>En cours
                                        </span>
                                    <?php else: ?>
                                        <span class="text-success">
                                            <i class="fas fa-check-circle me-1"></i><?= $ticket['date_validation_boss'] ?>
                                        </span>
                                    <?php endif; ?>
                                </td>


                                <td data-label="Montant">
                                    <?php if ($ticket['montant_paie'] === null): ?>
                                        <span class="status-badge status-info">
                                            <i class="fas fa-clock me-1"></i>Attente PU
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-success">
                                            <?= $ticket['montant_paie'] ?>
                                        </span>
                                    <?php endif; ?>
                                </td>


                                <td data-label="Date Paie">
                                    <?php if ($ticket['date_paie'] === null): ?>
                                        <span class="status-badge status-dark">
                                            <i class="fas fa-times me-1"></i>Non effectuée
                                        </span>
                                    <?php else: ?>
                                        <span class="text-success">
                                            <i class="fas fa-calendar-check me-1"></i><?= $ticket['date_paie'] ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
          <div class="modal fade" id="editModalTicket<?= $ticket['id_ticket'] ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Modification Ticket <?= $ticket['id_ticket'] ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Formulaire de modification du ticket -->
                <form action="commandes_update.php?id=<?= $ticket['id_ticket'] ?>" method="post">
                <div class="form-group">
                        <label for="prix_unitaire">Numéro du ticket</label>
                        <input type="text" class="form-control" id="numero_ticket" name="numero_ticket" value="<?= $ticket['numero_ticket'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="prix_unitaire">Prix Unitaire</label>
                        <input type="number" class="form-control" id="prix_unitaire" name="prix_unitaire" value="<?= $ticket['prix_unitaire'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="date_validation_boss">Date de Validation</label>
                        <input type="date" class="form-control" id="date_validation_boss" name="date_validation_boss" value="<?= $ticket['date_validation_boss'] ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Sauvegarder les modifications</button>
                </form>
            </div>
        </div>
    </div>
</div>
         <div class="modal" id="valider_ticket<?= $ticket['id_ticket'] ?>">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-body">
                <form action="traitement_tickets.php" method="post">
                  <input type="hidden" name="id_ticket" value="<?= $ticket['id_ticket'] ?>">
                  <div class="form-group">
                    <label>Ajouter le prix unitaire</label>
                  </div>
                  <div class="form-group">
                <input type="text" class="form-control" id="exampleInputEmail1" placeholder="Prix unitaire" name="prix_unitaire">
              </div>
                  <button type="submit" class="btn btn-primary mr-2" name="saveCommande">Ajouter</button>
                  <button class="btn btn-light">Annuler</button>
                </form>
              </div>
            </div>
          </div>
        </div>


                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    <h5>Aucun ticket validé</h5>
                                    <p>Il n'y a pas de tickets validés pour le moment.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination Container -->
    <div class="pagination-container">
        <div class="d-flex align-items-center justify-content-between w-100 flex-wrap gap-3">
            <!-- Navigation buttons -->
            <div class="d-flex align-items-center gap-2">
                <?php if($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="pagination-link">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <span class="text-white mx-3">
                    <i class="fas fa-file-alt me-2"></i>
                    Page <strong><?= $page ?></strong> sur <strong><?= $total_pages ?></strong>
                </span>
                
                <?php if($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="pagination-link">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Items per page form -->
            <form action="" method="get" class="d-flex align-items-center gap-2">
                <label for="limit" class="text-white mb-0">
                    <i class="fas fa-list me-2"></i>Afficher :
                </label>
                <select name="limit" id="limit" class="form-select" style="width: auto;">
                    <option value="5" <?php if ($limit == 5) { echo 'selected'; } ?>>5</option>
                    <option value="10" <?php if ($limit == 10) { echo 'selected'; } ?>>10</option>
                    <option value="15" <?php if ($limit == 15) { echo 'selected'; } ?>>15</option>
                    <option value="25" <?php if ($limit == 25) { echo 'selected'; } ?>>25</option>
                    <option value="50" <?php if ($limit == 50) { echo 'selected'; } ?>>50</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i>
                </button>
            </form>
        </div>
    </div>



    <!-- Modal for adding new ticket -->
    <div class="modal fade" id="add-ticket" tabindex="-1" aria-labelledby="addTicketLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTicketLabel">
                        <i class="fas fa-plus-circle me-2"></i>Nouveau Ticket
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form class="forms-sample" method="post" action="traitement_tickets.php">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="date_ticket" class="form-label">
                                    <i class="fas fa-calendar me-2"></i>Date ticket
                                </label>
                                <input type="date" class="form-control" id="date_ticket" name="date_ticket" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="numero_ticket" class="form-label">
                                    <i class="fas fa-ticket-alt me-2"></i>Numéro du Ticket
                                </label>
                                <input type="text" class="form-control" id="numero_ticket" placeholder="Numéro du ticket" name="numero_ticket" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="usine_select" class="form-label">
                                    <i class="fas fa-industry me-2"></i>Sélection Usine
                                </label>
                                <select id="usine_select" name="usine" class="form-select" required>
                                    <option value="">Choisir une usine</option>
                                    <?php
                                    if (!empty($usines)) {
                                        foreach ($usines as $usine) {
                                            echo '<option value="' . htmlspecialchars($usine['id_usine']) . '">' . htmlspecialchars($usine['nom_usine']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="chef_equipe_select" class="form-label">
                                    <i class="fas fa-user-tie me-2"></i>Chef d'Équipe
                                </label>
                                <select id="chef_equipe_select" name="chef_equipe" class="form-select" required>
                                    <option value="">Choisir un chef d'équipe</option>
                                    <?php
                                    if (!empty($chefs_equipes)) {
                                        foreach ($chefs_equipes as $chefs_equipe) {
                                            echo '<option value="' . htmlspecialchars($chefs_equipe['id_chef']) . '">' . htmlspecialchars($chefs_equipe['chef_nom_complet']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="vehicule_select" class="form-label">
                                    <i class="fas fa-truck me-2"></i>Sélection Véhicule
                                </label>
                                <select id="vehicule_select" name="vehicule" class="form-select" required>
                                    <option value="">Choisir un véhicule</option>
                                    <?php
                                    if (!empty($vehicules)) {
                                        foreach ($vehicules as $vehicule) {
                                            echo '<option value="' . htmlspecialchars($vehicule['vehicules_id']) . '">' . htmlspecialchars($vehicule['matricule_vehicule']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="poids" class="form-label">
                                    <i class="fas fa-weight me-2"></i>Poids (kg)
                                </label>
                                <input type="number" class="form-control" id="poids" placeholder="Poids en kilogrammes" name="poids" step="0.01" min="0" required>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Annuler
                            </button>
                            <button type="submit" class="btn btn-primary" name="saveCommande">
                                <i class="fas fa-save me-2"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- /.modal-dialog -->
  </div>

<!-- Recherche par Communes -->



  


</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- Enhanced JavaScript -->
<script>
// Scroll animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, observerOptions);

// Observe all animate-on-scroll elements
document.querySelectorAll('.animate-on-scroll').forEach(el => {
    observer.observe(el);
});

// Enhanced form interactions
document.querySelectorAll('.form-control, .form-select').forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'translateY(-2px)';
    });
    
    input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'translateY(0)';
    });
});

// Button click effects
document.querySelectorAll('.btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        let ripple = document.createElement('span');
        ripple.classList.add('ripple');
        this.appendChild(ripple);
        
        let x = e.clientX - e.target.offsetLeft;
        let y = e.clientY - e.target.offsetTop;
        
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    });
});
</script>

<style>
.ripple {
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
</style>

</body>
</html>
<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_tickets.php';
require_once '../inc/functions/requete/requete_usines.php';
require_once '../inc/functions/requete/requete_chef_equipes.php';
require_once '../inc/functions/requete/requete_vehicules.php';
require_once '../inc/functions/requete/requete_agents.php';

include('header.php');

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

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prix Unitaires - UniPalm</title>
    
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
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

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

/* Logo UniPalm */
.unipalm-logo {
    display: inline-flex;
    align-items: center;
    font-family: 'Poppins', sans-serif;
    font-weight: 700;
    font-size: 1.8rem;
    color: white;
    text-decoration: none;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.unipalm-logo:hover {
    transform: scale(1.05);
    color: white;
}

.unipalm-logo .logo-icon {
    background: linear-gradient(135deg, #4ade80, #22c55e);
    border-radius: 12px;
    padding: 8px;
    margin-right: 12px;
    box-shadow: 0 4px 15px rgba(74, 222, 128, 0.3);
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

/* Buttons */
.btn-modern {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    padding: 0.75rem 2rem;
    transition: all 0.3s ease;
    box-shadow: var(--shadow-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.9rem;
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-heavy);
    color: white;
}

.btn-modern:active {
    transform: translateY(0);
}

.btn-modern-success {
    background: linear-gradient(135deg, var(--success-color), #22c55e);
}

.btn-modern-danger {
    background: linear-gradient(135deg, var(--danger-color), #ef4444);
}

.btn-modern-secondary {
    background: linear-gradient(135deg, #6b7280, #4b5563);
}

/* Stats cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    animation: slideInUp 0.8s ease-out;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-heavy);
}

.stat-card-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-card-number {
    font-size: 2rem;
    font-weight: 700;
    color: #000000;
    margin-bottom: 0.5rem;
}

.stat-card-label {
    color: #333333;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 500;
}

/* Table styling */
.table-container {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 1.5rem;
    animation: slideInUp 0.8s ease-out 0.4s both;
}

.modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: transparent;
}

.modern-table thead th {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    font-weight: 600;
    padding: 1rem;
    text-align: left;
    border: none;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.modern-table thead th:first-child {
    border-top-left-radius: 12px;
}

.modern-table thead th:last-child {
    border-top-right-radius: 12px;
}

.modern-table tbody tr {
    background: rgba(255, 255, 255, 0.05);
    transition: all 0.3s ease;
}

.modern-table tbody tr:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.modern-table tbody td {
    padding: 1rem;
    border: none;
    color: #2c3e50;
    font-weight: 500;
    background: rgba(255, 255, 255, 0.9);
}

.modern-table tbody tr:last-child td:first-child {
    border-bottom-left-radius: 12px;
}

.modern-table tbody tr:last-child td:last-child {
    border-bottom-right-radius: 12px;
}

/* Action buttons */
.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 35px;
    height: 35px;
    border-radius: 8px;
    border: none;
    margin: 0 2px;
    transition: all 0.3s ease;
    text-decoration: none;
    font-size: 0.9rem;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.action-btn-edit {
    background: linear-gradient(135deg, var(--info-color), #3b82f6);
    color: white;
}

.action-btn-delete {
    background: linear-gradient(135deg, var(--danger-color), #ef4444);
    color: white;
}

/* Pagination */
.pagination-modern {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 2rem;
    gap: 0.5rem;
}

.pagination-modern .page-link {
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border: 1px solid var(--glass-border);
    border-radius: 8px;
    color: white;
    padding: 0.5rem 1rem;
    text-decoration: none;
    transition: all 0.3s ease;
}

.pagination-modern .page-link:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
    color: white;
}

.pagination-modern .page-link.active {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
}

/* Modal styling */
.modal-content {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    box-shadow: var(--shadow-heavy);
}

.modal-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border-radius: 20px 20px 0 0;
    border-bottom: none;
}

.modal-body {
    background: rgba(255, 255, 255, 0.95);
    color: #2c3e50;
}

.modal-footer {
    background: rgba(255, 255, 255, 0.95);
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 0 0 20px 20px;
}

/* Responsive design */
@media (max-width: 768px) {
    .page-header h1 {
        font-size: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .modern-table {
        font-size: 0.8rem;
    }
    
    .modern-table thead th,
    .modern-table tbody td {
        padding: 0.5rem;
    }
}

/* Active filters */
.active-filters {
    margin-top: 1rem;
}

.filter-badge {
    background: linear-gradient(135deg, var(--info-color), #3b82f6);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    margin: 0.25rem;
    display: inline-flex;
    align-items: center;
    font-size: 0.85rem;
    font-weight: 500;
}

.filter-badge .remove-filter {
    margin-left: 0.5rem;
    color: white;
    text-decoration: none;
    opacity: 0.8;
    transition: opacity 0.3s ease;
}

.filter-badge .remove-filter:hover {
    opacity: 1;
    color: white;
}

/* Styles spécifiques pour le tableau des prix */
.prix-display {
    display: flex;
    align-items: center;
    font-weight: 600;
}

.prix-value {
    font-size: 1.1rem;
    color: var(--success-color);
}

.prix-currency {
    margin-left: 0.25rem;
    font-size: 0.9rem;
    color: #6b7280;
}

.date-display {
    display: flex;
    align-items: center;
    font-size: 0.9rem;
}

.usine-icon {
    opacity: 0.7;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.no-data {
    padding: 3rem 1rem;
}

.badge {
    font-size: 0.75rem;
    padding: 0.5rem 0.75rem;
    border-radius: 20px;
    font-weight: 500;
}

/* Animation pour les lignes du tableau */
.modern-table tbody tr {
    animation: fadeInUp 0.5s ease-out;
}

.modern-table tbody tr:nth-child(even) {
    animation-delay: 0.1s;
}

.modern-table tbody tr:nth-child(odd) {
    animation-delay: 0.05s;
}

/* Responsive pour le tableau */
@media (max-width: 768px) {
    .action-buttons {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .prix-display {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .date-display {
        font-size: 0.8rem;
    }
}
</style>


<body>
<div class="container-fluid py-4">
    <!-- Header avec logo UniPalm -->
    <div class="page-header">
        <a href="#" class="unipalm-logo">
            <div class="logo-icon">
                <i class="fas fa-leaf"></i>
            </div>
            UniPalm
        </a>
        <h1 class="animate__animated animate__fadeInDown">
            Gestion des Prix Unitaires
        </h1>
        <p class="subtitle animate__animated animate__fadeInUp animate__delay-1s">
            Configuration et suivi des prix unitaires par usine - Total: <strong><?= $total_items ?></strong> prix configuré(s)
        </p>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card animate__animated animate__fadeInUp">
            <div class="stat-card-icon">
                <i class="fas fa-coins"></i>
            </div>
            <div class="stat-card-number"><?= $total_items ?></div>
            <div class="stat-card-label">Prix Configurés</div>
        </div>
        <div class="stat-card animate__animated animate__fadeInUp animate__delay-1s">
            <div class="stat-card-icon">
                <i class="fas fa-industry"></i>
            </div>
            <div class="stat-card-number"><?= count($usines) ?></div>
            <div class="stat-card-label">Usines</div>
        </div>
        <div class="stat-card animate__animated animate__fadeInUp animate__delay-2s">
            <div class="stat-card-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-card-number"><?= count(array_filter($prix_unitaires_list, function($p) { return $p['date_fin'] === null; })) ?></div>
            <div class="stat-card-label">Prix Actifs</div>
        </div>
    </div>

    <!-- Messages d'erreur et de succès -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start">
                <i class="fas fa-exclamation-triangle fa-2x me-3 text-danger"></i>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-2">
                        <i class="fas fa-times-circle me-2"></i>Erreur de validation
                    </h5>
                    <div class="error-message">
                        <?= nl2br(htmlspecialchars($_SESSION['error'])) ?>
                    </div>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle fa-2x me-3 text-success"></i>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-1">
                        <i class="fas fa-thumbs-up me-2"></i>Opération réussie
                    </h5>
                    <div><?= htmlspecialchars($_SESSION['success']) ?></div>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- Actions principales -->
    <div class="glass-container">
        <div class="row">
            <div class="col-md-6">
                <button type="button" class="btn btn-modern btn-modern-success w-100 mb-3" data-bs-toggle="modal" data-bs-target="#add-ticket">
                    <i class="fas fa-plus me-2"></i>Nouveau Prix Unitaire
                </button>
            </div>
            <div class="col-md-6">
                <button type="button" class="btn btn-modern btn-modern-secondary w-100 mb-3" data-bs-toggle="modal" data-bs-target="#print-bordereau">
                    <i class="fas fa-print me-2"></i>Imprimer la Liste
                </button>
            </div>
        </div>
    </div>

    <!-- Filtres de recherche -->
    <div class="search-container">
        <div class="row justify-content-center">
            <div class="col-12">
                <fieldset class="search-fieldset">
                    <legend class="search-legend">
                        <i class="fas fa-filter me-2"></i>Filtres de Recherche
                    </legend>
                    <form id="filterForm" method="GET" class="p-4">
                        <div class="row">
                            <!-- Recherche par usine -->
                            <div class="col-lg-3 col-md-6 mb-3">
                                <label for="usine_select" class="form-label">
                                    <i class="fas fa-industry me-2"></i>Usine
                                </label>
                                <select class="form-select" name="usine_id" id="usine_select">
                                    <option value="">Toutes les usines</option>
                                    <?php foreach($usines as $usine): ?>
                                        <option value="<?= $usine['id_usine'] ?>" <?= ($usine_id == $usine['id_usine']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($usine['nom_usine']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Prix minimum -->
                            <div class="col-lg-3 col-md-6 mb-3">
                                <label for="prix_min" class="form-label">
                                    <i class="fas fa-coins me-2"></i>Prix minimum
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       name="prix_min" 
                                       id="prix_min"
                                       placeholder="Prix minimum" 
                                       step="0.01"
                                       value="<?= htmlspecialchars($prix_min) ?>">
                            </div>

                            <!-- Prix maximum -->
                            <div class="col-lg-3 col-md-6 mb-3">
                                <label for="prix_max" class="form-label">
                                    <i class="fas fa-coins me-2"></i>Prix maximum
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       name="prix_max" 
                                       id="prix_max"
                                       placeholder="Prix maximum" 
                                       step="0.01"
                                       value="<?= htmlspecialchars($prix_max) ?>">
                            </div>

                            <!-- Date de début -->
                            <div class="col-lg-3 col-md-6 mb-3">
                                <label for="date_debut" class="form-label">
                                    <i class="fas fa-calendar-alt me-2"></i>Date de début
                                </label>
                                <input type="date" 
                                       class="form-control" 
                                       name="date_debut" 
                                       id="date_debut"
                                       value="<?= htmlspecialchars($date_debut) ?>">
                            </div>

                            <!-- Date de fin -->
                            <div class="col-lg-3 col-md-6 mb-3">
                                <label for="date_fin" class="form-label">
                                    <i class="fas fa-calendar-check me-2"></i>Date de fin
                                </label>
                                <input type="date" 
                                       class="form-control" 
                                       name="date_fin" 
                                       id="date_fin"
                                       value="<?= htmlspecialchars($date_fin) ?>">
                            </div>

                            <!-- Boutons -->
                            <div class="col-12 mb-3 d-flex flex-wrap gap-3 align-items-end justify-content-center">
                                <button type="submit" class="btn btn-modern btn-lg px-4">
                                    <i class="fas fa-search me-2"></i>Rechercher
                                </button>
                                <a href="prix_unitaires.php" class="btn btn-modern btn-modern-secondary btn-lg px-4">
                                    <i class="fas fa-sync-alt me-2"></i>Réinitialiser
                                </a>
                            </div>
                        </div>
                    </form>
            
                    <!-- Filtres actifs -->
                    <?php if($usine_id || $date_debut || $date_fin || $prix_min || $prix_max): ?>
                    <div class="active-filters">
                        <div class="d-flex align-items-center flex-wrap">
                            <strong class="text-white me-3">Filtres actifs :</strong>
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
                                <span class="filter-badge">
                                    <i class="fas fa-industry me-2"></i>
                                    Usine: <?= htmlspecialchars($usine_name) ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['usine_id' => null])) ?>" class="remove-filter">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            <?php if($prix_min): ?>
                                <span class="filter-badge">
                                    <i class="fas fa-coins me-2"></i>
                                    Prix min: <?= htmlspecialchars($prix_min) ?> FCFA
                                    <a href="?<?= http_build_query(array_merge($_GET, ['prix_min' => null])) ?>" class="remove-filter">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            <?php if($prix_max): ?>
                                <span class="filter-badge">
                                    <i class="fas fa-coins me-2"></i>
                                    Prix max: <?= htmlspecialchars($prix_max) ?> FCFA
                                    <a href="?<?= http_build_query(array_merge($_GET, ['prix_max' => null])) ?>" class="remove-filter">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            <?php if($date_debut): ?>
                                <span class="filter-badge">
                                    <i class="fas fa-calendar-alt me-2"></i>
                                    Depuis: <?= date('d/m/Y', strtotime($date_debut)) ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['date_debut' => null])) ?>" class="remove-filter">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            <?php if($date_fin): ?>
                                <span class="filter-badge">
                                    <i class="fas fa-calendar-check me-2"></i>
                                    Jusqu'au: <?= date('d/m/Y', strtotime($date_fin)) ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['date_fin' => null])) ?>" class="remove-filter">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </fieldset>
            </div>
        </div>
    </div>

    <!-- Tableau des prix unitaires -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="modern-table" id="prixTable">
                <thead>
                    <tr>
                        <th><i class="fas fa-industry me-2"></i>Usine</th>
                        <th>Prix Unitaire</th>
                        <th><i class="fas fa-calendar-alt me-2"></i>Date Début</th>
                        <th><i class="fas fa-calendar-check me-2"></i>Date Fin</th>
                        <th><i class="fas fa-chart-line me-2"></i>Statut</th>
                        <th><i class="fas fa-cogs me-2"></i>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($prix_unitaires_list)) : ?>
                        <?php foreach ($prix_unitaires_list as $prix) : ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="usine-icon me-2">
                                            <i class="fas fa-industry text-primary"></i>
                                        </div>
                                        <strong><?= htmlspecialchars($prix['nom_usine']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <div class="prix-display">
                                        <span class="prix-value"><?= number_format($prix['prix'], 2, ',', ' ') ?></span>
                                        <span class="prix-currency">FCFA</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="date-display">
                                        <i class="fas fa-calendar-alt text-info me-2"></i>
                                        <?= date('d/m/Y', strtotime($prix['date_debut'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="date-display">
                                        <?php if ($prix['date_fin']) : ?>
                                            <i class="fas fa-calendar-check text-warning me-2"></i>
                                            <?= date('d/m/Y', strtotime($prix['date_fin'])) ?>
                                        <?php else : ?>
                                            <i class="fas fa-infinity text-success me-2"></i>
                                            <span class="text-success">En cours</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($prix['date_fin'] === null) : ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Actif
                                        </span>
                                    <?php else : ?>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-archive me-1"></i>Archivé
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="#" class="action-btn action-btn-edit" data-bs-toggle="modal" data-bs-target="#editModal<?= $prix['id'] ?>" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="action-btn action-btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $prix['id'] ?>" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="no-data">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Aucun prix unitaire trouvé</h5>
                                    <p class="text-muted">Aucun prix unitaire ne correspond aux critères de recherche.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination moderne -->
        <div class="pagination-modern">
            <?php if($page > 1): ?>
                <a href="?page=<?= $page - 1 ?><?= $usine_id ? '&usine_id='.$usine_id : '' ?><?= $date_debut ? '&date_debut='.$date_debut : '' ?><?= $date_fin ? '&date_fin='.$date_fin : '' ?><?= $prix_min ? '&prix_min='.$prix_min : '' ?><?= $prix_max ? '&prix_max='.$prix_max : '' ?><?= isset($_GET['limit']) ? '&limit='.$_GET['limit'] : '' ?>" class="page-link">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            
            <span class="page-link active">
                Page <?= $page ?> sur <?= $total_pages ?>
            </span>
            
            <?php if($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?><?= $usine_id ? '&usine_id='.$usine_id : '' ?><?= $date_debut ? '&date_debut='.$date_debut : '' ?><?= $date_fin ? '&date_fin='.$date_fin : '' ?><?= $prix_min ? '&prix_min='.$prix_min : '' ?><?= $prix_max ? '&prix_max='.$prix_max : '' ?><?= isset($_GET['limit']) ? '&limit='.$_GET['limit'] : '' ?>" class="page-link">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
            
            <form method="get" class="d-inline-flex align-items-center ms-3">
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
                <label for="limit" class="text-white me-2">Afficher :</label>
                <select name="limit" id="limit" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                    <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>5</option>
                    <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                    <option value="15" <?= $limit == 15 ? 'selected' : '' ?>>15</option>
                    <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                </select>
            </form>
        </div>
    </div>
</div>


<!-- Modals de modification pour chaque prix unitaire -->
<?php if (!empty($prix_unitaires_list)) : ?>
    <?php foreach ($prix_unitaires_list as $prix) : ?>
        <!-- Modal Modification -->
        <div class="modal fade" id="editModal<?= $prix['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $prix['id'] ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel<?= $prix['id'] ?>">
                            <i class="fas fa-edit me-2"></i>Modifier le Prix Unitaire
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="traitement_prix_unitaires.php" method="post">
                            <input type="hidden" name="id" value="<?= $prix['id'] ?>">
                            
                            <div class="mb-3">
                                <label for="id_usine_<?= $prix['id'] ?>" class="form-label">
                                    <i class="fas fa-industry me-2"></i>Sélection Usine
                                </label>
                                <select name="id_usine" id="id_usine_<?= $prix['id'] ?>" class="form-select" required>
                                    <?php foreach ($usines as $usine) : ?>
                                        <option value="<?= $usine['id_usine'] ?>" <?= $usine['id_usine'] == $prix['id_usine'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($usine['nom_usine']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="prix_<?= $prix['id'] ?>" class="form-label">
                                    <i class="fas fa-coins me-2"></i>Prix Unitaire (FCFA)
                                </label>
                                <input type="number" step="0.01" id="prix_<?= $prix['id'] ?>" class="form-control" name="prix" value="<?= $prix['prix'] ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="date_debut_<?= $prix['id'] ?>" class="form-label">
                                    <i class="fas fa-calendar-alt me-2"></i>Date de début
                                </label>
                                <input type="date" id="date_debut_<?= $prix['id'] ?>" class="form-control" name="date_debut" value="<?= $prix['date_debut'] ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="date_fin_<?= $prix['id'] ?>" class="form-label">
                                    <i class="fas fa-calendar-check me-2"></i>Date de fin (optionnel)
                                </label>
                                <input type="date" id="date_fin_<?= $prix['id'] ?>" class="form-control" name="date_fin" value="<?= $prix['date_fin'] ?>">
                                <div class="form-text">Laissez vide si le prix unitaire est toujours en cours</div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-modern btn-modern-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Annuler
                                </button>
                                <button type="submit" class="btn btn-modern btn-modern-success" name="updatePrixUnitaire">
                                    <i class="fas fa-save me-2"></i>Enregistrer les modifications
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Suppression -->
        <div class="modal fade" id="deleteModal<?= $prix['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $prix['id'] ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deleteModalLabel<?= $prix['id'] ?>">
                            <i class="fas fa-exclamation-triangle me-2"></i>Confirmer la suppression
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                            <h6>Êtes-vous sûr de vouloir supprimer ce prix unitaire ?</h6>
                        </div>
                        <div class="alert alert-warning">
                            <strong>Usine :</strong> <?= htmlspecialchars($prix['nom_usine']) ?><br>
                            <strong>Prix :</strong> <?= number_format($prix['prix'], 2, ',', ' ') ?> FCFA<br>
                            <strong>Période :</strong> <?= date('d/m/Y', strtotime($prix['date_debut'])) ?> - 
                            <?= $prix['date_fin'] ? date('d/m/Y', strtotime($prix['date_fin'])) : 'En cours' ?>
                        </div>
                        <p class="text-muted"><strong>Attention :</strong> Cette action est irréversible.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-modern btn-modern-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Annuler
                        </button>
                        <form action="traitement_prix_unitaires.php" method="post" class="d-inline">
                            <input type="hidden" name="id" value="<?= $prix['id'] ?>">
                            <button type="submit" class="btn btn-modern btn-modern-danger" name="deletePrixUnitaire">
                                <i class="fas fa-trash me-2"></i>Supprimer définitivement
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Modal Ajout Prix Unitaire -->
<div class="modal fade" id="add-ticket" tabindex="-1" aria-labelledby="addTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTicketModalLabel">
                    <i class="fas fa-plus me-2"></i>Nouveau Prix Unitaire
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Zone d'alerte pour les conflits -->
                <div id="conflit-alert" class="alert alert-danger" style="display: none;">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div>
                            <h6 id="conflit-title" class="alert-heading mb-2"></h6>
                            <div id="conflit-details"></div>
                            <div id="conflit-list" class="mt-2"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Zone de succès -->
                <div id="success-alert" class="alert alert-success" style="display: none;">
                    <i class="fas fa-check-circle me-2"></i>
                    <span id="success-message"></span>
                </div>
                
                <form id="prix-unitaire-form" method="post" action="traitement_prix_unitaires.php">
                    <div class="mb-3">
                        <label for="id_usine" class="form-label">
                            <i class="fas fa-industry me-2"></i>Usine
                        </label>
                        <select id="usine-select" name="id_usine" class="form-select" required>
                            <option value="">Sélectionner une usine</option>
                            <?php foreach ($usines as $usine) : ?>
                                <option value="<?= htmlspecialchars($usine['id_usine']) ?>">
                                    <?= htmlspecialchars($usine['nom_usine']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="prix" class="form-label">
                            <i class="fas fa-coins me-2"></i>Prix Unitaire (FCFA)
                        </label>
                        <input type="number" step="0.01" id="prix-input" class="form-control" name="prix" placeholder="0.00" required>
                    </div>

                    <div class="mb-3">
                        <label for="date_debut" class="form-label">
                            <i class="fas fa-calendar-alt me-2"></i>Date de début
                        </label>
                        <input type="date" id="date-debut" class="form-control" name="date_debut" required>
                    </div>

                    <div class="mb-3">
                        <label for="date_fin" class="form-label">
                            <i class="fas fa-calendar-check me-2"></i>Date de fin (optionnel)
                        </label>
                        <input type="date" id="date-fin" class="form-control" name="date_fin">
                        <div class="form-text">Laissez vide si le prix est permanent</div>
                    </div>

                    <!-- Loader de vérification -->
                    <div id="verification-loader" class="text-center" style="display: none;">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="sr-only">Vérification...</span>
                        </div>
                        <span class="ml-2">Vérification des conflits...</span>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-modern btn-modern-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Annuler
                        </button>
                        <button type="submit" id="submit-btn" class="btn btn-modern btn-modern-success" name="savePrixUnitaire" disabled>
                            <i class="fas fa-save me-2"></i>Enregistrer
                        </button>
                    </div>
                </form>
            </div>
      </div>
    </div>
  </div>

<!-- Modal Impression -->
<div class="modal fade" id="print-bordereau" tabindex="-1" aria-labelledby="printBordereauLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="printBordereauLabel">
                    <i class="fas fa-print me-2"></i>Impression des Prix Unitaires
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="print_bordereau.php" target="_blank">
                    <div class="mb-3">
                        <label for="id_agent" class="form-label">
                            <i class="fas fa-user-tie me-2"></i>Chargé de Mission
                        </label>
                        <select name="id_agent" class="form-select">
                            <option value="">Tous les agents</option>
                            <?php foreach ($agents as $agent) : ?>
                                <option value="<?= htmlspecialchars($agent['id_agent']) ?>">
                                    <?= htmlspecialchars($agent['nom_complet_agent']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="date_debut_print" class="form-label">
                            <i class="fas fa-calendar-alt me-2"></i>Date de début
                        </label>
                        <input type="date" class="form-control" name="date_debut">
                    </div>

                    <div class="mb-3">
                        <label for="date_fin_print" class="form-label">
                            <i class="fas fa-calendar-check me-2"></i>Date de fin
                        </label>
                        <input type="date" class="form-control" name="date_fin">
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-modern btn-modern-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Annuler
                        </button>
                        <button type="submit" class="btn btn-modern" name="saveCommande">
                            <i class="fas fa-print me-2"></i>Imprimer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Scripts Bootstrap 5 et jQuery -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
// Validation en temps réel pour les prix unitaires
$(document).ready(function() {
    let validationTimeout;
    let isValidating = false;
    
    // Fonction pour vérifier les conflits
    function checkConflits() {
        const usineId = $('#usine-select').val();
        const dateDebut = $('#date-debut').val();
        const dateFin = $('#date-fin').val();
        
        // Debug: Afficher les valeurs des champs
        console.log('Vérification conflits - Usine:', usineId, 'Date début:', dateDebut, 'Date fin:', dateFin);
        
        // Vérifier que les champs obligatoires sont remplis
        if (!usineId || !dateDebut) {
            resetValidation();
            return;
        }
        
        // Afficher le loader
        showLoader();
        isValidating = true;
        
        $.ajax({
            url: 'check_conflit_ajax.php',
            method: 'POST',
            data: {
                id_usine: usineId,
                date_debut: dateDebut,
                date_fin: dateFin
            },
            success: function(response) {
                hideLoader();
                isValidating = false;
                
                // Debug: Afficher les informations dans la console
                console.log('Réponse de validation:', response);
                if (response.debug) {
                    console.log('Debug - Dates reçues:', response.debug.date_debut_recu, 'à', response.debug.date_fin_recu);
                    console.log('Debug - Prix existants pour', response.debug.usine + ':', response.debug.prix_existants);
                }
                
                if (response.conflit) {
                    showConflitAlert(response);
                    $('#submit-btn').prop('disabled', true);
                } else {
                    showSuccessAlert(response.message);
                    $('#submit-btn').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                hideLoader();
                isValidating = false;
                console.error('Erreur AJAX:', error);
                showErrorAlert('Erreur lors de la vérification des conflits');
                $('#submit-btn').prop('disabled', true);
            }
        });
    }
    
    // Fonction pour afficher l'alerte de conflit
    function showConflitAlert(response) {
        $('#conflit-title').text(response.message);
        $('#conflit-details').text(response.details);
        
        let conflitsList = '<ul class="mb-0">';
        response.conflits.forEach(function(conflit) {
            conflitsList += '<li><strong>Prix: ' + parseFloat(conflit.prix).toLocaleString('fr-FR') + ' FCFA</strong> - ' + conflit.periode_str + '</li>';
        });
        conflitsList += '</ul>';
        
        $('#conflit-list').html(conflitsList);
        $('#conflit-alert').show();
        $('#success-alert').hide();
    }
    
    // Fonction pour afficher l'alerte de succès
    function showSuccessAlert(message) {
        $('#success-message').text(message);
        $('#success-alert').show();
        $('#conflit-alert').hide();
    }
    
    // Fonction pour afficher une erreur
    function showErrorAlert(message) {
        $('#conflit-title').text('❌ Erreur de validation');
        $('#conflit-details').text(message);
        $('#conflit-list').html('');
        $('#conflit-alert').show();
        $('#success-alert').hide();
    }
    
    // Fonction pour réinitialiser la validation
    function resetValidation() {
        $('#conflit-alert').hide();
        $('#success-alert').hide();
        $('#submit-btn').prop('disabled', true);
    }
    
    // Fonction pour afficher le loader
    function showLoader() {
        $('#verification-loader').show();
        resetValidation();
    }
    
    // Fonction pour masquer le loader
    function hideLoader() {
        $('#verification-loader').hide();
    }
    
    // Événements de validation en temps réel
    $('#usine-select, #date-debut, #date-fin').on('change', function() {
        clearTimeout(validationTimeout);
        validationTimeout = setTimeout(checkConflits, 500); // Délai de 500ms
    });
    
    // Validation avant soumission
    $('#prix-unitaire-form').on('submit', function(e) {
        if (isValidating) {
            e.preventDefault();
            alert('Veuillez attendre la fin de la vérification des conflits.');
            return false;
        }
        
        if ($('#submit-btn').prop('disabled')) {
            e.preventDefault();
            alert('Impossible d\'enregistrer : des conflits ont été détectés ou la validation n\'est pas terminée.');
            return false;
        }
        
        // Confirmation finale
        const usine = $('#usine-select option:selected').text();
        const prix = $('#prix-input').val();
        const dateDebut = $('#date-debut').val();
        const dateFin = $('#date-fin').val() || 'Période ouverte';
        
        const confirmMessage = `Confirmer l'enregistrement :\n\nUsine: ${usine}\nPrix: ${prix} FCFA\nPériode: ${dateDebut} - ${dateFin}`;
        
        if (!confirm(confirmMessage)) {
            e.preventDefault();
            return false;
        }
    });
    
    // Réinitialiser le formulaire à l'ouverture du modal
    $('#add-ticket').on('show.bs.modal', function() {
        resetValidation();
        $('#prix-unitaire-form')[0].reset();
        $('#submit-btn').prop('disabled', true);
    });
});
</script>

<style>
/* Styles pour le modal de prix unitaire */
#add-ticket .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

#conflit-alert, #success-alert {
    border-radius: 8px;
    animation: slideInDown 0.3s ease-out;
}

#conflit-alert {
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    color: white;
    border: none;
}

#success-alert {
    background: linear-gradient(135deg, #51cf66, #40c057);
    color: white;
    border: none;
}

#verification-loader {
    padding: 1rem;
    background: rgba(0, 123, 255, 0.1);
    border-radius: 8px;
    margin: 1rem 0;
}

#submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

#conflit-list ul {
    padding-left: 1.2rem;
}

#conflit-list li {
    margin-bottom: 0.3rem;
}

@keyframes slideInDown {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Styles pour les messages d'erreur et de succès */
.alert {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    margin-bottom: 1.5rem;
}

.alert-danger {
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    color: white;
    border-left: 5px solid #dc3545;
}

.alert-success {
    background: linear-gradient(135deg, #51cf66, #40c057);
    color: white;
    border-left: 5px solid #28a745;
}

.alert-heading {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.error-message {
    font-size: 0.95rem;
    line-height: 1.5;
    white-space: pre-line;
}

.alert .fas {
    opacity: 0.9;
}

.btn-close {
    filter: brightness(0) invert(1);
    opacity: 0.8;
}

.btn-close:hover {
    opacity: 1;
}

/* Animation d'apparition */
.alert.show {
    animation: slideInDown 0.5s ease-out;
}
</style>

</body>
</html>

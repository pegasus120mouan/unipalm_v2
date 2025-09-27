<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_tickets.php';
require_once '../inc/functions/requete/requete_usines.php';
require_once '../inc/functions/requete/requete_chef_equipes.php';
require_once '../inc/functions/requete/requete_vehicules.php';
require_once '../inc/functions/requete/requete_agents.php';
include('header_caisse.php');

$id_user=$_SESSION['user_id'];

$limit = $_GET['limit'] ?? 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Récupérer les paramètres de filtrage
$agent_id = $_GET['agent_id'] ?? null;
$usine_id = $_GET['usine_id'] ?? null;
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$search_agent = $_GET['search_agent'] ?? '';
$search_usine = $_GET['search_usine'] ?? '';
$statut = $_GET['statut'] ?? ''; // Nouveau paramètre pour le statut

// Récupérer les données
$tickets = getTicketsPayes($conn, $agent_id, $usine_id, $date_debut, $date_fin);
$usines = getUsines($conn);
$chefs_equipes = getChefEquipes($conn);
$vehicules = getVehicules($conn);
$agents = getAgents($conn);

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

// Filtrer par statut si sélectionné
if (!empty($statut)) {
    $tickets = array_filter($tickets, function($ticket) use ($statut) {
        $montant_reste = isset($ticket['montant_reste']) ? (float)$ticket['montant_reste'] : 0;
        
        if ($statut === 'solde') {
            return $montant_reste <= 0;
        } else if ($statut === 'en_cours') {
            return $montant_reste > 0;
        }
        return true;
    });
}

// Calculer la pagination
$total_tickets = count($tickets);
$total_pages = ceil($total_tickets / $limit);
$page = max(1, min($page, $total_pages));
$offset = ($page - 1) * $limit;

// Extraire les tickets pour la page courante
$tickets_list = array_slice($tickets, $offset, $limit);
?>

<style>
/* ===== STYLES ULTRA-PROFESSIONNELS POUR TICKETS PAYÉS ===== */

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

/* En-tête de page */
.page-header-pro {
    background: var(--primary-gradient);
    color: white;
    padding: 2.5rem;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
    box-shadow: var(--shadow-heavy);
    position: relative;
    overflow: hidden;
}

.page-header-pro::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    animation: shimmer 3s infinite;
}

@keyframes shimmer {
    0% { left: -100%; }
    100% { left: 100%; }
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.page-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
}

/* Conteneur de recherche */
.search-container-pro {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: var(--border-radius);
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-light);
    position: relative;
}

.search-form-group {
    margin-bottom: 1.5rem;
}

.search-label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    display: block;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.search-control {
    border: 2px solid #e9ecef;
    border-radius: 15px;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    transition: var(--transition);
    background: white;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.search-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
    transform: translateY(-2px);
}

.search-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 2rem;
}

.btn-search-pro {
    background: var(--info-gradient);
    border: none;
    color: white;
    padding: 0.75rem 2rem;
    border-radius: 25px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: var(--transition);
    box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
}

.btn-search-pro:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(79, 172, 254, 0.4);
    color: white;
}

.btn-reset-pro {
    background: var(--warning-gradient);
    border: none;
    color: white;
    padding: 0.75rem 2rem;
    border-radius: 25px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: var(--transition);
    box-shadow: 0 4px 15px rgba(240, 147, 251, 0.3);
}

.btn-reset-pro:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(240, 147, 251, 0.4);
    color: white;
}

/* Filtres actifs */
.active-filters-pro {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 15px;
    padding: 1.5rem;
    margin-top: 1.5rem;
}

.filter-badge {
    background: var(--info-gradient);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    margin: 0.25rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 2px 10px rgba(79, 172, 254, 0.3);
    transition: var(--transition);
}

.filter-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
}

.filter-remove {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: white;
    font-size: 0.75rem;
    transition: var(--transition);
}

.filter-remove:hover {
    background: rgba(255, 255, 255, 0.3);
    color: white;
    transform: scale(1.1);
}

/* Tableau professionnel */
.table-container-pro {
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow-light);
    border: 1px solid rgba(102, 126, 234, 0.1);
    margin-bottom: 2rem;
}

.table-pro {
    margin: 0;
    width: 100%;
}

.table-pro thead th {
    background: var(--primary-gradient);
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    padding: 1.25rem 1rem;
    border: none;
    position: sticky;
    top: 0;
    z-index: 10;
}

.table-pro tbody tr {
    transition: var(--transition);
    border-bottom: 1px solid #f1f3f4;
}

.table-pro tbody tr:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
    transform: scale(1.01);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.table-pro tbody td {
    padding: 1rem;
    vertical-align: middle;
    border: none;
}

/* Boutons de statut */
.status-btn {
    border: none;
    border-radius: 20px;
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    min-width: 140px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    transition: var(--transition);
}

.status-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
}

.status-danger { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%); color: white; }
.status-dark { background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; }
.status-warning { background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); color: white; }
.status-primary { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: white; }
.status-info { background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%); color: white; }

/* Responsive */
@media (max-width: 768px) {
    .page-header-pro {
        padding: 1.5rem;
        text-align: center;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
    
    .search-container-pro {
        padding: 1.5rem;
    }
    
    .search-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn-search-pro,
    .btn-reset-pro {
        width: 100%;
        max-width: 250px;
    }
    
    .table-pro thead {
        display: none;
    }
    
    .table-pro tbody tr {
        display: block;
        margin-bottom: 1rem;
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        padding: 1rem;
    }
    
    .table-pro tbody td {
        display: block;
        text-align: left !important;
        padding: 0.5rem 0;
        border: none;
    }
    
    .table-pro tbody td:before {
        content: attr(data-label) ": ";
        font-weight: 600;
        color: #2c3e50;
        display: inline-block;
        width: 120px;
        margin-right: 10px;
    }
}

/* Animations */
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

.fade-in-up {
    animation: fadeInUp 0.6s ease-out;
}

.slide-in-left {
    animation: slideInLeft 0.6s ease-out;
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

/* ===== STYLES PROFESSIONNELS POUR LA TABLE ===== */
.tickets-table-container {
    margin: 20px 0;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: var(--shadow-heavy);
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border: 1px solid var(--glass-border);
}

.tickets-table-pro {
    width: 100%;
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
    font-family: 'Inter', sans-serif;
}

.table-header-pro {
    background: var(--primary-gradient);
    position: sticky;
    top: 0;
    z-index: 10;
}

.table-header-pro th {
    padding: 18px 15px;
    color: white;
    font-weight: 600;
    font-size: 0.95rem;
    text-align: left;
    border: none;
    white-space: nowrap;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.ticket-row {
    transition: var(--transition);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.ticket-row:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.ticket-row td {
    padding: 15px;
    vertical-align: middle;
    border: none;
    font-size: 0.9rem;
}

.date-badge, .ticket-badge, .weight-badge, .amount-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
}

.date-badge {
    background: var(--info-gradient);
    color: white;
}

.date-badge.validated {
    background: var(--success-gradient);
}

.date-badge.paid {
    background: var(--warning-gradient);
}

.ticket-badge {
    background: var(--primary-gradient);
    color: white;
    font-family: 'Courier New', monospace;
}

.weight-badge {
    background: var(--warning-gradient);
    color: white;
}

.amount-badge {
    background: var(--info-gradient);
    color: white;
    font-weight: 700;
}

.status-badge {
    display: inline-block;
    padding: 8px 14px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.8rem;
    text-align: center;
    min-width: 120px;
}

.status-pending {
    background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
    color: #d68910;
}

.status-validated {
    background: var(--success-gradient);
    color: white;
}

.status-progress {
    background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%);
    color: white;
}

.status-waiting {
    background: var(--primary-gradient);
    color: white;
}

.status-unpaid {
    background: linear-gradient(135deg, #fc466b 0%, #3f5efb 100%);
    color: white;
}

.no-data-cell {
    text-align: center;
    padding: 60px 20px !important;
    background: var(--glass-bg);
}

.no-data-content {
    color: #7f8c8d;
}

.no-data-content i {
    color: #bdc3c7;
    margin-bottom: 15px;
}

/* ===== PAGINATION PROFESSIONNELLE ===== */
.pagination-container-pro {
    margin: 30px 0;
    padding: 25px;
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
    border: 1px solid var(--glass-border);
}

.pagination-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 25px;
    flex-wrap: wrap;
}

.pagination-nav {
    display: flex;
    align-items: center;
    gap: 5px;
}

.pagination-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: var(--transition);
    border: 2px solid transparent;
}

.pagination-btn:not(.pagination-disabled):not(.pagination-current) {
    background: var(--primary-gradient);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.pagination-btn:not(.pagination-disabled):not(.pagination-current):hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    text-decoration: none;
    color: white;
}

.pagination-disabled {
    background: #e9ecef;
    color: #adb5bd;
    cursor: not-allowed;
}

.pagination-current {
    background: white;
    border: 2px solid #667eea;
    padding: 0 15px;
    border-radius: 25px;
    font-weight: 700;
    color: #667eea;
    min-width: 80px;
}

.items-per-page-form-pro {
    margin: 0;
}

.items-control {
    display: flex;
    align-items: center;
    gap: 10px;
    background: white;
    padding: 12px 18px;
    border-radius: 25px;
    box-shadow: var(--shadow-light);
    border: 1px solid rgba(102, 126, 234, 0.2);
}

.items-select {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 8px 12px;
    font-weight: 600;
    color: #495057;
    background: white;
    min-width: 70px;
    transition: var(--transition);
}

.items-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

.items-apply-btn {
    background: var(--primary-gradient);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 5px;
}

.items-apply-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

/* Responsive design pour la table */
@media (max-width: 768px) {
    .tickets-table-container {
        margin: 15px -15px;
        border-radius: 0;
    }
    
    .pagination-wrapper {
        flex-direction: column;
        text-align: center;
    }
    
    .pagination-controls {
        justify-content: center;
        flex-direction: column;
        gap: 15px;
    }
}

@media (max-width: 576px) {
    .ticket-row {
        display: block;
        margin-bottom: 15px;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        background: white;
        box-shadow: var(--shadow-light);
    }
    
    .ticket-row td {
        display: block;
        padding: 10px 15px;
        border-bottom: 1px solid #f8f9fa;
        position: relative;
        padding-left: 120px;
    }
    
    .ticket-row td:before {
        content: attr(data-label);
        position: absolute;
        left: 15px;
        top: 10px;
        font-weight: 600;
        color: #495057;
        width: 100px;
    }
    
    .table-header-pro {
        display: none;
    }
}

/* ===== STYLES ULTRA-PROFESSIONNELS POUR LES FILTRES AVANCÉS ===== */
.advanced-filters-container {
    margin: 25px 0;
    background: var(--glass-bg);
    backdrop-filter: blur(15px);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-heavy);
    border: 1px solid var(--glass-border);
    overflow: hidden;
}

.filters-header {
    background: var(--primary-gradient);
    padding: 20px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: var(--transition);
}

.filters-header:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6b4190 100%);
}

.filters-title {
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
}

.filters-toggle {
    color: white;
    font-size: 1.2rem;
    transition: var(--transition);
}

.filters-toggle.active {
    transform: rotate(180deg);
}

.filters-content {
    padding: 30px 25px;
    max-height: 0;
    overflow: hidden;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.filters-content.active {
    max-height: 1000px;
    padding: 30px 25px;
}

.advanced-filter-form {
    width: 100%;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-label {
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    margin-bottom: 5px;
}

.filter-label i {
    color: #667eea;
    width: 20px;
}

/* Custom Select Styling */
.custom-select-wrapper {
    position: relative;
}

.custom-select {
    width: 100%;
    padding: 12px 45px 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    background: white;
    font-size: 0.95rem;
    font-weight: 500;
    color: #495057;
    transition: var(--transition);
    appearance: none;
    cursor: pointer;
}

.custom-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    outline: none;
}

.custom-select:hover {
    border-color: #667eea;
}

.select-arrow {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
    transition: var(--transition);
}

.custom-select:focus + .select-arrow {
    color: #667eea;
    transform: translateY(-50%) rotate(180deg);
}

/* Custom Date Input Styling */
.custom-date-wrapper {
    position: relative;
}

.custom-date-input {
    width: 100%;
    padding: 12px 45px 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    background: white;
    font-size: 0.95rem;
    font-weight: 500;
    color: #495057;
    transition: var(--transition);
}

.custom-date-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    outline: none;
}

.custom-date-input:hover {
    border-color: #667eea;
}

.date-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
}

.custom-date-input:focus ~ .date-icon {
    color: #667eea;
}

/* Search Input Styling */
.search-input-wrapper {
    position: relative;
}

.search-input {
    width: 100%;
    padding: 12px 45px 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    background: white;
    font-size: 0.95rem;
    font-weight: 500;
    color: #495057;
    transition: var(--transition);
}

.search-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    outline: none;
}

.search-input:hover {
    border-color: #667eea;
}

.search-input::placeholder {
    color: #adb5bd;
    font-style: italic;
}

.search-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
}

.search-input:focus ~ .search-icon {
    color: #667eea;
}

/* Filter Actions */
.filters-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
    padding-top: 20px;
    border-top: 2px solid #f8f9fa;
}

.btn-filter-apply {
    background: var(--primary-gradient);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-filter-apply:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-filter-reset {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    text-decoration: none;
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
}

.btn-filter-reset:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
    text-decoration: none;
    color: white;
}

.btn-filter-save {
    background: var(--success-gradient);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    box-shadow: 0 4px 15px rgba(86, 171, 47, 0.3);
}

.btn-filter-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(86, 171, 47, 0.4);
}

/* Active Filters Section */
.active-filters-section {
    margin-top: 25px;
    padding: 20px;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
    border-radius: 15px;
    border: 1px solid rgba(102, 126, 234, 0.1);
}

.active-filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.active-filters-title {
    font-weight: 700;
    color: #2c3e50;
    font-size: 1rem;
    display: flex;
    align-items: center;
}

.active-filters-count {
    background: var(--primary-gradient);
    color: white;
    width: 25px;
    height: 25px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.8rem;
}

.active-filters-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
}

.filter-tag {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    color: white;
    transition: var(--transition);
}

.filter-tag-status {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.filter-tag-agent {
    background: var(--info-gradient);
}

.filter-tag-usine {
    background: var(--success-gradient);
}

.filter-tag-date {
    background: var(--warning-gradient);
}

.filter-tag-search {
    background: var(--primary-gradient);
}

.filter-remove {
    color: white;
    text-decoration: none;
    opacity: 0.8;
    transition: var(--transition);
    padding: 2px;
    border-radius: 50%;
}

.filter-remove:hover {
    opacity: 1;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    text-decoration: none;
}

.clear-all-filters {
    text-align: center;
}

.btn-clear-all {
    background: linear-gradient(135deg, #fc466b 0%, #3f5efb 100%);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
}

.btn-clear-all:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(252, 70, 107, 0.3);
    text-decoration: none;
    color: white;
}

/* Responsive Design pour les filtres */
@media (max-width: 768px) {
    .filters-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .filters-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .btn-filter-apply,
    .btn-filter-reset,
    .btn-filter-save {
        width: 100%;
        max-width: 250px;
        justify-content: center;
    }
    
    .active-filters-list {
        justify-content: center;
    }
    
    .filter-tag {
        font-size: 0.8rem;
        padding: 6px 10px;
    }
}

@media (max-width: 576px) {
    .advanced-filters-container {
        margin: 15px -15px;
        border-radius: 0;
    }
    
    .filters-header {
        padding: 15px 20px;
    }
    
    .filters-content.active {
        padding: 20px 15px;
    }
    
    .filters-grid {
        gap: 15px;
    }
    
    .active-filters-header {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
}
</style>
    
<!-- En-tête professionnel -->
<div class="page-header-pro fade-in-up">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="page-title">
                <i class="fas fa-money-check-alt mr-3"></i>
                Tickets Payés
            </h1>
            <p class="page-subtitle">
                <i class="fas fa-chart-line mr-2"></i>
                Gestion des tickets soldés et en cours de paiement
            </p>
        </div>
        <div class="text-right">
            <div class="badge badge-light p-3" style="font-size: 1.1rem;">
                <i class="fas fa-list-ol mr-2"></i>
                Total: <?php echo count($tickets); ?> ticket(s)
            </div>
        </div>
    </div>
</div>

<!-- Conteneur de filtres ultra-professionnel -->
<div class="advanced-filters-container fade-in-up">
    <div class="filters-header">
        <div class="filters-title">
            <i class="fas fa-filter mr-2"></i>
            <span>Filtres Avancés</span>
        </div>
        <div class="filters-toggle" id="filtersToggle">
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
    
    <div class="filters-content" id="filtersContent">
        <form id="filterForm" method="GET" class="advanced-filter-form">
            <div class="filters-grid">
                <!-- Statut du ticket -->
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-flag mr-2"></i>
                        Statut du ticket
                    </label>
                    <div class="custom-select-wrapper">
                        <select class="custom-select" name="statut" id="statut_select">
                            <option value="">Tous les statuts</option>
                            <option value="solde" <?= ($statut === 'solde') ? 'selected' : '' ?>>
                                <i class="fas fa-check-circle"></i> Soldés
                            </option>
                            <option value="en_cours" <?= ($statut === 'en_cours') ? 'selected' : '' ?>>
                                <i class="fas fa-clock"></i> En cours de paiement
                            </option>
                        </select>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                </div>

                <!-- Recherche par agent -->
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-user-tie mr-2"></i>
                        Agent responsable
                    </label>
                    <div class="custom-select-wrapper">
                        <select class="custom-select" name="agent_id" id="agent_select">
                            <option value="">Sélectionner un agent</option>
                            <?php foreach($agents as $agent): ?>
                                <option value="<?= $agent['id_agent'] ?>" <?= ($agent_id == $agent['id_agent']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($agent['nom_complet_agent']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                </div>
                
                <!-- Recherche par usine -->
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-industry mr-2"></i>
                        Usine de production
                    </label>
                    <div class="custom-select-wrapper">
                        <select class="custom-select" name="usine_id" id="usine_select">
                            <option value="">Sélectionner une usine</option>
                            <?php foreach($usines as $usine): ?>
                                <option value="<?= $usine['id_usine'] ?>" <?= ($usine_id == $usine['id_usine']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($usine['nom_usine']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                </div>

                <!-- Date de début -->
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        Date de début
                    </label>
                    <div class="custom-date-wrapper">
                        <input type="date" 
                               class="custom-date-input" 
                               name="date_debut" 
                               id="date_debut"
                               value="<?= htmlspecialchars($date_debut) ?>">
                        <i class="fas fa-calendar-alt date-icon"></i>
                    </div>
                </div>

                <!-- Date de fin -->
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-calendar-check mr-2"></i>
                        Date de fin
                    </label>
                    <div class="custom-date-wrapper">
                        <input type="date" 
                               class="custom-date-input" 
                               name="date_fin" 
                               id="date_fin"
                               value="<?= htmlspecialchars($date_fin) ?>">
                        <i class="fas fa-calendar-check date-icon"></i>
                    </div>
                </div>

                <!-- Recherche rapide -->
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-search mr-2"></i>
                        Recherche rapide
                    </label>
                    <div class="search-input-wrapper">
                        <input type="text" 
                               class="search-input" 
                               name="search_quick" 
                               id="search_quick"
                               placeholder="Numéro de ticket, nom..." 
                               value="<?= htmlspecialchars($_GET['search_quick'] ?? '') ?>">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="filters-actions">
                <button type="submit" class="btn-filter-apply">
                    <i class="fas fa-search mr-2"></i>
                    Appliquer les filtres
                </button>
                <a href="tickets_payes.php" class="btn-filter-reset">
                    <i class="fas fa-undo mr-2"></i>
                    Réinitialiser
                </a>
                <button type="button" class="btn-filter-save" id="saveFilters">
                    <i class="fas fa-bookmark mr-2"></i>
                    Sauvegarder
                </button>
            </div>
        </form>
            
        <!-- Filtres actifs avec design moderne -->
        <?php if($agent_id || $usine_id || $date_debut || $date_fin || $statut || !empty($_GET['search_quick'])): ?>
        <div class="active-filters-section">
            <div class="active-filters-header">
                <span class="active-filters-title">
                    <i class="fas fa-tags mr-2"></i>
                    Filtres actifs
                </span>
                <span class="active-filters-count">
                    <?php 
                    $active_count = 0;
                    if($statut) $active_count++;
                    if($agent_id) $active_count++;
                    if($usine_id) $active_count++;
                    if($date_debut) $active_count++;
                    if($date_fin) $active_count++;
                    if(!empty($_GET['search_quick'])) $active_count++;
                    echo $active_count;
                    ?>
                </span>
            </div>
            <div class="active-filters-list">
                <?php if($statut): ?>
                    <div class="filter-tag filter-tag-status">
                        <i class="fas fa-flag"></i>
                        <span>Statut: <?= $statut === 'solde' ? 'Soldés' : 'En cours de paiement' ?></span>
                        <a href="?<?= http_build_query(array_merge($_GET, ['statut' => null])) ?>" class="filter-remove">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                <?php endif; ?>
                <?php if($agent_id): ?>
                    <?php 
                    $agent_name = '';
                    foreach($agents as $agent) {
                        if($agent['id_agent'] == $agent_id) {
                            $agent_name = $agent['nom_complet_agent'];
                            break;
                        }
                    }
                    ?>
                    <div class="filter-tag filter-tag-agent">
                        <i class="fas fa-user-tie"></i>
                        <span>Agent: <?= htmlspecialchars($agent_name) ?></span>
                        <a href="?<?= http_build_query(array_merge($_GET, ['agent_id' => null])) ?>" class="filter-remove">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                <?php endif; ?>
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
                    <div class="filter-tag filter-tag-usine">
                        <i class="fas fa-industry"></i>
                        <span>Usine: <?= htmlspecialchars($usine_name) ?></span>
                        <a href="?<?= http_build_query(array_merge($_GET, ['usine_id' => null])) ?>" class="filter-remove">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                <?php endif; ?>
                <?php if($date_debut): ?>
                    <div class="filter-tag filter-tag-date">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Depuis: <?= date('d/m/Y', strtotime($date_debut)) ?></span>
                        <a href="?<?= http_build_query(array_merge($_GET, ['date_debut' => null])) ?>" class="filter-remove">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                <?php endif; ?>
                <?php if($date_fin): ?>
                    <div class="filter-tag filter-tag-date">
                        <i class="fas fa-calendar-check"></i>
                        <span>Jusqu'au: <?= date('d/m/Y', strtotime($date_fin)) ?></span>
                        <a href="?<?= http_build_query(array_merge($_GET, ['date_fin' => null])) ?>" class="filter-remove">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                <?php endif; ?>
                <?php if(!empty($_GET['search_quick'])): ?>
                    <div class="filter-tag filter-tag-search">
                        <i class="fas fa-search"></i>
                        <span>Recherche: "<?= htmlspecialchars($_GET['search_quick']) ?>"</span>
                        <a href="?<?= http_build_query(array_merge($_GET, ['search_quick' => null])) ?>" class="filter-remove">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="clear-all-filters">
                <a href="tickets_payes.php" class="btn-clear-all">
                    <i class="fas fa-trash-alt mr-1"></i>
                    Effacer tous les filtres
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Table professionnelle -->
<div class="tickets-table-container fade-in-up">
    <div class="table-responsive">
        <table id="example1" class="tickets-table-pro">
            <thead class="table-header-pro">
                <tr>
                    <th><i class="fas fa-calendar-alt mr-2"></i>Date ticket</th>
                    <th><i class="fas fa-ticket-alt mr-2"></i>Numéro Ticket</th>
                    <th><i class="fas fa-industry mr-2"></i>Usine</th>
                    <th><i class="fas fa-user-tie mr-2"></i>Chargé de Mission</th>
                    <th><i class="fas fa-truck mr-2"></i>Véhicule</th>
                    <th><i class="fas fa-weight mr-2"></i>Poids</th>
                    <th><i class="fas fa-user-plus mr-2"></i>Créé par</th>
                    <th><i class="fas fa-euro-sign mr-2"></i>Prix Unitaire</th>
                    <th><i class="fas fa-check-circle mr-2"></i>Date validation</th>
                    <th><i class="fas fa-money-bill-wave mr-2"></i>Montant</th>
                    <th><i class="fas fa-calendar-check mr-2"></i>Date Paie</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($tickets_list)) : ?>
                    <?php foreach ($tickets_list as $ticket) : ?>
                        <tr class="ticket-row">
                            <td class="date-cell">
                                <span class="date-badge">
                                    <?= date('d/m/Y', strtotime($ticket['date_ticket'])) ?>
                                </span>
                            </td>
                            <td class="ticket-number">
                                <span class="ticket-badge">
                                    #<?= $ticket['numero_ticket'] ?>
                                </span>
                            </td>
                            <td class="usine-cell"><?= htmlspecialchars($ticket['nom_usine']) ?></td>
                            <td class="agent-cell">
                                <i class="fas fa-user-circle mr-1"></i>
                                <?= htmlspecialchars($ticket['agent_nom_complet']) ?>
                            </td>
                            <td class="vehicle-cell">
                                <i class="fas fa-car mr-1"></i>
                                <?= htmlspecialchars($ticket['matricule_vehicule']) ?>
                            </td>
                            <td class="weight-cell">
                                <span class="weight-badge">
                                    <?= $ticket['poids'] ?> kg
                                </span>
                            </td>
                            <td class="creator-cell">
                                <i class="fas fa-user mr-1"></i>
                                <?= htmlspecialchars($ticket['utilisateur_nom_complet']) ?>
                            </td>

                            <td class="price-cell">
                                <?php if ($ticket['prix_unitaire'] === null || $ticket['prix_unitaire'] == 0.00): ?>
                                    <span class="status-badge status-pending">
                                        <i class="fas fa-clock mr-1"></i>
                                        En Attente
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-validated">
                                        <i class="fas fa-euro-sign mr-1"></i>
                                        <?= number_format($ticket['prix_unitaire'], 0, ',', ' ') ?> FCFA
                                    </span>
                                <?php endif; ?>
                            </td>




                            <td class="validation-cell">
                                <?php if ($ticket['date_validation_boss'] === null): ?>
                                    <span class="status-badge status-progress">
                                        <i class="fas fa-hourglass-half mr-1"></i>
                                        En cours
                                    </span>
                                <?php else: ?>
                                    <span class="date-badge validated">
                                        <i class="fas fa-check mr-1"></i>
                                        <?= date('d/m/Y', strtotime($ticket['date_validation_boss'])) ?>
                                    </span>
                                <?php endif; ?>
                            </td>


                            <td class="amount-cell">
                                <?php if ($ticket['montant_paie'] === null): ?>
                                    <span class="status-badge status-waiting">
                                        <i class="fas fa-clock mr-1"></i>
                                        En attente de PU
                                    </span>
                                <?php else: ?>
                                    <span class="amount-badge">
                                        <i class="fas fa-money-bill-wave mr-1"></i>
                                        <?= number_format($ticket['montant_paie'], 0, ',', ' ') ?> FCFA
                                    </span>
                                <?php endif; ?>
                            </td>


                            <td class="payment-cell">
                                <?php if ($ticket['date_paie'] === null): ?>
                                    <span class="status-badge status-unpaid">
                                        <i class="fas fa-times-circle mr-1"></i>
                                        Non payé
                                    </span>
                                <?php else: ?>
                                    <span class="date-badge paid">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        <?= date('d/m/Y', strtotime($ticket['date_paie'])) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
          
  
      <!--    <td class="actions">
            <a class="edit" data-toggle="modal" data-target="#editModalTicket<?= $ticket['id_ticket'] ?>">
            <i class="fas fa-pen fa-xs" style="font-size:24px;color:blue"></i>
            </a>
            <a href="delete_commandes.php?id=<?= $ticket['id_ticket'] ?>" class="trash"><i class="fas fa-trash fa-xs" style="font-size:24px;color:red"></i></a>
          </td>-->

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


                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" class="no-data-cell">
                            <div class="no-data-content">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <h5>Aucun ticket trouvé</h5>
                                <p class="text-muted">Il n'y a pas de tickets payés correspondant à vos critères de recherche.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination professionnelle -->
<div class="pagination-container-pro fade-in-up">
    <div class="pagination-wrapper">
        <div class="pagination-info">
            <span class="pagination-stats">
                <i class="fas fa-info-circle mr-2"></i>
                Affichage de <?= (($page - 1) * $limit) + 1 ?> à <?= min($page * $limit, count($tickets)) ?> 
                sur <?= count($tickets) ?> tickets
            </span>
        </div>
        
        <div class="pagination-controls">
            <div class="pagination-nav">
                <?php if($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" 
                       class="pagination-btn pagination-first" title="Première page">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                       class="pagination-btn pagination-prev" title="Page précédente">
                        <i class="fas fa-angle-left"></i>
                    </a>
                <?php else: ?>
                    <span class="pagination-btn pagination-disabled">
                        <i class="fas fa-angle-double-left"></i>
                    </span>
                    <span class="pagination-btn pagination-disabled">
                        <i class="fas fa-angle-left"></i>
                    </span>
                <?php endif; ?>
                
                <div class="pagination-current">
                    <span class="current-page"><?= $page ?></span>
                    <span class="page-separator">/</span>
                    <span class="total-pages"><?= $total_pages ?></span>
                </div>
                
                <?php if($page < $total_pages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                       class="pagination-btn pagination-next" title="Page suivante">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>" 
                       class="pagination-btn pagination-last" title="Dernière page">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php else: ?>
                    <span class="pagination-btn pagination-disabled">
                        <i class="fas fa-angle-right"></i>
                    </span>
                    <span class="pagination-btn pagination-disabled">
                        <i class="fas fa-angle-double-right"></i>
                    </span>
                <?php endif; ?>
            </div>
            
            <form action="" method="get" class="items-per-page-form-pro">
                <?php
                // Preserve existing GET parameters
                foreach ($_GET as $key => $value) {
                    if ($key !== 'limit') {
                        echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                    }
                }
                ?>
                <div class="items-control">
                    <label for="limit" class="items-label">
                        <i class="fas fa-list mr-1"></i>
                        Éléments par page :
                    </label>
                    <select name="limit" id="limit" class="items-select">
                        <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>5</option>
                        <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                        <option value="15" <?= $limit == 15 ? 'selected' : '' ?>>15</option>
                        <option value="20" <?= $limit == 20 ? 'selected' : '' ?>>20</option>
                    </select>
                    <button type="submit" class="items-apply-btn">
                        <i class="fas fa-check"></i>
                        Appliquer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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
                  <label>Selection chef Equipe</label>
                  <select id="select" name="chef_equipe" class="form-control">
                      <?php
                      // Vérifier si des usines existent
                      if (!empty($chefs_equipes)) {
                          foreach ($chefs_equipes as $chefs_equipe) {
                              echo '<option value="' . htmlspecialchars($chefs_equipe['id_chef']) . '">' . htmlspecialchars($chefs_equipe['chef_nom_complet']) . '</option>';
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

<script>
function appliquerFiltres() {
    const agent_id = document.getElementById('agent_select').value;
    const usine_id = document.getElementById('usine_select').value;
    const date_debut = document.getElementById('date_debut').value;
    const date_fin = document.getElementById('date_fin').value;
    const statut = document.getElementById('statut_select').value;
    
    let params = new URLSearchParams(window.location.search);
    
    if (statut) params.set('statut', statut);
    else params.delete('statut');
    
    if (agent_id) params.set('agent_id', agent_id);
    else params.delete('agent_id');
    
    if (usine_id) params.set('usine_id', usine_id);
    else params.delete('usine_id');
    
    if (date_debut) params.set('date_debut', date_debut);
    else params.delete('date_debut');
    
    if (date_fin) params.set('date_fin', date_fin);
    else params.delete('date_fin');
    
    window.location.href = '?' + params.toString();
}

$(document).ready(function() {
    // Initialiser les sélecteurs avec Select2
    $('#agent_select, #usine_select, #statut_select').select2({
        placeholder: 'Sélectionner...',
        allowClear: true,
        theme: 'bootstrap4'
    });
    
    // Gestion du toggle des filtres
    const filtersToggle = document.getElementById('filtersToggle');
    const filtersContent = document.getElementById('filtersContent');
    
    // Ouvrir les filtres par défaut si des filtres sont actifs
    const hasActiveFilters = <?= json_encode($agent_id || $usine_id || $date_debut || $date_fin || $statut || !empty($_GET['search_quick'])) ?>;
    if (hasActiveFilters) {
        filtersContent.classList.add('active');
        filtersToggle.classList.add('active');
    }
    
    filtersToggle.addEventListener('click', function() {
        filtersContent.classList.toggle('active');
        filtersToggle.classList.toggle('active');
    });
    
    // Gestion de la sauvegarde des filtres
    document.getElementById('saveFilters').addEventListener('click', function() {
        const currentUrl = new URL(window.location.href);
        const filterParams = currentUrl.search;
        
        // Sauvegarder dans localStorage
        localStorage.setItem('tickets_payes_filters', filterParams);
        
        // Notification de succès
        toastr.success('🔖 Filtres sauvegardés avec succès!', 'Sauvegarde', {
            timeOut: 3000,
            progressBar: true,
            positionClass: 'toast-top-right'
        });
    });
    
    // Charger les filtres sauvegardés au chargement de la page
    const savedFilters = localStorage.getItem('tickets_payes_filters');
    if (savedFilters && !window.location.search) {
        // Ajouter un bouton pour charger les filtres sauvegardés
        const loadFiltersBtn = document.createElement('button');
        loadFiltersBtn.className = 'btn-load-saved-filters';
        loadFiltersBtn.innerHTML = '<i class="fas fa-bookmark mr-2"></i>Charger les filtres sauvegardés';
        loadFiltersBtn.onclick = function() {
            window.location.href = window.location.pathname + savedFilters;
        };
        
        // Insérer le bouton dans les actions des filtres
        const filtersActions = document.querySelector('.filters-actions');
        if (filtersActions) {
            filtersActions.appendChild(loadFiltersBtn);
        }
    }
    
    // Animation d'entrée pour les éléments
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
    
    // Observer tous les éléments avec la classe fade-in-up
    document.querySelectorAll('.fade-in-up').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s ease-out';
        observer.observe(el);
    });
    
    // Amélioration de l'UX pour la recherche rapide
    const searchInput = document.getElementById('search_quick');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchIcon = this.nextElementSibling;
            
            if (this.value.length > 0) {
                searchIcon.className = 'fas fa-times search-icon';
                searchIcon.style.cursor = 'pointer';
                searchIcon.onclick = function() {
                    searchInput.value = '';
                    searchIcon.className = 'fas fa-search search-icon';
                    searchIcon.style.cursor = 'default';
                    searchIcon.onclick = null;
                };
            } else {
                searchIcon.className = 'fas fa-search search-icon';
                searchIcon.style.cursor = 'default';
                searchIcon.onclick = null;
            }
        });
    }
    
    // Validation des dates
    const dateDebut = document.getElementById('date_debut');
    const dateFin = document.getElementById('date_fin');
    
    if (dateDebut && dateFin) {
        dateDebut.addEventListener('change', function() {
            if (dateFin.value && this.value > dateFin.value) {
                toastr.warning('⚠️ La date de début ne peut pas être postérieure à la date de fin', 'Attention', {
                    timeOut: 4000,
                    progressBar: true
                });
                this.value = '';
            }
        });
        
        dateFin.addEventListener('change', function() {
            if (dateDebut.value && this.value < dateDebut.value) {
                toastr.warning('⚠️ La date de fin ne peut pas être antérieure à la date de début', 'Attention', {
                    timeOut: 4000,
                    progressBar: true
                });
                this.value = '';
            }
        });
    }
});

// Style pour le bouton de chargement des filtres sauvegardés
const style = document.createElement('style');
style.textContent = `
.btn-load-saved-filters {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    opacity: 0.9;
}

.btn-load-saved-filters:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    opacity: 1;
}
`;
document.head.appendChild(style);
</script>
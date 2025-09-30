<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_tickets.php';
require_once '../inc/functions/requete/requete_usines.php';
require_once '../inc/functions/requete/requete_chef_equipes.php';
require_once '../inc/functions/requete/requete_vehicules.php';
require_once '../inc/functions/requete/requete_agents.php';

include('header_operateurs.php');

// Récupérer l'ID de l'utilisateur connecté
$id_user = $_SESSION['user_id'];

$agents = getAgents($conn);
$usines = getUsines($conn);
$vehicules = getVehicules($conn);

// Paramètres de pagination
$limit = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Initialisation des filtres
$filters = [];

// Ajouter le filtre par utilisateur connecté
$filters['utilisateur'] = $id_user;

// Récupération du numéro de ticket depuis l'URL
if (isset($_GET['numero_ticket']) && !empty($_GET['numero_ticket'])) {
    $filters['numero_ticket'] = $_GET['numero_ticket'];
}

// Récupération des filtres depuis l'URL
if (isset($_GET['agent']) && !empty($_GET['agent'])) {
    $filters['agent'] = $_GET['agent'];
}

if (isset($_GET['usine']) && !empty($_GET['usine'])) {
    $filters['usine'] = $_GET['usine'];
}

if (isset($_GET['vehicule']) && !empty($_GET['vehicule'])) {
    $filters['vehicule'] = $_GET['vehicule'];
}

if (isset($_GET['date_debut']) && !empty($_GET['date_debut'])) {
    $filters['date_debut'] = $_GET['date_debut'];
}

if (isset($_GET['date_fin']) && !empty($_GET['date_fin'])) {
    $filters['date_fin'] = $_GET['date_fin'];
}

// Récupération des tickets filtrés
$tickets = getTickets($conn, $filters);

// Calcul de la pagination
$total_tickets = count($tickets);
$total_pages = ceil($total_tickets / $limit);
$page = max(1, min($page, $total_pages));
$offset = ($page - 1) * $limit;
$tickets_list = array_slice($tickets, $offset, $limit);
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
    text-align: center;
    margin-bottom: 2rem;
    color: white;
    animation: fadeInDown 1s ease-out;
}

.page-header h1 {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.page-header .subtitle {
    font-size: 1.2rem;
    opacity: 0.9;
    font-weight: 300;
}

.unipalm-logo {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 2.5rem;
    font-weight: 700;
    color: white;
    text-decoration: none;
    margin-bottom: 1rem;
}

.unipalm-logo i {
    color: #4ade80;
    filter: drop-shadow(0 0 10px rgba(74, 222, 128, 0.5));
}

/* Form styling */
.search-form {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 15px;
    padding: 2rem;
    box-shadow: var(--shadow-medium);
    margin-bottom: 2rem;
}

.form-label {
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
}

.form-control, .form-select {
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn {
    border-radius: 10px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

/* Table styling */
.table-container {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: var(--shadow-medium);
    overflow: hidden;
}

.table {
    margin-bottom: 0;
}

.table thead th {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border: none;
    padding: 1rem 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.table tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid #e5e7eb;
}

.table tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
}

.status-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-success {
    background: rgba(74, 222, 128, 0.2);
    color: #059669;
}

.status-warning {
    background: rgba(251, 191, 36, 0.2);
    color: #d97706;
}

.status-danger {
    background: rgba(248, 113, 113, 0.2);
    color: #dc2626;
}

.status-info {
    background: rgba(96, 165, 250, 0.2);
    color: #2563eb;
}

.status-dark {
    background: rgba(31, 41, 55, 0.2);
    color: #1f2937;
}

/* Pagination */
.pagination-container {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border-radius: 15px;
    padding: 1.5rem;
    margin-top: 2rem;
    border: 1px solid var(--glass-border);
}

.pagination-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.pagination-link:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
    color: white;
}

/* Loader */
.loader {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 3rem;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top: 4px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .page-header h1 {
        font-size: 2rem;
    }
    
    .glass-container {
        padding: 1rem;
        margin: 0.5rem;
    }
    
    .search-form {
        padding: 1rem;
    }
}
</style>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="page-header">
        <div class="unipalm-logo">
            <i class="fas fa-leaf"></i>
            UniPalm
        </div>
        <h1 class="animate__animated animate__fadeInDown">Recherche Avancée</h1>
        <p class="subtitle">Recherchez vos tickets avec des critères précis</p>
    </div>

    <!-- Search Form -->
    <div class="search-form animate__animated animate__fadeInUp">
        <h3 class="mb-4">
            <i class="fas fa-search me-2"></i>
            Critères de recherche
        </h3>
        <form id="searchForm" action="" method="GET">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="numero_ticket" class="form-label">
                        <i class="fas fa-ticket-alt me-2"></i>Numéro de ticket
                    </label>
                    <input type="text" class="form-control" name="numero_ticket" id="numero_ticket" 
                           value="<?= isset($_GET['numero_ticket']) ? htmlspecialchars($_GET['numero_ticket']) : '' ?>" 
                           placeholder="Entrez un numéro">
                    <input type="hidden" name="page" value="1">
                </div>
                <div class="col-md-3">
                    <label for="agent" class="form-label">
                        <i class="fas fa-user-tie me-2"></i>Agent
                    </label>
                    <select class="form-select" name="agent" id="agent">
                        <option value="">Tous les agents</option>
                        <?php foreach($agents as $agent): ?>
                            <option value="<?= $agent['id_agent'] ?>" 
                                <?= (isset($_GET['agent']) && $_GET['agent'] == $agent['id_agent']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($agent['nom_complet_agent']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="usine" class="form-label">
                        <i class="fas fa-industry me-2"></i>Usine
                    </label>
                    <select class="form-select" name="usine" id="usine">
                        <option value="">Toutes les usines</option>
                        <?php foreach($usines as $usine): ?>
                            <option value="<?= $usine['id_usine'] ?>" 
                                <?= (isset($_GET['usine']) && $_GET['usine'] == $usine['id_usine']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($usine['nom_usine']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="vehicule" class="form-label">
                        <i class="fas fa-truck me-2"></i>Véhicule
                    </label>
                    <select class="form-select" name="vehicule" id="vehicule">
                        <option value="">Tous les véhicules</option>
                        <?php foreach($vehicules as $vehicule): ?>
                            <option value="<?= $vehicule['vehicules_id'] ?>" 
                                <?= (isset($_GET['vehicule']) && $_GET['vehicule'] == $vehicule['vehicules_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($vehicule['matricule_vehicule']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-3">
                    <label for="date_debut" class="form-label">
                        <i class="fas fa-calendar-alt me-2"></i>Date début
                    </label>
                    <input type="date" class="form-control" id="date_debut" name="date_debut" 
                           value="<?= isset($_GET['date_debut']) ? htmlspecialchars($_GET['date_debut']) : '' ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_fin" class="form-label">
                        <i class="fas fa-calendar-check me-2"></i>Date fin
                    </label>
                    <input type="date" class="form-control" id="date_fin" name="date_fin" 
                           value="<?= isset($_GET['date_fin']) ? htmlspecialchars($_GET['date_fin']) : '' ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Rechercher
                        </button>
                        <a href="recherche_trie.php" class="btn btn-secondary">
                            <i class="fas fa-redo me-2"></i>Réinitialiser
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Loader -->
    <div id="loader" class="loader" style="display: none;">
        <div class="spinner"></div>
        <p class="text-white mt-3">Chargement des résultats...</p>
    </div>

    <!-- Results Table -->
    <div class="table-container animate__animated animate__fadeInUp" id="resultsTable">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">
                <i class="fas fa-list me-2"></i>
                Résultats de la recherche
                <span class="badge bg-primary ms-2"><?= $total_tickets ?> ticket(s)</span>
            </h3>
            <button type="button" id="printButton" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>Imprimer
            </button>
        </div>

        <div class="table-responsive">
            <table class="table" id="ticketsTable">
                <thead>
                    <tr>
                        <th><i class="fas fa-calendar me-2"></i>Date Réception</th>
                        <th><i class="fas fa-calendar-alt me-2"></i>Date Ticket</th>
                        <th><i class="fas fa-ticket-alt me-2"></i>N° Ticket</th>
                        <th><i class="fas fa-industry me-2"></i>Usine</th>
                        <th><i class="fas fa-weight me-2"></i>Poids</th>
                        <th><i class="fas fa-coins me-2"></i>Prix Unitaire</th>
                        <th><i class="fas fa-user-tie me-2"></i>Agent</th>
                        <th><i class="fas fa-truck me-2"></i>Véhicule</th>
                        <th><i class="fas fa-cogs me-2"></i>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tickets_list)): ?>
                        <?php foreach ($tickets_list as $ticket): ?>
                            <tr>
                                <td><?= isset($ticket['created_at']) ? date('d/m/Y', strtotime($ticket['created_at'])) : '01/01/1970' ?></td>
                                <td><?= isset($ticket['date_ticket']) ? date('d/m/Y', strtotime($ticket['date_ticket'])) : '-' ?></td>
                                <td>
                                    <span class="badge bg-primary"><?= $ticket['numero_ticket'] ?></span>
                                </td>
                                <td><?= $ticket['nom_usine'] ?></td>
                                <td><strong><?= $ticket['poids'] ?> kg</strong></td>
                                <td>
                                    <?php if (!isset($ticket['prix_unitaire']) || $ticket['prix_unitaire'] === null || $ticket['prix_unitaire'] == 0.00): ?>
                                        <span class="status-badge status-danger">
                                            <i class="fas fa-clock me-1"></i>En Attente
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-success">
                                            <?= $ticket['prix_unitaire'] ?> FCFA
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= isset($ticket['nom_complet_agent']) ? $ticket['nom_complet_agent'] : '-' ?></td>
                                <td>
                                    <?php if (isset($ticket['matricule_vehicule'])): ?>
                                        <span class="badge bg-info"><?= $ticket['matricule_vehicule'] ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#ticketModal<?= $ticket['id_ticket'] ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    <h5>Aucun ticket trouvé</h5>
                                    <p>Aucun résultat ne correspond à vos critères de recherche.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <div class="d-flex align-items-center justify-content-between w-100 flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <?php if($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="pagination-link">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <span class="text-white mx-3">
                        <i class="fas fa-file-alt me-2"></i>
                        Page <strong><?= $page ?></strong> sur <strong><?= $total_pages ?></strong>
                    </span>
                    
                    <?php if($page < $total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="pagination-link">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="text-white">
                    <i class="fas fa-list me-2"></i>
                    Affichage de <?= (($page - 1) * $limit) + 1 ?> à <?= min($page * $limit, $total_tickets) ?> sur <?= $total_tickets ?> résultats
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Ticket Detail Modals -->
<?php if (!empty($tickets_list)): ?>
    <?php foreach ($tickets_list as $ticket): ?>
        <div class="modal fade" id="ticketModal<?= $ticket['id_ticket'] ?>" tabindex="-1" aria-labelledby="ticketModalLabel<?= $ticket['id_ticket'] ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
                        <h5 class="modal-title text-white" id="ticketModalLabel<?= $ticket['id_ticket'] ?>">
                            <i class="fas fa-ticket-alt me-2"></i>
                            Détails du ticket #<?= $ticket['numero_ticket'] ?>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-primary">
                                            <i class="fas fa-info-circle me-2"></i>Informations générales
                                        </h6>
                                        <p><strong>Date de réception:</strong> <?= isset($ticket['created_at']) ? date('d/m/Y H:i', strtotime($ticket['created_at'])) : '01/01/1970' ?></p>
                                        <p><strong>Date du ticket:</strong> <?= isset($ticket['date_ticket']) ? date('d/m/Y', strtotime($ticket['date_ticket'])) : '-' ?></p>
                                        <p><strong>Numéro de ticket:</strong> <span class="badge bg-primary"><?= $ticket['numero_ticket'] ?></span></p>
                                        <p><strong>Usine:</strong> <?= $ticket['nom_usine'] ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-success">
                                            <i class="fas fa-chart-line me-2"></i>Détails techniques
                                        </h6>
                                        <p><strong>Poids:</strong> <span class="text-success"><?= $ticket['poids'] ?> kg</span></p>
                                        <p><strong>Prix unitaire:</strong> 
                                            <?php if (!isset($ticket['prix_unitaire']) || $ticket['prix_unitaire'] === null || $ticket['prix_unitaire'] == 0.00): ?>
                                                <span class="badge bg-warning">En attente</span>
                                            <?php else: ?>
                                                <span class="text-success"><?= $ticket['prix_unitaire'] ?> FCFA</span>
                                            <?php endif; ?>
                                        </p>
                                        <p><strong>Agent:</strong> <?= isset($ticket['nom_complet_agent']) ? $ticket['nom_complet_agent'] : '-' ?></p>
                                        <p><strong>Véhicule:</strong> 
                                            <?php if (isset($ticket['matricule_vehicule'])): ?>
                                                <span class="badge bg-info"><?= $ticket['matricule_vehicule'] ?></span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du formulaire de recherche
    const searchForm = document.getElementById('searchForm');
    const loader = document.getElementById('loader');
    const resultsTable = document.getElementById('resultsTable');
    
    searchForm.addEventListener('submit', function(e) {
        // Afficher le loader
        loader.style.display = 'flex';
        resultsTable.style.opacity = '0.5';
        
        // Simuler un délai de chargement
        setTimeout(function() {
            loader.style.display = 'none';
            resultsTable.style.opacity = '1';
        }, 1000);
    });
    
    // Gestion du bouton d'impression
    document.getElementById('printButton').addEventListener('click', function() {
        const params = new URLSearchParams(window.location.search);
        const printUrl = 'imprimer_recherche.php?' + params.toString();
        window.open(printUrl, '_blank', 'width=800,height=600');
    });
    
    // Animation des cartes au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate__animated', 'animate__fadeInUp');
            }
        });
    }, observerOptions);
    
    // Observer les éléments
    document.querySelectorAll('.table-container, .search-form').forEach(el => {
        observer.observe(el);
    });
});
</script>

</body>
</html>

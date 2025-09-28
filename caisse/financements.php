<?php
require_once '../inc/functions/connexion.php';
//require_once '../inc/functions/requete/requetes_selection_boutique.php';
include('header_caisse.php');


//$_SESSION['user_id'] = $user['id'];
$id_user=$_SESSION['user_id'];
//echo $id_user;

////$stmt = $conn->prepare("SELECT * FROM users");
$sql_agents = "SELECT id_agent, CONCAT(nom, ' ', prenom) as nom_complet FROM agents ORDER BY nom, prenom";
$stmt_agents = $conn->prepare($sql_agents);
$stmt_agents->execute();
$agents = $stmt_agents->fetchAll(PDO::FETCH_ASSOC);

// Récupération des agents avec leurs montants totaux de financement
$sql_totaux = "SELECT 
    a.id_agent,
    CONCAT(a.nom, ' ', a.prenom) AS nom_agent,
    COALESCE(SUM(f.montant), 0) AS montant_total,
    COALESCE(COUNT(f.Numero_financement), 0) AS nombre_financements
FROM agents a
LEFT JOIN financement f ON a.id_agent = f.id_agent
GROUP BY a.id_agent, a.nom, a.prenom
ORDER BY montant_total DESC, a.nom, a.prenom";
$stmt_totaux = $conn->prepare($sql_totaux);
$stmt_totaux->execute();
$agents_financements = $stmt_totaux->fetchAll(PDO::FETCH_ASSOC);

// Récupération des financements avec les noms des agents
$sql = "SELECT f.*, CONCAT(a.nom, ' ', a.prenom) as nom_agent 
       FROM financement f 
       INNER JOIN agents a ON f.id_agent = a.id_agent 
       ORDER BY f.Numero_financement DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$financements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiser les financements par agent
$financements_par_agent = [];
foreach ($financements as $financement) {
    $id_agent = $financement['id_agent'];
    if (!isset($financements_par_agent[$id_agent])) {
        $financements_par_agent[$id_agent] = [];
    }
    $financements_par_agent[$id_agent][] = $financement;
}


//$usines = getUsines($conn);
//$chefs_equipes=getChefEquipes($conn);
//$vehicules=getVehicules($conn);
//$agents=getAgents($conn);



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
    /* ===== STYLES ULTRA-PROFESSIONNELS POUR FINANCEMENTS ===== */
    
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

    .btn-add {
        background: var(--success-gradient);
        color: white;
        box-shadow: 0 4px 15px rgba(86, 171, 47, 0.3);
    }

    .btn-print {
        background: var(--danger-gradient);
        color: white;
        box-shadow: 0 4px 15px rgba(255, 65, 108, 0.3);
    }

    .btn-search {
        background: var(--info-gradient);
        color: white;
        box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
    }

    .btn-export {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(44, 62, 80, 0.3);
    }

    /* Statistics Cards */
    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: var(--glass-bg);
        backdrop-filter: blur(15px);
        border: 1px solid var(--glass-border);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--shadow-light);
        position: relative;
        overflow: hidden;
        transition: var(--transition);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-heavy);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: var(--primary-gradient);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        margin-bottom: 1rem;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        color: #7f8c8d;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
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

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f8f9fa;
    }

    .table-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 0.5rem;
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
        padding: 1rem;
        border-bottom: 1px solid #f8f9fa;
        font-size: 0.95rem;
        vertical-align: middle;
        text-align: center;
    }

    .table-professional tbody tr:last-child td {
        border-bottom: none;
    }

    /* Agent Links */
    .agent-link {
        color: #2c3e50;
        font-weight: 600;
        text-decoration: none;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .agent-link:hover {
        color: #667eea;
        text-decoration: none;
        transform: scale(1.05);
    }

    /* Price Display */
    .price-display {
        font-weight: 700;
        color: #27ae60;
        font-size: 1.1rem;
    }

    .count-badge {
        background: var(--info-gradient);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
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

    /* Modal Improvements */
    .modal-content {
        border: none;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-heavy);
        backdrop-filter: blur(15px);
    }

    .modal-header {
        background: var(--primary-gradient);
        color: white;
        border-radius: var(--border-radius) var(--border-radius) 0 0;
        padding: 1.5rem;
    }

    .modal-title {
        font-weight: 700;
        font-size: 1.2rem;
    }

    .modal-body {
        padding: 2rem;
    }

    .form-control {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 12px 16px;
        font-size: 0.95rem;
        transition: var(--transition);
        background: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }

    /* Styles pour les champs de montant */
    .input-group-text {
        background: var(--primary-gradient);
        color: white;
        border: none;
        font-weight: 600;
    }

    .form-control.focused {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-text {
        font-size: 0.8rem;
        margin-top: 0.5rem;
    }

    /* Animation pour les labels */
    .form-group label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
        transition: var(--transition);
    }

    .form-group:focus-within label {
        color: #667eea;
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

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
        100% {
            transform: scale(1);
        }
    }

    .animate-fade-in-up {
        animation: fadeInUp 0.6s ease-out forwards;
    }

    .animate-fade-in {
        animation: fadeIn 0.4s ease-out forwards;
    }

    .animate-pulse {
        animation: pulse 2s infinite;
    }

    /* Hover effects */
    .stat-card:hover .stat-icon {
        animation: pulse 1s ease-in-out;
    }

    /* Loading states */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
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
        
        .stats-container {
            grid-template-columns: 1fr;
        }

        .table-header {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
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

        .stats-container {
            grid-template-columns: repeat(2, 1fr);
        }

        .stat-card {
            padding: 1rem;
        }

        .stat-value {
            font-size: 1.5rem;
        }
    }
</style>


<!-- Page Header -->
<div class="page-header">
    <h1><i class="fas fa-money-bill-wave mr-3"></i>Gestion des Financements</h1>
    <p>Gérez et suivez les financements accordés aux agents avec des outils d'analyse avancés</p>
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
        Financement enregistré avec succès
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

<!-- Statistics Cards -->
<?php
$total_financements = array_sum(array_column($agents_financements, 'nombre_financements'));
$total_montant = array_sum(array_column($agents_financements, 'montant_total'));
$nb_agents_finances = count(array_filter($agents_financements, function($agent) { return $agent['nombre_financements'] > 0; }));
$montant_moyen = $nb_agents_finances > 0 ? $total_montant / $nb_agents_finances : 0;
?>

<div class="stats-container">
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--success-gradient);">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-value"><?= number_format($total_montant, 0, ',', ' ') ?></div>
        <div class="stat-label">Montant Total (FCFA)</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--info-gradient);">
            <i class="fas fa-list-ol"></i>
        </div>
        <div class="stat-value"><?= $total_financements ?></div>
        <div class="stat-label">Total Financements</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--warning-gradient);">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-value"><?= $nb_agents_finances ?></div>
        <div class="stat-label">Agents Financés</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--danger-gradient);">
            <i class="fas fa-calculator"></i>
        </div>
        <div class="stat-value"><?= number_format($montant_moyen, 0, ',', ' ') ?></div>
        <div class="stat-label">Montant Moyen (FCFA)</div>
    </div>
</div>

<!-- Action Buttons -->
<div class="action-buttons-container">
    <div class="d-flex gap-3 flex-wrap">
        <button type="button" class="btn-professional btn-add" data-toggle="modal" data-target="#add-financement">
            <i class="fas fa-plus"></i>
            Nouveau Financement
        </button>

        <button type="button" class="btn-professional btn-print" disabled style="cursor: not-allowed; opacity: 0.6;">
            <i class="fas fa-print"></i>
            Imprimer la Liste
        </button>

        <button type="button" class="btn-professional btn-search" disabled style="cursor: not-allowed; opacity: 0.6;">
            <i class="fas fa-search"></i>
            Rechercher
        </button>

        <button type="button" class="btn-professional btn-export" disabled style="cursor: not-allowed; opacity: 0.6;">
            <i class="fas fa-download"></i>
            Exporter
        </button>
    </div>
    <div class="d-flex align-items-center">
        <span class="badge badge-info">
            <i class="fas fa-database mr-1"></i>
            <?= count($agents_financements) ?> agents
        </span>
    </div>
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




<!-- Table Container -->
<div class="table-container">
    <div class="table-header">
        <div class="table-title">
            <i class="fas fa-chart-bar"></i>
            Résumé des Financements par Agent
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-info btn-sm" data-toggle="modal" data-target="#listeDetaillee">
                <i class="fas fa-list"></i> Liste détaillée
            </button>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table-professional w-100">
            <thead>
                <tr>
                    <th><i class="fas fa-user-tie mr-2"></i>Agent</th>
                    <th><i class="fas fa-hashtag mr-2"></i>Nb Financements</th>
                    <th><i class="fas fa-money-bill-wave mr-2"></i>Montant Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($agents_financements)): ?>
                    <?php foreach ($agents_financements as $agent): ?>
                    <tr>
                        <td data-label="Agent">
                            <a href="#" class="agent-link" data-toggle="modal" data-target="#detailsModal<?= $agent['id_agent'] ?>">
                                <i class="fas fa-user-circle"></i>
                                <?= htmlspecialchars($agent['nom_agent']) ?>
                            </a>
                        </td>
                        <td data-label="Nb Financements">
                            <span class="count-badge">
                                <?= $agent['nombre_financements'] ?>
                            </span>
                        </td>
                        <td data-label="Montant Total">
                            <span class="price-display">
                                <?= number_format($agent['montant_total'], 0, ',', ' ') ?> FCFA
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center py-5">
                            <div class="d-flex flex-column align-items-center">
                                <i class="fas fa-money-bill-wave text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                                <h5 class="text-muted mb-2">Aucun financement trouvé</h5>
                                <p class="text-muted mb-3">Commencez par ajouter un nouveau financement</p>
                                <button type="button" class="btn-professional btn-add" data-toggle="modal" data-target="#add-financement">
                                    <i class="fas fa-plus mr-2"></i>Nouveau Financement
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>




<div class="modal fade" id="add-financement" tabindex="-1" role="dialog" aria-labelledby="addFinancementModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addFinancementModalLabel">Nouveau Financement</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="add_financements.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="id_agent">Agent</label>
                        <select class="form-control" id="id_agent" name="id_agent" required>
                            <option value="">Sélectionner un agent</option>
                            <?php foreach ($agents as $agent): ?>
                            <option value="<?= $agent['id_agent'] ?>"><?= htmlspecialchars($agent['nom_complet']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="montant">Montant (FCFA)</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="montant" name="montant" 
                                   placeholder="Saisissez le montant..." 
                                   autocomplete="off" required>
                            <div class="input-group-append">
                                <span class="input-group-text">FCFA</span>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle mr-1"></i>
                            Le montant sera automatiquement formaté
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="motif">Motif</label>
                        <textarea class="form-control" id="motif" name="motif" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Modal Modification Financement -->
<div class="modal fade" id="editFinancementModal" tabindex="-1" role="dialog" aria-labelledby="editFinancementModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editFinancementModalLabel">Modifier Financement</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="edit_financements.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="numero_financement" id="edit_numero_financement">
                    <div class="form-group">
                        <label for="edit_id_agent">Agent</label>
                        <select class="form-control" id="edit_id_agent" name="id_agent" required>
                            <option value="">Sélectionner un agent</option>
                            <?php foreach ($agents as $agent): ?>
                            <option value="<?= $agent['id_agent'] ?>"><?= htmlspecialchars($agent['nom_complet']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_montant">Montant (FCFA)</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="edit_montant" name="montant" 
                                   placeholder="Saisissez le montant..." 
                                   autocomplete="off" required>
                            <div class="input-group-append">
                                <span class="input-group-text">FCFA</span>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle mr-1"></i>
                            Le montant sera automatiquement formaté
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="edit_motif">Motif</label>
                        <textarea class="form-control" id="edit_motif" name="motif" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                </div>
            </form>
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


</body>

</html>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-body text-center p-4">
                <div class="mb-4">
                    <div style="width: 70px; height: 70px; background-color: #4CAF50; border-radius: 50%; display: inline-flex; justify-content: center; align-items: center; margin-bottom: 20px;">
                        <i class="fas fa-check" style="font-size: 35px; color: white;"></i>
                    </div>
                    <h4 class="mb-3" style="font-weight: 600;">SUCCESS</h4>
                    <?php if (isset($_SESSION['message'])): ?>
                        <p class="mb-4"><?= $_SESSION['message'] ?></p>
                        <?php unset($_SESSION['message']); ?>
                    <?php else: ?>
                        <p class="mb-4">Ticket ajouté avec succès!</p>
                        <p style="color: #666;">Le prix unitaire pour cette période est : <strong><?= isset($_SESSION['prix_unitaire']) ? number_format($_SESSION['prix_unitaire'], 2, ',', ' ') : '0,00' ?> FCFA</strong></p>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-success px-4 py-2" data-dismiss="modal" style="min-width: 120px; border-radius: 25px;">CONTINUE</button>
            </div>
        </div>
    </div>
</div>

<!-- Warning Modal -->
<div class="modal fade" id="warningModal" tabindex="-1" role="dialog" aria-labelledby="warningModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-body text-center p-4">
                <div class="mb-4">
                    <div style="width: 70px; height: 70px; background-color: #FFC107; border-radius: 50%; display: inline-flex; justify-content: center; align-items: center; margin-bottom: 20px;">
                        <i class="fas fa-exclamation" style="font-size: 35px; color: white;"></i>
                    </div>
                    <h4 class="mb-3" style="font-weight: 600;">ATTENTION</h4>
                    <p style="color: #666;"><?= isset($_SESSION['warning']) ? $_SESSION['warning'] : '' ?></p>
                </div>
                <button type="button" class="btn btn-warning px-4 py-2" data-dismiss="modal" style="min-width: 120px; border-radius: 25px;">CONTINUE</button>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-body text-center p-4">
                <div class="mb-4">
                    <div style="width: 70px; height: 70px; background-color: #dc3545; border-radius: 50%; display: inline-flex; justify-content: center; align-items: center; margin-bottom: 20px;">
                        <i class="fas fa-times" style="font-size: 35px; color: white;"></i>
                    </div>
                    <h4 class="mb-3" style="font-weight: 600;">ERROR</h4>
                    <p style="color: #666;">Une erreur s'est produite lors de l'opération.</p>
                </div>
                <button type="button" class="btn btn-danger px-4 py-2" data-dismiss="modal" style="min-width: 120px; border-radius: 25px;">AGAIN</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation de tous les modals
    $('.modal').modal({
        keyboard: false,
        backdrop: 'static',
        show: false
    });

    // Animation des cartes statistiques au chargement
    $('.stat-card').each(function(index) {
        $(this).css('animation-delay', (index * 0.2) + 's');
        $(this).addClass('animate-fade-in-up');
    });

    // Animation des lignes du tableau
    $('.table-professional tbody tr').each(function(index) {
        $(this).css('animation-delay', (index * 0.1) + 's');
        $(this).addClass('animate-fade-in');
    });

    // Gestion des liens d'agents avec effet de chargement
    $('.agent-link').on('click', function(e) {
        const $this = $(this);
        const originalText = $this.html();
        
        $this.html('<i class="fas fa-spinner fa-spin mr-2"></i>Chargement...');
        
        // Restaurer le texte après 2 secondes (simulation)
        setTimeout(() => {
            $this.html(originalText);
        }, 2000);
    });

    // Validation du formulaire de financement (améliorée)
    $('#add-financement form').on('submit', function(e) {
        const agent = $('#id_agent').val();
        const montantField = $('#montant');
        const montantValue = montantField.attr('data-value') || montantField.val().replace(/\s/g, '');
        
        if (!agent) {
            e.preventDefault();
            alert('Veuillez sélectionner un agent');
            $('#id_agent').focus();
            return false;
        }
        
        if (!montantValue || parseInt(montantValue) <= 0) {
            e.preventDefault();
            alert('Veuillez saisir un montant valide supérieur à 0');
            montantField.focus();
            return false;
        }
        
        // Restaurer la valeur numérique avant soumission
        montantField.val(montantValue);
        
        // Animation de soumission
        $(this).find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin mr-2"></i>Enregistrement...');
    });

    // Formatage automatique des montants (amélioré)
    $('#montant, #edit_montant').on('input', function() {
        let value = $(this).val().replace(/\s/g, '');
        
        // Permettre seulement les chiffres
        value = value.replace(/[^\d]/g, '');
        
        if (value) {
            // Stocker la valeur numérique dans un attribut data
            $(this).attr('data-value', value);
            // Afficher la valeur formatée
            $(this).val(parseInt(value).toLocaleString('fr-FR'));
        } else {
            $(this).removeAttr('data-value');
            $(this).val('');
        }
    });

    // Gérer le focus pour une meilleure UX
    $('#montant, #edit_montant').on('focus', function() {
        $(this).addClass('focused');
        const numericValue = $(this).attr('data-value');
        if (numericValue) {
            $(this).val(numericValue);
        }
    });

    $('#montant, #edit_montant').on('blur', function() {
        $(this).removeClass('focused');
        const value = $(this).val().replace(/\s/g, '').replace(/[^\d]/g, '');
        if (value) {
            $(this).attr('data-value', value);
            $(this).val(parseInt(value).toLocaleString('fr-FR'));
        }
    });

    // Avant soumission, restaurer la valeur numérique
    $('#add-financement form, #editFinancementModal form').on('submit', function(e) {
        $('#montant, #edit_montant').each(function() {
            const numericValue = $(this).attr('data-value');
            if (numericValue) {
                $(this).val(numericValue);
            }
        });
    });

    // Statistiques animées (compteur)
    $('.stat-value').each(function() {
        const $this = $(this);
        const finalValue = parseInt($this.text().replace(/\s/g, ''));
        let currentValue = 0;
        const increment = finalValue / 50;
        
        const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
                currentValue = finalValue;
                clearInterval(timer);
            }
            $this.text(Math.floor(currentValue).toLocaleString('fr-FR'));
        }, 30);
    });

    // Gestion de la suppression avec confirmation moderne
    $('.trash, .btn-delete').click(function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Cette action ne peut pas être annulée !",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer !',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                // Procéder à la suppression
                window.location.href = $(this).attr('href') || $(this).data('href');
            }
        });
    });

    // Recherche en temps réel dans le tableau
    $('#searchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.table-professional tbody tr').each(function() {
            const rowText = $(this).text().toLowerCase();
            $(this).toggle(rowText.includes(searchTerm));
        });
    });

    console.log('✅ Page Financements initialisée avec succès');
    console.log('📊 Statistiques chargées:', {
        totalFinancements: <?= $total_financements ?>,
        totalMontant: <?= $total_montant ?>,
        agentsFinances: <?= $nb_agents_finances ?>
    });
});
</script>

<?php if (isset($_SESSION['success_modal'])): ?>
<script>
    $(document).ready(function() {
        $('#successModal').modal('show');
        var audio = new Audio("../inc/sons/notification.mp3");
        audio.volume = 1.0;
        audio.play().catch((error) => {
            console.error('Erreur de lecture audio :', error);
        });
    });
</script>
<?php 
    unset($_SESSION['success_modal']);
    unset($_SESSION['prix_unitaire']);
endif; ?>

<?php if (isset($_SESSION['warning'])): ?>
<script>
    $(document).ready(function() {
        $('#warningModal').modal('show');
    });
</script>
<?php 
    unset($_SESSION['warning']);
endif; ?>

<?php if (isset($_SESSION['delete_pop'])): ?>
<script>
    $(document).ready(function() {
        $('#errorModal').modal('show');
    });
</script>
<?php 
    unset($_SESSION['delete_pop']);
endif; ?>
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

<script>
// Code pour le modal de modification
$(document).ready(function() {
    $('.edit-btn').click(function() {
        var numero = $(this).data('numero');
        var agent = $(this).data('agent');
        var montant = $(this).data('montant');
        var motif = $(this).data('motif');

        $('#edit_numero_financement').val(numero);
        $('#edit_id_agent').val(agent);
        $('#edit_montant').val(montant);
        $('#edit_motif').val(motif);
    });
});
</script>

<?php foreach ($agents_financements as $agent): ?>
    <div class="modal fade" id="detailsModal<?= $agent['id_agent'] ?>" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détails des financements - <?= htmlspecialchars($agent['nom_agent']) ?></h5>
                    <div class="ml-auto">
                        <button type="button" class="btn btn-danger mr-2" onclick="window.open('print_details_financements.php?id_agent=<?= $agent['id_agent'] ?>', '_blank')">
                            <i class="fas fa-print"></i> Imprimer
                        </button>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="detailsTable<?= $agent['id_agent'] ?>">
                            <thead>
                                <tr>
                                    <th>N° Financement</th>
                                    <th>Agent</th>
                                    <th>Date</th>
                                    <th class="text-right">Montant</th>
                                    <th>Motif</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($financements_par_agent[$agent['id_agent']])): ?>
                                    <?php foreach ($financements_par_agent[$agent['id_agent']] as $financement): ?>
                                    <tr>
                                        <td><?= $financement['Numero_financement'] ?></td>
                                        <td><?= htmlspecialchars($financement['nom_agent']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($financement['date_financement'])) ?></td>
                                        <td class="text-right"><?= number_format($financement['montant'], 0, ',', ' ') ?> FCFA</td>
                                        <td><?= htmlspecialchars($financement['motif']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Aucun financement trouvé pour cet agent.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-right">Total:</th>
                                    <th class="text-right"><?= number_format($agent['montant_total'], 0, ',', ' ') ?> FCFA</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
function printDetailsFinancements(agentId, agentNom) {
    var table = $('#detailsTable' + agentId);
    var total = 0;
    
    // Créer une nouvelle fenêtre pour l'impression
    var printWindow = window.open('', '_blank');
    var html = `
        <html>
        <head>
            <title>Détails des financements - ${agentNom}</title>
            <style>
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f4f4f4; }
                .header { text-align: center; margin-bottom: 20px; }
                .date { text-align: right; margin-bottom: 20px; }
                @media print {
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>UNIPALM</h2>
                <h3>Détails des financements - ${agentNom}</h3>
            </div>
            <div class="date">
                Date d'impression: ${new Date().toLocaleDateString()}
            </div>
            <table>
                <thead>
                    <tr>
                        <th>N° Financement</th>
                        <th>Agent</th>
                        <th>Date</th>
                        <th class="text-right">Montant</th>
                        <th>Motif</th>
                    </tr>
                </thead>
                <tbody>
    `;

    // Ajouter toutes les lignes du tableau
    table.find('tbody tr').each(function() {
        var cells = $(this).find('td');
        html += '<tr>';
        cells.each(function(index) {
            if (index < 5) { // Exclure les colonnes d'actions
                html += '<td>' + $(this).text() + '</td>';
            }
            if (index === 3) { // Colonne du montant
                total += parseInt($(this).text().replace(/[^\d]/g, ''));
            }
        });
        html += '</tr>';
    });

    html += `
                </tbody>
            </table>
            <div class="total">
                Total: ${total.toLocaleString()} FCFA
            </div>
            <div class="no-print">
                <button onclick="window.print()">Imprimer</button>
                <button onclick="window.close()">Fermer</button>
            </div>
        </body>
        </html>
    `;

    printWindow.document.write(html);
    printWindow.document.close();
}
</script>

<!-- Modal Liste détaillée des financements -->
<div class="modal fade" id="listeDetaillee" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Liste détaillée des financements</h5>
                <button type="button" class="btn btn-danger ml-2" onclick="printListeDetaillee()">
                    <i class="fas fa-print"></i> Imprimer la liste
                </button>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="tableListeDetaillee">
                        <thead>
                            <tr>
                                <th>N° Financement</th>
                                <th>Agent</th>
                                <th>Date</th>
                                <th class="text-right">Montant</th>
                                <th>Motif</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($financements as $financement): ?>
                            <tr>
                                <td><?= $financement['Numero_financement'] ?></td>
                                <td><?= htmlspecialchars($financement['nom_agent']) ?></td>
                                <td><?= date('d/m/Y', strtotime($financement['date_financement'])) ?></td>
                                <td class="text-right"><?= number_format($financement['montant'], 0, ',', ' ') ?> FCFA</td>
                                <td><?= htmlspecialchars($financement['motif']) ?></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-warning edit-btn" 
                                        data-toggle="modal" 
                                        data-target="#editFinancementModal"
                                        data-numero="<?= $financement['Numero_financement'] ?>"
                                        data-agent="<?= $financement['id_agent'] ?>"
                                        data-montant="<?= $financement['montant'] ?>"
                                        data-motif="<?= htmlspecialchars($financement['motif']) ?>">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <a href="delete_financement.php?id=<?= $financement['Numero_financement'] ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce financement ?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
function printListeDetaillee() {
    var table = $('#tableListeDetaillee').DataTable();
    var allData = table.rows().data();
    
    // Créer une nouvelle fenêtre pour l'impression
    var printWindow = window.open('', '_blank');
    var html = `
        <html>
        <head>
            <title>Liste détaillée des financements</title>
            <style>
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f4f4f4; }
                .header { text-align: center; margin-bottom: 20px; }
                .date { text-align: right; margin-bottom: 20px; }
                @media print {
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>UNIPALM</h2>
                <h3>Liste détaillée des financements</h3>
            </div>
            <div class="date">
                Date d'impression: ${new Date().toLocaleDateString()}
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>N° Ticket</th>
                        <th>Usine</th>
                        <th>Agent</th>
                        <th>Véhicule</th>
                        <th>Poids</th>
                        <th>Prix Unitaire</th>
                        <th>Montant</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
    `;

    // Ajouter toutes les lignes du tableau
    table.rows().every(function() {
        var data = this.data();
        html += '<tr>';
        for(var i = 0; i < data.length; i++) {
            // Exclure la colonne des actions
            if(i < data.length - 1) {
                html += '<td>' + data[i] + '</td>';
            }
        }
        html += '</tr>';
    });

    html += `
                </tbody>
            </table>
            <div class="no-print">
                <button onclick="window.print()">Imprimer</button>
                <button onclick="window.close()">Fermer</button>
            </div>
        </body>
        </html>
    `;

    printWindow.document.write(html);
    printWindow.document.close();
}
</script>

<script>
    $(document).ready(function() {
        $('#tableListeDetaillee').DataTable({
            "responsive": true,
            "autoWidth": false,
            "order": [[0, "desc"]],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/French.json"
            },
            "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                  "<'row'<'col-sm-12'tr>>" +
                  "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            "pagingType": "simple_numbers",
            "lengthMenu": [[15, 25, 50, -1], [15, 25, 50, "Tout"]],
            "pageLength": 15
        });
    });
</script>

<script>
    $(document).ready(function() {
        $('#tableResume').DataTable({
            "responsive": true,
            "autoWidth": false,
            "order": [[2, "desc"]],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/French.json"
            },
            "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                  "<'row'<'col-sm-12'tr>>" +
                  "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            "pagingType": "simple_numbers", 
            "lengthMenu": [[15, 25, 50, -1], [15, 25, 50, "Tout"]],
            "pageLength": 15,
            "columnDefs": [
                { "orderable": true, "targets": 0 },
                { "orderable": true, "targets": 1, "type": "numeric" },
                { "orderable": true, "targets": 2, "type": "numeric",
                  "render": function(data, type, row) {
                    if (type === 'sort') {
                        return data.replace(/[^\d]/g, '');
                    }
                    return data;
                  }
                }
            ]
        });
    });
</script>
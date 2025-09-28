<?php
require_once '../inc/functions/connexion.php';
include('header_caisse.php');

// Paramètres de pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Paramètres de filtrage
$statut = isset($_GET['statut']) ? $_GET['statut'] : 'all';
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Construction de la requête SQL
$where_conditions = [];
$params = [];

if ($statut !== 'all') {
    $where_conditions[] = "d.statut = ?";
    $params[] = $statut;
}

if ($date_debut) {
    $where_conditions[] = "DATE(d.date_demande) >= ?";
    $params[] = $date_debut;
}

if ($date_fin) {
    $where_conditions[] = "DATE(d.date_demande) <= ?";
    $params[] = $date_fin;
}

if ($search) {
    $where_conditions[] = "(d.numero_demande LIKE ? OR d.motif LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Requête pour le nombre total de demandes
$count_query = "SELECT COUNT(*) as total FROM demande_sortie d $where_clause";
$stmt = $conn->prepare($count_query);
$stmt->execute($params);
$total_rows = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_rows / $limit);

// Requête pour les demandes avec les noms des utilisateurs
$query = "SELECT d.*, 
                 u1.nom as approbateur_nom, u1.prenoms as approbateur_prenoms,
                 u2.nom as payeur_nom, u2.prenoms as payeur_prenoms
          FROM demande_sortie d 
          LEFT JOIN utilisateurs u1 ON d.approuve_par = u1.id
          LEFT JOIN utilisateurs u2 ON d.paye_par = u2.id
          $where_clause 
          ORDER BY d.date_demande DESC 
          LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour générer un numéro de demande unique
function genererNumeroDemande($conn) {
    $date = date('Ymd');
    $sql = "SELECT COUNT(*) as count FROM demande_sortie WHERE DATE(created_at) = CURDATE()";
    $stmt = $conn->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $count = $result['count'] + 1;
    return 'DEM-' . $date . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
}

// Fonctions utilitaires
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'en_attente':
            return 'warning';
        case 'approuve':
            return 'success';
        case 'rejete':
            return 'danger';
        case 'paye':
            return 'info';
        default:
            return 'secondary';
    }
}

function getStatusLabel($status) {
    switch ($status) {
        case 'en_attente':
            return 'En attente';
        case 'approuve':
            return 'Approuvé';
        case 'rejete':
            return 'Rejeté';
        case 'paye':
            return 'Payé';
        default:
            return $status;
    }
}
?>

<!-- Modal pour ticket en doublon -->
<div class="modal fade" id="ticketExistModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Attention !
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-times-circle text-danger fa-4x mb-3"></i>
                <h4 class="text-danger">Numéro de ticket en double</h4>
                <p class="mb-0">Le ticket numéro <strong id="duplicateTicketNumber"></strong> existe déjà.</p>
                <p>Veuillez utiliser un autre numéro.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Message d'erreur/succès -->
<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= $_SESSION['error'] ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= $_SESSION['success'] ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<!-- Reste du code HTML -->

<script>
$(document).ready(function() {
    // Vérification lors de la saisie
    $('input[name="numero_ticket"]').on('change', function() {
        var numero_ticket = $(this).val().trim();
        if (numero_ticket) {
            $.ajax({
                url: 'check_ticket.php',
                method: 'POST',
                data: { numero_ticket: numero_ticket },
                dataType: 'json',
                success: function(response) {
                    if (response.exists) {
                        $('#duplicateTicketNumber').text(numero_ticket);
                        $('#ticketExistModal').modal('show');
                        $('input[name="numero_ticket"]').val('');
                    }
                }
            });
        }
    });

    // Focus sur le champ après fermeture du modal
    $('#ticketExistModal').on('hidden.bs.modal', function() {
        $('input[name="numero_ticket"]').focus();
    });
});
</script>

<!-- Styles Ultra-Professionnels pour Demandes -->
<style>
    /* ===== STYLES ULTRA-PROFESSIONNELS POUR DEMANDES ===== */
    
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
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: center;
        align-items: center;
    }

    .btn-professional {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        box-shadow: var(--shadow-light);
        min-width: 200px;
        justify-content: center;
    }

    .btn-professional:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-heavy);
        text-decoration: none;
        color: white;
    }

    .btn-professional.primary {
        background: var(--primary-gradient);
        color: white;
    }

    .btn-professional.danger {
        background: var(--danger-gradient);
        color: white;
    }

    .btn-professional.success {
        background: var(--success-gradient);
        color: white;
    }

    .btn-professional.dark {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
    }

    /* Filters Container */
    .filters-container {
        background: var(--glass-bg);
        backdrop-filter: blur(15px);
        border: 1px solid var(--glass-border);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-light);
    }

    .filters-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f8f9fa;
    }

    .filters-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-reset-filters {
        padding: 0.5rem 1rem;
        background: var(--warning-gradient);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
    }

    .btn-reset-filters:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);
    }

    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        align-items: end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
    }

    .filter-actions {
        display: flex;
        gap: 0.5rem;
        align-items: end;
    }

    .btn-filter {
        padding: 0.75rem 1rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        flex: 1;
        justify-content: center;
    }

    .btn-filter.primary {
        background: var(--primary-gradient);
        color: white;
    }

    .btn-filter.secondary {
        background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
        color: white;
    }

    .btn-filter:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    /* Active Filters Indicator */
    .active-filters {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        border: 1px solid rgba(102, 126, 234, 0.2);
        border-radius: 8px;
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        color: #667eea;
    }

    .filter-tag {
        background: var(--primary-gradient);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .filter-tag .remove-filter {
        cursor: pointer;
        opacity: 0.8;
        transition: opacity 0.2s;
    }

    .filter-tag .remove-filter:hover {
        opacity: 1;
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
        padding: 1rem 0.75rem;
        border: none;
        font-size: 0.8rem;
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
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-badge.warning {
        background: linear-gradient(135deg, #f39c12 0%, #f1c40f 100%);
        color: white;
    }

    .status-badge.success {
        background: var(--success-gradient);
        color: white;
    }

    .status-badge.danger {
        background: var(--danger-gradient);
        color: white;
    }

    .status-badge.info {
        background: var(--info-gradient);
        color: white;
    }

    /* Action Buttons in Table */
    .btn-action {
        padding: 0.4rem 0.8rem;
        border: none;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        margin: 0.1rem;
    }

    .btn-action.edit {
        background: var(--info-gradient);
        color: white;
    }

    .btn-action.delete {
        background: var(--danger-gradient);
        color: white;
    }

    .btn-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    /* Date/Status Buttons */
    .status-button {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 600;
        width: 100%;
        cursor: default;
    }

    .status-button.pending {
        background: var(--danger-gradient);
        color: white;
    }

    .status-button.completed {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
    }

    /* Pagination Professional */
    .pagination-container {
        background: var(--glass-bg);
        backdrop-filter: blur(15px);
        border: 1px solid var(--glass-border);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-top: 2rem;
        box-shadow: var(--shadow-light);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .pagination-nav {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .pagination-btn {
        padding: 0.5rem 1rem;
        background: var(--primary-gradient);
        color: white;
        border: none;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
    }

    .pagination-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        color: white;
        text-decoration: none;
    }

    .pagination-info {
        padding: 0.5rem 1rem;
        color: #7f8c8d;
        font-weight: 500;
    }

    .items-per-page-form {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .items-per-page-select {
        padding: 0.5rem;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        background: white;
        font-size: 0.9rem;
    }

    .submit-button {
        padding: 0.5rem 1rem;
        background: var(--primary-gradient);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
    }

    .submit-button:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    /* Modal Improvements */
    .modal-content {
        border: none;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-heavy);
    }

    .modal-header {
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
        font-weight: 600;
        border: 2px solid #e9ecef;
    }

    .form-control.focused {
        border-color: #667eea !important;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
    }

    /* Amélioration des modaux */
    .modal-header.bg-primary {
        background: var(--primary-gradient) !important;
    }

    .modal-header.bg-info {
        background: var(--info-gradient) !important;
    }

    .input-group-text.bg-primary {
        background: var(--primary-gradient) !important;
        border-color: transparent !important;
    }

    .input-group-text.bg-info {
        background: var(--info-gradient) !important;
        border-color: transparent !important;
    }

    /* Animation pour les champs de saisie */
    .form-control {
        transition: all 0.3s ease;
    }

    .form-control:focus {
        transform: translateY(-1px);
    }

    /* Styles pour le compteur de caractères */
    .char-counter {
        font-size: 0.8rem;
        margin-top: 0.25rem;
        transition: color 0.3s ease;
    }

    .char-counter.text-warning {
        color: #f39c12 !important;
    }

    .char-counter.text-danger {
        color: #e74c3c !important;
        font-weight: 600;
    }

    .form-group label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
        transition: var(--transition);
    }

    .form-group:focus-within label {
        color: #667eea;
    }

    /* Alert Improvements */
    .alert {
        border: none;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-light);
    }

    .alert-success {
        background: linear-gradient(135deg, rgba(86, 171, 47, 0.1) 0%, rgba(168, 230, 207, 0.1) 100%);
        border-left: 4px solid #56ab2f;
        color: #2d5016;
    }

    .alert-danger {
        background: linear-gradient(135deg, rgba(255, 65, 108, 0.1) 0%, rgba(255, 75, 43, 0.1) 100%);
        border-left: 4px solid #ff416c;
        color: #8b1538;
    }

    .alert-warning {
        background: linear-gradient(135deg, rgba(240, 147, 251, 0.1) 0%, rgba(245, 87, 108, 0.1) 100%);
        border-left: 4px solid #f093fb;
        color: #7d1a7d;
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

    .animate-fade-in-up {
        animation: fadeInUp 0.6s ease-out forwards;
    }

    .animate-fade-in {
        animation: fadeIn 0.4s ease-out forwards;
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
            min-width: auto;
            width: 100%;
        }

        .filters-grid {
            grid-template-columns: 1fr;
        }

        .filter-actions {
            flex-direction: column;
        }

        .filters-header {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }

        .active-filters {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .filter-tag {
            margin-right: 0.5rem;
            margin-bottom: 0.25rem;
        }
        
        .table-container {
            padding: 1rem;
            overflow-x: auto;
        }
        
        .pagination-container {
            flex-direction: column;
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
    }
</style>


<!-- Page Header -->
<div class="page-header">
    <h1><i class="fas fa-file-invoice mr-3"></i>Gestion des Demandes de Sortie</h1>
    <p>Gérez et suivez toutes les demandes de sortie avec des outils d'analyse et de traitement avancés</p>
</div>

<!-- Alerts Section -->
<?php if (isset($_SESSION['warning'])): ?>
    <div class="alert alert-warning alert-dismissible fade show animate-fade-in" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <?= $_SESSION['warning'] ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php unset($_SESSION['warning']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['popup'])): ?>
    <div class="alert alert-success alert-dismissible fade show animate-fade-in" role="alert">
        <i class="fas fa-check-circle mr-2"></i>
        Demande enregistrée avec succès
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php unset($_SESSION['popup']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['delete_pop'])): ?>
    <div class="alert alert-danger alert-dismissible fade show animate-fade-in" role="alert">
        <i class="fas fa-times-circle mr-2"></i>
        Une erreur s'est produite lors de l'opération
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php unset($_SESSION['delete_pop']); ?>
<?php endif; ?>

<!-- Action Buttons -->
<div class="action-buttons-container">
    <button type="button" class="btn-professional primary" data-toggle="modal" data-target="#add-demande">
        <i class="fas fa-plus-circle"></i>
        Nouvelle Demande
    </button>

    <button type="button" class="btn-professional danger" data-toggle="modal" data-target="#print-bordereau">
        <i class="fas fa-print"></i>
        Imprimer Liste
    </button>
</div>

<!-- Filters Section -->
<div class="filters-container">
    <div class="filters-header">
        <div class="filters-title">
            <i class="fas fa-filter"></i>
            Filtres de Recherche
        </div>
        <button type="button" class="btn-reset-filters" onclick="resetFilters()">
            <i class="fas fa-undo"></i>
            Réinitialiser
        </button>
    </div>
    
    <form method="GET" action="" class="filters-form">
        <div class="filters-grid">
            <div class="filter-group">
                <label for="filter_statut">
                    <i class="fas fa-flag mr-1"></i>
                    Statut
                </label>
                <select name="statut" id="filter_statut" class="form-control">
                    <option value="all" <?= $statut === 'all' ? 'selected' : '' ?>>Tous les statuts</option>
                    <option value="en_attente" <?= $statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                    <option value="approuve" <?= $statut === 'approuve' ? 'selected' : '' ?>>Approuvé</option>
                    <option value="rejete" <?= $statut === 'rejete' ? 'selected' : '' ?>>Rejeté</option>
                    <option value="paye" <?= $statut === 'paye' ? 'selected' : '' ?>>Payé</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter_date_debut">
                    <i class="fas fa-calendar-alt mr-1"></i>
                    Date début
                </label>
                <input type="date" name="date_debut" id="filter_date_debut" class="form-control" value="<?= htmlspecialchars($date_debut) ?>">
            </div>
            
            <div class="filter-group">
                <label for="filter_date_fin">
                    <i class="fas fa-calendar-check mr-1"></i>
                    Date fin
                </label>
                <input type="date" name="date_fin" id="filter_date_fin" class="form-control" value="<?= htmlspecialchars($date_fin) ?>">
            </div>
            
            <div class="filter-group">
                <label for="filter_search">
                    <i class="fas fa-search mr-1"></i>
                    Recherche
                </label>
                <input type="text" name="search" id="filter_search" class="form-control" 
                       placeholder="N° demande ou motif..." value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div class="filter-group filter-actions">
                <button type="submit" class="btn-filter primary">
                    <i class="fas fa-search"></i>
                    Filtrer
                </button>
                <button type="button" class="btn-filter secondary" onclick="clearFilters()">
                    <i class="fas fa-times"></i>
                    Effacer
                </button>
            </div>
        </div>
    </form>
</div>



<!-- Active Filters Indicator -->
<?php 
$active_filters = [];
if ($statut !== 'all') $active_filters[] = 'Statut: ' . getStatusLabel($statut);
if ($date_debut) $active_filters[] = 'Début: ' . date('d/m/Y', strtotime($date_debut));
if ($date_fin) $active_filters[] = 'Fin: ' . date('d/m/Y', strtotime($date_fin));
if ($search) $active_filters[] = 'Recherche: ' . htmlspecialchars($search);
?>

<?php if (!empty($active_filters)): ?>
<div class="active-filters">
    <i class="fas fa-filter mr-2"></i>
    <strong>Filtres actifs:</strong>
    <?php foreach ($active_filters as $filter): ?>
        <span class="filter-tag">
            <?= $filter ?>
            <i class="fas fa-times remove-filter" onclick="removeFilter('<?= explode(':', $filter)[0] ?>')"></i>
        </span>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Table Container -->
<div class="table-container">
    <div class="table-header">
        <div class="table-title">
            <i class="fas fa-list-alt"></i>
            Liste des Demandes de Sortie
            <?php if (!empty($active_filters)): ?>
                <small class="text-muted">(filtrées)</small>
            <?php endif; ?>
        </div>
        <div class="badge badge-info">
            <i class="fas fa-database mr-1"></i>
            <?= count($demandes) ?> demande<?= count($demandes) > 1 ? 's' : '' ?>
            <?php if ($total_rows > count($demandes)): ?>
                <small>sur <?= $total_rows ?> total</small>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table-professional w-100">
            <thead>
                <tr>
                    <th data-label="Date"><i class="fas fa-calendar mr-1"></i>Date</th>
                    <th data-label="N° Demande"><i class="fas fa-hashtag mr-1"></i>N° Demande</th>
                    <th data-label="Montant"><i class="fas fa-money-bill-wave mr-1"></i>Montant</th>
                    <th data-label="Motif"><i class="fas fa-comment mr-1"></i>Motif</th>
                    <th data-label="Statut"><i class="fas fa-flag mr-1"></i>Statut</th>
                    <th data-label="Date Approbation"><i class="fas fa-check mr-1"></i>Approbation</th>
                    <th data-label="Approuvé par"><i class="fas fa-user-check mr-1"></i>Approuvé par</th>
                    <th data-label="Date Paiement"><i class="fas fa-credit-card mr-1"></i>Paiement</th>
                    <th data-label="Payé par"><i class="fas fa-user-tie mr-1"></i>Payé par</th>
                    <th data-label="Actions"><i class="fas fa-cogs mr-1"></i>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($demandes)): ?>
                    <?php foreach ($demandes as $demande): ?>
                        <tr>
                            <td data-label="Date">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-calendar-day text-primary mr-2"></i>
                                    <?= date('d/m/Y H:i', strtotime($demande['date_demande'])) ?>
                                </div>
                            </td>
                            <td data-label="N° Demande">
                                <span class="font-weight-bold text-primary">
                                    #<?= htmlspecialchars($demande['numero_demande']) ?>
                                </span>
                            </td>
                            <td data-label="Montant">
                                <div class="font-weight-bold text-success">
                                    <?= number_format($demande['montant'], 0, ',', ' ') ?> <small>FCFA</small>
                                </div>
                            </td>
                            <td data-label="Motif">
                                <div class="text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($demande['motif']) ?>">
                                    <?= htmlspecialchars($demande['motif']) ?>
                                </div>
                            </td>
                            <td data-label="Statut">
                                <span class="status-badge <?= getStatusBadgeClass($demande['statut']) ?>">
                                    <?= getStatusLabel($demande['statut']) ?>
                                </span>
                            </td>
                            <td data-label="Date Approbation">
                                <?php if (empty($demande['date_approbation'])): ?>
                                    <button class="status-button pending" disabled>
                                        <i class="fas fa-clock mr-1"></i>
                                        En attente
                                    </button>
                                <?php else: ?>
                                    <button class="status-button completed" disabled>
                                        <i class="fas fa-check mr-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($demande['date_approbation'])) ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td data-label="Approuvé par">
                                <?php if (!empty($demande['approbateur_nom'])): ?>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center mr-2" style="width: 30px; height: 30px; font-size: 0.7rem;">
                                            <?= strtoupper(substr($demande['approbateur_nom'], 0, 2)) ?>
                                        </div>
                                        <small><?= htmlspecialchars($demande['approbateur_nom'] . ' ' . $demande['approbateur_prenoms']) ?></small>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Date Paiement">
                                <?php if (empty($demande['date_paiement'])): ?>
                                    <button class="status-button pending" disabled>
                                        <i class="fas fa-clock mr-1"></i>
                                        En attente
                                    </button>
                                <?php else: ?>
                                    <button class="status-button completed" disabled>
                                        <i class="fas fa-credit-card mr-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($demande['date_paiement'])) ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td data-label="Payé par">
                                <?php if (!empty($demande['payeur_nom'])): ?>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center mr-2" style="width: 30px; height: 30px; font-size: 0.7rem;">
                                            <?= strtoupper(substr($demande['payeur_nom'], 0, 2)) ?>
                                        </div>
                                        <small><?= htmlspecialchars($demande['payeur_nom'] . ' ' . $demande['payeur_prenoms']) ?></small>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Actions">
                                <?php if ($demande['statut'] === 'en_attente'): ?>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn-action edit" onclick="editDemande(<?= $demande['id_demande'] ?>)" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn-action delete" onclick="deleteDemande(<?= $demande['id_demande'] ?>)" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">
                                        <i class="fas fa-lock mr-1"></i>
                                        Verrouillé
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center py-5">
                            <div class="d-flex flex-column align-items-center">
                                <i class="fas fa-file-invoice text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                                <h5 class="text-muted mb-2">Aucune demande trouvée</h5>
                                <p class="text-muted mb-3">Aucune demande ne correspond aux critères actuels</p>
                                <button class="btn-professional primary" data-toggle="modal" data-target="#add-demande">
                                    <i class="fas fa-plus-circle mr-2"></i>
                                    Créer une nouvelle demande
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination Professional -->
<?php if ($total_pages > 1): ?>
<div class="pagination-container">
    <div class="pagination-nav">
        <?php if($page > 1): ?>
            <a href="?page=<?= $page - 1 ?><?= isset($_GET['statut']) ? '&statut='.$_GET['statut'] : '' ?><?= isset($_GET['date_debut']) ? '&date_debut='.$_GET['date_debut'] : '' ?><?= isset($_GET['date_fin']) ? '&date_fin='.$_GET['date_fin'] : '' ?><?= isset($_GET['search']) ? '&search='.$_GET['search'] : '' ?>" class="pagination-btn" title="Page précédente">
                <i class="fas fa-chevron-left"></i>
            </a>
        <?php endif; ?>
        
        <div class="pagination-info">
            Page <?= $page ?> sur <?= $total_pages ?>
        </div>

        <?php if($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?><?= isset($_GET['statut']) ? '&statut='.$_GET['statut'] : '' ?><?= isset($_GET['date_debut']) ? '&date_debut='.$_GET['date_debut'] : '' ?><?= isset($_GET['date_fin']) ? '&date_fin='.$_GET['date_fin'] : '' ?><?= isset($_GET['search']) ? '&search='.$_GET['search'] : '' ?>" class="pagination-btn" title="Page suivante">
                <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
    
    <form action="" method="get" class="items-per-page-form">
        <?php if(isset($_GET['statut'])): ?>
            <input type="hidden" name="statut" value="<?= htmlspecialchars($_GET['statut']) ?>">
        <?php endif; ?>
        <?php if(isset($_GET['date_debut'])): ?>
            <input type="hidden" name="date_debut" value="<?= htmlspecialchars($_GET['date_debut']) ?>">
        <?php endif; ?>
        <?php if(isset($_GET['date_fin'])): ?>
            <input type="hidden" name="date_fin" value="<?= htmlspecialchars($_GET['date_fin']) ?>">
        <?php endif; ?>
        <?php if(isset($_GET['search'])): ?>
            <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search']) ?>">
        <?php endif; ?>
        <label for="limit" class="text-muted mr-2">
            <i class="fas fa-list mr-1"></i>Afficher :
        </label>
        <select name="limit" id="limit" class="items-per-page-select">
            <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>5 demandes</option>
            <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10 demandes</option>
            <option value="15" <?= $limit == 15 ? 'selected' : '' ?>>15 demandes</option>
            <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25 demandes</option>
        </select>
        <button type="submit" class="submit-button ml-2">
            <i class="fas fa-check mr-1"></i>Appliquer
        </button>
    </form>
</div>
<?php endif; ?>

  <div class="modal fade" id="add-demande" tabindex="-1" role="dialog" aria-labelledby="addDemandeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h4 class="modal-title" id="addDemandeModalLabel">
            <i class="fas fa-plus-circle mr-2"></i>Enregistrer une demande
          </h4>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form class="forms-sample" method="post" action="traitement_demande.php" id="form-add-demande">
            <div class="form-group">
              <label for="montant">
                <i class="fas fa-money-bill-wave mr-1"></i>
                Montant (FCFA)
              </label>
              <div class="input-group">
                <input type="text" class="form-control" id="montant" name="montant" 
                       placeholder="Saisissez le montant..." 
                       autocomplete="off" required>
                <div class="input-group-append">
                  <span class="input-group-text bg-primary text-white">FCFA</span>
                </div>
              </div>
              <small class="form-text text-muted">
                <i class="fas fa-info-circle mr-1"></i>
                Le montant sera automatiquement formaté
              </small>
            </div>
            
            <div class="form-group">
              <label for="motif">
                <i class="fas fa-comment mr-1"></i>
                Motif de la sortie
              </label>
              <textarea class="form-control" id="motif" name="motif" rows="4" 
                        placeholder="Décrivez le motif de votre demande de sortie (minimum 10 caractères)..." 
                        required maxlength="500"></textarea>
              <small class="form-text text-muted">
                <i class="fas fa-keyboard mr-1"></i>
                <span class="char-counter">0/500 caractères</span>
              </small>
            </div>
            
            <input type="hidden" name="statut" value="en_attente">
            
            <div class="form-group mb-0">
              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                  <i class="fas fa-times mr-1"></i>Annuler
                </button>
                <button type="submit" class="btn btn-primary" name="saveDemande">
                  <i class="fas fa-save mr-1"></i>Enregistrer
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    $(document).ready(function() {
        // Mettre à jour la date automatiquement quand le modal s'ouvre
        // $('#add-demande').on('show.bs.modal', function () {
        //     var now = new Date();
        //     var year = now.getFullYear();
        //     var month = String(now.getMonth() + 1).padStart(2, '0');
        //     var day = String(now.getDate()).padStart(2, '0');
        //     var hours = String(now.getHours()).padStart(2, '0');
        //     var minutes = String(now.getMinutes()).padStart(2, '0');
            
        //     var datetime = `${year}-${month}-${day}T${hours}:${minutes}`;
        //     $('#date_demande').val(datetime);
        // });
    });
  </script>

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

<!-- Modal Modifier -->
<div class="modal fade" id="edit-demande" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h4 class="modal-title">
                    <i class="fas fa-edit mr-2"></i>Modifier la demande
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="modifier_demande.php" method="post" id="form-edit-demande">
                <div class="modal-body">
                    <input type="hidden" id="edit_id_demande" name="id_demande">
                    
                    <div class="form-group">
                        <label for="edit_montant">
                            <i class="fas fa-money-bill-wave mr-1"></i>
                            Montant (FCFA)
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="edit_montant" name="montant" 
                                   placeholder="Saisissez le montant..." 
                                   autocomplete="off" required>
                            <div class="input-group-append">
                                <span class="input-group-text bg-info text-white">FCFA</span>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle mr-1"></i>
                            Le montant sera automatiquement formaté
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_motif">
                            <i class="fas fa-comment mr-1"></i>
                            Motif de la sortie
                        </label>
                        <textarea class="form-control" id="edit_motif" name="motif" rows="4" 
                                  placeholder="Décrivez le motif de votre demande de sortie (minimum 10 caractères)..." 
                                  required maxlength="500"></textarea>
                        <small class="form-text text-muted">
                            <i class="fas fa-keyboard mr-1"></i>
                            <span class="char-counter">0/500 caractères</span>
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-info" name="update_demande">
                        <i class="fas fa-save mr-1"></i>Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Supprimer -->
<div class="modal fade" id="delete-demande" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Confirmer la suppression</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette demande ?</p>
                <form method="post" action="supprimer_demande.php">
                    <input type="hidden" id="delete_id_demande" name="id_demande">
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                        <button type="button" class="btn btn-light" data-dismiss="modal">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Debug
    console.log('Script chargé');
    
    // Fonction pour éditer une demande
    window.editDemande = function(id) {
        // Récupérer les données de la ligne
        var row = $('button[onclick="editDemande(' + id + ')"]').closest('tr');
        var montant = row.find('td:eq(2)').text().replace(' FCFA', '').replace(/\s/g, '');
        var motif = row.find('td:eq(3)').text();
        
        // Remplir le formulaire
        $('#edit_id_demande').val(id);
        $('#edit_montant').val(montant);
        $('#edit_motif').val(motif);
        
        // Afficher le modal
        $('#edit-demande').modal('show');
    };
    
    // Fonction pour supprimer une demande
    window.deleteDemande = function(id) {
        $('#delete_id_demande').val(id);
        $('#delete-demande').modal('show');
    };
});

// Vérifier que jQuery et Bootstrap sont chargés
console.log('jQuery version:', typeof $ !== 'undefined' ? $.fn.jquery : 'non chargé');
console.log('Bootstrap modal:', typeof $.fn.modal !== 'undefined' ? 'chargé' : 'non chargé');
</script>

<!-- Modal pour ticket existant -->
<div class="modal fade" id="ticketExistModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Ticket déjà existant
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Le ticket numéro <strong id="existingTicketNumber"></strong> existe déjà dans la base de données.</p>
                <p>Veuillez utiliser un autre numéro de ticket.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<?php foreach ($demandes as $demande) : ?>
  <div class="modal fade" id="demandeModal<?= $demande['id_demande'] ?>" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title" id="demandeModalLabel<?= $demande['id_demande'] ?>">
            <i class="fas fa-file-alt mr-2"></i>Détails de la demande #<?= $demande['numero_demande'] ?>
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-12">
              <table class="table table-bordered">
                <tr>
                  <th style="width: 150px;">Date demande</th>
                  <td><?= date('d/m/Y H:i', strtotime($demande['date_demande'])) ?></td>
                </tr>
                <tr>
                  <th>Motif</th>
                  <td><?= nl2br(htmlspecialchars($demande['motif'])) ?></td>
                </tr>
                <tr>
                  <th>Montant</th>
                  <td><?= number_format($demande['montant'], 0, ',', ' ') ?> FCFA</td>
                </tr>
                <tr>
                  <th>Statut</th>
                  <td>
                    <span class="badge badge-<?= getStatusBadgeClass($demande['statut']) ?>">
                      <?= getStatusLabel($demande['statut']) ?>
                    </span>
                  </td>
                </tr>
                <?php if ($demande['approuve_par']): ?>
                <tr>
                  <th>Approuvé par</th>
                  <td><?= htmlspecialchars($demande['approbateur_nom'] . ' ' . $demande['approbateur_prenoms']) ?><br>
                      Le <?= date('d/m/Y H:i', strtotime($demande['date_approbation'])) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($demande['paye_par']): ?>
                <tr>
                  <th>Payé par</th>
                  <td><?= htmlspecialchars($demande['payeur_nom'] . ' ' . $demande['payeur_prenoms']) ?><br>
                      Le <?= date('d/m/Y H:i', strtotime($demande['date_paiement'])) ?></td>
                </tr>
                <?php endif; ?>
              </table>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>





<script src="../../plugins/jquery/jquery.min.js"></script>
<script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../dist/js/adminlte.min.js"></script>

<script>
$(document).ready(function() {
    console.log('✅ Page Demandes initialisée avec succès');
    
    // Animation des éléments au chargement
    $('.action-buttons-container .btn-professional').each(function(index) {
        $(this).css('animation-delay', (index * 0.1) + 's');
        $(this).addClass('animate-fade-in-up');
    });

    // Animation des lignes du tableau
    $('.table-professional tbody tr').each(function(index) {
        $(this).css('animation-delay', (index * 0.05) + 's');
        $(this).addClass('animate-fade-in');
    });
    
    // Fonction pour éditer une demande (améliorée)
    window.editDemande = function(id) {
        console.log('Edit clicked:', id);
        
        // Récupérer les données de la ligne
        var row = $('button[onclick="editDemande(' + id + ')"]').closest('tr');
        var montantText = row.find('td[data-label="Montant"] .font-weight-bold').text();
        var montant = montantText.replace(' FCFA', '').replace(/\s/g, '').replace('FCFA', '');
        var motif = row.find('td[data-label="Motif"] .text-truncate').attr('title') || row.find('td[data-label="Motif"] .text-truncate').text();
        
        console.log('Data:', { montant, motif });
        
        // Remplir le formulaire
        $('#edit_id_demande').val(id);
        $('#edit_montant').val(montant);
        $('#edit_motif').val(motif.trim());
        
        // Afficher le modal avec animation
        $('#edit-demande').modal('show');
        
        // Animation du modal
        setTimeout(() => {
            $('#edit-demande .modal-content').addClass('animate-fade-in-up');
        }, 100);
    };
    
    // Fonction pour supprimer une demande (avec confirmation moderne)
    window.deleteDemande = function(id) {
        console.log('Delete clicked:', id);
        
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Cette demande sera définitivement supprimée !",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff416c',
            cancelButtonColor: '#667eea',
            confirmButtonText: 'Oui, supprimer !',
            cancelButtonText: 'Annuler',
            background: 'rgba(255, 255, 255, 0.95)',
            backdrop: 'rgba(0, 0, 0, 0.4)'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#delete_id_demande').val(id);
                $('#delete-demande').modal('show');
            }
        });
    };

    // Validation du formulaire d'ajout (améliorée)
    $('#form-add-demande').on('submit', function(e) {
        const montantField = $('#montant');
        const montantValue = montantField.attr('data-value') || montantField.val().replace(/\s/g, '').replace(/[^\d]/g, '');
        const motif = $('#motif').val().trim();
        
        if (!montantValue || parseInt(montantValue) <= 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Montant invalide',
                text: 'Veuillez saisir un montant valide supérieur à 0',
                confirmButtonColor: '#667eea'
            });
            montantField.focus();
            return false;
        }
        
        if (!motif || motif.length < 10) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Motif insuffisant',
                text: 'Veuillez saisir un motif d\'au moins 10 caractères',
                confirmButtonColor: '#667eea'
            });
            $('#motif').focus();
            return false;
        }
        
        // Restaurer la valeur numérique avant soumission
        montantField.val(montantValue);
        
        // Animation de soumission
        $(this).find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin mr-2"></i>Enregistrement...');
    });

    // Validation du formulaire de modification (améliorée)
    $('#form-edit-demande').on('submit', function(e) {
        const montantField = $('#edit_montant');
        const montantValue = montantField.attr('data-value') || montantField.val().replace(/\s/g, '').replace(/[^\d]/g, '');
        const motif = $('#edit_motif').val().trim();
        
        if (!montantValue || parseInt(montantValue) <= 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Montant invalide',
                text: 'Veuillez saisir un montant valide supérieur à 0',
                confirmButtonColor: '#667eea'
            });
            montantField.focus();
            return false;
        }
        
        if (!motif || motif.length < 10) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Motif insuffisant',
                text: 'Veuillez saisir un motif d\'au moins 10 caractères',
                confirmButtonColor: '#667eea'
            });
            $('#edit_motif').focus();
            return false;
        }
        
        // Restaurer la valeur numérique avant soumission
        montantField.val(montantValue);
        
        // Animation de soumission
        $(this).find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin mr-2"></i>Modification...');
    });

    // Formatage automatique des montants (amélioré)
    $('#montant, #edit_montant').on('input', function() {
        let value = $(this).val().replace(/\s/g, '');
        
        // Permettre seulement les chiffres
        value = value.replace(/[^\d]/g, '');
        
        if (value && value.length > 0) {
            // Stocker la valeur numérique dans un attribut data
            $(this).attr('data-value', value);
            
            // Éviter le formatage pendant la saisie si le champ est focalisé
            if (!$(this).hasClass('focused')) {
                // Afficher la valeur formatée seulement si pas en cours de saisie
                $(this).val(parseInt(value).toLocaleString('fr-FR'));
            }
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
            // Afficher la valeur numérique pure pendant l'édition
            $(this).val(numericValue);
        }
    });

    $('#montant, #edit_montant').on('blur', function() {
        $(this).removeClass('focused');
        const value = $(this).val().replace(/\s/g, '').replace(/[^\d]/g, '');
        if (value && value.length > 0) {
            $(this).attr('data-value', value);
            // Formater à la perte de focus
            $(this).val(parseInt(value).toLocaleString('fr-FR'));
        } else {
            $(this).removeAttr('data-value');
            $(this).val('');
        }
    });

    // Réinitialiser les champs quand les modaux s'ouvrent
    $('#add-demande').on('show.bs.modal', function() {
        $('#montant').val('').removeAttr('data-value').removeClass('focused');
        $('#motif').val('');
        $('.char-counter').text('0/500 caractères');
    });

    $('#edit-demande').on('show.bs.modal', function() {
        $('#edit_montant').removeClass('focused');
        $('.char-counter').text($('#edit_motif').val().length + '/500 caractères');
    });

    // Compteur de caractères pour le motif (amélioré)
    $('#motif, #edit_motif').on('input', function() {
        const maxLength = 500;
        const currentLength = $(this).val().length;
        const remaining = maxLength - currentLength;
        
        // Trouver le compteur dans le même groupe de formulaire
        let counterElement = $(this).closest('.form-group').find('.char-counter');
        
        if (counterElement.length > 0) {
            counterElement.text(`${currentLength}/${maxLength} caractères`);
            
            if (remaining < 50) {
                counterElement.removeClass('text-muted').addClass('text-warning');
            } else if (remaining < 20) {
                counterElement.removeClass('text-muted text-warning').addClass('text-danger');
            } else {
                counterElement.removeClass('text-warning text-danger').addClass('text-muted');
            }
        }
    });

    // Initialiser les compteurs au chargement
    $('#motif, #edit_motif').each(function() {
        $(this).trigger('input');
    });

    // Gestion des filtres
    window.resetFilters = function() {
        window.location.href = window.location.pathname;
    };

    window.clearFilters = function() {
        $('#filter_statut').val('all');
        $('#filter_date_debut').val('');
        $('#filter_date_fin').val('');
        $('#filter_search').val('');
    };

    window.removeFilter = function(filterType) {
        const url = new URL(window.location);
        
        switch(filterType) {
            case 'Statut':
                url.searchParams.delete('statut');
                break;
            case 'Début':
                url.searchParams.delete('date_debut');
                break;
            case 'Fin':
                url.searchParams.delete('date_fin');
                break;
            case 'Recherche':
                url.searchParams.delete('search');
                break;
        }
        
        // Réinitialiser la page à 1
        url.searchParams.delete('page');
        
        window.location.href = url.toString();
    };

    // Validation des dates
    $('#filter_date_debut, #filter_date_fin').on('change', function() {
        const dateDebut = $('#filter_date_debut').val();
        const dateFin = $('#filter_date_fin').val();
        
        if (dateDebut && dateFin && dateDebut > dateFin) {
            Swal.fire({
                icon: 'warning',
                title: 'Dates invalides',
                text: 'La date de début doit être antérieure à la date de fin',
                confirmButtonColor: '#667eea'
            });
            
            if ($(this).attr('id') === 'filter_date_fin') {
                $(this).val('');
            } else {
                $('#filter_date_fin').val('');
            }
        }
    });

    // Animation des filtres
    $('.filters-container .filter-group').each(function(index) {
        $(this).css('animation-delay', (index * 0.1) + 's');
        $(this).addClass('animate-fade-in-up');
    });

    // Animation des tags de filtres actifs
    $('.filter-tag').each(function(index) {
        $(this).css('animation-delay', (index * 0.1) + 's');
        $(this).addClass('animate-fade-in');
    });

    // Recherche en temps réel (optionnelle)
    let searchTimeout;
    $('#filter_search').on('input', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val();
        
        if (searchTerm.length >= 3 || searchTerm.length === 0) {
            searchTimeout = setTimeout(() => {
                // Auto-submit après 1 seconde de pause
                // $(this).closest('form').submit();
            }, 1000);
        }
    });

    // Soumission du formulaire de filtres
    $('.filters-form').on('submit', function(e) {
        // Réinitialiser la page à 1 lors d'un nouveau filtre
        $('<input>').attr({
            type: 'hidden',
            name: 'page',
            value: '1'
        }).appendTo(this);
        
        // Animation du bouton
        $(this).find('.btn-filter.primary').html('<i class="fas fa-spinner fa-spin mr-2"></i>Filtrage...');
    });

    console.log('📊 Statistiques chargées:', {
        totalDemandes: <?= count($demandes) ?>,
        totalPages: <?= $total_pages ?>,
        pageActuelle: <?= $page ?>,
        filtresActifs: <?= count($active_filters) ?>
    });
});
</script>
</body>
</html>
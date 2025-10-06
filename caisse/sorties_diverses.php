<?php
require_once '../inc/functions/connexion.php';
include('header_caisse.php');

$id_user = $_SESSION['user_id'];

// Get total cash balance
$getSommeCaisseQuery = "SELECT
    SUM(CASE WHEN type_transaction = 'approvisionnement' THEN montant
             WHEN type_transaction = 'paiement' THEN -montant
             ELSE 0 END) AS solde_caisse
FROM transactions";
$getSommeCaisseQueryStmt = $conn->query($getSommeCaisseQuery);
$somme_caisse = $getSommeCaisseQueryStmt->fetch(PDO::FETCH_ASSOC);

// Get statistics for sorties diverses
$getStatsQuery = "SELECT 
    COUNT(*) as total_sorties,
    SUM(montant) as total_montant
FROM sorties_diverses";
$getStatsStmt = $conn->query($getStatsQuery);
$stats_sorties = $getStatsStmt->fetch(PDO::FETCH_ASSOC);

// Get all sorties diverses with pagination
$limit = $_GET['limit'] ?? 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$getSortiesQuery = "SELECT * FROM sorties_diverses ORDER BY date_sortie DESC";
$getSortiesStmt = $conn->query($getSortiesQuery);
$sorties = $getSortiesStmt->fetchAll(PDO::FETCH_ASSOC);

// Debug: Afficher le nombre de sorties trouvées
echo "<!-- DEBUG: Nombre de sorties trouvées: " . count($sorties) . " -->";
if (count($sorties) > 0) {
    echo "<!-- DEBUG: Première sortie: " . print_r($sorties[0], true) . " -->";
}

// Paginate results
$sorties_pages = array_chunk($sorties, $limit);
$sorties_list = $sorties_pages[$page - 1] ?? [];

echo "<!-- DEBUG: Nombre de sorties dans la page actuelle: " . count($sorties_list) . " -->";
?>

<style>
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
    --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
    --glass-bg: rgba(255, 255, 255, 0.95);
    --glass-border: rgba(255, 255, 255, 0.3);
    --shadow-light: 0 8px 32px rgba(31, 38, 135, 0.15);
    --shadow-heavy: 0 15px 35px rgba(31, 38, 135, 0.25);
    --border-radius: 20px;
    --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

body {
    background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
    background-size: 400% 400%;
    animation: gradientShift 15s ease infinite;
    font-family: 'Inter', sans-serif;
}

@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.page-header {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    text-align: center;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 0.5rem;
}

.stats-card {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid var(--glass-border);
    transition: var(--transition);
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-heavy);
}

.stat-item {
    text-align: center;
    padding: 1.25rem;
    border-radius: 15px;
    margin-bottom: 0.75rem;
    transition: var(--transition);
}

.stat-item:hover {
    transform: scale(1.02);
}

.stat-item.primary {
    background: var(--primary-gradient);
    color: white;
}

.stat-item.success {
    background: var(--success-gradient);
    color: white;
}

.stat-item.danger {
    background: var(--danger-gradient);
    color: white;
}

.stat-item.info {
    background: var(--info-gradient);
    color: white;
}

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
    opacity: 0.9;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
    font-weight: 500;
}

.action-buttons {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    justify-content: center;
}

.btn-modern {
    padding: 10px 20px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    border: none;
    transition: var(--transition);
    box-shadow: var(--shadow-light);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-heavy);
}

.btn-modern.primary {
    background: var(--primary-gradient);
    color: white;
}

.btn-modern.danger {
    background: var(--danger-gradient);
    color: white;
}

.transactions-card {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.card-header-modern {
    border-bottom: 2px solid var(--glass-border);
    padding-bottom: 0.75rem;
    margin-bottom: 1.5rem;
}

.card-title-modern {
    font-size: 1.5rem;
    font-weight: 600;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.table-modern {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: var(--shadow-light);
    margin-bottom: 1.5rem;
}

.table-modern thead {
    background: var(--primary-gradient);
    color: white;
}

.table-modern th, .table-modern td {
    padding: 0.75rem;
    border: none;
    text-align: center;
    vertical-align: middle;
}

.table-modern tbody tr:hover {
    background: rgba(102, 126, 234, 0.1);
}

.badge-modern {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
}

.badge-success-modern {
    background: var(--success-gradient);
    color: white;
}

.badge-danger-modern {
    background: var(--danger-gradient);
    color: white;
}

.pagination-modern {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border-radius: 15px;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.pagination-btn {
    padding: 8px 16px;
    background: var(--primary-gradient);
    color: white;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: var(--transition);
}

.pagination-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    color: white;
    text-decoration: none;
}

.pagination-info {
    font-weight: 600;
    color: #2c3e50;
}

.items-per-page {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.items-per-page select {
    padding: 8px 12px;
    border-radius: 8px;
    border: 2px solid #e9ecef;
    background: white;
    font-weight: 500;
}

.items-per-page button {
    padding: 8px 16px;
    background: var(--success-gradient);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
}

.items-per-page button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(86, 171, 47, 0.3);
}

/* Version compacte pour réduire les espaces vides */
.compact-layout {
    max-width: 1400px;
    margin: 0 auto;
}

.row.no-gutters-custom {
    margin-left: -0.5rem;
    margin-right: -0.5rem;
}

.row.no-gutters-custom > [class*="col-"] {
    padding-left: 0.5rem;
    padding-right: 0.5rem;
}

@media (max-width: 768px) {
    .action-buttons {
        flex-direction: column;
        align-items: stretch;
        padding: 1rem;
    }
    
    .btn-modern {
        justify-content: center;
        margin-bottom: 0.5rem;
    }
    
    .pagination-modern {
        flex-direction: column;
        text-align: center;
    }
    
    .stats-card {
        padding: 1rem;
    }
    
    .stat-item {
        padding: 1rem;
        margin-bottom: 0.5rem;
    }
}
</style>

<!-- Bootstrap CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">

<div class="content-wrapper">
    <div class="container-fluid compact-layout" style="padding: 0.75rem 1.5rem;">
        <!-- Header -->
        <div class="page-header">
            <div style="font-size: 2.25rem; margin-bottom: 0.5rem;">
                <i class="fas fa-arrow-down" style="background: var(--danger-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
            </div>
            <h1 class="page-title">Gestion des Sorties Diverses</h1>
            <p style="color: #6c757d; font-size: 0.95rem; margin-bottom: 0;">Gérez les sorties diverses de caisse en toute simplicité</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-card">
            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6 col-sm-8">
                    <div class="stat-item primary">
                        <div class="stat-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="stat-value"><?= number_format($somme_caisse['solde_caisse'] ?? 0, 0, ',', ' ') ?></div>
                        <div class="stat-label">Solde Caisse (FCFA)</div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-8">
                    <div class="stat-item danger">
                        <div class="stat-icon">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="stat-value"><?= number_format($stats_sorties['total_montant'] ?? 0, 0, ',', ' ') ?></div>
                        <div class="stat-label">Total Sorties Diverses (FCFA)</div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-8">
                    <div class="stat-item info">
                        <div class="stat-icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <div class="stat-value"><?= $stats_sorties['total_sorties'] ?? 0 ?></div>
                        <div class="stat-label">Nombre de Sorties</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button type="button" class="btn-modern danger" data-toggle="modal" data-target="#add-sortie">
                <i class="fas fa-minus"></i>
                Nouvelle Sortie Diverse
            </button>
            <button type="button" class="btn-modern primary" data-toggle="modal" data-target="#print-sorties">
                <i class="fas fa-print"></i>
                Imprimer Sorties
            </button>
        </div>

        <!-- Sorties Diverses Table -->
        <div class="transactions-card">
            <div class="card-header-modern">
                <h3 class="card-title-modern">
                    <i class="fas fa-list"></i>
                    Historique des Sorties Diverses
                </h3>
            </div>

            <!-- Loading Animation -->
            <div id="loader" class="text-center p-4" style="display: none;">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="sr-only">Chargement...</span>
                </div>
                <p class="mt-2 text-muted">Chargement des données...</p>
            </div>

            <!-- Sorties Table -->
            <div class="table-responsive">
                <table id="example1" class="table table-modern">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag me-2"></i>N° Sortie</th>
                            <th><i class="fas fa-calendar me-2"></i>Date</th>
                            <th><i class="fas fa-money-bill me-2"></i>Montant</th>
                            <th><i class="fas fa-comment me-2"></i>Motifs</th>
                            <th><i class="fas fa-cog me-2"></i>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sorties_list as $sortie) : ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: #2c3e50; background: linear-gradient(135deg, rgba(255, 107, 107, 0.1), rgba(238, 90, 82, 0.1)); padding: 0.5rem; border-radius: 8px; border-left: 3px solid #ff6b6b;">
                                        <i class="fas fa-hashtag me-1 text-danger"></i>
                                        <?= $sortie['numero_sorties'] ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #2c3e50;">
                                        <?= date('d/m/Y', strtotime($sortie['date_sortie'])) ?>
                                    </div>
                                    <div style="font-size: 0.85rem; color: #6c757d;">
                                        <?= date('H:i', strtotime($sortie['date_sortie'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; font-size: 1.1rem; color: #dc3545;">
                                        <?= number_format($sortie['montant'], 0, ',', ' ') ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: #6c757d;">FCFA</div>
                                </td>
                                <td>
                                    <div style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($sortie['motifs']) ?>">
                                        <span style="color: #2c3e50; font-weight: 500;">
                                            <i class="fas fa-comment me-1 text-info"></i><?= $sortie['motifs'] ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-view-sortie" 
                                            data-id="<?= $sortie['id_sorties'] ?>" 
                                            data-numero="<?= $sortie['numero_sorties'] ?>" 
                                            data-date="<?= $sortie['date_sortie'] ?>" 
                                            data-montant="<?= $sortie['montant'] ?>" 
                                            data-motifs="<?= htmlspecialchars($sortie['motifs']) ?>" 
                                            style="background: var(--info-gradient); color: white; border: none; border-radius: 8px; padding: 8px 12px;" 
                                            title="Voir détails">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-edit-sortie" 
                                            data-id="<?= $sortie['id_sorties'] ?>" 
                                            data-numero="<?= $sortie['numero_sorties'] ?>" 
                                            data-date="<?= $sortie['date_sortie'] ?>" 
                                            data-montant="<?= $sortie['montant'] ?>" 
                                            data-motifs="<?= htmlspecialchars($sortie['motifs']) ?>" 
                                            style="background: var(--warning-gradient); color: white; border: none; border-radius: 8px; padding: 8px 12px; margin-left: 5px;" 
                                            title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-delete-sortie" 
                                            data-id="<?= $sortie['id_sorties'] ?>" 
                                            data-numero="<?= $sortie['numero_sorties'] ?>" 
                                            style="background: var(--danger-gradient); color: white; border: none; border-radius: 8px; padding: 8px 12px; margin-left: 5px;" 
                                            title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Modern Pagination -->
            <div class="pagination-modern">
                <div class="pagination-controls">
                    <?php if($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>" class="pagination-btn">
                            <i class="fas fa-chevron-left me-1"></i>Précédent
                        </a>
                    <?php endif; ?>

                    <span class="pagination-info">
                        Page <?= $page ?> sur <?= count($sorties_pages) ?: 1 ?>
                    </span>

                    <?php if($page < count($sorties_pages)): ?>
                        <a href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>" class="pagination-btn">
                            Suivant<i class="fas fa-chevron-right ms-1"></i>
                        </a>
                    <?php endif; ?>
                </div>

                <form action="" method="get" class="items-per-page">
                    <label for="limit" style="font-weight: 600; color: #2c3e50;">Afficher :</label>
                    <select name="limit" id="limit">
                        <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>5</option>
                        <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                        <option value="15" <?= $limit == 15 ? 'selected' : '' ?>>15</option>
                        <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                    </select>
                    <button type="submit">
                        <i class="fas fa-check me-1"></i>Appliquer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modern Modal for new sortie -->
<div class="modal fade" id="add-sortie">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: 20px; box-shadow: var(--shadow-heavy);">
            <div class="modal-header" style="background: var(--danger-gradient); color: white; border-radius: 20px 20px 0 0; border: none;">
                <h4 class="modal-title" style="font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-minus-circle"></i>
                    Nouvelle Sortie Diverse
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 1;">
                    <span aria-hidden="true" style="font-size: 1.5rem;">&times;</span>
                </button>
            </div>
            <form class="needs-validation" method="post" action="save_sortie_diverse.php" novalidate>
                <div class="modal-body" style="padding: 2rem;">
                    <input type="hidden" name="save_sortie" value="1">
                    

                    <div class="form-group">
                        <label for="montant" style="font-weight: 600; color: #2c3e50; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-money-bill-wave text-danger"></i>
                            Montant <span class="text-danger">*</span>
                        </label>
                        <div class="input-group" style="border-radius: 12px; overflow: hidden; box-shadow: var(--shadow-light);">
                            <input type="text" 
                                class="form-control" 
                                id="montant" 
                                name="montant"
                                placeholder="Entrez le montant de la sortie"
                                style="border: 2px solid #e9ecef; border-right: none; padding: 15px; font-size: 1.1rem; font-weight: 500;"
                                required>
                            <div class="input-group-append">
                                <span class="input-group-text" style="background: var(--danger-gradient); color: white; border: 2px solid #e9ecef; border-left: none; font-weight: 600; padding: 15px;">
                                    FCFA
                                </span>
                            </div>
                            <div class="invalid-feedback" style="font-size: 0.9rem; margin-top: 0.5rem;">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                Le montant est requis et doit être supérieur à 0
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            <i class="fas fa-info-circle me-1"></i>
                            Le montant sera automatiquement formaté avec des espaces
                        </small>
                    </div>

                    <div class="form-group mt-4">
                        <label for="motifs" style="font-weight: 600; color: #2c3e50; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-comment text-info"></i>
                            Motifs de la sortie <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" 
                                id="motifs" 
                                name="motifs" 
                                rows="4" 
                                placeholder="Décrivez la raison de cette sortie diverse (ex: Achat fournitures bureau, Frais de transport, Réparation équipement...)"
                                style="border: 2px solid #e9ecef; border-radius: 12px; padding: 15px; font-size: 1rem; resize: vertical; box-shadow: var(--shadow-light);"
                                required></textarea>
                        <div class="invalid-feedback" style="font-size: 0.9rem; margin-top: 0.5rem;">
                            <i class="fas fa-exclamation-circle me-1"></i>
                            Veuillez décrire les motifs de la sortie
                        </div>
                        <small class="text-muted mt-2 d-block">
                            <i class="fas fa-info-circle me-1"></i>
                            Soyez précis pour faciliter le suivi comptable
                        </small>
                    </div>

                </div>
                <div class="modal-footer" style="background: #f8f9fa; border-radius: 0 0 20px 20px; border: none; padding: 1.5rem 2rem;">
                    <button type="button" class="btn" data-dismiss="modal" style="background: #6c757d; color: white; border: none; border-radius: 10px; padding: 12px 24px; font-weight: 600; transition: var(--transition);">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <button type="submit" class="btn" style="background: var(--danger-gradient); color: white; border: none; border-radius: 10px; padding: 12px 24px; font-weight: 600; transition: var(--transition); box-shadow: var(--shadow-light);">
                        <i class="fas fa-check me-2"></i>Enregistrer la sortie
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modern Print Modal -->
<div class="modal fade" id="print-sorties">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: 20px; box-shadow: var(--shadow-heavy);">
            <div class="modal-header" style="background: var(--primary-gradient); color: white; border-radius: 20px 20px 0 0; border: none;">
                <h4 class="modal-title" style="font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-print"></i>
                    Imprimer les Sorties Diverses
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 1;">
                    <span aria-hidden="true" style="font-size: 1.5rem;">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger" style="border-radius: 12px; border: none; background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(255, 193, 7, 0.1)); color: #721c24;">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= $_SESSION['error_message'] ?>
                        <?php unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>
                
                <form class="forms-sample" method="post" action="impression_sorties_diverses.php" id="print-form">
                    <div class="form-group mb-4">
                        <label for="date_debut" style="font-weight: 600; color: #2c3e50; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-calendar-alt text-primary"></i>
                            Date de début <span class="text-danger">*</span>
                        </label>
                        <input type="date" 
                               class="form-control" 
                               id="date_debut" 
                               name="date_debut_sorties"
                               style="border: 2px solid #e9ecef; border-radius: 12px; padding: 15px; font-size: 1rem; box-shadow: var(--shadow-light);"
                               required>
                    </div>
                    
                    <div class="form-group mb-4">
                        <label for="date_fin" style="font-weight: 600; color: #2c3e50; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-calendar-check text-primary"></i>
                            Date de fin <span class="text-danger">*</span>
                        </label>
                        <input type="date" 
                               class="form-control" 
                               id="date_fin" 
                               name="date_fin_sorties"
                               style="border: 2px solid #e9ecef; border-radius: 12px; padding: 15px; font-size: 1rem; box-shadow: var(--shadow-light);"
                               required>
                    </div>

                    <div class="alert alert-info" style="border-radius: 12px; border: none; background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(52, 144, 220, 0.1)); color: #0c5460;">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Information :</strong> Le rapport inclura toutes les sorties diverses dans la période sélectionnée.
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="background: #f8f9fa; border-radius: 0 0 20px 20px; border: none; padding: 1.5rem 2rem;">
                <button type="button" class="btn" data-dismiss="modal" style="background: #6c757d; color: white; border: none; border-radius: 10px; padding: 12px 24px; font-weight: 600; transition: var(--transition);">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <button type="submit" form="print-form" class="btn" style="background: var(--primary-gradient); color: white; border: none; border-radius: 10px; padding: 12px 24px; font-weight: 600; transition: var(--transition); box-shadow: var(--shadow-light);">
                    <i class="fas fa-print me-2"></i>Générer le rapport
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de visualisation des détails -->
<div class="modal fade" id="view-sortie-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: 20px; box-shadow: var(--shadow-heavy);">
            <div class="modal-header" style="background: var(--info-gradient); color: white; border-radius: 20px 20px 0 0; border: none;">
                <h4 class="modal-title" style="font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-eye"></i>
                    Détails de la Sortie
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 1;">
                    <span aria-hidden="true" style="font-size: 1.5rem;">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-item mb-3">
                            <label style="font-weight: 600; color: #2c3e50; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-hashtag text-primary"></i>
                                Numéro de sortie
                            </label>
                            <div id="view-numero" style="font-size: 1.1rem; color: #495057; font-weight: 500;"></div>
                        </div>
                        <div class="detail-item mb-3">
                            <label style="font-weight: 600; color: #2c3e50; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-calendar text-info"></i>
                                Date de sortie
                            </label>
                            <div id="view-date" style="font-size: 1.1rem; color: #495057; font-weight: 500;"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-item mb-3">
                            <label style="font-weight: 600; color: #2c3e50; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-money-bill-wave text-success"></i>
                                Montant
                            </label>
                            <div id="view-montant" style="font-size: 1.3rem; color: #28a745; font-weight: 700;"></div>
                        </div>
                    </div>
                </div>
                <div class="detail-item">
                    <label style="font-weight: 600; color: #2c3e50; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <i class="fas fa-comment text-warning"></i>
                        Motifs de la sortie
                    </label>
                    <div id="view-motifs" style="background: #f8f9fa; padding: 1rem; border-radius: 10px; border-left: 4px solid var(--info-gradient); font-size: 1rem; line-height: 1.5;"></div>
                </div>
            </div>
            <div class="modal-footer" style="background: #f8f9fa; border-radius: 0 0 20px 20px; border: none; padding: 1.5rem 2rem;">
                <button type="button" class="btn" data-dismiss="modal" style="background: #6c757d; color: white; border: none; border-radius: 10px; padding: 12px 24px; font-weight: 600;">
                    <i class="fas fa-times me-2"></i>Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de modification -->
<div class="modal fade" id="edit-sortie-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: 20px; box-shadow: var(--shadow-heavy);">
            <div class="modal-header" style="background: var(--warning-gradient); color: white; border-radius: 20px 20px 0 0; border: none;">
                <h4 class="modal-title" style="font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-edit"></i>
                    Modifier la Sortie Diverse
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 1;">
                    <span aria-hidden="true" style="font-size: 1.5rem;">&times;</span>
                </button>
            </div>
            <form class="needs-validation" method="post" action="update_sortie_diverse.php" novalidate>
                <div class="modal-body" style="padding: 2rem;">
                    <input type="hidden" name="update_sortie" value="1">
                    <input type="hidden" name="sortie_id" id="edit-sortie-id">
                    
                    <div class="form-group">
                        <label for="edit-montant" style="font-weight: 600; color: #2c3e50; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-money-bill-wave text-warning"></i>
                            Montant <span class="text-danger">*</span>
                        </label>
                        <div class="input-group" style="border-radius: 12px; overflow: hidden; box-shadow: var(--shadow-light);">
                            <input type="text" 
                                class="form-control" 
                                id="edit-montant" 
                                name="montant"
                                placeholder="Entrez le montant de la sortie"
                                style="border: 2px solid #e9ecef; border-right: none; padding: 15px; font-size: 1.1rem; font-weight: 500;"
                                required>
                            <div class="input-group-append">
                                <span class="input-group-text" style="background: var(--warning-gradient); color: white; border: 2px solid #e9ecef; border-left: none; font-weight: 600; padding: 15px;">
                                    FCFA
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <label for="edit-motifs" style="font-weight: 600; color: #2c3e50; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-comment text-info"></i>
                            Motifs de la sortie <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" 
                                id="edit-motifs" 
                                name="motifs" 
                                rows="4" 
                                placeholder="Décrivez la raison de cette sortie diverse"
                                style="border: 2px solid #e9ecef; border-radius: 12px; padding: 15px; font-size: 1rem; resize: vertical; box-shadow: var(--shadow-light);"
                                required></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="background: #f8f9fa; border-radius: 0 0 20px 20px; border: none; padding: 1.5rem 2rem;">
                    <button type="button" class="btn" data-dismiss="modal" style="background: #6c757d; color: white; border: none; border-radius: 10px; padding: 12px 24px; font-weight: 600;">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <button type="submit" class="btn" style="background: var(--warning-gradient); color: white; border: none; border-radius: 10px; padding: 12px 24px; font-weight: 600; box-shadow: var(--shadow-light);">
                        <i class="fas fa-save me-2"></i>Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="delete-sortie-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: 20px; box-shadow: var(--shadow-heavy);">
            <div class="modal-header" style="background: var(--danger-gradient); color: white; border-radius: 20px 20px 0 0; border: none;">
                <h4 class="modal-title" style="font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-exclamation-triangle"></i>
                    Confirmer la suppression
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 1;">
                    <span aria-hidden="true" style="font-size: 1.5rem;">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 2rem; text-align: center;">
                <div style="font-size: 4rem; color: #dc3545; margin-bottom: 1rem;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h5 style="color: #2c3e50; margin-bottom: 1rem;">Êtes-vous sûr de vouloir supprimer cette sortie ?</h5>
                <p style="color: #6c757d; margin-bottom: 1.5rem;">Sortie N° <strong id="delete-numero"></strong></p>
                <div class="alert alert-warning" style="border-radius: 12px; border: none; background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 107, 107, 0.1));">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Attention :</strong> Cette action est irréversible !
                </div>
            </div>
            <div class="modal-footer" style="background: #f8f9fa; border-radius: 0 0 20px 20px; border: none; padding: 1.5rem 2rem; justify-content: center;">
                <button type="button" class="btn" data-dismiss="modal" style="background: #6c757d; color: white; border: none; border-radius: 10px; padding: 12px 24px; font-weight: 600; margin-right: 1rem;">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <button type="button" class="btn btn-confirm-delete" style="background: var(--danger-gradient); color: white; border: none; border-radius: 10px; padding: 12px 24px; font-weight: 600; box-shadow: var(--shadow-light);">
                    <i class="fas fa-trash me-2"></i>Supprimer définitivement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- jQuery et Bootstrap CDN -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables CDN -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>

<!-- Modern JavaScript -->
<script>
$(document).ready(function() {
    // Initialize DataTable with modern styling
    $("#example1").DataTable({
        "responsive": true,
        "pageLength": <?= $limit ?>,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/French.json"
        },
        "order": [[0, "desc"]],
        "dom": '<"d-flex justify-content-between align-items-center mb-3"<"d-flex align-items-center"l><"d-flex align-items-center"f>>rtip',
        "drawCallback": function() {
            // Add modern styling to DataTable elements
            $('.dataTables_length select').addClass('form-select form-select-sm');
            $('.dataTables_filter input').addClass('form-control form-control-sm');
            
            // Réattacher les événements après redraw de DataTables
            attachSortieEvents();
        }
    });

    // Modern form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();

    // Enhanced modal interactions
    $('.modal').on('show.bs.modal', function() {
        $(this).find('.modal-content').css({
            'transform': 'scale(0.8)',
            'opacity': '0'
        }).animate({
            'transform': 'scale(1)',
            'opacity': '1'
        }, 300);
    });

    $('.modal').on('hidden.bs.modal', function() {
        $(this).find('form').trigger('reset');
        $(this).find('.was-validated').removeClass('was-validated');
    });

    // Enhanced number formatting for amount input
    $('#montant').on('input', function(e) {
        let value = this.value.replace(/\D/g, '');
        if (value !== '') {
            value = parseInt(value).toLocaleString('fr-FR');
        }
        this.value = value;
    });

    // Form submission handling
    $('form[action="save_sortie_diverse.php"]').on('submit', function(e) {
        let montantInput = $('#montant');
        let cleanValue = montantInput.val().replace(/\s/g, '');
        montantInput.val(cleanValue);
        
        // Show loading state
        $(this).find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin me-2"></i>Traitement...');
    });

    // Date validation for print modal
    $('form[action="impression_sorties_diverses.php"]').on('submit', function(e) {
        const dateDebut = $('#date_debut').val();
        const dateFin = $('#date_fin').val();

        if (!dateDebut || !dateFin) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Dates manquantes',
                text: 'Veuillez sélectionner les dates de début et de fin',
                confirmButtonColor: '#667eea'
            });
            return;
        }

        if (dateDebut > dateFin) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Dates invalides',
                text: 'La date de début doit être antérieure à la date de fin',
                confirmButtonColor: '#667eea'
            });
            return;
        }

        // Show loading state
        $(this).find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin me-2"></i>Génération...');
    });

    // Smooth animations for statistics cards
    $('.stat-item').each(function(index) {
        $(this).delay(index * 100).animate({
            opacity: 1,
            transform: 'translateY(0)'
        }, 600);
    });

    // Enhanced hover effects
    $('.btn-modern').hover(
        function() {
            $(this).css('transform', 'translateY(-2px) scale(1.02)');
        },
        function() {
            $(this).css('transform', 'translateY(0) scale(1)');
        }
    );

    // Fonction pour attacher les événements des boutons
    function attachSortieEvents() {
        // Supprimer les anciens événements pour éviter les doublons
        $('.btn-view-sortie, .btn-edit-sortie, .btn-delete-sortie').off('click');
        
        // Bouton Voir détails
        $('.btn-view-sortie').on('click', function(e) {
            e.preventDefault();
            const data = $(this).data();
            
            $('#view-numero').text(data.numero);
            $('#view-date').text(new Date(data.date).toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }));
            $('#view-montant').text(parseInt(data.montant).toLocaleString('fr-FR') + ' FCFA');
            $('#view-motifs').text(data.motifs);
            
            $('#view-sortie-modal').modal('show');
        });
        
        // Bouton Modifier
        $('.btn-edit-sortie').on('click', function(e) {
            e.preventDefault();
            console.log('Clic sur modifier détecté');
            
            const data = $(this).data();
            console.log('Données:', data);
            
            // Vérifier si Bootstrap est chargé
            if (typeof $.fn.modal === 'undefined') {
                console.error('Bootstrap modal n\'est pas chargé');
                alert('Erreur: Bootstrap n\'est pas chargé correctement');
                return;
            }
            
            $('#edit-sortie-id').val(data.id);
            $('#edit-montant').val(parseInt(data.montant).toLocaleString('fr-FR'));
            $('#edit-motifs').val(data.motifs);
            
            console.log('Ouverture du modal...');
            $('#edit-sortie-modal').modal('show');
        });
        
        // Bouton Supprimer
        $('.btn-delete-sortie').on('click', function(e) {
            e.preventDefault();
            const data = $(this).data();
            
            $('#delete-numero').text(data.numero);
            $('.btn-confirm-delete').data('id', data.id);
            
            $('#delete-sortie-modal').modal('show');
        });
    }
    
    // Attacher les événements au chargement initial
    attachSortieEvents();
    
    // Formatage du montant dans le modal de modification
    $('#edit-montant').on('input', function(e) {
        let value = this.value.replace(/\D/g, '');
        if (value !== '') {
            value = parseInt(value).toLocaleString('fr-FR');
        }
        this.value = value;
    });
    
    // Soumission du formulaire de modification
    $('form[action="update_sortie_diverse.php"]').on('submit', function(e) {
        let montantInput = $('#edit-montant');
        let cleanValue = montantInput.val().replace(/\s/g, '');
        montantInput.val(cleanValue);
        
        // Show loading state
        $(this).find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin me-2"></i>Modification...');
    });
    
    // Confirmation de suppression
    $(document).on('click', '.btn-confirm-delete', function() {
        const sortieId = $(this).data('id');
        
        // Show loading state
        $(this).html('<i class="fas fa-spinner fa-spin me-2"></i>Suppression...');
        
        // Redirection vers le script de suppression
        window.location.href = 'delete_sortie_diverse.php?id=' + sortieId;
    });

    // Success/Error message handling
    <?php if (isset($_SESSION['success_message'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'Succès !',
            text: '<?= $_SESSION['success_message'] ?>',
            confirmButtonColor: '#56ab2f',
            timer: 3000,
            timerProgressBar: true
        });
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Erreur !',
            text: '<?= $_SESSION['error_message'] ?>',
            confirmButtonColor: '#ff6b6b'
        });
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
});

// Loading animation management
function showLoader() {
    $('#loader').fadeIn(300);
}

function hideLoader() {
    $('#loader').fadeOut(300);
}

// Enhanced page transitions
$(window).on('beforeunload', function() {
    showLoader();
});
</script>

<!-- SweetAlert2 for modern alerts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php include('footer.php'); ?>
</body>
</html>

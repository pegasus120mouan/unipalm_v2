<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_vehicules.php';
include('header.php');

// Récupérer la liste des véhicules
$stmt = $conn->prepare("SELECT * FROM vehicules ORDER BY created_at DESC");
$stmt->execute();
$vehicules = $stmt->fetchAll();

// Statistiques des véhicules
$stmt_stats = $conn->prepare("
    SELECT 
        COUNT(*) as total_vehicules,
        SUM(CASE WHEN type_vehicule = 'voiture' THEN 1 ELSE 0 END) as total_voitures,
        SUM(CASE WHEN type_vehicule = 'moto' THEN 1 ELSE 0 END) as total_motos,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as vehicules_aujourd_hui
    FROM vehicules
");
$stmt_stats->execute();
$stats = $stmt_stats->fetch();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Véhicules - UniPalm</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- DataTables Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: rgba(255, 255, 255, 0.18);
            --shadow-light: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            --shadow-hover: 0 15px 35px rgba(31, 38, 135, 0.1);
            --border-radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #f5576c, #4facfe, #00f2fe);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            color: #333;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .main-container {
            padding: 2rem;
            animation: fadeInUp 0.8s ease-out;
        }

        /* Header UniPalm */
        .unipalm-header {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: var(--shadow-light);
            animation: fadeInDown 0.8s ease-out;
        }

        .unipalm-logo {
            display: inline-flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .unipalm-logo i {
            font-size: 3rem;
            background: var(--success-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: pulse 2s infinite;
        }

        .unipalm-logo h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
        }

        .unipalm-subtitle {
            font-size: 1.2rem;
            color: #666;
            font-weight: 400;
        }

        /* Cartes statistiques */
        .stats-container {
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            animation: fadeInUp 0.8s ease-out;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.primary { background: var(--primary-gradient); }
        .stat-icon.success { background: var(--success-gradient); }
        .stat-icon.warning { background: var(--warning-gradient); }
        .stat-icon.danger { background: var(--danger-gradient); }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-weight: 500;
        }

        /* Conteneur principal */
        .main-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            overflow: hidden;
            animation: slideInRight 0.8s ease-out;
        }

        .card-header-custom {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem;
            border: none;
        }

        .card-header-custom h3 {
            margin: 0;
            font-weight: 600;
        }

        .btn-add {
            background: var(--success-gradient);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        /* Tableau */
        .table-container {
            padding: 1.5rem;
        }

        .table {
            background: transparent;
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .table thead th {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.01);
        }

        .table tbody td {
            padding: 1rem;
            border: none;
            color: #333;
            font-weight: 500;
        }

        .vehicle-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            color: white;
            font-size: 1.2rem;
        }

        .vehicle-icon.car { background: var(--primary-gradient); }
        .vehicle-icon.moto { background: var(--success-gradient); }

        .btn-action {
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-edit {
            background: var(--warning-gradient);
            color: white;
        }

        .btn-delete {
            background: var(--danger-gradient);
            color: white;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        /* Alertes */
        .alert-custom {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            color: #333;
            margin-bottom: 1rem;
            animation: fadeInDown 0.5s ease-out;
        }

        .alert-success-custom {
            border-left: 4px solid #00f2fe;
        }

        .alert-danger-custom {
            border-left: 4px solid #fee140;
        }

        /* Modals */
        .modal-content {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            color: white;
        }

        .modal-header {
            background: var(--primary-gradient);
            border: none;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .modal-title {
            font-weight: 600;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: #333;
            padding: 1.25rem 1.75rem;
            font-size: 1.2rem;
            min-height: 60px;
            transition: var(--transition);
        }

        .form-control-lg {
            padding: 1.5rem 2rem;
            font-size: 1.3rem;
            min-height: 65px;
            width: 100%;
        }

        select.form-control-lg {
            height: 70px !important;
            line-height: 1.5;
            padding-top: 1.2rem;
            padding-bottom: 1.2rem;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(102, 126, 234, 0.6);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.2);
            color: #333;
        }

        .form-control::placeholder {
            color: rgba(0, 0, 0, 0.5);
            font-size: 1.1rem;
        }

        select.form-control option {
            padding: 0.75rem;
            font-size: 1.2rem;
            color: #333;
            background: white;
        }

        .form-label {
            color: #333;
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .btn-modal {
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary-modal {
            background: var(--primary-gradient);
            border: none;
        }

        .btn-secondary-modal {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }

        .btn-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        /* DataTables customization */
        .dataTables_wrapper {
            color: #333;
        }

        .dataTables_filter input {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            color: #333;
            padding: 0.5rem 1rem;
        }

        .dataTables_length select {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            color: #333;
            padding: 0.25rem 0.5rem;
        }

        .page-link {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(0, 0, 0, 0.2);
            color: #333;
        }

        .page-link:hover {
            background: rgba(255, 255, 255, 0.9);
            color: #333;
        }

        .page-item.active .page-link {
            background: var(--primary-gradient);
            border-color: transparent;
        }

        /* Message vide */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }
            
            .unipalm-logo h1 {
                font-size: 2rem;
            }
            
            .stat-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header UniPalm -->
        <div class="unipalm-header">
            <div class="unipalm-logo">
                <i class="fas fa-leaf"></i>
                <h1>UniPalm</h1>
            </div>
            <p class="unipalm-subtitle">Gestion des Véhicules</p>
        </div>

        <!-- Messages d'alerte -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-custom alert-success-custom alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-custom alert-danger-custom alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Cartes statistiques -->
        <div class="stats-container">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fas fa-car-side"></i>
                        </div>
                        <div class="stat-number" id="totalVehicules"><?= $stats['total_vehicules'] ?></div>
                        <div class="stat-label">Total Véhicules</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-car"></i>
                        </div>
                        <div class="stat-number" id="totalVoitures"><?= $stats['total_voitures'] ?></div>
                        <div class="stat-label">Voitures</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="fas fa-motorcycle"></i>
                        </div>
                        <div class="stat-number" id="totalMotos"><?= $stats['total_motos'] ?></div>
                        <div class="stat-label">Motos</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon danger">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <div class="stat-number" id="vehiculesAujourdhui"><?= $stats['vehicules_aujourd_hui'] ?></div>
                        <div class="stat-label">Ajoutés Aujourd'hui</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau principal -->
        <div class="main-card">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h3><i class="fas fa-list me-2"></i>Liste des Véhicules</h3>
                <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addVehiculeModal">
                    <i class="fas fa-plus me-2"></i>Ajouter un véhicule
                </button>
            </div>
            <div class="table-container">
                <?php if (count($vehicules) > 0): ?>
                    <table id="vehiculesTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">Type</th>
                                <th>Matricule</th>
                                <th class="text-center">Date d'ajout</th>
                                <th class="text-center">Dernière modification</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicules as $vehicule): ?>
                                <tr>
                                    <td class="text-center">
                                        <div class="vehicle-icon <?= $vehicule['type_vehicule'] == 'voiture' ? 'car' : 'moto' ?>">
                                            <i class="fas fa-<?= $vehicule['type_vehicule'] == 'voiture' ? 'car' : 'motorcycle' ?>"></i>
                                        </div>
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($vehicule['matricule_vehicule']) ?></td>
                                    <td class="text-center"><?= date('d/m/Y', strtotime($vehicule['created_at'])) ?></td>
                                    <td class="text-center"><?= date('d/m/Y', strtotime($vehicule['updated_at'])) ?></td>
                                    <td class="text-center">
                                        <button type="button" 
                                                class="btn btn-action btn-edit edit-btn" 
                                                data-id="<?= htmlspecialchars($vehicule['vehicules_id']) ?>"
                                                data-matricule="<?= htmlspecialchars($vehicule['matricule_vehicule']) ?>"
                                                data-type="<?= htmlspecialchars($vehicule['type_vehicule']) ?>"
                                                title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-action btn-delete delete-btn"
                                                data-id="<?= htmlspecialchars($vehicule['vehicules_id']) ?>"
                                                title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-car-side"></i>
                        <h4>Aucun véhicule trouvé</h4>
                        <p>Commencez par ajouter votre premier véhicule</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Ajout Véhicule -->
    <div class="modal fade" id="addVehiculeModal" tabindex="-1" aria-labelledby="addVehiculeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addVehiculeModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>Ajouter un véhicule
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="add_vehicule.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-4">
                            <label for="matricule" class="form-label">
                                <i class="fas fa-id-card me-2"></i>Matricule
                            </label>
                            <input type="text" class="form-control form-control-lg" id="matricule" name="matricule" 
                                   placeholder="Entrez le matricule du véhicule" required>
                        </div>
                        <div class="mb-4">
                            <label for="type_vehicule" class="form-label">
                                <i class="fas fa-car me-2"></i>Type de véhicule
                            </label>
                            <select class="form-control form-control-lg" id="type_vehicule" name="type_vehicule" required>
                                <option value="">-- Sélectionnez un type de véhicule --</option>
                                <option value="voiture">🚗 Voiture</option>
                                <option value="moto">🏍️ Moto</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-modal btn-secondary-modal" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Annuler
                        </button>
                        <button type="submit" class="btn btn-modal btn-primary-modal">
                            <i class="fas fa-save me-2"></i>Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Modification Véhicule -->
    <div class="modal fade" id="editVehiculeModal" tabindex="-1" aria-labelledby="editVehiculeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editVehiculeModalLabel">
                        <i class="fas fa-edit me-2"></i>Modifier un véhicule
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="update_vehicule.php" method="POST" id="editVehiculeForm">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-4">
                            <label for="edit_matricule" class="form-label">
                                <i class="fas fa-id-card me-2"></i>Matricule
                            </label>
                            <input type="text" class="form-control form-control-lg" id="edit_matricule" name="matricule" 
                                   placeholder="Entrez le matricule du véhicule" required>
                        </div>
                        <div class="mb-4">
                            <label for="edit_type_vehicule" class="form-label">
                                <i class="fas fa-car me-2"></i>Type de véhicule
                            </label>
                            <select class="form-control form-control-lg" id="edit_type_vehicule" name="type_vehicule" required>
                                <option value="">-- Sélectionnez un type de véhicule --</option>
                                <option value="voiture">🚗 Voiture</option>
                                <option value="moto">🏍️ Moto</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-modal btn-secondary-modal" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Annuler
                        </button>
                        <button type="submit" class="btn btn-modal btn-primary-modal">
                            <i class="fas fa-save me-2"></i>Modifier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--danger-gradient);">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirmer la suppression
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-trash-alt" style="font-size: 3rem; color: #fee140; margin-bottom: 1rem;"></i>
                    <h4>Êtes-vous sûr ?</h4>
                    <p class="text-muted">Cette action est irréversible. Le véhicule sera définitivement supprimé.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal btn-secondary-modal" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-modal" id="confirmDeleteBtn" 
                            style="background: var(--danger-gradient); color: white;">
                        <i class="fas fa-trash me-2"></i>Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Form pour la suppression -->
    <form id="deleteForm" action="delete_vehicule.php" method="POST" style="display: none;">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
    $(document).ready(function() {
        // Animation des compteurs
        function animateCounter(element, target) {
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                $(element).text(Math.floor(current));
            }, 30);
        }

        // Animer les statistiques au chargement
        setTimeout(() => {
            animateCounter('#totalVehicules', <?= $stats['total_vehicules'] ?>);
            animateCounter('#totalVoitures', <?= $stats['total_voitures'] ?>);
            animateCounter('#totalMotos', <?= $stats['total_motos'] ?>);
            animateCounter('#vehiculesAujourdhui', <?= $stats['vehicules_aujourd_hui'] ?>);
        }, 500);

        // Initialisation de la DataTable
        <?php if (count($vehicules) > 0): ?>
        var table = $('#vehiculesTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "pageLength": 10,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json"
            },
            "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            "columnDefs": [
                { "orderable": false, "targets": [0, 4] }
            ]
        });
        <?php endif; ?>

        // Gestionnaire d'événement pour le bouton d'édition
        $(document).on('click', '.edit-btn', function(e) {
            e.preventDefault();
            
            var id = $(this).data('id');
            var matricule = $(this).data('matricule');
            var type = $(this).data('type');
            
            $('#edit_id').val(id);
            $('#edit_matricule').val(matricule);
            $('#edit_type_vehicule').val(type);
            
            var editModal = new bootstrap.Modal(document.getElementById('editVehiculeModal'));
            editModal.show();
        });

        // Gestionnaire pour la suppression
        var vehicleIdToDelete = null;
        
        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            vehicleIdToDelete = $(this).data('id');
            
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            deleteModal.show();
        });

        // Confirmation de suppression
        $('#confirmDeleteBtn').on('click', function() {
            if (vehicleIdToDelete) {
                $('#delete_id').val(vehicleIdToDelete);
                $('#deleteForm').submit();
            }
        });

        // Validation des formulaires
        $('form').on('submit', function(e) {
            var form = $(this);
            var submitBtn = form.find('button[type="submit"]');
            
            // Désactiver le bouton et ajouter un spinner
            submitBtn.prop('disabled', true);
            submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Traitement...');
            
            // Réactiver après 3 secondes si pas de redirection
            setTimeout(() => {
                submitBtn.prop('disabled', false);
                if (form.attr('id') === 'editVehiculeForm') {
                    submitBtn.html('<i class="fas fa-save me-2"></i>Modifier');
                } else {
                    submitBtn.html('<i class="fas fa-save me-2"></i>Ajouter');
                }
            }, 3000);
        });

        // Effets visuels supplémentaires
        $('.stat-card').hover(
            function() {
                $(this).addClass('animate__animated animate__pulse');
            },
            function() {
                $(this).removeClass('animate__animated animate__pulse');
            }
        );

        // Animation d'apparition des éléments
        $('.main-container').addClass('animate__animated animate__fadeIn');
        $('.stat-card').each(function(index) {
            $(this).addClass('animate__animated animate__fadeInUp');
            $(this).css('animation-delay', (index * 0.1) + 's');
        });
    });
    </script>
</body>
</html>

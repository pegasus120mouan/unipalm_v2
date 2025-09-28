<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_tickets.php';
require_once '../inc/functions/requete/requete_usines.php';
require_once '../inc/functions/requete/requete_chef_equipes.php';
require_once '../inc/functions/requete/requete_vehicules.php';
require_once '../inc/functions/requete/requete_agents.php';
//require_once '../inc/functions/requete/requetes_selection_boutique.php';
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

// Récupérer les données (functions)
$tickets = getTickets($conn); 
$usines = getMontantUsines($conn);
$chefs_equipes=getChefEquipes($conn);
$vehicules=getVehicules($conn);
$agents=getAgents($conn);

// Récupérer les montants totaux pour chaque usine
$montants_usines = [];
foreach ($usines as $usine) {
    $sql = "SELECT COALESCE(SUM(montant_paie), 0) as total_montant FROM tickets WHERE id_usine = :id_usine";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id_usine' => $usine['id_usine']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $montants_usines[$usine['id_usine']] = $result['total_montant'];
}

// Calculer le montant total
$montant_total = 0;
foreach ($usines as $usine) {
    $montant_total += floatval($montants_usines[$usine['id_usine']]);
}

// Calculer le montant total payé
$sql_paye = "SELECT SUM(montant_paye) as total_paye FROM usines";
$stmt = $conn->prepare($sql_paye);
$stmt->execute();
$result_paye = $stmt->fetch(PDO::FETCH_ASSOC);
$montant_total_paye = $result_paye['total_paye'] ?? 0;

// Calculer le montant total restant
$sql_restant = "SELECT SUM(montant_restant) as total_restant FROM usines";
$stmt = $conn->prepare($sql_restant);
$stmt->execute();
$result_restant = $stmt->fetch(PDO::FETCH_ASSOC);
$montant_total_restant = $result_restant['total_restant'] ?? 0;

$montant_total_restant = $montant_total - $montant_total_paye;



// Vérifiez si des tickets existent avant de procéder
if (!empty($usines)) {
    $usine_pages = array_chunk($usines, $limit); // Divise les tickets en pages
    $usines_list = $usine_pages[$page - 1] ?? []; // Tickets pour la page actuelle
} else {
    $usines_list = []; // Aucun ticket à afficher
}

?>




<!-- Main row -->
<style>
    /* ===== STYLES ULTRA-PROFESSIONNELS POUR GESTION USINES ===== */
    
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
        text-align: center;
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
    }

    .stat-card.total::before { background: var(--primary-gradient); }
    .stat-card.paye::before { background: var(--success-gradient); }
    .stat-card.restant::before { background: var(--warning-gradient); }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        margin: 0 auto 1rem;
    }

    .stat-value {
        font-size: 1.8rem;
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

    /* Action Button */
    .action-button {
        background: var(--danger-gradient);
        color: white;
        border: none;
        border-radius: var(--border-radius);
        padding: 1rem 2rem;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: var(--transition);
        box-shadow: var(--shadow-light);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0 auto;
    }

    .action-button:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-heavy);
        color: white;
        text-decoration: none;
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
        padding: 1.5rem 1rem;
        border-bottom: 1px solid #f8f9fa;
        font-size: 0.95rem;
        vertical-align: middle;
        text-align: center;
    }

    .table-professional tbody tr:last-child td {
        border-bottom: none;
    }

    /* Usine Card */
    .usine-card {
        background: var(--glass-bg);
        border: 2px solid transparent;
        border-radius: 12px;
        padding: 1rem;
        transition: var(--transition);
        text-decoration: none;
        color: inherit;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: var(--shadow-light);
    }

    .usine-card:hover {
        border-color: #667eea;
        transform: translateY(-2px);
        box-shadow: var(--shadow-heavy);
        text-decoration: none;
        color: inherit;
    }

    .usine-icon {
        width: 50px;
        height: 50px;
        background: var(--primary-gradient);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
    }

    .usine-name {
        font-weight: 600;
        font-size: 1.1rem;
        color: #2c3e50;
    }

    /* Amount Cards */
    .amount-card {
        background: white;
        border-radius: 12px;
        padding: 1rem;
        box-shadow: var(--shadow-light);
        border-left: 4px solid;
        transition: var(--transition);
    }

    .amount-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-heavy);
    }

    .amount-card.total { border-left-color: #667eea; }
    .amount-card.paye { border-left-color: #56ab2f; }
    .amount-card.restant { border-left-color: #f093fb; }

    .amount-label {
        font-size: 0.8rem;
        color: #7f8c8d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .amount-value {
        font-size: 1.2rem;
        font-weight: 700;
        color: #2c3e50;
    }

    /* Action Button in Table */
    .btn-action {
        background: var(--success-gradient);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(86, 171, 47, 0.3);
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

    /* Form improvements */
    .form-group label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
        transition: var(--transition);
    }

    .form-group:focus-within label {
        color: #667eea;
    }

    .form-control.focused {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .page-header h1 {
            font-size: 2rem;
        }
        
        .stats-container {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .table-container {
            padding: 1rem;
            overflow-x: auto;
        }
        
        .pagination-container {
            flex-direction: column;
            align-items: stretch;
        }

        .usine-card {
            flex-direction: column;
            text-align: center;
        }

        .amount-card {
            margin: 0.5rem 0;
        }
    }

    @media (max-width: 576px) {
        .stats-container {
            grid-template-columns: 1fr;
        }

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

        .usine-card {
            padding: 0.75rem;
        }

        .usine-icon {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }

        .amount-card {
            padding: 0.75rem;
        }
    }
</style>


<!-- Page Header -->
<div class="page-header">
    <h1><i class="fas fa-industry mr-3"></i>Gestion des Usines</h1>
    <p>Gérez les paiements et suivez les montants par usine avec des outils d'analyse avancés</p>
</div>

<!-- Statistics Cards -->
<div class="stats-container">
    <div class="stat-card total">
        <div class="stat-icon" style="background: var(--primary-gradient);">
            <i class="fas fa-coins"></i>
        </div>
        <div class="stat-value"><?= number_format($montant_total, 0, ',', ' ') ?></div>
        <div class="stat-label">Montant Total (FCFA)</div>
    </div>
    
    <div class="stat-card paye">
        <div class="stat-icon" style="background: var(--success-gradient);">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-value"><?= number_format($montant_total_paye, 0, ',', ' ') ?></div>
        <div class="stat-label">Montant Payé (FCFA)</div>
    </div>
    
    <div class="stat-card restant">
        <div class="stat-icon" style="background: var(--warning-gradient);">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-value"><?= number_format($montant_total_restant, 0, ',', ' ') ?></div>
        <div class="stat-label">Reste à Payer (FCFA)</div>
    </div>
    
    <div class="stat-card">
        <button type="button" class="action-button" data-toggle="modal" data-target="#printPaiementsModal">
            <i class="fas fa-print"></i>
            Imprimer la Liste
        </button>
    </div>
</div>

<!-- Modal d'impression des paiements -->
<div class="modal fade" id="printPaiementsModal" tabindex="-1" role="dialog" aria-labelledby="printPaiementsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="printPaiementsModalLabel">
                    <i class="fas fa-print mr-2"></i>Impression des paiements par usine
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="usines_paiement_liste.php" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="id_usine">Sélectionner une usine</label>
                        <select name="id_usine" id="id_usine" class="form-control select2" style="width: 100%" required>
                            <option value="">Sélectionner une usine</option>
                            <?php foreach ($usines as $usine): ?>
                                <option value="<?= $usine['id_usine'] ?>"><?= htmlspecialchars($usine['nom_usine']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_debut">Date début</label>
                        <input type="date" class="form-control" id="date_debut" name="date_debut" required>
                    </div>
                    <div class="form-group">
                        <label for="date_fin">Date fin</label>
                        <input type="date" class="form-control" id="date_fin" name="date_fin" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-print mr-2"></i>Imprimer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        placeholder: 'Sélectionner une usine',
        allowClear: true
    });

    // Set default dates
    var today = new Date();
    var firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    
    $('#date_debut').val(firstDayOfMonth.toISOString().split('T')[0]);
    $('#date_fin').val(today.toISOString().split('T')[0]);

    // Date validation with modern alerts
    $('#date_fin').change(function() {
        var dateDebut = $('#date_debut').val();
        var dateFin = $(this).val();
        
        if(dateDebut && dateFin && dateDebut > dateFin) {
            Swal.fire({
                icon: 'warning',
                title: 'Date invalide',
                text: 'La date de fin doit être supérieure à la date de début',
                confirmButtonColor: '#667eea'
            });
            $(this).val('');
        }
    });

    $('#date_debut').change(function() {
        var dateDebut = $(this).val();
        var dateFin = $('#date_fin').val();
        
        if(dateDebut && dateFin && dateDebut > dateFin) {
            Swal.fire({
                icon: 'warning',
                title: 'Date invalide',
                text: 'La date de début doit être inférieure à la date de fin',
                confirmButtonColor: '#667eea'
            });
            $(this).val('');
        }
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

    // Validation des formulaires de paiement
    $('form[action="traitement_paiement.php"]').on('submit', function(e) {
        const montant = $(this).find('input[name="montant"]').val();
        const datePaiement = $(this).find('input[name="date_paiement"]').val();
        const modePaiement = $(this).find('select[name="mode_paiement"]').val();
        
        if (!montant || montant <= 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Montant invalide',
                text: 'Veuillez saisir un montant valide supérieur à 0',
                confirmButtonColor: '#667eea'
            });
            return false;
        }
        
        if (!datePaiement) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Date manquante',
                text: 'Veuillez sélectionner une date de paiement',
                confirmButtonColor: '#667eea'
            });
            return false;
        }
        
        if (!modePaiement) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Mode de paiement manquant',
                text: 'Veuillez sélectionner un mode de paiement',
                confirmButtonColor: '#667eea'
            });
            return false;
        }
        
        // Animation de soumission
        $(this).find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin mr-2"></i>Enregistrement...');
    });

    // Formatage automatique des montants
    $('input[name="montant"]').on('input', function() {
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
    $('input[name="montant"]').on('focus', function() {
        $(this).addClass('focused');
        const numericValue = $(this).attr('data-value');
        if (numericValue) {
            $(this).val(numericValue);
        }
    });

    $('input[name="montant"]').on('blur', function() {
        $(this).removeClass('focused');
        const value = $(this).val().replace(/\s/g, '').replace(/[^\d]/g, '');
        if (value) {
            $(this).attr('data-value', value);
            $(this).val(parseInt(value).toLocaleString('fr-FR'));
        }
    });

    // Avant soumission, restaurer la valeur numérique
    $('form[action="traitement_paiement.php"]').on('submit', function(e) {
        $(this).find('input[name="montant"]').each(function() {
            const numericValue = $(this).attr('data-value');
            if (numericValue) {
                $(this).val(numericValue);
            }
        });
    });

    console.log('✅ Page Gestion Usines initialisée avec succès');
    console.log('📊 Statistiques chargées:', {
        totalUsines: <?= count($usines_list) ?>,
        montantTotal: <?= $montant_total ?>,
        montantPaye: <?= $montant_total_paye ?>,
        montantRestant: <?= $montant_total_restant ?>
    });
});
</script>

<!-- Table Container -->
<div class="table-container">
    <div class="table-header">
        <div class="table-title">
            <i class="fas fa-list-alt"></i>
            Liste des Usines
        </div>
        <div class="badge badge-info">
            <i class="fas fa-database mr-1"></i>
            <?= count($usines_list) ?> usines
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table-professional w-100">
            <thead>
                <tr>
                    <th><i class="fas fa-industry mr-2"></i>Nom Usine</th>
                    <th><i class="fas fa-coins mr-2"></i>Total Montant</th>
                    <th><i class="fas fa-check-circle mr-2"></i>Montant Payé</th>
                    <th><i class="fas fa-clock mr-2"></i>Reste à Payer</th>
                    <th><i class="fas fa-cogs mr-2"></i>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($usines_list)): ?>
                    <?php foreach ($usines_list as $usine) : ?>
                    <tr>
                        <td data-label="Nom Usine">
                            <a href="details_usine.php?id=<?= $usine['id_usine'] ?>" class="usine-card">
                                <div class="usine-icon">
                                    <i class="fas fa-industry"></i>
                                </div>
                                <div class="usine-name">
                                    <?= htmlspecialchars($usine['nom_usine']) ?>
                                </div>
                            </a>
                        </td>
                        <td data-label="Total Montant">
                            <div class="amount-card total">
                                <div class="amount-label">
                                    <i class="fas fa-coins"></i>
                                    Total
                                </div>
                                <div class="amount-value">
                                    <?= number_format($montants_usines[$usine['id_usine']], 0, ',', ' ') ?> FCFA
                                </div>
                            </div>
                        </td>
                        <td data-label="Montant Payé">
                            <div class="amount-card paye">
                                <div class="amount-label">
                                    <i class="fas fa-check-circle"></i>
                                    Payé
                                </div>
                                <div class="amount-value">
                                    <?= number_format($usine['montant_paye'], 0, ',', ' ') ?> FCFA
                                </div>
                            </div>
                        </td>
                        <td data-label="Reste à Payer">
                            <div class="amount-card restant">
                                <div class="amount-label">
                                    <i class="fas fa-clock"></i>
                                    Reste
                                </div>
                                <div class="amount-value">
                                    <?= number_format($usine['montant_restant'], 0, ',', ' ') ?> FCFA
                                </div>
                            </div>
                        </td>
                        <td data-label="Actions">
                            <button type="button" class="btn-action" data-toggle="modal" data-target="#paiementModal<?= $usine['id_usine'] ?>">
                                <i class="fas fa-money-bill-wave"></i>
                                Effectuer Paiement
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="d-flex flex-column align-items-center">
                                <i class="fas fa-industry text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                                <h5 class="text-muted mb-2">Aucune usine trouvée</h5>
                                <p class="text-muted mb-3">Aucune usine ne correspond aux critères actuels</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modaux Paiement pour chaque usine -->
<?php foreach ($usines_list as $usine) : ?>
        <!-- Modal Paiement pour chaque usine -->
        <div class="modal fade" id="paiementModal<?= $usine['id_usine'] ?>" tabindex="-1" role="dialog" aria-labelledby="paiementModalLabel<?= $usine['id_usine'] ?>" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="paiementModalLabel<?= $usine['id_usine'] ?>">
                  Effectuer un paiement - <?= htmlspecialchars($usine['nom_usine']) ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <form action="traitement_paiement.php" method="POST">
                <div class="modal-body">
                  <input type="hidden" name="id_usine" value="<?= $usine['id_usine'] ?>">
                  
                  <div class="form-group">
                    <label for="montant<?= $usine['id_usine'] ?>">Montant du paiement (FCFA)</label>
                    <input type="number" class="form-control" id="montant<?= $usine['id_usine'] ?>" name="montant" required>
                  </div>
                  
                  <div class="form-group">
                    <label for="date_paiement<?= $usine['id_usine'] ?>">Date du paiement</label>
                    <input type="date" class="form-control" id="date_paiement<?= $usine['id_usine'] ?>" name="date_paiement" required>
                  </div>

                  <div class="form-group">
                    <label for="mode_paiement<?= $usine['id_usine'] ?>">Mode de paiement</label>
                    <select class="form-control" id="mode_paiement<?= $usine['id_usine'] ?>" name="mode_paiement" required>
                      <option value="">Sélectionner un mode de paiement</option>
                      <option value="Espèces">Espèces</option>
                      <option value="Chèque">Chèque</option>
                      <option value="Virement">Virement bancaire</option>
                      <option value="Mobile Money">Mobile Money</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label for="reference<?= $usine['id_usine'] ?>">Référence du paiement</label>
                    <input type="text" class="form-control" id="reference<?= $usine['id_usine'] ?>" name="reference" placeholder="N° chèque, N° transaction...">
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                  <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Enregistrer le paiement
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

<!-- Pagination Professional -->
<?php if (count($usine_pages) > 1): ?>
<div class="pagination-container">
    <div class="pagination-nav">
        <?php if($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="pagination-btn" title="Page précédente">
                <i class="fas fa-chevron-left"></i>
            </a>
        <?php endif; ?>
        
        <div class="pagination-info">
            Page <?= $page ?> sur <?= count($usine_pages) ?>
        </div>

        <?php if($page < count($usine_pages)): ?>
            <a href="?page=<?= $page + 1 ?>" class="pagination-btn" title="Page suivante">
                <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
    
    <form action="" method="get" class="items-per-page-form">
        <label for="limit" class="text-muted mr-2">
            <i class="fas fa-list mr-1"></i>Afficher :
        </label>
        <select name="limit" id="limit" class="items-per-page-select">
            <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>5 usines</option>
            <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10 usines</option>
            <option value="15" <?= $limit == 15 ? 'selected' : '' ?>>15 usines</option>
        </select>
        <button type="submit" class="submit-button ml-2">
            <i class="fas fa-check mr-1"></i>Appliquer
        </button>
    </form>
</div>
<?php endif; ?>

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

</body>

</html>
<?php
// Démarrer la session avant tout autre output
if (session_status() == PHP_SESSION_NONE) {
    //session_start();
}

require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_chef_equipes.php';
require_once '../inc/functions/requete/requete_agents.php';
require_once '../inc/functions/requete/requete_usines.php';
require_once '../inc/functions/requete/requete_tickets.php';

$chefs = getChefEquipesFull($conn); 
$usines = getUsines($conn);
$selectedChef = null;
$searchResults = [];

// Gestion des messages d'erreur de redirection
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'no_data':
            $pdfError = "Erreur : Aucune donnée PDF trouvée. Veuillez refaire votre sélection.";
            break;
        case 'no_tickets':
            $pdfError = "Erreur : Aucun ticket trouvé pour les critères sélectionnés.";
            break;
        default:
            $pdfError = "Erreur lors de la génération du PDF : " . htmlspecialchars($_GET['error']);
    }
}

if (isset($_POST['search_chef']) && !empty($_POST['chef_id'])) {
    $chefId = $_POST['chef_id'];
    $stmt = $conn->prepare("SELECT * FROM chef_equipe WHERE id_chef = ?");
    $stmt->execute([$chefId]);
    $selectedChef = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupération des agents associés avec plus de détails
    $stmtAgents = $conn->prepare("
        SELECT 
            agents.id_agent,
            agents.nom AS nom_agent,
            agents.prenom AS prenom_agent,
            CONCAT(agents.nom, ' ', agents.prenom) AS nom_complet_agent,
            agents.contact,
            agents.date_ajout
        FROM 
            agents
        WHERE 
            agents.id_chef = ?
        ORDER BY 
            agents.nom, agents.prenom
    ");
    $stmtAgents->execute([$chefId]);
    $searchResults['agents'] = $stmtAgents->fetchAll(PDO::FETCH_ASSOC);
}

// Traitement de la génération PDF
if (isset($_POST['generate_pdf'])) {
    // Récupérer les agents sélectionnés
    $selectedAgents = isset($_POST['selected_agents']) ? $_POST['selected_agents'] : [];
    
    if (!empty($selectedAgents) && !empty($_POST['chef_id'])) {
        // Récupérer les informations du chef d'équipe pour le PDF
        $chefId = $_POST['chef_id'];
        $stmt = $conn->prepare("SELECT * FROM chef_equipe WHERE id_chef = ?");
        $stmt->execute([$chefId]);
        $selectedChefForPDF = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Préparer les filtres pour les tickets
        $filters = [];
        
        // Filtre par usine si spécifiée
        if (!empty($_POST['usine_id'])) {
            $filters['usine'] = $_POST['usine_id'];
        }
        
        // Filtre par période si spécifiée
        if (!empty($_POST['date_debut'])) {
            $filters['date_debut'] = $_POST['date_debut'];
        }
        if (!empty($_POST['date_fin'])) {
            $filters['date_fin'] = $_POST['date_fin'];
        }
        
        // Récupérer tous les tickets pour les agents sélectionnés
        $allTickets = [];
        $agentsInfo = [];
        
        foreach ($selectedAgents as $agentId) {
            // Récupérer les informations de l'agent
            $stmtAgent = $conn->prepare("SELECT * FROM agents WHERE id_agent = ?");
            $stmtAgent->execute([$agentId]);
            $agentInfo = $stmtAgent->fetch(PDO::FETCH_ASSOC);
            if ($agentInfo) {
                $agentsInfo[] = $agentInfo;
            }
            
            // Récupérer les tickets de cet agent
            $filters['agent'] = $agentId;
            $agentTickets = getTickets($conn, $filters);
            $allTickets = array_merge($allTickets, $agentTickets);
        }
        
        if (!empty($allTickets)) {
            // Stocker les données dans la session pour le PDF
            $_SESSION['pdf_data'] = [
                'chef' => $selectedChefForPDF,
                'agents' => $selectedAgents,
                'agents_info' => $agentsInfo,
                'usine_id' => $_POST['usine_id'] ?? null,
                'date_debut' => $_POST['date_debut'] ?? null,
                'date_fin' => $_POST['date_fin'] ?? null,
                'tickets' => $allTickets
            ];
            
            // Message de succès avec détails
            $nbTickets = count($allTickets);
            $nbAgents = count($agentsInfo);
            $poidTotal = array_sum(array_column($allTickets, 'poids'));
            
            $pdfSuccess = "PDF généré avec succès ! $nbTickets ticket(s) trouvé(s) pour $nbAgents agent(s) sélectionné(s). Poids total: " . number_format($poidTotal, 0, ',', ' ') . " Kg.";
            
            // Rediriger vers la page de génération PDF
            echo "<script>window.location.href = 'redirect_to_pdf.php';</script>";
            exit;
        } else {
            $pdfError = "Aucun ticket trouvé pour les critères sélectionnés. Vérifiez que les agents ont des tickets dans la période spécifiée.";
        }
    }
}

// Inclure le header après le traitement
include('header_caisse.php');
?>

<style>
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
    --glass-bg: rgba(255, 255, 255, 0.95);
    --shadow-light: 0 8px 32px rgba(31, 38, 135, 0.15);
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
    padding: 2rem;
    margin-bottom: 2rem;
    text-align: center;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 0.5rem;
}

.search-card {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
    padding: 2rem;
    margin-bottom: 2rem;
}

.form-select-modern {
    height: 60px;
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    box-shadow: var(--shadow-light);
    transition: var(--transition);
    font-size: 1rem;
    font-weight: 500;
}

.form-select-modern:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn-search {
    height: 60px;
    background: var(--primary-gradient);
    border: none;
    border-radius: 15px;
    color: white;
    font-weight: 600;
    font-size: 1.1rem;
    transition: var(--transition);
    box-shadow: var(--shadow-light);
}

.btn-search:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
    color: white;
}

.result-card {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
    padding: 2rem;
    margin-bottom: 2rem;
}

.modern-table {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: var(--shadow-light);
}

.modern-table thead {
    background: var(--primary-gradient);
    color: white;
}

.modern-table th, .modern-table td {
    padding: 1rem;
    border: none;
    text-align: center;
}

.modern-table tbody tr:hover {
    background: rgba(102, 126, 234, 0.1);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.info-item {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
}

.info-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.info-value {
    font-size: 1.2rem;
    font-weight: 600;
    color: #2c3e50;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.form-check-input {
    border: 2px solid #667eea;
    border-radius: 6px;
    transition: var(--transition);
}

.form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-check-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.selection-controls {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.btn-select-all, .btn-select-none {
    background: var(--primary-gradient);
    border: none;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    transition: var(--transition);
    cursor: pointer;
}

.btn-select-all:hover, .btn-select-none:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.selected-count {
    font-weight: 600;
    color: #667eea;
}

.filter-section {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
    padding: 2rem;
    margin-top: 2rem;
}

.filter-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-label-modern {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.form-control-modern, .form-select-modern-small {
    height: 50px;
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    box-shadow: var(--shadow-light);
    transition: var(--transition);
    font-size: 0.95rem;
    font-weight: 500;
}

.form-control-modern:focus, .form-select-modern-small:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.date-input-group {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.date-separator {
    font-weight: 600;
    color: #6c757d;
    font-size: 1.1rem;
}

.btn-filter {
    height: 50px;
    background: var(--success-gradient);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    transition: var(--transition);
    box-shadow: var(--shadow-light);
}

.btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(86, 171, 47, 0.4);
    color: white;
}

.btn-pdf {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%) !important;
    color: white !important;
    border: none !important;
    border-radius: 12px !important;
    font-weight: 600 !important;
    transition: var(--transition) !important;
    box-shadow: var(--shadow-light) !important;
}

.btn-pdf:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 20px rgba(231, 76, 60, 0.4) !important;
    color: white !important;
}

.btn-pdf:disabled {
    opacity: 0.6 !important;
    cursor: not-allowed !important;
    transform: none !important;
}

@media (max-width: 768px) {
    .date-input-group {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .date-separator {
        display: none;
    }
}
</style>

<div class="content-wrapper">
    <div class="container-fluid p-4">
        <!-- Header -->
        <div class="page-header">
            <div style="font-size: 3rem; margin-bottom: 1rem;">
                <i class="fas fa-users-cog" style="background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
            </div>
            <h1 class="page-title">Recherche Chef d'Équipe</h1>
            <p style="color: #6c757d; font-size: 1.1rem;">Recherchez et consultez les informations des chefs d'équipe</p>
        </div>

        <!-- Messages PDF -->
        <?php if (isset($pdfError)): ?>
        <div class="alert alert-warning" style="background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 235, 59, 0.1)); border: 1px solid #ffc107; border-radius: 12px; padding: 1rem; margin-bottom: 1rem;">
            <i class="fas fa-exclamation-triangle me-2" style="color: #ff9800;"></i>
            <strong>Attention:</strong> <?= htmlspecialchars($pdfError) ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($pdfSuccess)): ?>
        <div class="alert alert-success" style="background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(144, 238, 144, 0.1)); border: 1px solid #28a745; border-radius: 12px; padding: 1rem; margin-bottom: 1rem;">
            <i class="fas fa-check-circle me-2" style="color: #28a745;"></i>
            <strong>Succès:</strong> <?= htmlspecialchars($pdfSuccess) ?>
            <br><br>
            <button onclick="openPDF()" class="btn btn-success" style="background: linear-gradient(135deg, #28a745, #20c997); border: none; padding: 10px 20px; border-radius: 8px; color: white; font-weight: 600;">
                <i class="fas fa-file-pdf me-2"></i>Ouvrir le PDF
            </button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($pdfReady)): ?>
        <div class="alert alert-info" style="background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(52, 144, 220, 0.1)); border: 1px solid #17a2b8; border-radius: 12px; padding: 1rem; margin-bottom: 1rem;">
            <i class="fas fa-info-circle me-2" style="color: #17a2b8;"></i>
            <strong>PDF Prêt:</strong> Votre bordereau de déchargement est prêt à être consulté.
            <br><br>
            <button onclick="openPDF()" class="btn" style="background: linear-gradient(135deg, #e74c3c, #c0392b); border: none; padding: 10px 20px; border-radius: 8px; color: white; font-weight: 600;">
                <i class="fas fa-file-pdf me-2"></i>Ouvrir le Bordereau PDF
            </button>
        </div>
        <script>
        function openPDF() {
            window.open('generate_bordereau_pdf_model.php', '_blank');
        }
        
        // Auto-ouvrir le PDF après un délai
        setTimeout(function() {
            if (confirm('Voulez-vous ouvrir le bordereau PDF maintenant ?')) {
                openPDF();
            }
        }, 1000);
        </script>
        <?php endif; ?>
        
        <!-- Message de succès dynamique (affiché via JavaScript) -->
        <div id="pdf-success-message" class="alert alert-success" style="background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(144, 238, 144, 0.1)); border: 1px solid #28a745; border-radius: 12px; padding: 1rem; margin-bottom: 1rem; display: none;">
            <i class="fas fa-file-pdf me-2" style="color: #28a745;"></i>
            <strong>PDF généré:</strong> Le bordereau de déchargement a été ouvert dans un nouvel onglet.
        </div>

        <!-- Formulaire de recherche -->
        <div class="search-card">
            <h3 style="margin-bottom: 1.5rem; color: #2c3e50;">
                <i class="fas fa-search" style="color: #667eea; margin-right: 0.5rem;"></i>
                Sélectionner un Chef d'Équipe
            </h3>
            
            <form method="POST" action="" class="row g-3">
                <div class="col-md-9">
                    <select name="chef_id" class="form-select form-select-modern" required>
                        <option value="">🔍 Choisissez un chef d'équipe...</option>
                        <?php foreach ($chefs as $chef): ?>
                            <option value="<?= $chef['id_chef'] ?>" 
                                    <?= (isset($_POST['chef_id']) && $_POST['chef_id'] == $chef['id_chef']) ? 'selected' : '' ?>>
                                👤 <?= htmlspecialchars($chef['nom'] . ' ' . $chef['prenoms']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" name="search_chef" class="btn btn-search w-100">
                        <i class="fas fa-search me-2"></i>
                        Rechercher
                    </button>
                </div>
            </form>
        </div>

        <!-- Résultats -->
        <?php if ($selectedChef): ?>
        <div class="result-card">
            <h3 style="color: #2c3e50; margin-bottom: 1.5rem;">
                <i class="fas fa-user-tie" style="color: #667eea; margin-right: 0.5rem;"></i>
                Informations du Chef d'Équipe
            </h3>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Nom Complet</div>
                    <div class="info-value"><?= htmlspecialchars($selectedChef['nom'] . ' ' . $selectedChef['prenoms']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">ID Chef</div>
                    <div class="info-value">#<?= $selectedChef['id_chef'] ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Agents Associés</div>
                    <div class="info-value"><?= count($searchResults['agents']) ?> agent(s)</div>
                </div>
            </div>
        </div>

        <?php if (!empty($searchResults['agents'])): ?>
        <div class="result-card">
            <h3 style="color: #2c3e50; margin-bottom: 1.5rem;">
                <i class="fas fa-users" style="color: #667eea; margin-right: 0.5rem;"></i>
                Agents de l'Équipe (<?= count($searchResults['agents']) ?> trouvé(s))
            </h3>
            
            <!-- Contrôles de sélection -->
            <div class="selection-controls">
                <div>
                    <span class="selected-count" id="selectedCount">0 agent(s) sélectionné(s)</span>
                </div>
                <div>
                    <button type="button" class="btn-select-all" onclick="selectAllAgents()">
                        <i class="fas fa-check-double me-1"></i>
                        Tout sélectionner
                    </button>
                    <button type="button" class="btn-select-none ms-2" onclick="selectNoneAgents()">
                        <i class="fas fa-times me-1"></i>
                        Tout désélectionner
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table modern-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-check-square me-2"></i>Sélection</th>
                            <th><i class="fas fa-hashtag me-2"></i>ID</th>
                            <th><i class="fas fa-user me-2"></i>Nom Complet</th>
                            <th><i class="fas fa-phone me-2"></i>Contact</th>
                            <th><i class="fas fa-calendar me-2"></i>Date d'ajout</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($searchResults['agents'] as $agent): ?>
                        <tr>
                            <td>
                                <div class="form-check d-flex justify-content-center">
                                    <input class="form-check-input agent-checkbox" type="checkbox" 
                                           value="<?= $agent['id_agent'] ?>" 
                                           id="agent_<?= $agent['id_agent'] ?>"
                                           style="transform: scale(1.2);">
                                </div>
                            </td>
                            <td><strong>#<?= $agent['id_agent'] ?></strong></td>
                            <td><?= htmlspecialchars($agent['nom_complet_agent']) ?></td>
                            <td><?= htmlspecialchars($agent['contact'] ?? 'Non renseigné') ?></td>
                            <td><?= date('d/m/Y', strtotime($agent['date_ajout'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Section de filtres -->
        <div class="filter-section">
            <h3 class="filter-title">
                <i class="fas fa-filter" style="color: #667eea;"></i>
                Filtres et Période
            </h3>
            
            <form method="POST" action="" class="row g-3" id="filter-form">
                <!-- Champ caché pour maintenir la sélection du chef -->
                <input type="hidden" name="chef_id" value="<?= htmlspecialchars($_POST['chef_id'] ?? '') ?>" id="hidden-chef-id">
                <input type="hidden" name="search_chef" value="1">
                
                <!-- Sélection d'usine -->
                <div class="col-md-4">
                    <label class="form-label form-label-modern">
                        <i class="fas fa-industry me-2"></i>Usine
                    </label>
                    <select name="usine_id" class="form-select form-select-modern-small">
                        <option value="">🏭 Toutes les usines</option>
                        <?php foreach ($usines as $usine): ?>
                            <option value="<?= $usine['id_usine'] ?>" 
                                    <?= (isset($_POST['usine_id']) && $_POST['usine_id'] == $usine['id_usine']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($usine['nom_usine']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Période -->
                <div class="col-md-6">
                    <label class="form-label form-label-modern">
                        <i class="fas fa-calendar-alt me-2"></i>Période
                    </label>
                    <div class="date-input-group">
                        <input type="date" name="date_debut" class="form-control form-control-modern" 
                               value="<?= htmlspecialchars($_POST['date_debut'] ?? '') ?>" 
                               placeholder="Date de début">
                        <span class="date-separator">au</span>
                        <input type="date" name="date_fin" class="form-control form-control-modern" 
                               value="<?= htmlspecialchars($_POST['date_fin'] ?? '') ?>" 
                               placeholder="Date de fin">
                    </div>
                </div>
                
                <!-- Boutons d'action -->
                <div class="col-md-2">
                    <label class="form-label form-label-modern" style="opacity: 0;">Actions</label>
                    <div class="d-grid gap-2">
                        <button type="submit" name="apply_filters" class="btn btn-filter">
                            <i class="fas fa-search me-2"></i>
                            Appliquer
                        </button>
                        <button type="button" class="btn btn-pdf" onclick="generatePDF()">
                            <i class="fas fa-file-pdf me-2"></i>
                            PDF
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- Informations sur les filtres appliqués -->
            <?php if (isset($_POST['apply_filters']) || isset($_POST['usine_id']) || isset($_POST['date_debut']) || isset($_POST['date_fin'])): ?>
            <div class="mt-3 p-3" style="background: linear-gradient(135deg, rgba(86, 171, 47, 0.1), rgba(168, 230, 207, 0.1)); border-radius: 10px;">
                <h6 style="color: #2c3e50; margin-bottom: 0.5rem;">
                    <i class="fas fa-info-circle me-2"></i>Filtres appliqués :
                </h6>
                <div class="d-flex flex-wrap gap-2">
                    <?php if (!empty($_POST['usine_id'])): ?>
                        <?php 
                        $selectedUsine = array_filter($usines, function($u) { return $u['id_usine'] == $_POST['usine_id']; });
                        $selectedUsine = reset($selectedUsine);
                        ?>
                        <span class="badge" style="background: var(--success-gradient); font-size: 0.85rem;">
                            🏭 <?= htmlspecialchars($selectedUsine['nom_usine']) ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($_POST['date_debut']) || !empty($_POST['date_fin'])): ?>
                        <span class="badge" style="background: var(--primary-gradient); font-size: 0.85rem;">
                            📅 
                            <?= !empty($_POST['date_debut']) ? date('d/m/Y', strtotime($_POST['date_debut'])) : '...' ?>
                            au 
                            <?= !empty($_POST['date_fin']) ? date('d/m/Y', strtotime($_POST['date_fin'])) : '...' ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="result-card">
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <h4>Aucun agent trouvé</h4>
                <p>Ce chef d'équipe n'a actuellement aucun agent associé dans le système.</p>
            </div>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="result-card">
            <div class="empty-state">
                <i class="fas fa-users-cog"></i>
                <h4>Prêt pour la recherche</h4>
                <p>Sélectionnez un chef d'équipe pour afficher ses informations détaillées et ses agents associés.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // Animation d'entrée
    $('.page-header, .search-card, .result-card').each(function(i) {
        $(this).delay(i * 200).animate({
            opacity: 1,
            transform: 'translateY(0)'
        }, 600);
    });
    
    // Gestion des checkboxes
    $('.agent-checkbox').on('change', function() {
        updateSelectedCount();
    });
    
    // Initialiser le compteur
    updateSelectedCount();
});

// Fonction pour sélectionner tous les agents
function selectAllAgents() {
    $('.agent-checkbox').prop('checked', true);
    updateSelectedCount();
}

// Fonction pour désélectionner tous les agents
function selectNoneAgents() {
    $('.agent-checkbox').prop('checked', false);
    updateSelectedCount();
}

// Fonction pour mettre à jour le compteur de sélection
function updateSelectedCount() {
    const selectedCount = $('.agent-checkbox:checked').length;
    const totalCount = $('.agent-checkbox').length;
    $('#selectedCount').text(selectedCount + ' agent(s) sélectionné(s) sur ' + totalCount);
}

// Fonction pour obtenir les IDs des agents sélectionnés
function getSelectedAgents() {
    const selectedIds = [];
    $('.agent-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });
    return selectedIds;
}

// Fonction utilitaire pour afficher les agents sélectionnés (pour debug ou utilisation future)
function showSelectedAgents() {
    const selected = getSelectedAgents();
    if (selected.length > 0) {
        console.log('Agents sélectionnés:', selected);
        alert('Agents sélectionnés: ' + selected.join(', '));
    } else {
        alert('Aucun agent sélectionné');
    }
}

// Validation des dates
function validateDateRange() {
    const dateDebut = document.querySelector('input[name="date_debut"]');
    const dateFin = document.querySelector('input[name="date_fin"]');
    
    if (dateDebut && dateFin) {
        dateDebut.addEventListener('change', function() {
            if (dateFin.value && this.value > dateFin.value) {
                alert('⚠️ La date de début ne peut pas être postérieure à la date de fin');
                this.value = '';
            }
        });
        
        dateFin.addEventListener('change', function() {
            if (dateDebut.value && this.value < dateDebut.value) {
                alert('⚠️ La date de fin ne peut pas être antérieure à la date de début');
                this.value = '';
            }
        });
    }
}

// Fonction pour générer le PDF
function generatePDF() {
    const selectedAgents = getSelectedAgents();
    // Récupérer le chef_id depuis le champ caché du formulaire de filtres
    const chefId = $('#hidden-chef-id').val() || $('select[name="chef_id"]').val();
    const usineId = $('select[name="usine_id"]').val();
    const dateDebut = $('input[name="date_debut"]').val();
    const dateFin = $('input[name="date_fin"]').val();
    
    console.log('Debug PDF:', {
        chefId: chefId,
        selectedAgents: selectedAgents,
        usineId: usineId,
        dateDebut: dateDebut,
        dateFin: dateFin
    });
    
    if (!chefId) {
        alert('⚠️ Veuillez d\'abord sélectionner un chef d\'équipe et faire une recherche');
        return;
    }
    
    if (selectedAgents.length === 0) {
        alert('⚠️ Veuillez sélectionner au moins un agent');
        return;
    }
    
    // Créer un formulaire temporaire pour envoyer les données
    const form = $('<form>', {
        method: 'POST',
        action: '',
        style: 'display: none;'
    });
    
    // Ajouter les champs
    form.append($('<input>', { name: 'generate_pdf', value: '1' }));
    form.append($('<input>', { name: 'chef_id', value: chefId }));
    form.append($('<input>', { name: 'search_chef', value: '1' }));
    
    if (usineId) {
        form.append($('<input>', { name: 'usine_id', value: usineId }));
    }
    if (dateDebut) {
        form.append($('<input>', { name: 'date_debut', value: dateDebut }));
    }
    if (dateFin) {
        form.append($('<input>', { name: 'date_fin', value: dateFin }));
    }
    
    // Ajouter les agents sélectionnés
    selectedAgents.forEach(function(agentId) {
        form.append($('<input>', { name: 'selected_agents[]', value: agentId }));
    });
    
    // Ajouter le formulaire au DOM et le soumettre
    $('body').append(form);
    form.submit();
}

// Initialiser la validation des dates
$(document).ready(function() {
    validateDateRange();
    
    // Animation d'entrée pour la section de filtres
    $('.filter-section').delay(800).animate({
        opacity: 1,
        transform: 'translateY(0)'
    }, 600);
});
</script>

<?php include('footer.php'); ?>

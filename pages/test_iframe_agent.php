<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_tickets.php';
require_once '../inc/functions/requete/requete_usines.php';
require_once '../inc/functions/requete/requete_chef_equipes.php';
require_once '../inc/functions/requete/requete_vehicules.php';
require_once '../inc/functions/requete/requete_agents.php';
include('header.php');

$agents = getAgents($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Iframe Agent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid mt-3">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Liste des Agents (<?= count($agents) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Contact</th>
                                        <th>Chef d'Équipe</th>
                                        <th>Date Ajout</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($agents, 0, 10) as $agent): ?>
                                    <tr>
                                        <td><?= $agent['id_agent'] ?></td>
                                        <td><?= htmlspecialchars($agent['nom_agent']) ?></td>
                                        <td><?= htmlspecialchars($agent['prenom_agent']) ?></td>
                                        <td><?= htmlspecialchars($agent['contact']) ?></td>
                                        <td><?= htmlspecialchars($agent['chef_equipe']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($agent['date_ajout'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button onclick="document.getElementById('agentFrame').src = document.getElementById('agentFrame').src" class="btn btn-secondary">
                            <i class="fas fa-sync"></i> Actualiser
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-user-plus"></i> Ajouter un Agent</h5>
                    </div>
                    <div class="card-body p-0">
                        <iframe id="agentFrame" src="add_agent_simple.php" 
                                style="width: 100%; height: 600px; border: none;"
                                onload="this.style.height = this.contentWindow.document.body.scrollHeight + 'px'">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    
    <script>
        // Écouter les messages de l'iframe
        window.addEventListener('message', function(event) {
            if (event.data === 'agent_added') {
                // Recharger la page principale
                location.reload();
            }
        });
    </script>
</body>
</html>

<?php
require_once '../inc/functions/connexion.php';

// Récupérer les chefs d'équipe
$stmt = $conn->prepare("SELECT id_chef, CONCAT(nom, ' ', prenoms) as chef_nom_complet FROM chef_equipe ORDER BY nom");
$stmt->execute();
$chefs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_SESSION['popup'])) {
    echo "<div style='padding: 10px; background: " . ($_SESSION['status'] == 'success' ? '#d4edda' : '#f8d7da') . "; border: 1px solid " . ($_SESSION['status'] == 'success' ? '#c3e6cb' : '#f5c6cb') . "; margin: 10px 0;'>";
    echo $_SESSION['message'];
    echo "</div>";
    unset($_SESSION['popup'], $_SESSION['message'], $_SESSION['status']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Formulaire Agent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Test Formulaire d'Ajout d'Agent</h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Ajouter un Agent</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="traitement_agents.php">
                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom *</label>
                                <input type="text" class="form-control" id="nom" name="nom" required>
                            </div>

                            <div class="mb-3">
                                <label for="prenom" class="form-label">Prénom *</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" required>
                            </div>

                            <div class="mb-3">
                                <label for="contact" class="form-label">Contact *</label>
                                <input type="text" class="form-control" id="contact" name="contact" required>
                            </div>

                            <div class="mb-3">
                                <label for="id_chef" class="form-label">Chef d'Équipe *</label>
                                <select id="id_chef" name="id_chef" class="form-control" required>
                                    <option value="">Sélectionner un chef d'équipe</option>
                                    <?php if (!empty($chefs)): ?>
                                        <?php foreach ($chefs as $chef): ?>
                                            <option value="<?= htmlspecialchars($chef['id_chef']) ?>">
                                                <?= htmlspecialchars($chef['chef_nom_complet']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">Aucun chef d'équipe disponible</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <button type="submit" name="add_agent" class="btn btn-primary">
                                Ajouter Agent
                            </button>
                            <a href="agents.php" class="btn btn-secondary">Retour</a>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Agents Récents</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $conn->prepare("SELECT * FROM agents WHERE date_suppression IS NULL ORDER BY date_ajout DESC LIMIT 5");
                        $stmt->execute();
                        $agents = $stmt->fetchAll();

                        if ($agents) {
                            echo "<table class='table table-sm'>";
                            echo "<tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Contact</th></tr>";
                            foreach ($agents as $agent) {
                                echo "<tr>";
                                echo "<td>{$agent['id_agent']}</td>";
                                echo "<td>{$agent['nom']}</td>";
                                echo "<td>{$agent['prenom']}</td>";
                                echo "<td>{$agent['contact']}</td>";
                                echo "</tr>";
                            }
                            echo "</table>";
                        } else {
                            echo "<p>Aucun agent trouvé</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

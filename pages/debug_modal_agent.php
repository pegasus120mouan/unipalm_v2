<?php
require_once '../inc/functions/connexion.php';

// Récupérer les chefs d'équipe
$stmt = $conn->prepare("SELECT id_chef, CONCAT(nom, ' ', prenoms) as chef_nom_complet FROM chef_equipe ORDER BY nom");
$stmt->execute();
$chefs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Debug Modal Agent</h2>";
echo "<p>Nombre de chefs trouvés: " . count($chefs) . "</p>";

if (isset($_POST['add_agent'])) {
    echo "<h3>Données reçues:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Vérifier si l'ID chef est numérique
    if (is_numeric($_POST['id_chef'])) {
        echo "<p style='color: green;'>✅ ID Chef est numérique: " . $_POST['id_chef'] . "</p>";
    } else {
        echo "<p style='color: red;'>❌ ID Chef n'est pas numérique: " . $_POST['id_chef'] . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Modal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-3">
        
        <!-- Bouton pour ouvrir le modal -->
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add-agent">
            Ouvrir Modal Agent
        </button>
        
        <!-- Modal -->
        <div class="modal fade" id="add-agent" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ajouter Agent</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form method="post">
                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="contact" class="form-label">Contact</label>
                                <input type="text" class="form-control" id="contact" name="contact" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="id_chef" class="form-label">Chef d'Équipe</label>
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
                            
                            <button type="submit" name="add_agent" class="btn btn-success">
                                Ajouter Agent (Debug)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <h3>Chefs disponibles:</h3>
        <ul>
        <?php foreach ($chefs as $chef): ?>
            <li>ID: <?= $chef['id_chef'] ?> - <?= $chef['chef_nom_complet'] ?></li>
        <?php endforeach; ?>
        </ul>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

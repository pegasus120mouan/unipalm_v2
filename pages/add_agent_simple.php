<?php
require_once '../inc/functions/connexion.php';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_agent'])) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $contact = trim($_POST['contact']);
    $id_chef = $_POST['id_chef'];
    $cree_par = $_SESSION['user_id'] ?? 1;
    
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;'>";
    echo "<h3>Données reçues :</h3>";
    echo "Nom: " . htmlspecialchars($nom) . "<br>";
    echo "Prénom: " . htmlspecialchars($prenom) . "<br>";
    echo "Contact: " . htmlspecialchars($contact) . "<br>";
    echo "ID Chef: " . htmlspecialchars($id_chef) . "<br>";
    echo "Créé par: " . $cree_par . "<br>";
    echo "</div>";
    
    if (!empty($nom) && !empty($prenom) && !empty($contact) && !empty($id_chef)) {
        try {
            $stmt = $conn->prepare("INSERT INTO agents (nom, prenom, contact, id_chef, cree_par, date_ajout) VALUES (?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([$nom, $prenom, $contact, $id_chef, $cree_par]);
            
            if ($result) {
                $last_id = $conn->lastInsertId();
                echo "<div style='background: #d4edda; padding: 10px; margin: 10px; border: 1px solid #c3e6cb; color: #155724;'>";
                echo "✅ Agent ajouté avec succès ! ID: $last_id";
                echo "</div>";
                
                // Script pour fermer la fenêtre et rafraîchir la page parent
                echo "<script>";
                echo "setTimeout(function() {";
                echo "  if (window.opener) {";
                echo "    window.opener.location.reload();";
                echo "    window.close();";
                echo "  } else {";
                echo "    window.location.href = 'agents.php';";
                echo "  }";
                echo "}, 2000);";
                echo "</script>";
            } else {
                echo "<div style='background: #f8d7da; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; color: #721c24;'>";
                echo "❌ Erreur lors de l'ajout";
                echo "</div>";
            }
        } catch (PDOException $e) {
            echo "<div style='background: #f8d7da; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; color: #721c24;'>";
            echo "❌ Erreur PDO: " . $e->getMessage();
            echo "</div>";
        }
    } else {
        echo "<div style='background: #fff3cd; padding: 10px; margin: 10px; border: 1px solid #ffeaa7; color: #856404;'>";
        echo "⚠️ Tous les champs sont obligatoires !";
        echo "</div>";
    }
}

// Récupérer les chefs d'équipe
$stmt = $conn->prepare("SELECT id_chef, CONCAT(nom, ' ', prenoms) as chef_nom_complet FROM chef_equipe ORDER BY nom");
$stmt->execute();
$chefs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Agent - UniPalm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4>Ajouter un Agent - Version Simple</h4>
                    </div>
                    <div class="card-body">
                        <form method="post">
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
                                <select id="id_chef" name="id_chef" class="form-select" required>
                                    <option value="">-- Choisir un chef --</option>
                                    <?php foreach ($chefs as $chef): ?>
                                        <option value="<?= $chef['id_chef'] ?>">
                                            <?= htmlspecialchars($chef['chef_nom_complet']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Chefs disponibles: <?= count($chefs) ?></div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="add_agent" class="btn btn-primary">
                                    Ajouter Agent
                                </button>
                                <a href="agents.php" class="btn btn-secondary">
                                    Retour à la liste
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Affichage des agents récents -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Agents Récents</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $conn->prepare("SELECT * FROM agents WHERE date_suppression IS NULL ORDER BY date_ajout DESC LIMIT 5");
                        $stmt->execute();
                        $agents_recents = $stmt->fetchAll();
                        
                        if ($agents_recents) {
                            echo "<table class='table table-sm'>";
                            echo "<tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Contact</th><th>Date</th></tr>";
                            foreach ($agents_recents as $agent) {
                                echo "<tr>";
                                echo "<td>{$agent['id_agent']}</td>";
                                echo "<td>{$agent['nom']}</td>";
                                echo "<td>{$agent['prenom']}</td>";
                                echo "<td>{$agent['contact']}</td>";
                                echo "<td>" . date('d/m/Y H:i', strtotime($agent['date_ajout'])) . "</td>";
                                echo "</tr>";
                            }
                            echo "</table>";
                        } else {
                            echo "<p class='text-muted'>Aucun agent trouvé</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

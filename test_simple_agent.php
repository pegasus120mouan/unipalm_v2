<?php
require_once 'inc/functions/connexion.php';

// Simuler une session utilisateur
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

echo "<h2>Test Simple d'Ajout d'Agent</h2>";

// Vérifier les chefs d'équipe disponibles
$stmt = $conn->prepare("SELECT id_chef, CONCAT(nom, ' ', prenoms) as chef_nom_complet FROM chef_equipe ORDER BY nom LIMIT 1");
$stmt->execute();
$chef = $stmt->fetch();

if (!$chef) {
    echo "<p style='color: red;'>❌ Aucun chef d'équipe trouvé ! Créez d'abord un chef d'équipe.</p>";
    exit;
}

echo "<p>Chef d'équipe trouvé : {$chef['chef_nom_complet']} (ID: {$chef['id_chef']})</p>";

// Test d'ajout direct
if (isset($_POST['test_add'])) {
    $nom = "TestAgent";
    $prenom = "Nouveau";
    $contact = "0123456789";
    $id_chef = $chef['id_chef'];
    $cree_par = $_SESSION['user_id'];
    
    try {
        $stmt = $conn->prepare("INSERT INTO agents (nom, prenom, contact, id_chef, cree_par, date_ajout) VALUES (?, ?, ?, ?, ?, NOW())");
        $result = $stmt->execute([$nom, $prenom, $contact, $id_chef, $cree_par]);
        
        if ($result) {
            $last_id = $conn->lastInsertId();
            echo "<p style='color: green;'>✅ Agent ajouté avec succès ! ID: $last_id</p>";
        } else {
            echo "<p style='color: red;'>❌ Échec de l'ajout</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Erreur: " . $e->getMessage() . "</p>";
    }
}

// Simuler l'ajout via le formulaire
if (isset($_POST['simulate_form'])) {
    echo "<h3>Simulation du formulaire agents.php</h3>";
    
    // Simuler les données POST du formulaire
    $_POST['add_agent'] = true;
    $_POST['nom'] = "FormAgent";
    $_POST['prenom'] = "Test";
    $_POST['contact'] = "0987654321";
    $_POST['id_chef'] = $chef['id_chef'];
    
    // Inclure le traitement
    include 'pages/traitement_agents.php';
}
?>

<form method="post">
    <button type="submit" name="test_add" style="padding: 10px; background: #28a745; color: white; border: none; margin: 5px;">Test Direct</button>
    <button type="submit" name="simulate_form" style="padding: 10px; background: #007bff; color: white; border: none; margin: 5px;">Simuler Formulaire</button>
</form>

<h3>Agents existants :</h3>
<?php
$stmt = $conn->prepare("SELECT * FROM agents WHERE date_suppression IS NULL ORDER BY date_ajout DESC LIMIT 5");
$stmt->execute();
$agents = $stmt->fetchAll();

if ($agents) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Contact</th><th>Date</th></tr>";
    foreach ($agents as $agent) {
        echo "<tr>";
        echo "<td>{$agent['id_agent']}</td>";
        echo "<td>{$agent['nom']}</td>";
        echo "<td>{$agent['prenom']}</td>";
        echo "<td>{$agent['contact']}</td>";
        echo "<td>{$agent['date_ajout']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Aucun agent trouvé</p>";
}
?>

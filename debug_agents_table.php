<?php
require_once 'inc/functions/connexion.php';

echo "<h2>Structure de la table agents</h2>";

try {
    // Vérifier la structure de la table agents
    $stmt = $conn->prepare("DESCRIBE agents");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Colonnes de la table agents :</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Vérifier les chefs d'équipe disponibles
    echo "<h3>Chefs d'équipe disponibles :</h3>";
    $stmt = $conn->prepare("SELECT id_chef, nom, prenoms FROM chef_equipe ORDER BY nom");
    $stmt->execute();
    $chefs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach ($chefs as $chef) {
        echo "<li>ID: " . $chef['id_chef'] . " - " . $chef['nom'] . " " . $chef['prenoms'] . "</li>";
    }
    echo "</ul>";
    
    // Test d'insertion simple
    echo "<h3>Test d'insertion :</h3>";
    
    if (isset($_POST['test_insert'])) {
        $nom = "Test";
        $prenom = "Agent";
        $contact = "0123456789";
        $id_chef = 1; // Premier chef disponible
        $cree_par = 1; // ID utilisateur test
        
        $stmt = $conn->prepare("INSERT INTO agents (nom, prenom, contact, id_chef, cree_par, date_ajout) VALUES (?, ?, ?, ?, ?, NOW())");
        $result = $stmt->execute([$nom, $prenom, $contact, $id_chef, $cree_par]);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Test d'insertion réussi !</p>";
        } else {
            echo "<p style='color: red;'>❌ Test d'insertion échoué !</p>";
        }
    }
    
    echo "<form method='post'>";
    echo "<button type='submit' name='test_insert'>Tester l'insertion</button>";
    echo "</form>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur : " . $e->getMessage() . "</p>";
}
?>

<?php
require_once '../inc/functions/connexion.php';

echo "<h2>Test de la table sorties_diverses</h2>";

try {
    // Vérifier si la table existe
    $checkTable = $conn->query("SHOW TABLES LIKE 'sorties_diverses'");
    if ($checkTable->rowCount() == 0) {
        echo "<p style='color: red;'>❌ La table 'sorties_diverses' n'existe pas!</p>";
        
        // Proposer de créer la table
        echo "<h3>Création de la table :</h3>";
        echo "<pre>";
        echo "CREATE TABLE `sorties_diverses` (
  `id_sorties` int NOT NULL AUTO_INCREMENT,
  `numero_sorties` varchar(255) NOT NULL,
  `date_sortie` datetime NOT NULL,
  `montant` decimal(20,2) NOT NULL,
  `motifs` text NOT NULL,
  PRIMARY KEY (`id_sorties`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;";
        echo "</pre>";
        
    } else {
        echo "<p style='color: green;'>✅ La table 'sorties_diverses' existe!</p>";
        
        // Vérifier la structure
        echo "<h3>Structure de la table :</h3>";
        $structure = $conn->query("DESCRIBE sorties_diverses");
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
        while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Compter les enregistrements
        $count = $conn->query("SELECT COUNT(*) FROM sorties_diverses")->fetchColumn();
        echo "<p>Nombre d'enregistrements : <strong>$count</strong></p>";
        
        if ($count > 0) {
            echo "<h3>Derniers enregistrements :</h3>";
            $records = $conn->query("SELECT * FROM sorties_diverses ORDER BY id_sorties DESC LIMIT 5");
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Numéro</th><th>Date</th><th>Montant</th><th>Motifs</th></tr>";
            while ($row = $records->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $row['id_sorties'] . "</td>";
                echo "<td>" . $row['numero_sorties'] . "</td>";
                echo "<td>" . $row['date_sortie'] . "</td>";
                echo "<td>" . number_format($row['montant'], 0, ',', ' ') . " FCFA</td>";
                echo "<td>" . htmlspecialchars($row['motifs']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    // Test de génération de numéro
    echo "<h3>Test de génération de numéro :</h3>";
    $annee = date('Y');
    $mois = date('m');
    $pattern = "SD-{$annee}-{$mois}-%";
    
    echo "<p>Pattern de recherche : <code>$pattern</code></p>";
    
    $lastNumberQuery = "SELECT numero_sorties FROM sorties_diverses 
                       WHERE numero_sorties LIKE :pattern 
                       ORDER BY id_sorties DESC LIMIT 1";
    $lastNumberStmt = $conn->prepare($lastNumberQuery);
    $lastNumberStmt->bindValue(':pattern', $pattern, PDO::PARAM_STR);
    $lastNumberStmt->execute();
    
    $lastNumber = $lastNumberStmt->fetchColumn();
    
    if ($lastNumber) {
        $parts = explode('-', $lastNumber);
        $sequence = intval($parts[3]) + 1;
        echo "<p>Dernier numéro trouvé : <code>$lastNumber</code></p>";
        echo "<p>Prochaine séquence : <strong>$sequence</strong></p>";
    } else {
        $sequence = 1;
        echo "<p>Aucun numéro trouvé pour ce mois</p>";
        echo "<p>Première séquence : <strong>$sequence</strong></p>";
    }
    
    $numero_sorties = sprintf("SD-%s-%s-%03d", $annee, $mois, $sequence);
    echo "<p>Prochain numéro généré : <strong style='color: blue;'>$numero_sorties</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur : " . $e->getMessage() . "</p>";
}
?>

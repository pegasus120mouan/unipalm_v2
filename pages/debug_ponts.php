<?php
require_once '../inc/functions/connexion.php';

echo "<h2>Debug Ponts-Bascules</h2>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Test 1: Connexion
try {
    echo "<div class='success'>✅ Connexion DB réussie</div>";
    echo "<div class='info'>Host: " . DB_HOST . "</div>";
    echo "<div class='info'>DB: " . DB_NAME . "</div>";
    echo "<hr>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur connexion: " . $e->getMessage() . "</div>";
    exit;
}

// Test 2: Vérifier si la table existe
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'pont_bascule'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "<div class='success'>✅ Table 'pont_bascule' existe</div>";
    } else {
        echo "<div class='error'>❌ Table 'pont_bascule' n'existe PAS</div>";
        
        // Afficher toutes les tables
        echo "<h3>Tables disponibles dans la base:</h3>";
        $stmt = $conn->query("SHOW TABLES");
        while ($row = $stmt->fetch()) {
            echo "<div>- " . $row[0] . "</div>";
        }
        
        echo "<hr><h3>Solution:</h3>";
        echo "<div class='info'>Vous devez créer la table pont_bascule. Voici le SQL:</div>";
        echo "<textarea style='width:100%;height:200px;'>
CREATE TABLE IF NOT EXISTS `pont_bascule` (
  `id_pont` int(11) NOT NULL AUTO_INCREMENT,
  `code_pont` varchar(50) NOT NULL UNIQUE,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `gerant` varchar(100) NOT NULL,
  `cooperatif` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pont`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Données de test
INSERT INTO `pont_bascule` (`code_pont`, `latitude`, `longitude`, `gerant`, `cooperatif`) VALUES
('PB001', 5.9342400, -5.3260000, 'Agenor', 'Unicoop'),
('PB002', 6.1234567, -5.4567890, 'Marie Kouassi', 'COOPAG'),
('PB003', 5.8765432, -5.2345678, 'Jean Baptiste', NULL);
        </textarea>";
        exit;
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur vérification table: " . $e->getMessage() . "</div>";
    exit;
}

// Test 3: Structure de la table
try {
    $stmt = $conn->query("DESCRIBE pont_bascule");
    echo "<h3>Structure table pont_bascule:</h3>";
    while ($row = $stmt->fetch()) {
        echo "<div>- " . $row['Field'] . " (" . $row['Type'] . ")</div>";
    }
    echo "<hr>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur structure: " . $e->getMessage() . "</div>";
}

// Test 4: Compter les enregistrements
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM pont_bascule");
    $count = $stmt->fetch()['total'];
    echo "<h3>Nombre d'enregistrements: " . $count . "</h3>";
    
    if ($count > 0) {
        echo "<h3>Enregistrements existants:</h3>";
        $stmt = $conn->query("SELECT * FROM pont_bascule ORDER BY id_pont");
        echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
        echo "<tr><th>ID</th><th>Code</th><th>Latitude</th><th>Longitude</th><th>Gérant</th><th>Coopérative</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>" . $row['id_pont'] . "</td>";
            echo "<td>" . $row['code_pont'] . "</td>";
            echo "<td>" . $row['latitude'] . "</td>";
            echo "<td>" . $row['longitude'] . "</td>";
            echo "<td>" . $row['gerant'] . "</td>";
            echo "<td>" . ($row['cooperatif'] ?? 'Non spécifiée') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ Aucun enregistrement dans la table</div>";
        echo "<div class='info'>Vous devez ajouter des données de test</div>";
    }
    echo "<hr>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur comptage: " . $e->getMessage() . "</div>";
}

// Test 5: Tester la fonction getAllPontsBascules
require_once '../inc/functions/requete/requete_ponts.php';
echo "<h3>Test fonction getAllPontsBascules():</h3>";
$ponts = getAllPontsBascules($conn);
if ($ponts === false) {
    echo "<div class='error'>❌ Fonction retourne FALSE</div>";
} else {
    echo "<div class='success'>✅ Fonction retourne " . count($ponts) . " ponts</div>";
    if (count($ponts) > 0) {
        foreach ($ponts as $pont) {
            echo "<div>- " . $pont['code_pont'] . " (" . $pont['gerant'] . ")</div>";
        }
    }
}

echo "<hr>";
echo "<h3>Conclusion:</h3>";
if ($ponts !== false && count($ponts) > 0) {
    echo "<div class='success'>✅ Tout fonctionne ! Le problème n'est pas dans la récupération des données.</div>";
    echo "<div class='info'>Vérifiez votre fichier ponts.php pour d'autres erreurs (JavaScript, CSS, etc.)</div>";
} else {
    echo "<div class='error'>❌ Le problème est identifié. Suivez les solutions ci-dessus.</div>";
}

echo "<br><br><a href='ponts.php'>← Retour aux ponts-bascules</a>";
?>

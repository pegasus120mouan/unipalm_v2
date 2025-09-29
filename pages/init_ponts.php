<?php
require_once '../inc/functions/connexion.php';

echo "<h2>Initialisation des Ponts-Bascules</h2>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    // Vérifier si la table existe
    $stmt = $conn->query("SHOW TABLES LIKE 'pont_bascule'");
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        echo "<div class='info'>Création de la table pont_bascule...</div>";
        
        $sql_create = "CREATE TABLE IF NOT EXISTS `pont_bascule` (
            `id_pont` int(11) NOT NULL AUTO_INCREMENT,
            `code_pont` varchar(50) NOT NULL UNIQUE,
            `latitude` decimal(10,8) NOT NULL,
            `longitude` decimal(11,8) NOT NULL,
            `gerant` varchar(100) NOT NULL,
            `cooperatif` varchar(100) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_pont`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $conn->exec($sql_create);
        echo "<div class='success'>✅ Table créée avec succès</div>";
    } else {
        echo "<div class='success'>✅ Table pont_bascule existe déjà</div>";
    }
    
    // Vérifier s'il y a des données
    $stmt = $conn->query("SELECT COUNT(*) as total FROM pont_bascule");
    $count = $stmt->fetch()['total'];
    
    if ($count == 0) {
        echo "<div class='info'>Ajout des données de test...</div>";
        
        $sql_insert = "INSERT INTO `pont_bascule` (`code_pont`, `latitude`, `longitude`, `gerant`, `cooperatif`) VALUES
            ('UNI-001', 5.9342400, -5.3260000, 'Agenor', 'Unicoop'),
            ('UNI-002', 6.1234567, -5.4567890, 'Marie Kouassi', 'COOPAG'),
            ('UNI-003', 5.8765432, -5.2345678, 'Jean Baptiste', 'COPACI'),
            ('UNI-004', 6.2345678, -5.1234567, 'Kouame Yao', 'SCOOP-CA'),
            ('UNI-005', 5.7654321, -5.5678901, 'Fatou Traore', NULL)";
        
        $conn->exec($sql_insert);
        echo "<div class='success'>✅ 5 ponts-bascules ajoutés avec succès</div>";
    } else {
        echo "<div class='info'>La table contient déjà " . $count . " enregistrements</div>";
    }
    
    // Afficher les données
    echo "<h3>Ponts-bascules dans la base locale:</h3>";
    $stmt = $conn->query("SELECT * FROM pont_bascule ORDER BY code_pont");
    echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
    echo "<tr style='background:#f0f0f0;'><th>ID</th><th>Code</th><th>Latitude</th><th>Longitude</th><th>Gérant</th><th>Coopérative</th></tr>";
    
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . $row['id_pont'] . "</td>";
        echo "<td><strong>" . $row['code_pont'] . "</strong></td>";
        echo "<td>" . $row['latitude'] . "</td>";
        echo "<td>" . $row['longitude'] . "</td>";
        echo "<td>" . $row['gerant'] . "</td>";
        echo "<td>" . ($row['cooperatif'] ?? '<em>Non spécifiée</em>') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<div class='success'>✅ Initialisation terminée ! Vous pouvez maintenant utiliser la page ponts.php</div>";
    echo "<br><a href='ponts.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>→ Aller aux Ponts-Bascules</a>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur: " . $e->getMessage() . "</div>";
}
?>

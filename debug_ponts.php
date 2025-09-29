<?php
require_once 'inc/functions/connexion.php';

echo "<h2>Debug Ponts-Bascules</h2>";

// Test 1: Connexion
try {
    echo "✅ Connexion DB réussie<br>";
    echo "Host: " . DB_HOST . "<br>";
    echo "DB: " . DB_NAME . "<br>";
} catch (Exception $e) {
    echo "❌ Erreur connexion: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Vérifier si la table existe
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'pont_bascule'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "✅ Table 'pont_bascule' existe<br>";
    } else {
        echo "❌ Table 'pont_bascule' n'existe PAS<br>";
        
        // Afficher toutes les tables
        echo "<h3>Tables disponibles:</h3>";
        $stmt = $conn->query("SHOW TABLES");
        while ($row = $stmt->fetch()) {
            echo "- " . $row[0] . "<br>";
        }
        exit;
    }
} catch (Exception $e) {
    echo "❌ Erreur vérification table: " . $e->getMessage() . "<br>";
}

// Test 3: Structure de la table
try {
    $stmt = $conn->query("DESCRIBE pont_bascule");
    echo "<h3>Structure table pont_bascule:</h3>";
    while ($row = $stmt->fetch()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur structure: " . $e->getMessage() . "<br>";
}

// Test 4: Compter les enregistrements
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM pont_bascule");
    $count = $stmt->fetch()['total'];
    echo "<h3>Nombre d'enregistrements: " . $count . "</h3>";
    
    if ($count > 0) {
        echo "<h3>Premiers enregistrements:</h3>";
        $stmt = $conn->query("SELECT * FROM pont_bascule LIMIT 5");
        while ($row = $stmt->fetch()) {
            echo "- ID: " . $row['id_pont'] . ", Code: " . $row['code_pont'] . "<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Erreur comptage: " . $e->getMessage() . "<br>";
}

// Test 5: Tester la fonction getAllPontsBascules
require_once 'inc/functions/requete/requete_ponts.php';
echo "<h3>Test fonction getAllPontsBascules():</h3>";
$ponts = getAllPontsBascules($conn);
if ($ponts === false) {
    echo "❌ Fonction retourne FALSE<br>";
} else {
    echo "✅ Fonction retourne " . count($ponts) . " ponts<br>";
    foreach ($ponts as $pont) {
        echo "- " . $pont['code_pont'] . " (" . $pont['gerant'] . ")<br>";
    }
}
?>

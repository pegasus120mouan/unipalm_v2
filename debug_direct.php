<?php
// Debug direct pour le code UNIPALM-PB-0003-CI
header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔧 Debug Direct - UNIPALM-PB-0003-CI</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
    .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 5px 0; }
    .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 5px 0; }
    .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 5px 0; }
    .code { background: #f8f9fa; padding: 15px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background-color: #f8f9fa; }
</style>";

echo "<div class='container'>";

$test_code = "UNIPALM-PB-0003-CI";

try {
    // Étape 1: Configuration
    echo "<h3>🔧 Étape 1: Configuration</h3>";
    require_once 'config_verification.php';
    echo "<div class='success'>✅ Configuration chargée</div>";
    echo "<div class='info'>Host: " . $_SERVER['HTTP_HOST'] . "</div>";
    
    // Étape 2: Test de connexion
    echo "<h3>🗄️ Étape 2: Test de Connexion</h3>";
    $stmt = $conn->query("SELECT VERSION() as version, DATABASE() as db");
    $info = $stmt->fetch();
    echo "<div class='success'>✅ Connexion réussie</div>";
    echo "<div class='info'>Version MySQL: {$info['version']}</div>";
    echo "<div class='info'>Base de données: {$info['db']}</div>";
    
    // Étape 3: Vérification de la table
    echo "<h3>📊 Étape 3: Vérification de la Table</h3>";
    $stmt = $conn->query("SELECT COUNT(*) as count FROM pont_bascule");
    $count = $stmt->fetch()['count'];
    echo "<div class='info'>Nombre total de ponts: {$count}</div>";
    
    // Étape 4: Recherche du code spécifique
    echo "<h3>🔍 Étape 4: Recherche du Code Spécifique</h3>";
    
    // Recherche exacte
    echo "<h4>Recherche Exacte:</h4>";
    $stmt = $conn->prepare("SELECT * FROM pont_bascule WHERE code_pont = ?");
    $stmt->execute([$test_code]);
    $result = $stmt->fetch();
    
    if ($result) {
        echo "<div class='success'>✅ Code trouvé en recherche directe !</div>";
        echo "<table>";
        echo "<tr><th>Champ</th><th>Valeur</th></tr>";
        foreach ($result as $key => $value) {
            if (!is_numeric($key)) {
                echo "<tr><td>{$key}</td><td>" . htmlspecialchars($value) . "</td></tr>";
            }
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ Code non trouvé en recherche directe</div>";
    }
    
    // Recherche avec LIKE
    echo "<h4>Recherche avec LIKE:</h4>";
    $stmt = $conn->prepare("SELECT code_pont FROM pont_bascule WHERE code_pont LIKE ?");
    $stmt->execute(["%0003%"]);
    $like_results = $stmt->fetchAll();
    
    if ($like_results) {
        echo "<div class='info'>Codes similaires trouvés:</div>";
        foreach ($like_results as $lr) {
            echo "<div>• {$lr['code_pont']}</div>";
        }
    } else {
        echo "<div class='error'>❌ Aucun code similaire trouvé</div>";
    }
    
    // Étape 5: Test de la fonction
    echo "<h3>⚙️ Étape 5: Test de la Fonction getPontBasculeByCode</h3>";
    
    if (function_exists('getPontBasculeByCode')) {
        echo "<div class='success'>✅ Fonction disponible</div>";
        
        try {
            echo "<div class='info'>Appel de la fonction...</div>";
            $func_result = getPontBasculeByCode($conn, $test_code);
            
            if ($func_result) {
                echo "<div class='success'>✅ Fonction retourne un résultat !</div>";
                echo "<table>";
                echo "<tr><th>Champ</th><th>Valeur</th></tr>";
                foreach ($func_result as $key => $value) {
                    echo "<tr><td>{$key}</td><td>" . htmlspecialchars($value) . "</td></tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='error'>❌ Fonction retourne null/false</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erreur dans la fonction:</div>";
            echo "<div class='code'>";
            echo "Message: " . $e->getMessage() . "\n";
            echo "Fichier: " . $e->getFile() . "\n";
            echo "Ligne: " . $e->getLine() . "\n";
            echo "Trace:\n" . $e->getTraceAsString();
            echo "</div>";
        }
    } else {
        echo "<div class='error'>❌ Fonction non disponible</div>";
    }
    
    // Étape 6: Afficher le code de la fonction
    echo "<h3>📝 Étape 6: Code de la Fonction</h3>";
    
    $function_code = '
function getPontBasculeByCode($conn, $code) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                id_pont,
                code_pont,
                nom_pont,
                gerant,
                cooperatif,
                latitude,
                longitude,
                statut,
                date_creation
            FROM pont_bascule 
            WHERE code_pont = :code 
            LIMIT 1
        ");
        
        $stmt->bindParam(\':code\', $code, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch();
        
    } catch(PDOException $e) {
        error_log("Erreur getPontBasculeByCode : " . $e->getMessage());
        throw new Exception("Erreur lors de la récupération des données du pont");
    }
}';
    
    echo "<div class='code'>" . htmlspecialchars($function_code) . "</div>";
    
    // Étape 7: Test manuel de la requête
    echo "<h3>🧪 Étape 7: Test Manuel de la Requête</h3>";
    
    try {
        $manual_stmt = $conn->prepare("
            SELECT 
                id_pont,
                code_pont,
                nom_pont,
                gerant,
                cooperatif,
                latitude,
                longitude,
                statut,
                date_creation
            FROM pont_bascule 
            WHERE code_pont = :code 
            LIMIT 1
        ");
        
        $manual_stmt->bindParam(':code', $test_code, PDO::PARAM_STR);
        $manual_stmt->execute();
        $manual_result = $manual_stmt->fetch();
        
        if ($manual_result) {
            echo "<div class='success'>✅ Requête manuelle réussie !</div>";
            echo "<table>";
            echo "<tr><th>Champ</th><th>Valeur</th></tr>";
            foreach ($manual_result as $key => $value) {
                if (!is_numeric($key)) {
                    echo "<tr><td>{$key}</td><td>" . htmlspecialchars($value) . "</td></tr>";
                }
            }
            echo "</table>";
        } else {
            echo "<div class='error'>❌ Requête manuelle ne retourne rien</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erreur requête manuelle: " . $e->getMessage() . "</div>";
    }
    
    // Étape 8: Tous les codes disponibles
    echo "<h3>📋 Étape 8: Tous les Codes Disponibles</h3>";
    
    $stmt = $conn->query("SELECT code_pont, nom_pont FROM pont_bascule ORDER BY code_pont");
    $all_codes = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>Code</th><th>Nom</th><th>Test</th></tr>";
    foreach ($all_codes as $code_info) {
        $test_url = "verification_pont.php?code=" . urlencode($code_info['code_pont']);
        echo "<tr>";
        echo "<td>{$code_info['code_pont']}</td>";
        echo "<td>" . ($code_info['nom_pont'] ?: 'Non défini') . "</td>";
        echo "<td><a href='{$test_url}' target='_blank'>Tester</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur générale: " . $e->getMessage() . "</div>";
    echo "<div class='code'>" . $e->getTraceAsString() . "</div>";
}

echo "</div>";

// Boutons d'action
echo "<div style='text-align:center; margin:20px;'>";
echo "<a href='verification_pont.php?code=UNIPALM-PB-0003-CI' target='_blank' style='background:#dc3545;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;font-weight:bold;'>🧪 TESTER LA VÉRIFICATION</a>";
echo "<br><br>";
echo "<a href='fix_verification_issue.php' style='background:#ffc107;color:black;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🔧 Correction</a>";
echo "<a href='pages/ponts.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🏠 Retour</a>";
echo "</div>";
?>

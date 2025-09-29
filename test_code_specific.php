<?php
// Test spécifique pour le code UNIPALM-PB-0003-CI
header('Content-Type: text/html; charset=utf-8');

echo "<h2>🧪 Test Spécifique - Code UNIPALM-PB-0003-CI</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 5px 0; }
    .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 5px 0; }
    .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 5px 0; }
    .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 5px 0; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background-color: #f8f9fa; }
    .code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
</style>";

echo "<div class='container'>";

$test_code = "UNIPALM-PB-0003-CI";

try {
    require_once 'config_verification.php';
    echo "<div class='success'>✅ Configuration chargée</div>";
    
    // Test 1: Recherche directe dans la base
    echo "<h3>🔍 Test 1: Recherche Directe SQL</h3>";
    
    $stmt = $conn->prepare("SELECT * FROM pont_bascule WHERE code_pont = :code");
    $stmt->bindParam(':code', $test_code);
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "<div class='success'>✅ Code trouvé dans la base de données !</div>";
        echo "<div class='info'>📊 Détails du pont:</div>";
        echo "<table>";
        foreach ($result as $key => $value) {
            echo "<tr><td><strong>{$key}</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ Code non trouvé avec la requête SQL directe</div>";
    }
    
    // Test 2: Utilisation de la fonction getPontBasculeByCode
    echo "<h3>⚙️ Test 2: Fonction getPontBasculeByCode</h3>";
    
    if (function_exists('getPontBasculeByCode')) {
        echo "<div class='success'>✅ Fonction disponible</div>";
        
        try {
            $pont = getPontBasculeByCode($conn, $test_code);
            
            if ($pont) {
                echo "<div class='success'>✅ Code trouvé via la fonction !</div>";
                echo "<div class='info'>📊 Données retournées:</div>";
                echo "<table>";
                foreach ($pont as $key => $value) {
                    echo "<tr><td><strong>{$key}</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='error'>❌ Fonction retourne null/false</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erreur dans la fonction: " . $e->getMessage() . "</div>";
        }
        
    } else {
        echo "<div class='error'>❌ Fonction getPontBasculeByCode non disponible</div>";
    }
    
    // Test 3: Recherche avec LIKE pour voir les variations
    echo "<h3>🔎 Test 3: Recherche avec Variations</h3>";
    
    $variations = [
        $test_code,
        strtolower($test_code),
        strtoupper($test_code),
        trim($test_code),
        str_replace('-', '_', $test_code)
    ];
    
    foreach ($variations as $variation) {
        $stmt = $conn->prepare("SELECT code_pont FROM pont_bascule WHERE code_pont = :code");
        $stmt->bindParam(':code', $variation);
        $stmt->execute();
        $found = $stmt->fetch();
        
        if ($found) {
            echo "<div class='success'>✅ Trouvé avec variation: <code>{$variation}</code></div>";
        } else {
            echo "<div class='warning'>⚠️ Non trouvé: <code>{$variation}</code></div>";
        }
    }
    
    // Test 4: Recherche partielle
    echo "<h3>🔍 Test 4: Recherche Partielle</h3>";
    
    $partial_searches = [
        '%PB-0003%',
        '%0003%',
        'UNIPALM%',
        '%CI'
    ];
    
    foreach ($partial_searches as $pattern) {
        $stmt = $conn->prepare("SELECT code_pont FROM pont_bascule WHERE code_pont LIKE :pattern");
        $stmt->bindParam(':pattern', $pattern);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        if ($results) {
            echo "<div class='info'>📋 Pattern '{$pattern}' trouve " . count($results) . " résultat(s):</div>";
            foreach ($results as $res) {
                echo "<div>• {$res['code_pont']}</div>";
            }
        } else {
            echo "<div class='warning'>⚠️ Pattern '{$pattern}' : aucun résultat</div>";
        }
    }
    
    // Test 5: Vérification de l'encodage
    echo "<h3>🔤 Test 5: Vérification de l'Encodage</h3>";
    
    $stmt = $conn->query("SELECT code_pont, HEX(code_pont) as hex_code FROM pont_bascule WHERE code_pont LIKE '%0003%'");
    $encoding_results = $stmt->fetchAll();
    
    if ($encoding_results) {
        echo "<div class='info'>📊 Analyse de l'encodage:</div>";
        echo "<table>";
        echo "<tr><th>Code</th><th>Hex</th><th>Longueur</th></tr>";
        foreach ($encoding_results as $res) {
            echo "<tr>";
            echo "<td class='code'>{$res['code_pont']}</td>";
            echo "<td class='code'>{$res['hex_code']}</td>";
            echo "<td>" . strlen($res['code_pont']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 6: Test de la page de vérification
    echo "<h3>🌐 Test 6: Test de la Page de Vérification</h3>";
    
    $verification_url = "verification_pont.php?code=" . urlencode($test_code);
    echo "<div class='info'>🔗 URL de test: <a href='{$verification_url}' target='_blank'>{$verification_url}</a></div>";
    
    // Simuler l'appel de vérification
    echo "<div class='info'>🧪 Simulation de l'appel de vérification:</div>";
    
    $_GET['code'] = $test_code;
    $code_pont = $test_code;
    $pont = null;
    $error_message = '';
    
    try {
        $pont = getPontBasculeByCode($conn, $code_pont);
        if (!$pont) {
            $error_message = "Aucun pont-bascule trouvé avec le code : " . htmlspecialchars($code_pont);
            echo "<div class='error'>❌ {$error_message}</div>";
        } else {
            echo "<div class='success'>✅ Simulation réussie - Pont trouvé !</div>";
            logVerification($conn, $code_pont);
            echo "<div class='info'>📝 Vérification loggée</div>";
        }
    } catch (Exception $e) {
        $error_message = "Erreur lors de la récupération des données : " . $e->getMessage();
        echo "<div class='error'>❌ {$error_message}</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur générale: " . $e->getMessage() . "</div>";
}

echo "</div>"; // Fin container

// Boutons d'action
echo "<div style='text-align:center; margin:20px;'>";
echo "<a href='verification_pont.php?code=UNIPALM-PB-0003-CI' target='_blank' style='background:#28a745;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;font-weight:bold;'>🧪 TESTER LA VÉRIFICATION</a>";
echo "<br><br>";
echo "<a href='debug_verification.php' style='background:#17a2b8;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🔍 Diagnostic Complet</a>";
echo "<a href='pages/ponts.php' style='background:#6c757d;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🏠 Retour</a>";
echo "</div>";
?>

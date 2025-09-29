<?php
// Script de diagnostic pour la vérification des ponts-bascules
header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔍 Diagnostic - Vérification Pont-Bascule</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
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

// Test 1: Configuration et connexion
echo "<h3>🔧 Test 1: Configuration et Connexion</h3>";

try {
    require_once 'config_verification.php';
    echo "<div class='success'>✅ Configuration chargée</div>";
    
    // Afficher la configuration active
    echo "<div class='info'>📊 Configuration active:</div>";
    echo "<table>";
    echo "<tr><th>Paramètre</th><th>Valeur</th></tr>";
    echo "<tr><td>Host</td><td class='code'>{$db_config['host']}</td></tr>";
    echo "<tr><td>Base de données</td><td class='code'>{$db_config['dbname']}</td></tr>";
    echo "<tr><td>Utilisateur</td><td class='code'>{$db_config['username']}</td></tr>";
    echo "<tr><td>Mot de passe</td><td class='code'>" . (empty($db_config['password']) ? 'Vide' : str_repeat('*', strlen($db_config['password']))) . "</td></tr>";
    echo "</table>";
    
    // Test de connexion
    if (isset($conn)) {
        echo "<div class='success'>✅ Connexion à la base de données réussie</div>";
        
        // Version MySQL
        $stmt = $conn->query("SELECT VERSION() as version");
        $version = $stmt->fetch();
        echo "<div class='info'>🗄️ Version MySQL: {$version['version']}</div>";
        
    } else {
        echo "<div class='error'>❌ Pas de connexion à la base de données</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur: " . $e->getMessage() . "</div>";
}

// Test 2: Vérification de la table pont_bascule
echo "<h3>🗄️ Test 2: Table pont_bascule</h3>";

try {
    // Vérifier l'existence de la table
    $stmt = $conn->query("SHOW TABLES LIKE 'pont_bascule'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'>✅ Table 'pont_bascule' existe</div>";
        
        // Compter les enregistrements
        $stmt = $conn->query("SELECT COUNT(*) as count FROM pont_bascule");
        $result = $stmt->fetch();
        echo "<div class='info'>📊 Nombre total de ponts: {$result['count']}</div>";
        
        if ($result['count'] > 0) {
            // Lister tous les codes disponibles
            $stmt = $conn->query("SELECT code_pont, nom_pont, gerant, statut FROM pont_bascule ORDER BY code_pont");
            $ponts = $stmt->fetchAll();
            
            echo "<div class='info'>📋 Liste de tous les ponts dans la base:</div>";
            echo "<table>";
            echo "<tr><th>Code Pont</th><th>Nom</th><th>Gérant</th><th>Statut</th></tr>";
            
            foreach ($ponts as $pont) {
                echo "<tr>";
                echo "<td class='code'>{$pont['code_pont']}</td>";
                echo "<td>" . ($pont['nom_pont'] ?: 'Non défini') . "</td>";
                echo "<td>{$pont['gerant']}</td>";
                echo "<td>{$pont['statut']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } else {
            echo "<div class='warning'>⚠️ La table est vide - aucun pont-bascule enregistré</div>";
        }
        
    } else {
        echo "<div class='error'>❌ Table 'pont_bascule' n'existe pas</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur lors de la vérification de la table: " . $e->getMessage() . "</div>";
}

// Test 3: Test du code spécifique
echo "<h3>🔍 Test 3: Code Spécifique - UNIPALM-PB-0003-CI</h3>";

$test_code = "UNIPALM-PB-0003-CI";

try {
    // Test de recherche exacte
    $stmt = $conn->prepare("SELECT * FROM pont_bascule WHERE code_pont = :code");
    $stmt->bindParam(':code', $test_code);
    $stmt->execute();
    $pont = $stmt->fetch();
    
    if ($pont) {
        echo "<div class='success'>✅ Pont trouvé avec le code: {$test_code}</div>";
        echo "<div class='info'>📊 Détails du pont:</div>";
        echo "<table>";
        foreach ($pont as $key => $value) {
            echo "<tr><td>{$key}</td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ Aucun pont trouvé avec le code exact: {$test_code}</div>";
        
        // Recherche similaire
        $stmt = $conn->prepare("SELECT code_pont FROM pont_bascule WHERE code_pont LIKE :code");
        $like_code = "%PB-0003%";
        $stmt->bindParam(':code', $like_code);
        $stmt->execute();
        $similar = $stmt->fetchAll();
        
        if ($similar) {
            echo "<div class='warning'>⚠️ Codes similaires trouvés:</div>";
            foreach ($similar as $s) {
                echo "<div class='info'>- {$s['code_pont']}</div>";
            }
        } else {
            echo "<div class='info'>ℹ️ Aucun code similaire trouvé</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur lors du test: " . $e->getMessage() . "</div>";
}

// Test 4: Test de la fonction getPontBasculeByCode
echo "<h3>⚙️ Test 4: Fonction getPontBasculeByCode</h3>";

try {
    if (function_exists('getPontBasculeByCode')) {
        echo "<div class='success'>✅ Fonction getPontBasculeByCode disponible</div>";
        
        $result = getPontBasculeByCode($conn, $test_code);
        if ($result) {
            echo "<div class='success'>✅ Fonction retourne un résultat</div>";
        } else {
            echo "<div class='warning'>⚠️ Fonction retourne null/false</div>";
        }
    } else {
        echo "<div class='error'>❌ Fonction getPontBasculeByCode non disponible</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur fonction: " . $e->getMessage() . "</div>";
}

// Test 5: Recherche de patterns de codes
echo "<h3>🔎 Test 5: Analyse des Patterns de Codes</h3>";

try {
    $stmt = $conn->query("
        SELECT 
            code_pont,
            LENGTH(code_pont) as longueur,
            CASE 
                WHEN code_pont LIKE 'UNIPALM-PB-%' THEN 'Format standard'
                ELSE 'Format non-standard'
            END as format_type
        FROM pont_bascule 
        ORDER BY code_pont
    ");
    
    $codes = $stmt->fetchAll();
    
    if ($codes) {
        echo "<div class='info'>📊 Analyse des formats de codes:</div>";
        echo "<table>";
        echo "<tr><th>Code</th><th>Longueur</th><th>Format</th></tr>";
        
        foreach ($codes as $code) {
            $class = $code['format_type'] === 'Format standard' ? 'success' : 'warning';
            echo "<tr class='{$class}'>";
            echo "<td class='code'>{$code['code_pont']}</td>";
            echo "<td>{$code['longueur']}</td>";
            echo "<td>{$code['format_type']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Statistiques
        $standard_count = count(array_filter($codes, function($c) { return $c['format_type'] === 'Format standard'; }));
        $total_count = count($codes);
        
        echo "<div class='info'>📊 {$standard_count}/{$total_count} codes suivent le format standard UNIPALM-PB-XXXX-CI</div>";
        
    } else {
        echo "<div class='warning'>⚠️ Aucun code à analyser</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur analyse: " . $e->getMessage() . "</div>";
}

// Test 6: Suggestions de correction
echo "<h3>💡 Test 6: Suggestions de Correction</h3>";

echo "<div class='info'>🔧 Actions recommandées:</div>";

// Vérifier si le code existe dans la base locale
try {
    require_once 'inc/functions/connexion.php';
    $local_stmt = $conn_local->prepare("SELECT code_pont FROM pont_bascule WHERE code_pont = :code");
    $local_stmt->bindParam(':code', $test_code);
    $local_stmt->execute();
    $local_pont = $local_stmt->fetch();
    
    if ($local_pont) {
        echo "<div class='warning'>⚠️ Le code existe dans la base locale mais pas sur le serveur externe</div>";
        echo "<div class='info'>💡 Solution: Synchroniser les données depuis la base locale</div>";
    }
} catch (Exception $e) {
    // Ignorer si pas de base locale
}

echo "<div class='info'>💡 Solutions possibles:</div>";
echo "<ul>";
echo "<li><strong>Synchronisation:</strong> Exporter les données depuis la base locale et les importer sur le serveur externe</li>";
echo "<li><strong>Vérification du code:</strong> S'assurer que le QR code contient le bon code pont</li>";
echo "<li><strong>Format du code:</strong> Vérifier que le format respecte UNIPALM-PB-XXXX-CI</li>";
echo "<li><strong>Permissions:</strong> Vérifier que l'utilisateur a accès à la table pont_bascule</li>";
echo "</ul>";

// Script de synchronisation rapide
echo "<h3>🔄 Script de Synchronisation Rapide</h3>";
echo "<div class='info'>📋 Pour synchroniser manuellement, exécutez ces requêtes SQL:</div>";
echo "<pre style='background:#f8f9fa; padding:10px; border-radius:4px; overflow-x:auto;'>";
echo "-- 1. Vérifier la structure de la table\n";
echo "DESCRIBE pont_bascule;\n\n";
echo "-- 2. Insérer un pont de test\n";
echo "INSERT INTO pont_bascule (code_pont, nom_pont, gerant, cooperatif, latitude, longitude, statut) \n";
echo "VALUES ('UNIPALM-PB-0003-CI', 'Pont Test', 'Gérant Test', 'Coop Test', 5.3364, -4.0267, 'Actif');\n\n";
echo "-- 3. Vérifier l'insertion\n";
echo "SELECT * FROM pont_bascule WHERE code_pont = 'UNIPALM-PB-0003-CI';";
echo "</pre>";

echo "</div>"; // Fin container

// Boutons d'action
echo "<div style='text-align:center; margin:20px;'>";
echo "<a href='verification_pont.php?code=UNIPALM-PB-0003-CI' target='_blank' style='background:#dc3545;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🧪 Re-tester le Code</a>";
echo "<a href='test_config_verification.php' style='background:#17a2b8;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🔧 Test Complet</a>";
echo "<a href='pages/ponts.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🏠 Retour aux Ponts</a>";
echo "</div>";
?>

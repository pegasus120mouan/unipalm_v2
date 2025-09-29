<?php
echo "<h2>🧪 Test de Configuration - Système de Vérification</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 5px 0; }
    .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 5px 0; }
    .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 5px 0; }
    .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 5px 0; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background-color: #f8f9fa; }
    .badge { padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
    .badge-success { background: #28a745; color: white; }
    .badge-danger { background: #dc3545; color: white; }
    .badge-warning { background: #ffc107; color: black; }
</style>";

echo "<div class='container'>";

// Test 1: Vérification des fichiers
echo "<div class='test-section'>";
echo "<h3>📁 Test 1: Vérification des Fichiers</h3>";

$required_files = [
    'verification_pont.php' => 'Page principale de vérification',
    'config_verification.php' => 'Configuration de la base de données'
];

foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "<div class='success'>✅ {$file} - {$description}</div>";
    } else {
        echo "<div class='error'>❌ {$file} - MANQUANT - {$description}</div>";
    }
}
echo "</div>";

// Test 2: Test de la configuration
echo "<div class='test-section'>";
echo "<h3>🔧 Test 2: Configuration de la Base de Données</h3>";

try {
    require_once 'config_verification.php';
    echo "<div class='success'>✅ Fichier de configuration chargé avec succès</div>";
    
    // Afficher la configuration détectée
    $current_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    echo "<div class='info'>🌐 Host détecté: <strong>{$current_host}</strong></div>";
    
    if (isset($db_config)) {
        echo "<div class='info'>📊 Configuration active:</div>";
        echo "<table>";
        echo "<tr><th>Paramètre</th><th>Valeur</th></tr>";
        echo "<tr><td>Host</td><td>{$db_config['host']}</td></tr>";
        echo "<tr><td>Base de données</td><td>{$db_config['dbname']}</td></tr>";
        echo "<tr><td>Utilisateur</td><td>{$db_config['username']}</td></tr>";
        echo "<tr><td>Mot de passe</td><td>" . (empty($db_config['password']) ? 'Vide' : '***masqué***') . "</td></tr>";
        echo "<tr><td>Charset</td><td>{$db_config['charset']}</td></tr>";
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur de configuration: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 3: Test de connexion à la base de données
echo "<div class='test-section'>";
echo "<h3>🗄️ Test 3: Connexion à la Base de Données</h3>";

if (isset($conn)) {
    try {
        // Test de connexion simple
        $stmt = $conn->query("SELECT 1");
        echo "<div class='success'>✅ Connexion à la base de données réussie</div>";
        
        // Vérifier la version MySQL
        $stmt = $conn->query("SELECT VERSION() as version");
        $version = $stmt->fetch();
        echo "<div class='info'>📊 Version MySQL: {$version['version']}</div>";
        
        // Vérifier l'existence de la table pont_bascule
        $stmt = $conn->query("SHOW TABLES LIKE 'pont_bascule'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='success'>✅ Table 'pont_bascule' existe</div>";
            
            // Compter les enregistrements
            $stmt = $conn->query("SELECT COUNT(*) as count FROM pont_bascule");
            $result = $stmt->fetch();
            echo "<div class='info'>📊 Nombre de ponts-bascules: {$result['count']}</div>";
            
            // Vérifier la structure de la table
            $stmt = $conn->query("DESCRIBE pont_bascule");
            $columns = $stmt->fetchAll();
            
            echo "<div class='info'>📋 Structure de la table:</div>";
            echo "<table>";
            echo "<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Clé</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>" . ($col['Null'] === 'YES' ? 'Oui' : 'Non') . "</td>";
                echo "<td>{$col['Key']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } else {
            echo "<div class='error'>❌ Table 'pont_bascule' n'existe pas</div>";
        }
        
        // Vérifier la table de logs
        $stmt = $conn->query("SHOW TABLES LIKE 'verification_logs'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='success'>✅ Table 'verification_logs' existe</div>";
        } else {
            echo "<div class='warning'>⚠️ Table 'verification_logs' sera créée automatiquement</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erreur de base de données: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='error'>❌ Aucune connexion à la base de données disponible</div>";
}
echo "</div>";

// Test 4: Test des fonctions
echo "<div class='test-section'>";
echo "<h3>⚙️ Test 4: Fonctions de Vérification</h3>";

if (function_exists('getPontBasculeByCode')) {
    echo "<div class='success'>✅ Fonction getPontBasculeByCode() disponible</div>";
    
    // Test avec un code exemple
    try {
        $test_code = "UNIPALM-PB-0001-CI";
        $test_pont = getPontBasculeByCode($conn, $test_code);
        
        if ($test_pont) {
            echo "<div class='success'>✅ Test de récupération réussi avec le code: {$test_code}</div>";
            echo "<div class='info'>📊 Données récupérées:</div>";
            echo "<table>";
            foreach ($test_pont as $key => $value) {
                echo "<tr><td>{$key}</td><td>" . htmlspecialchars($value) . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='warning'>⚠️ Aucun pont trouvé avec le code: {$test_code}</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erreur lors du test: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='error'>❌ Fonction getPontBasculeByCode() non disponible</div>";
}

if (function_exists('logVerification')) {
    echo "<div class='success'>✅ Fonction logVerification() disponible</div>";
} else {
    echo "<div class='error'>❌ Fonction logVerification() non disponible</div>";
}
echo "</div>";

// Test 5: Test des URLs
echo "<div class='test-section'>";
echo "<h3>🌐 Test 5: URLs de Vérification</h3>";

$test_codes = ["UNIPALM-PB-0001-CI", "UNIPALM-PB-0002-CI", "TEST-CODE"];
$base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verification_pont.php";

echo "<div class='info'>🔗 URL de base: {$base_url}</div>";
echo "<table>";
echo "<tr><th>Code Test</th><th>URL Complète</th><th>Action</th></tr>";

foreach ($test_codes as $code) {
    $full_url = $base_url . "?code=" . urlencode($code);
    echo "<tr>";
    echo "<td>{$code}</td>";
    echo "<td style='font-size:12px;'>{$full_url}</td>";
    echo "<td><a href='{$full_url}' target='_blank'>Tester</a></td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// Test 6: Test de sécurité
echo "<div class='test-section'>";
echo "<h3>🔒 Test 6: Sécurité</h3>";

// Vérifier les headers de sécurité
$security_headers = [
    'X-Content-Type-Options',
    'X-Frame-Options',
    'X-XSS-Protection'
];

echo "<div class='info'>🛡️ Headers de sécurité:</div>";
foreach ($security_headers as $header) {
    $header_value = '';
    foreach (headers_list() as $sent_header) {
        if (stripos($sent_header, $header) === 0) {
            $header_value = $sent_header;
            break;
        }
    }
    
    if ($header_value) {
        echo "<div class='success'>✅ {$header_value}</div>";
    } else {
        echo "<div class='warning'>⚠️ {$header} non défini</div>";
    }
}

// Vérifier HTTPS en production
if ($_SERVER['HTTP_HOST'] === 'unipalm.ci' || $_SERVER['HTTP_HOST'] === 'www.unipalm.ci') {
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        echo "<div class='success'>✅ HTTPS activé</div>";
    } else {
        echo "<div class='error'>❌ HTTPS non activé (requis en production)</div>";
    }
} else {
    echo "<div class='info'>ℹ️ Test HTTPS ignoré (environnement de développement)</div>";
}
echo "</div>";

// Résumé final
echo "<div class='test-section'>";
echo "<h3>📋 Résumé Final</h3>";

$total_tests = 6;
$passed_tests = 0;

// Compter les tests réussis (logique simplifiée)
if (file_exists('verification_pont.php') && file_exists('config_verification.php')) $passed_tests++;
if (isset($conn)) $passed_tests++;
if (function_exists('getPontBasculeByCode')) $passed_tests++;

$percentage = round(($passed_tests / $total_tests) * 100);

if ($percentage >= 80) {
    echo "<div class='success'>✅ Système prêt pour le déploiement ({$percentage}% des tests réussis)</div>";
} elseif ($percentage >= 60) {
    echo "<div class='warning'>⚠️ Système partiellement prêt ({$percentage}% des tests réussis) - Vérifier les erreurs</div>";
} else {
    echo "<div class='error'>❌ Système non prêt pour le déploiement ({$percentage}% des tests réussis) - Corriger les erreurs</div>";
}

echo "<div class='info'>📊 Tests réussis: {$passed_tests}/{$total_tests}</div>";
echo "</div>";

echo "</div>"; // Fin container

// Boutons d'action
echo "<div style='text-align:center; margin:20px;'>";
echo "<a href='verification_pont.php?code=UNIPALM-PB-0001-CI' target='_blank' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🧪 Tester la Vérification</a>";
echo "<a href='pages/ponts.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🏠 Retour aux Ponts</a>";
echo "</div>";
?>

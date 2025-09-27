<?php
/**
 * FICHIER DE DIAGNOSTIC UNIPALM
 * À utiliser UNIQUEMENT pour diagnostiquer les problèmes en ligne
 * SUPPRIMER ce fichier après résolution du problème pour des raisons de sécurité
 */

// FORCER l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>🔍 Diagnostic UniPalm - Environnement Online</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .section { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .warning { color: #ffc107; }
    .info { color: #17a2b8; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
    th { background: #f8f9fa; }
</style>";

// 1. INFORMATIONS SERVEUR
echo "<div class='section'>";
echo "<h2>🖥️ Informations Serveur</h2>";
echo "<table>";
echo "<tr><th>Variable</th><th>Valeur</th></tr>";
echo "<tr><td>HTTP_HOST</td><td>" . ($_SERVER['HTTP_HOST'] ?? 'Non défini') . "</td></tr>";
echo "<tr><td>SERVER_NAME</td><td>" . ($_SERVER['SERVER_NAME'] ?? 'Non défini') . "</td></tr>";
echo "<tr><td>DOCUMENT_ROOT</td><td>" . ($_SERVER['DOCUMENT_ROOT'] ?? 'Non défini') . "</td></tr>";
echo "<tr><td>SCRIPT_NAME</td><td>" . ($_SERVER['SCRIPT_NAME'] ?? 'Non défini') . "</td></tr>";
echo "<tr><td>REQUEST_URI</td><td>" . ($_SERVER['REQUEST_URI'] ?? 'Non défini') . "</td></tr>";
echo "<tr><td>PHP Version</td><td>" . phpversion() . "</td></tr>";
echo "<tr><td>OS</td><td>" . php_uname() . "</td></tr>";
echo "</table>";
echo "</div>";

// 2. TEST DE CONNEXION BASE DE DONNÉES
echo "<div class='section'>";
echo "<h2>🗄️ Test Connexion Base de Données</h2>";

// Configuration à tester (MODIFIEZ avec vos vraies données de production)
$db_configs = [
    'local' => [
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'name' => 'unipalm_gestion_new'
    ],
    'production' => [
        'host' => '82.25.118.46',
        'user' => 'unipalm_user',
        'pass' => 'z1V07GpfhUqi7XeAlQ8',
        'name' => 'unipalm_gestion_new'
    ]
];

foreach ($db_configs as $env => $config) {
    echo "<h3>Configuration $env :</h3>";
    echo "<pre>";
    echo "Host: " . $config['host'] . "\n";
    echo "User: " . $config['user'] . "\n";
    echo "Pass: " . (empty($config['pass']) ? '(vide)' : '***masqué***') . "\n";
    echo "DB: " . $config['name'] . "\n";
    echo "</pre>";
    
    try {
        $dsn = "mysql:host={$config['host']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<p class='success'>✅ Connexion au serveur MySQL réussie</p>";
        
        // Test de la base de données
        try {
            $pdo->exec("USE {$config['name']}");
            echo "<p class='success'>✅ Base de données '{$config['name']}' accessible</p>";
            
            // Test d'une table
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            if ($stmt->rowCount() > 0) {
                echo "<p class='success'>✅ Table 'users' trouvée</p>";
            } else {
                echo "<p class='warning'>⚠️ Table 'users' non trouvée</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Erreur base de données: " . $e->getMessage() . "</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Erreur connexion: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}
echo "</div>";

// 3. TEST INCLUSION DU FICHIER DE CONNEXION
echo "<div class='section'>";
echo "<h2>🔗 Test Inclusion Fichier Connexion</h2>";

try {
    echo "<p>Tentative d'inclusion du fichier de connexion...</p>";
    
    ob_start(); // Capturer la sortie
    include_once 'inc/functions/connexion.php';
    $output = ob_get_clean();
    
    if (!empty($output)) {
        echo "<p class='warning'>⚠️ Sortie du fichier de connexion:</p>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    } else {
        echo "<p class='success'>✅ Fichier de connexion inclus sans erreur visible</p>";
    }
    
    // Tester la variable de connexion
    if (isset($conn) && $conn instanceof PDO) {
        echo "<p class='success'>✅ Variable \$conn créée et est une instance PDO</p>";
        
        // Test simple de requête
        try {
            $stmt = $conn->query("SELECT 1 as test");
            $result = $stmt->fetch();
            if ($result['test'] == 1) {
                echo "<p class='success'>✅ Test de requête réussi</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erreur test requête: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Variable \$conn non créée ou incorrecte</p>";
        echo "<p>Type de \$conn: " . (isset($conn) ? gettype($conn) : 'non définie') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur lors de l'inclusion: " . $e->getMessage() . "</p>";
    echo "<p>Trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
}
echo "</div>";

// 4. EXTENSIONS PHP
echo "<div class='section'>";
echo "<h2>🔌 Extensions PHP</h2>";

$required_extensions = ['pdo', 'pdo_mysql', 'mysqli', 'json', 'session', 'mbstring'];

echo "<table>";
echo "<tr><th>Extension</th><th>Status</th></tr>";
foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? "<span class='success'>✅ Chargée</span>" : "<span class='error'>❌ Manquante</span>";
    echo "<tr><td>$ext</td><td>$status</td></tr>";
}
echo "</table>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>⚠️ SÉCURITÉ</h2>";
echo "<p class='error'><strong>ATTENTION:</strong> Ce fichier expose des informations sensibles. Supprimez-le immédiatement après avoir identifié le problème !</p>";
echo "</div>";
?>

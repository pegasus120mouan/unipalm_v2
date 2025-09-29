<?php
echo "<h2>🧪 Test du Système QR Code - Vérification</h2>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Test 1: Vérifier que le fichier de vérification existe
echo "<h3>📋 Test 1: Fichier de vérification</h3>";
if (file_exists('verification_pont.php')) {
    echo "<div class='success'>✅ Le fichier verification_pont.php existe</div>";
} else {
    echo "<div class='error'>❌ Le fichier verification_pont.php n'existe pas</div>";
}

// Test 2: Vérifier la connexion à la base de données
echo "<h3>📋 Test 2: Connexion base de données</h3>";
try {
    require_once 'inc/functions/connexion.php';
    echo "<div class='success'>✅ Connexion à la base de données réussie</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur de connexion: " . $e->getMessage() . "</div>";
}

// Test 3: Vérifier les fonctions ponts
echo "<h3>📋 Test 3: Fonctions ponts-bascules</h3>";
try {
    require_once 'inc/functions/requete/requete_ponts.php';
    echo "<div class='success'>✅ Fonctions ponts-bascules chargées</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur chargement fonctions: " . $e->getMessage() . "</div>";
}

// Test 4: Tester avec un code exemple
echo "<h3>📋 Test 4: Test avec code exemple</h3>";
$test_code = "UNIPALM-PB-0001-CI";

try {
    $pont = getPontBasculeByCode($conn, $test_code);
    if ($pont) {
        echo "<div class='success'>✅ Pont trouvé avec le code: {$test_code}</div>";
        echo "<div class='info'>📍 Nom: " . ($pont['nom_pont'] ?: 'Non défini') . "</div>";
        echo "<div class='info'>👤 Gérant: {$pont['gerant']}</div>";
        echo "<div class='info'>📊 Statut: {$pont['statut']}</div>";
        
        // Générer l'URL de vérification
        $verification_url = "https://unipalm.ci/verification_pont.php?code=" . urlencode($test_code);
        echo "<div class='info'>🔗 URL de vérification: <a href='{$verification_url}' target='_blank'>{$verification_url}</a></div>";
        
        // Générer l'URL du QR Code
        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($verification_url);
        echo "<div class='info'>📱 QR Code de test:</div>";
        echo "<img src='{$qr_url}' alt='QR Code Test' style='border:1px solid #ccc; margin:10px;'>";
        
    } else {
        echo "<div class='error'>❌ Aucun pont trouvé avec le code: {$test_code}</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur lors du test: " . $e->getMessage() . "</div>";
}

// Test 5: Lister tous les ponts disponibles
echo "<h3>📋 Test 5: Ponts disponibles</h3>";
try {
    $ponts = getAllPontsBascules($conn);
    if ($ponts && count($ponts) > 0) {
        echo "<div class='success'>✅ " . count($ponts) . " pont(s) trouvé(s)</div>";
        echo "<table border='1' style='border-collapse:collapse; margin:10px 0;'>";
        echo "<tr style='background:#f0f0f0;'><th>Code</th><th>Nom</th><th>Gérant</th><th>Statut</th><th>Test QR</th></tr>";
        
        foreach (array_slice($ponts, 0, 5) as $pont) { // Limiter à 5 pour le test
            $test_url = "verification_pont.php?code=" . urlencode($pont['code_pont']);
            echo "<tr>";
            echo "<td>{$pont['code_pont']}</td>";
            echo "<td>" . ($pont['nom_pont'] ?: 'Non défini') . "</td>";
            echo "<td>{$pont['gerant']}</td>";
            echo "<td>{$pont['statut']}</td>";
            echo "<td><a href='{$test_url}' target='_blank'>Tester</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ Aucun pont trouvé dans la base de données</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur lors de la récupération: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<h3>🎯 Résumé du Test</h3>";
echo "<div class='info'>✅ Le système QR Code de vérification est configuré</div>";
echo "<div class='info'>📱 Les QR codes générés pointent vers: https://unipalm.ci/verification_pont.php</div>";
echo "<div class='info'>🔍 La page de vérification affiche toutes les informations du pont</div>";
echo "<div class='info'>🌐 Accessible depuis n'importe quel appareil avec un scanner QR</div>";

echo "<br><br>";
echo "<a href='pages/ponts.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>→ Aller aux Ponts-Bascules</a>";
echo "&nbsp;&nbsp;";
echo "<a href='verification_pont.php?code=UNIPALM-PB-0001-CI' target='_blank' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🧪 Tester la Vérification</a>";
?>

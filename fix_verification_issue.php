<?php
// Script de correction rapide pour le problème de vérification
header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔧 Correction Rapide - Problème de Vérification</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 5px 0; }
    .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 5px 0; }
    .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 5px 0; }
    .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 5px 0; }
    .code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
</style>";

echo "<div class='container'>";

$action = $_GET['action'] ?? 'analyze';

try {
    require_once 'config_verification.php';
    echo "<div class='success'>✅ Connexion établie</div>";
    
    if ($action === 'analyze') {
        echo "<h3>🔍 Analyse du Problème</h3>";
        
        // Vérifier tous les codes qui contiennent "0003"
        $stmt = $conn->query("SELECT code_pont, LENGTH(code_pont) as len, HEX(code_pont) as hex FROM pont_bascule WHERE code_pont LIKE '%0003%'");
        $results = $stmt->fetchAll();
        
        if ($results) {
            echo "<div class='info'>📊 Codes contenant '0003' trouvés:</div>";
            foreach ($results as $result) {
                echo "<div class='info'>";
                echo "Code: <code>{$result['code_pont']}</code><br>";
                echo "Longueur: {$result['len']} caractères<br>";
                echo "Hex: {$result['hex']}<br>";
                echo "---<br>";
                echo "</div>";
                
                // Test direct de ce code
                $test_stmt = $conn->prepare("SELECT * FROM pont_bascule WHERE code_pont = ?");
                $test_stmt->execute([$result['code_pont']]);
                $test_result = $test_stmt->fetch();
                
                if ($test_result) {
                    echo "<div class='success'>✅ Ce code fonctionne en recherche directe</div>";
                    
                    // Tester avec la fonction
                    $func_result = getPontBasculeByCode($conn, $result['code_pont']);
                    if ($func_result) {
                        echo "<div class='success'>✅ Ce code fonctionne avec la fonction</div>";
                    } else {
                        echo "<div class='error'>❌ Ce code ne fonctionne PAS avec la fonction</div>";
                    }
                } else {
                    echo "<div class='error'>❌ Ce code ne fonctionne pas en recherche directe</div>";
                }
                
                echo "<hr>";
            }
        } else {
            echo "<div class='warning'>⚠️ Aucun code contenant '0003' trouvé</div>";
        }
        
        // Proposer des actions
        echo "<h3>🛠️ Actions Disponibles</h3>";
        echo "<div style='text-align:center;'>";
        echo "<a href='?action=clean_codes' style='background:#ffc107;color:black;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🧹 Nettoyer les Codes</a>";
        echo "<a href='?action=add_missing' style='background:#17a2b8;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>➕ Ajouter Code Manquant</a>";
        echo "<a href='?action=test_function' style='background:#6f42c1;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🧪 Tester Fonction</a>";
        echo "</div>";
        
    } elseif ($action === 'clean_codes') {
        echo "<h3>🧹 Nettoyage des Codes</h3>";
        
        // Nettoyer tous les codes (supprimer espaces, caractères invisibles)
        $stmt = $conn->query("SELECT id_pont, code_pont FROM pont_bascule");
        $ponts = $stmt->fetchAll();
        
        $cleaned = 0;
        foreach ($ponts as $pont) {
            $original = $pont['code_pont'];
            $cleaned_code = trim(preg_replace('/[^\x20-\x7E]/', '', $original)); // Garder seulement les caractères ASCII imprimables
            
            if ($original !== $cleaned_code) {
                $update_stmt = $conn->prepare("UPDATE pont_bascule SET code_pont = ? WHERE id_pont = ?");
                $update_stmt->execute([$cleaned_code, $pont['id_pont']]);
                echo "<div class='success'>✅ Nettoyé: '{$original}' → '{$cleaned_code}'</div>";
                $cleaned++;
            }
        }
        
        if ($cleaned === 0) {
            echo "<div class='info'>ℹ️ Aucun code à nettoyer</div>";
        } else {
            echo "<div class='success'>🎉 {$cleaned} codes nettoyés</div>";
        }
        
    } elseif ($action === 'add_missing') {
        echo "<h3>➕ Ajout du Code Manquant</h3>";
        
        $missing_code = "UNIPALM-PB-0003-CI";
        
        // Vérifier s'il existe déjà
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM pont_bascule WHERE code_pont = ?");
        $check_stmt->execute([$missing_code]);
        $exists = $check_stmt->fetchColumn();
        
        if ($exists) {
            echo "<div class='warning'>⚠️ Le code {$missing_code} existe déjà</div>";
        } else {
            // L'ajouter
            $insert_stmt = $conn->prepare("
                INSERT INTO pont_bascule (code_pont, nom_pont, gerant, cooperatif, latitude, longitude, statut) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $insert_stmt->execute([
                $missing_code,
                'Pont UNIPALM PB 0003 CI',
                'Fisher',
                'Unicoop',
                5.3364,
                -4.0267,
                'Actif'
            ]);
            
            echo "<div class='success'>✅ Code {$missing_code} ajouté avec succès</div>";
        }
        
    } elseif ($action === 'test_function') {
        echo "<h3>🧪 Test de la Fonction</h3>";
        
        $test_codes = [
            "UNIPALM-PB-0001-CI",
            "UNIPALM-PB-0002-CI", 
            "UNIPALM-PB-0003-CI",
            "UNIPALM-PB-0004-CI",
            "UNIPALM-PB-0005-CI"
        ];
        
        foreach ($test_codes as $code) {
            echo "<div class='info'>Test du code: <code>{$code}</code></div>";
            
            try {
                $result = getPontBasculeByCode($conn, $code);
                if ($result) {
                    echo "<div class='success'>✅ Trouvé: {$result['nom_pont']} - {$result['gerant']}</div>";
                } else {
                    echo "<div class='error'>❌ Non trouvé</div>";
                }
            } catch (Exception $e) {
                echo "<div class='error'>❌ Erreur: {$e->getMessage()}</div>";
            }
            
            echo "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur: " . $e->getMessage() . "</div>";
}

echo "</div>"; // Fin container

// Boutons de navigation
echo "<div style='text-align:center; margin:20px;'>";
echo "<a href='verification_pont.php?code=UNIPALM-PB-0003-CI' target='_blank' style='background:#28a745;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;font-weight:bold;'>🧪 TESTER MAINTENANT</a>";
echo "<br><br>";
echo "<a href='?action=analyze' style='background:#6c757d;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🔍 Analyser</a>";
echo "<a href='test_code_specific.php' style='background:#17a2b8;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🧪 Test Spécifique</a>";
echo "<a href='pages/ponts.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🏠 Retour</a>";
echo "</div>";
?>

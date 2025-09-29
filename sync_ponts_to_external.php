<?php
// Script de synchronisation des ponts-bascules vers le serveur externe
header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔄 Synchronisation des Ponts-Bascules</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 5px 0; }
    .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 5px 0; }
    .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 5px 0; }
    .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 5px 0; }
    .progress { background: #e9ecef; border-radius: 4px; margin: 10px 0; }
    .progress-bar { background: #007bff; color: white; text-align: center; padding: 5px; border-radius: 4px; transition: width 0.3s; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background-color: #f8f9fa; }
</style>";

echo "<div class='container'>";

// Configuration
$sync_mode = $_GET['mode'] ?? 'preview';

// Connexions
$local_conn = null;
$external_conn = null;

try {
    // Connexion externe (serveur de production)
    require_once 'config_verification.php';
    $external_conn = $conn;
    echo "<div class='success'>✅ Connexion externe réussie</div>";
    
    // Pour la connexion locale, on va utiliser les mêmes paramètres mais pointer vers la base locale
    // Ou simplement utiliser la base externe comme référence
    $local_conn = $external_conn; // Utiliser la même connexion pour l'instant
    echo "<div class='info'>ℹ️ Utilisation de la base externe comme source</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur de connexion: " . $e->getMessage() . "</div>";
    exit;
}

// Étape 1: Récupérer les données locales
echo "<h3>📊 Étape 1: Données Locales</h3>";

try {
    $local_stmt = $local_conn->query("SELECT COUNT(*) as count FROM pont_bascule");
    $local_count = $local_stmt->fetch()['count'];
    echo "<div class='info'>📊 Ponts dans la base locale: {$local_count}</div>";
    
    if ($local_count > 0) {
        $local_stmt = $local_conn->query("
            SELECT code_pont, nom_pont, gerant, cooperatif, latitude, longitude, statut, date_creation 
            FROM pont_bascule 
            ORDER BY code_pont
        ");
        $local_ponts = $local_stmt->fetchAll();
        
        echo "<div class='info'>📋 Aperçu des données locales:</div>";
        echo "<table>";
        echo "<tr><th>Code</th><th>Nom</th><th>Gérant</th><th>Statut</th></tr>";
        
        foreach (array_slice($local_ponts, 0, 5) as $pont) {
            echo "<tr>";
            echo "<td>{$pont['code_pont']}</td>";
            echo "<td>" . ($pont['nom_pont'] ?: 'Non défini') . "</td>";
            echo "<td>{$pont['gerant']}</td>";
            echo "<td>{$pont['statut']}</td>";
            echo "</tr>";
        }
        
        if ($local_count > 5) {
            echo "<tr><td colspan='4'><em>... et " . ($local_count - 5) . " autres ponts</em></td></tr>";
        }
        
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ Aucun pont dans la base locale</div>";
        exit;
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur lecture locale: " . $e->getMessage() . "</div>";
    exit;
}

// Étape 2: Vérifier les données externes
echo "<h3>🌐 Étape 2: Données Externes</h3>";

try {
    $external_stmt = $external_conn->query("SELECT COUNT(*) as count FROM pont_bascule");
    $external_count = $external_stmt->fetch()['count'];
    echo "<div class='info'>📊 Ponts dans la base externe: {$external_count}</div>";
    
    if ($external_count > 0) {
        $external_stmt = $external_conn->query("SELECT code_pont FROM pont_bascule ORDER BY code_pont");
        $external_codes = array_column($external_stmt->fetchAll(), 'code_pont');
        
        echo "<div class='info'>📋 Codes existants sur le serveur externe:</div>";
        echo "<div style='max-height:150px; overflow-y:auto; background:#f8f9fa; padding:10px; border-radius:4px;'>";
        foreach ($external_codes as $code) {
            echo "<div>• {$code}</div>";
        }
        echo "</div>";
    } else {
        echo "<div class='warning'>⚠️ Aucun pont dans la base externe</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur lecture externe: " . $e->getMessage() . "</div>";
    exit;
}

// Étape 3: Analyse des différences
echo "<h3>🔍 Étape 3: Analyse des Différences</h3>";

$local_codes = array_column($local_ponts, 'code_pont');
$external_codes = $external_codes ?? [];

$to_insert = array_diff($local_codes, $external_codes);
$to_update = array_intersect($local_codes, $external_codes);
$to_delete = array_diff($external_codes, $local_codes);

echo "<div class='info'>📊 Analyse:</div>";
echo "<table>";
echo "<tr><th>Action</th><th>Nombre</th><th>Description</th></tr>";
echo "<tr><td>🆕 Insertion</td><td>" . count($to_insert) . "</td><td>Nouveaux ponts à ajouter</td></tr>";
echo "<tr><td>🔄 Mise à jour</td><td>" . count($to_update) . "</td><td>Ponts existants à mettre à jour</td></tr>";
echo "<tr><td>🗑️ Suppression</td><td>" . count($to_delete) . "</td><td>Ponts à supprimer (orphelins)</td></tr>";
echo "</table>";

if (count($to_insert) > 0) {
    echo "<div class='warning'>🆕 Ponts à insérer:</div>";
    foreach ($to_insert as $code) {
        echo "<div>• {$code}</div>";
    }
}

// Mode preview ou exécution
if ($sync_mode === 'preview') {
    echo "<div class='warning'>👁️ Mode APERÇU - Aucune modification effectuée</div>";
    echo "<div style='text-align:center; margin:20px;'>";
    echo "<a href='?mode=execute' style='background:#dc3545;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;font-weight:bold;' onclick='return confirm(\"Êtes-vous sûr de vouloir synchroniser les données ?\")'>🚀 EXÉCUTER LA SYNCHRONISATION</a>";
    echo "</div>";
    
} else if ($sync_mode === 'execute') {
    echo "<h3>🚀 Étape 4: Exécution de la Synchronisation</h3>";
    
    try {
        $external_conn->beginTransaction();
        
        $inserted = 0;
        $updated = 0;
        $errors = 0;
        
        // Préparer les requêtes
        $insert_stmt = $external_conn->prepare("
            INSERT INTO pont_bascule (code_pont, nom_pont, gerant, cooperatif, latitude, longitude, statut, date_creation) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $update_stmt = $external_conn->prepare("
            UPDATE pont_bascule 
            SET nom_pont = ?, gerant = ?, cooperatif = ?, latitude = ?, longitude = ?, statut = ?, date_creation = ?
            WHERE code_pont = ?
        ");
        
        // Traitement des ponts
        foreach ($local_ponts as $pont) {
            try {
                if (in_array($pont['code_pont'], $to_insert)) {
                    // Insertion
                    $insert_stmt->execute([
                        $pont['code_pont'],
                        $pont['nom_pont'],
                        $pont['gerant'],
                        $pont['cooperatif'],
                        $pont['latitude'],
                        $pont['longitude'],
                        $pont['statut'],
                        $pont['date_creation']
                    ]);
                    $inserted++;
                    echo "<div class='success'>✅ Inséré: {$pont['code_pont']}</div>";
                    
                } else if (in_array($pont['code_pont'], $to_update)) {
                    // Mise à jour
                    $update_stmt->execute([
                        $pont['nom_pont'],
                        $pont['gerant'],
                        $pont['cooperatif'],
                        $pont['latitude'],
                        $pont['longitude'],
                        $pont['statut'],
                        $pont['date_creation'],
                        $pont['code_pont']
                    ]);
                    $updated++;
                    echo "<div class='info'>🔄 Mis à jour: {$pont['code_pont']}</div>";
                }
                
            } catch (Exception $e) {
                $errors++;
                echo "<div class='error'>❌ Erreur {$pont['code_pont']}: " . $e->getMessage() . "</div>";
            }
        }
        
        $external_conn->commit();
        
        echo "<div class='success'>🎉 Synchronisation terminée !</div>";
        echo "<div class='info'>📊 Résultats:</div>";
        echo "<table>";
        echo "<tr><td>Insertions</td><td>{$inserted}</td></tr>";
        echo "<tr><td>Mises à jour</td><td>{$updated}</td></tr>";
        echo "<tr><td>Erreurs</td><td>{$errors}</td></tr>";
        echo "</table>";
        
        // Test du code problématique
        echo "<h3>🧪 Test du Code Problématique</h3>";
        $test_stmt = $external_conn->prepare("SELECT * FROM pont_bascule WHERE code_pont = ?");
        $test_stmt->execute(['UNIPALM-PB-0003-CI']);
        $test_result = $test_stmt->fetch();
        
        if ($test_result) {
            echo "<div class='success'>✅ Le code UNIPALM-PB-0003-CI est maintenant disponible !</div>";
            echo "<div style='text-align:center; margin:20px;'>";
            echo "<a href='verification_pont.php?code=UNIPALM-PB-0003-CI' target='_blank' style='background:#28a745;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;font-weight:bold;'>🧪 TESTER LA VÉRIFICATION</a>";
            echo "</div>";
        } else {
            echo "<div class='warning'>⚠️ Le code UNIPALM-PB-0003-CI n'a pas été trouvé après synchronisation</div>";
        }
        
    } catch (Exception $e) {
        $external_conn->rollBack();
        echo "<div class='error'>❌ Erreur de synchronisation: " . $e->getMessage() . "</div>";
    }
}

echo "</div>"; // Fin container

// Boutons de navigation
echo "<div style='text-align:center; margin:20px;'>";
echo "<a href='debug_verification.php' style='background:#17a2b8;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🔍 Diagnostic</a>";
echo "<a href='test_config_verification.php' style='background:#6f42c1;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🧪 Tests</a>";
echo "<a href='pages/ponts.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🏠 Retour</a>";
echo "</div>";
?>

<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_ponts.php';

echo "<h2>Test de la Modification des Ponts-Bascules</h2>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    // Créer un pont de test s'il n'existe pas
    $test_code = createPontBascule($conn, "Pont de Test", 5.3373670, -3.9752116, "Gérant Test", "Coopérative Test", "Actif");
    
    if ($test_code) {
        echo "<div class='success'>✅ Pont de test créé avec le code: {$test_code}</div>";
        
        // Récupérer l'ID du pont créé
        $pont = getPontBasculeByCode($conn, $test_code);
        if ($pont) {
            echo "<div class='info'>📋 Pont créé avec l'ID: {$pont['id_pont']}</div>";
            echo "<div class='info'>📍 Nom: {$pont['nom_pont']}</div>";
            echo "<div class='info'>👤 Gérant: {$pont['gerant']}</div>";
            echo "<div class='info'>🏢 Coopérative: {$pont['cooperatif']}</div>";
            echo "<div class='info'>📊 Statut: {$pont['statut']}</div>";
            
            echo "<hr>";
            echo "<h3>Test de Modification</h3>";
            
            // Tester la modification
            $result = updatePontBascule(
                $conn, 
                $pont['id_pont'], 
                $pont['code_pont'], 
                "Pont Modifié Test", 
                5.1234567, 
                -3.7654321, 
                "Nouveau Gérant", 
                "Nouvelle Coopérative", 
                "Inactif"
            );
            
            if ($result) {
                echo "<div class='success'>✅ Modification réussie !</div>";
                
                // Vérifier les modifications
                $pont_modifie = getPontBasculeById($conn, $pont['id_pont']);
                if ($pont_modifie) {
                    echo "<div class='info'>📋 Nouveau nom: {$pont_modifie['nom_pont']}</div>";
                    echo "<div class='info'>📍 Nouvelles coordonnées: {$pont_modifie['latitude']}, {$pont_modifie['longitude']}</div>";
                    echo "<div class='info'>👤 Nouveau gérant: {$pont_modifie['gerant']}</div>";
                    echo "<div class='info'>🏢 Nouvelle coopérative: {$pont_modifie['cooperatif']}</div>";
                    echo "<div class='info'>📊 Nouveau statut: {$pont_modifie['statut']}</div>";
                }
            } else {
                echo "<div class='error'>❌ Erreur lors de la modification</div>";
            }
        }
    } else {
        echo "<div class='error'>❌ Erreur lors de la création du pont de test</div>";
    }
    
    echo "<hr>";
    echo "<h3>Tous les Ponts-Bascules</h3>";
    
    $ponts = getAllPontsBascules($conn);
    if ($ponts) {
        echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
        echo "<tr style='background:#f0f0f0;'><th>ID</th><th>Code</th><th>Nom</th><th>Gérant</th><th>Coopérative</th><th>Statut</th><th>Actions</th></tr>";
        
        foreach ($ponts as $pont) {
            echo "<tr>";
            echo "<td>{$pont['id_pont']}</td>";
            echo "<td><strong>{$pont['code_pont']}</strong></td>";
            echo "<td>{$pont['nom_pont']}</td>";
            echo "<td>{$pont['gerant']}</td>";
            echo "<td>" . ($pont['cooperatif'] ?: 'Non spécifiée') . "</td>";
            echo "<td><span style='color:" . ($pont['statut'] === 'Actif' ? 'green' : 'orange') . ";'>●</span> {$pont['statut']}</td>";
            echo "<td><button onclick=\"testEdit({$pont['id_pont']})\">Tester Modification</button></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<br><a href='ponts.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>→ Aller aux Ponts-Bascules</a>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur: " . $e->getMessage() . "</div>";
}
?>

<script>
function testEdit(id) {
    alert('Test de modification pour le pont ID: ' + id + '\n\nCliquez sur "Aller aux Ponts-Bascules" pour tester la vraie interface de modification.');
}
</script>

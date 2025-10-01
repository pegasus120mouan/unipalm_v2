<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_ponts.php';

echo "<h2>Mise à Jour des Codes Ponts - Format Professionnel</h2>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    // Récupérer tous les ponts existants
    $stmt = $conn->query("SELECT * FROM pont_bascule ORDER BY id_pont");
    $ponts = $stmt->fetchAll();
    
    if (empty($ponts)) {
        echo "<div class='error'>❌ Aucun pont-bascule trouvé</div>";
        echo "<br><a href='init_ponts.php'>→ Initialiser les données de test</a>";
        exit;
    }
    
    echo "<h3>Ponts existants :</h3>";
    echo "<table border='1' style='border-collapse:collapse;width:100%;margin-bottom:20px;'>";
    echo "<tr style='background:#f0f0f0;'><th>ID</th><th>Ancien Code</th><th>Nouveau Code</th><th>Gérant</th><th>Status</th></tr>";
    
    $counter = 1;
    foreach ($ponts as $pont) {
        $oldCode = $pont['code_pont'];
        
        // Générer le nouveau code professionnel
        $newCode = "UNIPALM-PB-" . str_pad($counter, 4, '0', STR_PAD_LEFT) . "-CI";
        
        // Mettre à jour en base
        try {
            $updateSql = "UPDATE pont_bascule SET code_pont = :new_code WHERE id_pont = :id";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bindParam(':new_code', $newCode);
            $updateStmt->bindParam(':id', $pont['id_pont']);
            $updateStmt->execute();
            
            $status = "<span class='success'>✅ Mis à jour</span>";
        } catch (Exception $e) {
            $status = "<span class='error'>❌ Erreur</span>";
        }
        
        echo "<tr>";
        echo "<td>" . $pont['id_pont'] . "</td>";
        echo "<td><code>" . $oldCode . "</code></td>";
        echo "<td><strong><code>" . $newCode . "</code></strong></td>";
        echo "<td>" . $pont['gerant'] . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>";
        
        $counter++;
    }
    echo "</table>";
    
    echo "<div class='success'>✅ Mise à jour terminée !</div>";
    echo "<h3>Nouveau format de codes :</h3>";
    echo "<ul>";
    echo "<li><strong>UNIPALM</strong> : Nom de l'entreprise</li>";
    echo "<li><strong>PB</strong> : Pont-Bascule</li>";
    echo "<li><strong>XXXX</strong> : Numéro séquentiel sur 4 chiffres</li>";
    echo "<li><strong>CI</strong> : Code pays (Côte d'Ivoire)</li>";
    echo "</ul>";
    
    echo "<hr>";
    echo "<div class='info'>Les prochains ponts ajoutés utiliseront automatiquement ce format.</div>";
    echo "<br><a href='ponts.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>→ Retour aux Ponts-Bascules</a>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur: " . $e->getMessage() . "</div>";
}
?>

<?php
require_once '../inc/functions/connexion.php';

echo "<h2>Test rapide de détection des conflits VOP</h2>";

try {
    // Requête spécifique pour VOP
    $sql = "SELECT 
                p1.id as id1, 
                p1.prix as prix1, 
                p1.date_debut as debut1, 
                p1.date_fin as fin1,
                p2.id as id2, 
                p2.prix as prix2, 
                p2.date_debut as debut2, 
                p2.date_fin as fin2
            FROM prix_unitaires p1
            INNER JOIN prix_unitaires p2 ON p1.id_usine = p2.id_usine AND p1.id < p2.id
            INNER JOIN usines u ON p1.id_usine = u.id_usine
            WHERE u.nom_usine = 'VOP'
            AND p1.date_debut = p2.date_debut 
            AND COALESCE(p1.date_fin, '9999-12-31') = COALESCE(p2.date_fin, '9999-12-31')";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $conflits_vop = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Conflits détectés pour VOP :</h3>";
    if (empty($conflits_vop)) {
        echo "<p style='color: green;'>✅ Aucun conflit détecté pour VOP</p>";
    } else {
        echo "<p style='color: red;'>❌ " . count($conflits_vop) . " conflit(s) détecté(s) pour VOP :</p>";
        foreach ($conflits_vop as $conflit) {
            echo "<div style='border: 1px solid red; padding: 10px; margin: 10px;'>";
            echo "<strong>Conflit détecté :</strong><br>";
            echo "ID1: {$conflit['id1']} - Prix: {$conflit['prix1']} - Période: " . date('d/m/Y', strtotime($conflit['debut1'])) . " à " . ($conflit['fin1'] ? date('d/m/Y', strtotime($conflit['fin1'])) : 'ouverte') . "<br>";
            echo "ID2: {$conflit['id2']} - Prix: {$conflit['prix2']} - Période: " . date('d/m/Y', strtotime($conflit['debut2'])) . " à " . ($conflit['fin2'] ? date('d/m/Y', strtotime($conflit['fin2'])) : 'ouverte') . "<br>";
            
            // Bouton de correction rapide
            echo "<form method='POST' style='margin-top: 10px;'>";
            echo "<input type='hidden' name='supprimer_id' value='{$conflit['id1']}'>";
            echo "<button type='submit' style='background: red; color: white; padding: 5px 10px;' onclick=\"return confirm('Supprimer le prix {$conflit['prix1']} (ID: {$conflit['id1']}) ?')\">Supprimer l'ancien prix ({$conflit['prix1']})</button>";
            echo "</form>";
            echo "</div>";
        }
    }
    
    // Traitement de la suppression
    if ($_POST['supprimer_id']) {
        $id_a_supprimer = $_POST['supprimer_id'];
        $sql_delete = "DELETE FROM prix_unitaires WHERE id = :id";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bindParam(':id', $id_a_supprimer, PDO::PARAM_INT);
        
        if ($stmt_delete->execute()) {
            echo "<p style='color: green; font-weight: bold;'>✅ Prix supprimé avec succès ! Actualisez la page pour voir le résultat.</p>";
            echo "<script>setTimeout(() => window.location.reload(), 2000);</script>";
        } else {
            echo "<p style='color: red;'>❌ Erreur lors de la suppression</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur : " . $e->getMessage() . "</p>";
}

echo "<br><a href='prix_unitaires.php' style='background: blue; color: white; padding: 10px; text-decoration: none;'>← Retour aux prix unitaires</a>";
echo "<br><br><a href='detect_conflits_prix.php' style='background: orange; color: white; padding: 10px; text-decoration: none;'>🔍 Outil de détection complet</a>";
?>

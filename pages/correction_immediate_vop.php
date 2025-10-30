<?php
require_once '../inc/functions/connexion.php';

echo "<h2>🚨 Correction immédiate du problème VOP</h2>";

try {
    // Démarrer une transaction
    $conn->beginTransaction();
    
    // 1. D'abord, identifier tous les prix VOP en conflit
    $sql_conflits = "SELECT 
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
    
    $stmt_conflits = $conn->prepare($sql_conflits);
    $stmt_conflits->execute();
    $conflits = $stmt_conflits->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($conflits)) {
        echo "<p style='color: green;'>✅ Aucun conflit VOP détecté dans la base de données.</p>";
    } else {
        echo "<p style='color: red;'>❌ " . count($conflits) . " conflit(s) VOP détecté(s) :</p>";
        
        $ids_a_supprimer = [];
        
        foreach ($conflits as $conflit) {
            echo "<div style='border: 1px solid red; padding: 10px; margin: 10px; background: #ffe6e6;'>";
            echo "<strong>Conflit VOP détecté :</strong><br>";
            echo "• ID {$conflit['id1']} - Prix: {$conflit['prix1']} FCFA (ANCIEN - sera supprimé)<br>";
            echo "• ID {$conflit['id2']} - Prix: {$conflit['prix2']} FCFA (RÉCENT - sera conservé)<br>";
            echo "• Période: " . date('d/m/Y', strtotime($conflit['debut1'])) . " - " . 
                 ($conflit['fin1'] ? date('d/m/Y', strtotime($conflit['fin1'])) : 'ouverte') . "<br>";
            echo "</div>";
            
            // Ajouter l'ancien ID à la liste de suppression
            $ids_a_supprimer[] = $conflit['id1'];
        }
        
        // 2. Supprimer tous les anciens prix en conflit
        if (!empty($ids_a_supprimer)) {
            $placeholders = str_repeat('?,', count($ids_a_supprimer) - 1) . '?';
            $sql_delete = "DELETE FROM prix_unitaires WHERE id IN ($placeholders)";
            $stmt_delete = $conn->prepare($sql_delete);
            
            if ($stmt_delete->execute($ids_a_supprimer)) {
                echo "<div style='background: #e6ffe6; padding: 15px; margin: 10px; border: 1px solid green;'>";
                echo "<h3 style='color: green;'>✅ CORRECTION RÉUSSIE !</h3>";
                echo "<p><strong>" . count($ids_a_supprimer) . " ancien(s) prix VOP supprimé(s) avec succès.</strong></p>";
                echo "<p>IDs supprimés : " . implode(', ', $ids_a_supprimer) . "</p>";
                echo "</div>";
                
                // Valider la transaction
                $conn->commit();
                
                echo "<p style='color: blue; font-weight: bold;'>🔄 Actualisez la page des prix unitaires pour voir le résultat !</p>";
                
            } else {
                throw new Exception("Erreur lors de la suppression des prix en conflit");
            }
        }
    }
    
    // 3. Vérification finale
    echo "<h3>🔍 Vérification finale :</h3>";
    $sql_verif = "SELECT COUNT(*) as nb_vop FROM prix_unitaires p 
                  INNER JOIN usines u ON p.id_usine = u.id_usine 
                  WHERE u.nom_usine = 'VOP'";
    $stmt_verif = $conn->prepare($sql_verif);
    $stmt_verif->execute();
    $nb_vop = $stmt_verif->fetch(PDO::FETCH_ASSOC)['nb_vop'];
    
    echo "<p>Nombre total de prix VOP restants : <strong>{$nb_vop}</strong></p>";
    
    // Afficher les prix VOP restants
    $sql_restants = "SELECT p.*, u.nom_usine FROM prix_unitaires p 
                     INNER JOIN usines u ON p.id_usine = u.id_usine 
                     WHERE u.nom_usine = 'VOP' 
                     ORDER BY p.date_debut";
    $stmt_restants = $conn->prepare($sql_restants);
    $stmt_restants->execute();
    $prix_restants = $stmt_restants->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($prix_restants)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Prix</th><th>Date Début</th><th>Date Fin</th>";
        echo "</tr>";
        
        foreach ($prix_restants as $prix) {
            echo "<tr>";
            echo "<td>{$prix['id']}</td>";
            echo "<td>{$prix['prix']} FCFA</td>";
            echo "<td>" . date('d/m/Y', strtotime($prix['date_debut'])) . "</td>";
            echo "<td>" . ($prix['date_fin'] ? date('d/m/Y', strtotime($prix['date_fin'])) : 'Ouverte') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    $conn->rollBack();
    echo "<div style='background: #ffe6e6; padding: 15px; margin: 10px; border: 1px solid red;'>";
    echo "<h3 style='color: red;'>❌ ERREUR</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<br><br>";
echo "<a href='prix_unitaires.php' style='background: blue; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Retour aux Prix Unitaires</a>";
echo "&nbsp;&nbsp;";
echo "<a href='detect_conflits_prix.php' style='background: orange; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔍 Vérifier tous les conflits</a>";
?>

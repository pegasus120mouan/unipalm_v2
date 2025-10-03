<?php
require_once '../inc/functions/connexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_agent=$_POST['id_agent'];
    $date_debut=$_POST['date_debut'];
    $date_fin=$_POST['date_fin'];


    try {
        $sql = "INSERT INTO bordereau (numero_bordereau, id_agent, date_debut, date_fin, poids_total, montant_total)
                SELECT 
                    CONCAT('BORD-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', :id_agent1, '-', FLOOR(RAND() * 9000) + 1000), 
                    :id_agent2, 
                    :date_debut1, 
                    :date_fin1, 
                    SUM(0), 
                    SUM(0)
                FROM tickets t 
                WHERE t.id_agent = :id_agent3 
                AND t.created_at BETWEEN CONCAT(:date_debut2, ' 00:00:00') AND CONCAT(:date_fin2, ' 23:59:59')";
    
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'id_agent1' => $id_agent,
            'id_agent2' => $id_agent,
            'id_agent3' => $id_agent,
            'date_debut1' => $date_debut,
            'date_debut2' => $date_debut,
            'date_fin1' => $date_fin,
            'date_fin2' => $date_fin
        ]);
        
        $id_bordereau = $conn->lastInsertId();
        
        echo "Bordereau créé avec succès ! ID: " . $id_bordereau . "\n";
        
        // Afficher les détails du bordereau créé
        if ($id_bordereau) {
            $sql = "SELECT * FROM bordereau WHERE id_bordereau = :id_bordereau";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':id_bordereau', $id_bordereau);
            $stmt->execute();
            $bordereau = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($bordereau) {
                header("Location: bordereaux.php");
                exit();
            }
            else
            {
                header("Location: bordereaux.php");
                exit();
            }
        }
    
    } catch (Exception $e) {
        echo "Erreur : " . $e->getMessage() . "\n";
    }


    
}
?>

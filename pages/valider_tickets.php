<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_tickets.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$is_mass_validation = isset($_POST['is_mass_validation']) ? $_POST['is_mass_validation'] : false;
$update_all_usine = isset($_POST['update_all_usine']) ? $_POST['update_all_usine'] : false;
$ticket_ids = [];
$prix_unitaire = isset($_POST['prix_unitaire']) ? $_POST['prix_unitaire'] : 1000; // Valeur par défaut pour la validation en masse

if ($is_mass_validation) {
    // Validation en masse
    if (!isset($_POST['ticket_ids']) || empty($_POST['ticket_ids'])) {
        echo json_encode(['success' => false, 'message' => 'Aucun ticket sélectionné']);
        exit;
    }
    $ticket_ids = is_array($_POST['ticket_ids']) ? $_POST['ticket_ids'] : [$_POST['ticket_ids']];
} else {
    // Validation individuelle
    if (!isset($_POST['ticket_id']) || !isset($_POST['prix_unitaire'])) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes pour la validation']);
        exit;
    }
    $ticket_ids = [$_POST['ticket_id']];
    $prix_unitaire = $_POST['prix_unitaire'];
}

try {
    $conn->beginTransaction();

    $sql = "UPDATE tickets 
            SET date_validation_boss = NOW(),
                prix_unitaire = :prix_unitaire,
                updated_at = NOW()
            WHERE id_ticket = :ticket_id 
            AND date_validation_boss IS NULL";

    $stmt = $conn->prepare($sql);
    $validated = 0;
    $usines_affected = [];

    foreach ($ticket_ids as $ticket_id) {
        // Récupérer l'ID de l'usine avant la mise à jour
        if ($update_all_usine) {
            $sql_get_usine = "SELECT usine_id FROM tickets WHERE id_ticket = :ticket_id";
            $stmt_get_usine = $conn->prepare($sql_get_usine);
            $stmt_get_usine->execute([':ticket_id' => $ticket_id]);
            $ticket_info = $stmt_get_usine->fetch(PDO::FETCH_ASSOC);
            
            if ($ticket_info) {
                $usines_affected[] = $ticket_info['usine_id'];
            }
        }

        $result = $stmt->execute([
            ':prix_unitaire' => $prix_unitaire,
            ':ticket_id' => $ticket_id
        ]);

        if ($result && $stmt->rowCount() > 0) {
            $validated++;
        }
    }

    // Si l'option de mise à jour de tous les tickets de l'usine est activée
    if ($update_all_usine && !empty($usines_affected)) {
        $usines_affected = array_unique($usines_affected);
        
        foreach ($usines_affected as $usine_id) {
            // Mettre à jour tous les autres tickets de la même usine qui sont en attente
            $sql_update_usine = "UPDATE tickets 
                               SET prix_unitaire = :prix_unitaire,
                                   updated_at = NOW()
                               WHERE usine_id = :usine_id 
                               AND date_validation_boss IS NULL
                               AND prix_unitaire IS NULL";
            
            $stmt_update_usine = $conn->prepare($sql_update_usine);
            $stmt_update_usine->execute([
                ':prix_unitaire' => $prix_unitaire,
                ':usine_id' => $usine_id
            ]);
        }
    }

    if ($validated > 0) {
        $conn->commit();
        
        $message = $validated . ' ticket(s) validé(s) avec succès';
        if ($update_all_usine && !empty($usines_affected)) {
            $message .= '. Prix unitaire appliqué à tous les tickets en attente des usines concernées.';
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'usines_updated' => $update_all_usine ? $usines_affected : []
        ]);
    } else {
        $conn->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Aucun ticket n\'a pu être validé'
        ]);
    }

} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la validation des tickets: ' . $e->getMessage()
    ]);
}

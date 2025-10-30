<?php
require_once '../inc/functions/connexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Vérifier les paramètres requis
if (!isset($_POST['ticket_id']) || !isset($_POST['prix_unitaire'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$ticket_id = $_POST['ticket_id'];
$nouveau_prix_unitaire = $_POST['prix_unitaire'];
$update_all_usine = isset($_POST['update_all_usine']) ? $_POST['update_all_usine'] : false;

try {
    $conn->beginTransaction();

    // D'abord, récupérer l'ID de l'usine du ticket concerné
    $sql_get_usine = "SELECT usine_id FROM tickets WHERE id_ticket = :ticket_id";
    $stmt_get_usine = $conn->prepare($sql_get_usine);
    $stmt_get_usine->execute([':ticket_id' => $ticket_id]);
    $ticket_info = $stmt_get_usine->fetch(PDO::FETCH_ASSOC);

    if (!$ticket_info) {
        throw new Exception('Ticket non trouvé');
    }

    $usine_id = $ticket_info['usine_id'];

    if ($update_all_usine) {
        // Mettre à jour tous les tickets de la même usine qui sont en attente (non validés)
        $sql_update_all = "UPDATE tickets 
                          SET prix_unitaire = :prix_unitaire,
                              updated_at = NOW()
                          WHERE usine_id = :usine_id 
                          AND date_validation_boss IS NULL
                          AND prix_unitaire IS NULL";
        
        $stmt_update_all = $conn->prepare($sql_update_all);
        $result_all = $stmt_update_all->execute([
            ':prix_unitaire' => $nouveau_prix_unitaire,
            ':usine_id' => $usine_id
        ]);

        $tickets_updated = $stmt_update_all->rowCount();

        // Aussi mettre à jour le ticket spécifique s'il n'était pas inclus dans la requête précédente
        $sql_update_specific = "UPDATE tickets 
                               SET prix_unitaire = :prix_unitaire,
                                   updated_at = NOW()
                               WHERE id_ticket = :ticket_id";
        
        $stmt_update_specific = $conn->prepare($sql_update_specific);
        $stmt_update_specific->execute([
            ':prix_unitaire' => $nouveau_prix_unitaire,
            ':ticket_id' => $ticket_id
        ]);

        if ($stmt_update_specific->rowCount() > 0 && $tickets_updated == 0) {
            $tickets_updated = 1;
        }

    } else {
        // Mettre à jour seulement le ticket spécifique
        $sql_update_one = "UPDATE tickets 
                          SET prix_unitaire = :prix_unitaire,
                              updated_at = NOW()
                          WHERE id_ticket = :ticket_id";
        
        $stmt_update_one = $conn->prepare($sql_update_one);
        $result_one = $stmt_update_one->execute([
            ':prix_unitaire' => $nouveau_prix_unitaire,
            ':ticket_id' => $ticket_id
        ]);

        $tickets_updated = $stmt_update_one->rowCount();
    }

    if ($tickets_updated > 0) {
        $conn->commit();
        
        // Récupérer le nom de l'usine pour le message de retour
        $sql_get_usine_name = "SELECT nom_usine FROM usines WHERE id_usine = :usine_id";
        $stmt_get_usine_name = $conn->prepare($sql_get_usine_name);
        $stmt_get_usine_name->execute([':usine_id' => $usine_id]);
        $usine_info = $stmt_get_usine_name->fetch(PDO::FETCH_ASSOC);
        $nom_usine = $usine_info ? $usine_info['nom_usine'] : 'Usine inconnue';

        $message = $update_all_usine ? 
            "Prix unitaire mis à jour pour {$tickets_updated} ticket(s) de l'usine {$nom_usine}" :
            "Prix unitaire mis à jour pour le ticket sélectionné";

        echo json_encode([
            'success' => true,
            'message' => $message,
            'tickets_updated' => $tickets_updated,
            'usine_name' => $nom_usine,
            'usine_id' => $usine_id
        ]);
    } else {
        $conn->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Aucun ticket n\'a pu être mis à jour'
        ]);
    }

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
    ]);
}
?>

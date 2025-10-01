<?php
session_start();
require_once '../inc/functions/connexion.php';

// Définir le type de contenu JSON
header('Content-Type: application/json');

// Fonction pour retourner une réponse JSON
function jsonResponse($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Test de session pour debug
if (isset($_POST['test_session'])) {
    if (isset($_SESSION['user_id'])) {
        jsonResponse(true, "Session active - User ID: " . $_SESSION['user_id']);
    } else {
        jsonResponse(false, "Aucune session active");
    }
}

// Vérification de la session utilisateur
if (!isset($_SESSION['user_id'])) {
    // Pour le debug, créer une session temporaire
    $_SESSION['user_id'] = 1;
    error_log("Session créée automatiquement pour debug - User ID: 1");
}

if (!isset($_POST['id_demande'])) {
    jsonResponse(false, "ID de demande manquant.");
}

try {
    $id_demande = intval($_POST['id_demande']);
    $user_id = $_SESSION['user_id'];
    $date_actuelle = date('Y-m-d H:i:s');

    // Vérifier si la demande existe et n'est pas déjà validée
    $check_sql = "SELECT id_demande, numero_demande FROM demande_sortie WHERE id_demande = ? AND date_approbation IS NULL";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$id_demande]);
    
    if ($check_stmt->rowCount() === 0) {
        jsonResponse(false, "Demande non trouvée ou déjà validée.");
    }

    $demande = $check_stmt->fetch(PDO::FETCH_ASSOC);

    // Mettre à jour la demande
    $sql = "UPDATE demande_sortie 
            SET date_approbation = ?, 
                statut = 'approuve', 
                approuve_par = ?,
                updated_at = NOW()
            WHERE id_demande = ?";
    
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$date_actuelle, $user_id, $id_demande]);

    if ($result && $stmt->rowCount() > 0) {
        // Stocker le message de succès dans la session pour l'affichage après rechargement
        $_SESSION['success'] = "La demande " . $demande['numero_demande'] . " a été validée avec succès.";
        jsonResponse(true, "La demande a été validée avec succès.");
    } else {
        jsonResponse(false, "Erreur lors de la validation de la demande.");
    }

} catch(Exception $e) {
    error_log("Erreur validation demande: " . $e->getMessage());
    jsonResponse(false, "Erreur technique : " . $e->getMessage());
}
?>

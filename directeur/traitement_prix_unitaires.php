<?php
session_start();
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_prix_unitaires.php';

// Traitement de l'ajout d'un prix unitaire
if (isset($_POST['savePrixUnitaire'])) {
    $id_usine = $_POST['id_usine'];
    $prix = $_POST['prix'];
    $date_debut = $_POST['date_debut'];
    $date_fin = !empty($_POST['date_fin']) ? $_POST['date_fin'] : null;

    try {
        // Démarrer une transaction
        $conn->beginTransaction();

        // 1. Créer le prix unitaire avec validation des chevauchements
        $result = createPrixUnitaire($conn, $id_usine, $prix, $date_debut, $date_fin);
        
        if (!$result['success']) {
            if ($result['error'] === 'period_overlap') {
                // Construire un message détaillé avec les périodes en conflit
                $conflictMessage = "Enregistrement impossible : Un prix unitaire existe déjà pour cette usine dans la période spécifiée.\n\n";
                $conflictMessage .= "Périodes en conflit :\n";
                
                foreach ($result['conflicting_periods'] as $conflict) {
                    $conflictMessage .= "• Prix: " . number_format($conflict['prix'], 0, ',', ' ') . " FCFA";
                    $conflictMessage .= " - Période: " . $conflict['periode_str'] . "\n";
                }
                
                $_SESSION['error'] = $conflictMessage;
            } else {
                $_SESSION['error'] = $result['message'];
            }
            $conn->rollBack();
        } else {
            // Valider la transaction
            $conn->commit();
            $_SESSION['success'] = $result['message'];
        }
        
    } catch (PDOException $e) {
        // En cas d'erreur, annuler la transaction
        $conn->rollBack();
        error_log("Erreur lors de la création: " . $e->getMessage());
        $_SESSION['error'] = "Erreur lors de l'ajout du prix unitaire: " . $e->getMessage();
    }
    header('Location: prix_unitaires.php');
    exit();
}

// Traitement de la mise à jour d'un prix unitaire
if (isset($_POST['updatePrixUnitaire'])) {
    $id = $_POST['id'];
    $id_usine = $_POST['id_usine'];
    $prix = $_POST['prix'];
    $date_debut = $_POST['date_debut'];
    $date_fin = !empty($_POST['date_fin']) ? $_POST['date_fin'] : null;

    try {
        // Démarrer une transaction
        $conn->beginTransaction();

        // 1. Mettre à jour le prix unitaire avec validation des chevauchements
        $result = updatePrixUnitaire($conn, $id, $id_usine, $prix, $date_debut, $date_fin);
        
        if (!$result['success']) {
            if ($result['error'] === 'period_overlap') {
                // Construire un message détaillé avec les périodes en conflit
                $conflictMessage = "Modification impossible : Un prix unitaire existe déjà pour cette usine dans la période spécifiée.\n\n";
                $conflictMessage .= "Périodes en conflit :\n";
                
                foreach ($result['conflicting_periods'] as $conflict) {
                    $conflictMessage .= "• Prix: " . number_format($conflict['prix'], 0, ',', ' ') . " FCFA";
                    $conflictMessage .= " - Période: " . $conflict['periode_str'] . "\n";
                }
                
                $_SESSION['error'] = $conflictMessage;
            } else {
                $_SESSION['error'] = $result['message'];
            }
            $conn->rollBack();
        } else {
            // 2. Mettre à jour les tickets associés
            $sql_tickets = "UPDATE tickets 
                           SET prix_unitaire = :prix 
                           WHERE id_usine = :id_usine 
                           AND DATE(date_ticket) BETWEEN :date_debut 
                           AND COALESCE(:date_fin, CURRENT_DATE)";
            
            $stmt_tickets = $conn->prepare($sql_tickets);
            $stmt_tickets->bindParam(':prix', $prix);
            $stmt_tickets->bindParam(':id_usine', $id_usine, PDO::PARAM_INT);
            $stmt_tickets->bindParam(':date_debut', $date_debut);
            $stmt_tickets->bindParam(':date_fin', $date_fin);
            $stmt_tickets->execute();

            // Valider la transaction
            $conn->commit();
            $_SESSION['success'] = "Prix unitaire et tickets associés mis à jour avec succès";
        }
        
    } catch (PDOException $e) {
        // En cas d'erreur, annuler la transaction
        $conn->rollBack();
        error_log("Erreur lors de la mise à jour: " . $e->getMessage());
        $_SESSION['error'] = "Erreur lors de la mise à jour du prix unitaire: " . $e->getMessage();
    }
    header('Location: prix_unitaires.php');
    exit();
}

// Traitement de la suppression d'un prix unitaire
if (isset($_POST['deletePrixUnitaire'])) {
    $id = $_POST['id'];

    try {
        // Démarrer une transaction
        $conn->beginTransaction();

        // 1. Supprimer le prix unitaire
        if (!deletePrixUnitaire($conn, $id)) {
            throw new PDOException("Erreur lors de la suppression du prix unitaire");
        }

        // Valider la transaction
        $conn->commit();
        $_SESSION['success'] = "Prix unitaire supprimé avec succès";
    } catch (PDOException $e) {
        // En cas d'erreur, annuler la transaction
        $conn->rollBack();
        error_log("Erreur lors de la suppression: " . $e->getMessage());
        $_SESSION['error'] = "Erreur lors de la suppression du prix unitaire";
    }
    header('Location: prix_unitaires.php');
    exit();
}

// Si aucune action n'est spécifiée, redirection vers la page des prix unitaires
header('Location: prix_unitaires.php');
exit();
?>

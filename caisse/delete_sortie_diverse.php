<?php
session_start();
require_once '../inc/functions/connexion.php';

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Vous devez être connecté pour effectuer cette action.";
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    try {
        // Récupération et validation de l'ID
        $sortie_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$sortie_id || $sortie_id <= 0) {
            throw new Exception("ID de sortie invalide.");
        }

        // Commencer une transaction pour assurer la cohérence
        $conn->beginTransaction();

        // Vérification que la sortie existe et récupération des détails pour le log
        $checkQuery = "SELECT numero_sorties, montant, motifs FROM sorties_diverses WHERE id_sorties = :sortie_id";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindValue(':sortie_id', $sortie_id, PDO::PARAM_INT);
        $checkStmt->execute();

        $sortie = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$sortie) {
            throw new Exception("La sortie diverse à supprimer n'existe pas.");
        }

        $montantSortie = floatval($sortie['montant']);

        // Suppression de la sortie diverse
        $deleteQuery = "DELETE FROM sorties_diverses WHERE id_sorties = :sortie_id";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bindValue(':sortie_id', $sortie_id, PDO::PARAM_INT);

        if ($deleteStmt->execute()) {
            // Rembourser le montant au solde de caisse (car on supprime une sortie)
            // Récupérer le solde actuel
            $soldeQuery = "SELECT COALESCE(MAX(solde), 0) as solde_actuel FROM transactions";
            $soldeStmt = $conn->query($soldeQuery);
            $soldeActuel = $soldeStmt->fetch(PDO::FETCH_ASSOC)['solde_actuel'];
            
            // Le nouveau solde augmente car on annule une sortie
            $nouveauSolde = $soldeActuel + $montantSortie;
            
            // Créer une transaction d'approvisionnement pour compenser la suppression
            $sourceTransaction = "Annulation sortie diverse - " . $sortie['numero_sorties'];
            $motifsTransaction = "Suppression sortie diverse: " . $sortie['motifs'] . " (Remboursement: " . number_format($montantSortie, 0, ',', ' ') . " FCFA)";
            
            $transactionStmt = $conn->prepare("
                INSERT INTO transactions (
                    type_transaction, 
                    montant, 
                    date_transaction, 
                    motifs, 
                    source,
                    id_utilisateur,
                    solde
                ) VALUES (
                    'approvisionnement',
                    :montant,
                    NOW(),
                    :motifs,
                    :source,
                    :id_utilisateur,
                    :solde
                )
            ");
            
            $transactionStmt->bindValue(':montant', $montantSortie, PDO::PARAM_STR);
            $transactionStmt->bindValue(':motifs', $motifsTransaction, PDO::PARAM_STR);
            $transactionStmt->bindValue(':source', $sourceTransaction, PDO::PARAM_STR);
            $transactionStmt->bindValue(':id_utilisateur', $_SESSION['user_id'], PDO::PARAM_INT);
            $transactionStmt->bindValue(':solde', $nouveauSolde, PDO::PARAM_STR);
            
            $transactionStmt->execute();
            // Log de l'action pour audit (optionnel si la table logs_actions existe)
            try {
                $logQuery = "INSERT INTO logs_actions (user_id, action, table_name, record_id, details, date_action) 
                            VALUES (:user_id, 'DELETE', 'sorties_diverses', :record_id, :details, NOW())";
                
                $logStmt = $conn->prepare($logQuery);
                $logDetails = "Suppression sortie diverse N°" . $sortie['numero_sorties'] . 
                             " - Montant: " . number_format($sortie['montant'], 0, ',', ' ') . " FCFA" .
                             " - Motifs: " . substr($sortie['motifs'], 0, 100);
                
                $logStmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                $logStmt->bindValue(':record_id', $sortie_id, PDO::PARAM_INT);
                $logStmt->bindValue(':details', $logDetails, PDO::PARAM_STR);
                $logStmt->execute();
            } catch (PDOException $logError) {
                // Ignorer les erreurs de log si la table n'existe pas
                error_log("Erreur de log dans delete_sortie_diverse.php : " . $logError->getMessage());
            }

            // Valider la transaction
            $conn->commit();
            
            $_SESSION['success_message'] = "La sortie diverse N°" . $sortie['numero_sorties'] . " a été supprimée avec succès. Le solde de caisse a été remboursé de " . number_format($montantSortie, 0, ',', ' ') . " FCFA.";
        } else {
            throw new Exception("Erreur lors de la suppression de la sortie diverse.");
        }

    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
    } catch (PDOException $e) {
        // Annuler la transaction en cas d'erreur
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error_message'] = "Erreur de base de données lors de la suppression.";
        error_log("Erreur PDO dans delete_sortie_diverse.php : " . $e->getMessage());
    }
} else {
    $_SESSION['error_message'] = "Méthode de requête non autorisée ou paramètre manquant.";
}

// Redirection vers la page des sorties diverses
header('Location: sorties_diverses.php');
exit();
?>

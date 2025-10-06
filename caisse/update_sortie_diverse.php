<?php
session_start();
require_once '../inc/functions/connexion.php';

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Vous devez être connecté pour effectuer cette action.";
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_sortie'])) {
    try {
        // Récupération et validation des données
        $sortie_id = filter_input(INPUT_POST, 'sortie_id', FILTER_VALIDATE_INT);
        $montant = filter_input(INPUT_POST, 'montant', FILTER_VALIDATE_FLOAT);
        $motifs = trim($_POST['motifs']);

        // Validation des données
        if (!$sortie_id || $sortie_id <= 0) {
            throw new Exception("ID de sortie invalide.");
        }

        if (!$montant || $montant <= 0) {
            throw new Exception("Le montant doit être supérieur à 0.");
        }

        if (empty($motifs)) {
            throw new Exception("Les motifs de la sortie sont obligatoires.");
        }

        if (strlen($motifs) > 500) {
            throw new Exception("Les motifs ne peuvent pas dépasser 500 caractères.");
        }

        // Commencer une transaction pour assurer la cohérence
        $conn->beginTransaction();

        // Récupérer l'ancien montant de la sortie pour calculer la différence
        $checkQuery = "SELECT montant, numero_sorties FROM sorties_diverses WHERE id_sorties = :sortie_id";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindValue(':sortie_id', $sortie_id, PDO::PARAM_INT);
        $checkStmt->execute();

        $ancienneSortie = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if (!$ancienneSortie) {
            throw new Exception("La sortie diverse à modifier n'existe pas.");
        }

        $ancienMontant = floatval($ancienneSortie['montant']);
        $numeroSortie = $ancienneSortie['numero_sorties'];
        $differenceMonant = $montant - $ancienMontant;

        // Mise à jour de la sortie diverse
        $updateQuery = "UPDATE sorties_diverses 
                       SET montant = :montant, 
                           motifs = :motifs
                       WHERE id_sorties = :sortie_id";

        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bindValue(':montant', $montant, PDO::PARAM_STR);
        $updateStmt->bindValue(':motifs', $motifs, PDO::PARAM_STR);
        $updateStmt->bindValue(':sortie_id', $sortie_id, PDO::PARAM_INT);

        if ($updateStmt->execute()) {
            // Mettre à jour le solde de caisse si le montant a changé
            if ($differenceMonant != 0) {
                // Récupérer le solde actuel
                $soldeQuery = "SELECT COALESCE(MAX(solde), 0) as solde_actuel FROM transactions";
                $soldeStmt = $conn->query($soldeQuery);
                $soldeActuel = $soldeStmt->fetch(PDO::FETCH_ASSOC)['solde_actuel'];
                
                // Calculer le nouveau solde
                // Si la différence est positive (augmentation de sortie), le solde diminue
                // Si la différence est négative (diminution de sortie), le solde augmente
                $nouveauSolde = $soldeActuel - $differenceMonant;
                
                // Créer une transaction d'ajustement
                $sourceTransaction = "Ajustement sortie diverse - " . $numeroSortie;
                $motifsTransaction = "Modification sortie diverse: " . $motifs . " (Différence: " . number_format($differenceMonant, 0, ',', ' ') . " FCFA)";
                
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
                        :type_transaction,
                        :montant,
                        NOW(),
                        :motifs,
                        :source,
                        :id_utilisateur,
                        :solde
                    )
                ");
                
                // Déterminer le type de transaction selon la différence
                $typeTransaction = ($differenceMonant > 0) ? 'paiement' : 'approvisionnement';
                $montantTransaction = abs($differenceMonant);
                
                $transactionStmt->bindValue(':type_transaction', $typeTransaction, PDO::PARAM_STR);
                $transactionStmt->bindValue(':montant', $montantTransaction, PDO::PARAM_STR);
                $transactionStmt->bindValue(':motifs', $motifsTransaction, PDO::PARAM_STR);
                $transactionStmt->bindValue(':source', $sourceTransaction, PDO::PARAM_STR);
                $transactionStmt->bindValue(':id_utilisateur', $_SESSION['user_id'], PDO::PARAM_INT);
                $transactionStmt->bindValue(':solde', $nouveauSolde, PDO::PARAM_STR);
                
                $transactionStmt->execute();
            }

            // Log de l'action pour audit (optionnel si la table logs_actions existe)
            try {
                $logQuery = "INSERT INTO logs_actions (user_id, action, table_name, record_id, details, date_action) 
                            VALUES (:user_id, 'UPDATE', 'sorties_diverses', :record_id, :details, NOW())";
                
                $logStmt = $conn->prepare($logQuery);
                $logDetails = "Modification sortie diverse - Ancien: " . number_format($ancienMontant, 0, ',', ' ') . " FCFA - Nouveau: " . number_format($montant, 0, ',', ' ') . " FCFA - Différence: " . number_format($differenceMonant, 0, ',', ' ') . " FCFA";
                
                $logStmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                $logStmt->bindValue(':record_id', $sortie_id, PDO::PARAM_INT);
                $logStmt->bindValue(':details', $logDetails, PDO::PARAM_STR);
                $logStmt->execute();
            } catch (PDOException $logError) {
                // Ignorer les erreurs de log si la table n'existe pas
                error_log("Erreur de log dans update_sortie_diverse.php : " . $logError->getMessage());
            }

            // Valider la transaction
            $conn->commit();
            
            $messageSucces = "La sortie diverse a été modifiée avec succès.";
            if ($differenceMonant != 0) {
                $messageSucces .= " Le solde de caisse a été ajusté de " . number_format(abs($differenceMonant), 0, ',', ' ') . " FCFA.";
            }
            $_SESSION['success_message'] = $messageSucces;
        } else {
            throw new Exception("Erreur lors de la modification de la sortie diverse.");
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
        $_SESSION['error_message'] = "Erreur de base de données : " . $e->getMessage();
        error_log("Erreur PDO dans update_sortie_diverse.php : " . $e->getMessage());
    }
} else {
    $_SESSION['error_message'] = "Méthode de requête non autorisée.";
}

// Redirection vers la page des sorties diverses
header('Location: sorties_diverses.php');
exit();
?>

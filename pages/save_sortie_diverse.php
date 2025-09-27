<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/log_functions.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_sortie'])) {
    try {
        $conn->beginTransaction();
        writeLog("Début de l'enregistrement de la sortie diverse");

        // Validation des données
        if (!isset($_POST['montant']) || empty($_POST['montant'])) {
            throw new Exception("Le montant est requis");
        }
        
        if (!isset($_POST['motifs']) || empty(trim($_POST['motifs']))) {
            throw new Exception("Les motifs de la sortie sont requis");
        }
        
        // Nettoyer le montant des espaces et autres caractères non numériques
        $montant = preg_replace('/[^0-9]/', '', $_POST['montant']);
        $montant = floatval($montant);
        
        if ($montant <= 0) {
            throw new Exception("Le montant doit être supérieur à 0");
        }
        
        // Récupérer et nettoyer les motifs
        $motifs = trim($_POST['motifs']);

        // Vérifier que la table existe
        try {
            $checkTable = $conn->query("SHOW TABLES LIKE 'sorties_diverses'");
            if ($checkTable->rowCount() == 0) {
                throw new Exception("La table 'sorties_diverses' n'existe pas dans la base de données");
            }
            writeLog("Table sorties_diverses trouvée");
        } catch (Exception $e) {
            throw new Exception("Erreur de vérification de la table: " . $e->getMessage());
        }

        // Générer automatiquement le numéro de sortie
        $annee = date('Y');
        $mois = date('m');
        writeLog("Génération du numéro pour année: $annee, mois: $mois");
        
        // Récupérer le dernier numéro de sortie pour l'année/mois en cours
        $lastNumberQuery = "SELECT numero_sorties FROM sorties_diverses 
                           WHERE numero_sorties LIKE :pattern 
                           ORDER BY id_sorties DESC LIMIT 1";
        $lastNumberStmt = $conn->prepare($lastNumberQuery);
        $pattern = "SD-{$annee}-{$mois}-%";
        $lastNumberStmt->bindValue(':pattern', $pattern, PDO::PARAM_STR);
        $lastNumberStmt->execute();
        
        $lastNumber = $lastNumberStmt->fetchColumn();
        writeLog("Dernier numéro trouvé: " . ($lastNumber ?: 'aucun'));
        
        if ($lastNumber) {
            // Extraire le numéro séquentiel du dernier numéro
            $parts = explode('-', $lastNumber);
            $sequence = intval($parts[3]) + 1;
            writeLog("Séquence extraite: " . $parts[3] . ", nouvelle séquence: " . $sequence);
        } else {
            // Premier numéro de l'année/mois
            $sequence = 1;
            writeLog("Premier numéro du mois, séquence: 1");
        }
        
        // Générer le nouveau numéro avec format SD-YYYY-MM-XXX
        $numero_sorties = sprintf("SD-%s-%s-%03d", $annee, $mois, $sequence);

        // Debug logs
        writeLog("Numéro sortie généré automatiquement: " . $numero_sorties);
        writeLog("Montant reçu: " . $montant);
        writeLog("Motifs reçus: " . $motifs);

        // Créer la sortie diverse
        $stmt = $conn->prepare("
            INSERT INTO sorties_diverses (
                numero_sorties, 
                montant, 
                date_sortie, 
                motifs
            ) VALUES (
                :numero_sorties,
                :montant,
                NOW(),
                :motifs
            )
        ");
        
        $stmt->bindValue(':numero_sorties', $numero_sorties, PDO::PARAM_STR);
        $stmt->bindValue(':montant', $montant, PDO::PARAM_STR);
        $stmt->bindValue(':motifs', $motifs, PDO::PARAM_STR);
        
        $stmt->execute();
        
        $id_sortie = $conn->lastInsertId();
        writeLog("Sortie diverse créée #$id_sortie");

        // Optionnel: Créer aussi une transaction de paiement dans la table transactions
        // pour maintenir la cohérence du solde de caisse
        
        // D'abord récupérer le solde actuel
        $soldeQuery = "SELECT COALESCE(MAX(solde), 0) as solde_actuel FROM transactions";
        $soldeStmt = $conn->query($soldeQuery);
        $soldeActuel = $soldeStmt->fetch(PDO::FETCH_ASSOC)['solde_actuel'];
        $nouveauSolde = $soldeActuel - $montant;
        
        $sourceTransaction = "Sortie diverse - " . $numero_sorties;
        $motifsTransaction = "Sortie diverse: " . $motifs;
        
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
                'paiement',
                :montant,
                NOW(),
                :motifs,
                :source,
                :id_utilisateur,
                :solde
            )
        ");
        
        $transactionStmt->bindValue(':montant', $montant, PDO::PARAM_STR);
        $transactionStmt->bindValue(':motifs', $motifsTransaction, PDO::PARAM_STR);
        $transactionStmt->bindValue(':source', $sourceTransaction, PDO::PARAM_STR);
        $transactionStmt->bindValue(':id_utilisateur', $_SESSION['user_id'], PDO::PARAM_INT);
        $transactionStmt->bindValue(':solde', $nouveauSolde, PDO::PARAM_STR);
        
        $transactionStmt->execute();
        writeLog("Transaction de paiement créée pour la sortie diverse");

        $conn->commit();
        $_SESSION['success_message'] = "Sortie diverse " . $numero_sorties . " de " . number_format($montant, 0, ',', ' ') . " FCFA enregistrée avec succès.";
        
        header("Location: sorties_diverses.php");
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        writeLog("ERREUR: " . $e->getMessage());
        writeLog("Trace: " . $e->getTraceAsString());
        $_SESSION['error_message'] = "Erreur lors de l'enregistrement : " . $e->getMessage();
        header("Location: sorties_diverses.php");
        exit;
    }
}

$_SESSION['error_message'] = "Erreur : requête invalide";
header("Location: sorties_diverses.php");
exit;
?>

<?php
session_start();

// Connexion à la base de données (à adapter avec vos informations)
require_once '../inc/functions/connexion.php';

// Vérifier si c'est une validation en masse
$is_mass_validation = isset($_POST['is_mass_validation']) && $_POST['is_mass_validation'] == true;

if ($is_mass_validation) {
    // Validation en masse
    if (!isset($_POST['ticket_ids']) || !isset($_POST['prix_unitaire'])) {
        $_SESSION['delete_pop'] = true;
        header('Location: tickets_attente.php');
        exit(0);
    }
    
    $ticket_ids = $_POST['ticket_ids'];
    $prix_unitaire = (float)$_POST['prix_unitaire'];
    
    // Validation du prix unitaire
    if ($prix_unitaire <= 0) {
        $_SESSION['delete_pop'] = true;
        header('Location: tickets_attente.php');
        exit(0);
    }
    
    // Validation en masse
    $success_count = 0;
    foreach ($ticket_ids as $ticket_id) {
        $id_ticket = (int)$ticket_id;
        
        $sql = "UPDATE tickets 
                SET date_validation_boss = NOW(),
                    prix_unitaire = ?,
                    montant_paie = ? * poids
                WHERE id_ticket = ?";
        
        $stmt = $conn->prepare($sql);
        if ($stmt->execute([$prix_unitaire, $prix_unitaire, $id_ticket])) {
            $success_count++;
        }
    }
    
    if ($success_count > 0) {
        $_SESSION['popup'] = true;
        header('Location: tickets_attente.php');
        exit(0);
    } else {
        $_SESSION['delete_pop'] = true;
        header('Location: tickets_attente.php');
        exit(0);
    }
    
} else {
    // Validation individuelle
    if (!isset($_POST['id_ticket']) || !isset($_POST['prix_unitaire'])) {
        $_SESSION['delete_pop'] = true;
        header('Location: tickets_attente.php');
        exit(0);
    }
    
    $id_ticket = (int)$_POST['id_ticket'];
    $prix_unitaire = (float)$_POST['prix_unitaire'];
    
    // Validation du prix unitaire
    if ($prix_unitaire <= 0) {
        $_SESSION['delete_pop'] = true;
        header('Location: tickets_attente.php');
        exit(0);
    }
    // Validation individuelle - traitement
    $sql = "UPDATE tickets 
                SET date_validation_boss = NOW(),
                    prix_unitaire = ?,
                    montant_paie = ? * poids
                WHERE id_ticket = ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$prix_unitaire, $prix_unitaire, $id_ticket])) {
        $_SESSION['popup'] = true;
        header('Location: tickets_attente.php');
        exit(0);
    } else {
        $_SESSION['delete_pop'] = true;
        header('Location: tickets_attente.php');
        exit(0);
    }
}

$conn = null; // Fermer la connexion    

?>
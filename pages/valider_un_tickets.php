<?php

// Connexion à la base de données (à adapter avec vos informations)
require_once '../inc/functions/connexion.php';

$id_ticket = $_POST['id_ticket'];
$prix_unitaire = $_POST['prix_unitaire'];




$sql = "UPDATE tickets 
            SET date_validation_boss = NOW(),
                prix_unitaire = :prix_unitaire,
                montant_paie = :prix_unitaire2 * poids
            WHERE id_ticket = :id_ticket";

$stmt = $conn->prepare($sql);

if ($stmt->execute([
    ':prix_unitaire' => $prix_unitaire,
    ':prix_unitaire2' => $prix_unitaire,
    ':id_ticket' => $id_ticket
])) {
    $_SESSION['popup'] = true;
    header('Location: tickets_attente.php');
    exit(0);
} else {
    $_SESSION['delete_pop'] = true;
   header('Location: tickets_attente.php');
   exit(0);
}               
$conn = null; // Fermer la connexion    


?>
<?php
// Connexion à la base de données (à adapter avec vos informations)
require_once '../../inc/functions/connexion.php'; 

require_once '../../inc/functions/verification_password.php';  

//session_start(); 

// Récupération des données soumises via le formulaire
$id_utilisateur=$_POST['id'];
$nom = $_POST['nom'];
$prenoms = $_POST['prenoms'];
$contact = $_POST['contact'];

if (!isPhoneNumberValid($contact)) {
        $_SESSION['error'] = "Numéro de téléphone invalide";
        // Déterminer le chemin de redirection selon le referer
        $redirect_path = (strpos($_SERVER['HTTP_REFERER'], '/operateurs/') !== false) 
            ? '../operateurs/utilisateurs_profile.php?id=' . $id_utilisateur
            : '../utilisateurs_profile.php?id=' . $id_utilisateur;
        header('Location: ' . $redirect_path);
        exit(0);
} else {
        $sql = "UPDATE utilisateurs
        SET nom = :nom, prenoms = :prenoms, contact = :contact
        WHERE id = :id_utilisateur";

// Préparation de la requête
$requete = $conn->prepare($sql);

// Exécution de la requête avec les nouvelles valeurs
$query_execute = $requete->execute(array(
    ':id_utilisateur' => $id_utilisateur,
    ':nom' => $nom,
    ':prenoms' => $prenoms,
    ':contact' => $contact
));

  
//var_dump($query_exec/die();
if($query_execute)
        {
            $_SESSION['success'] = "Profil mis à jour avec succès";
            // Déterminer le chemin de redirection selon le referer
            $redirect_path = (strpos($_SERVER['HTTP_REFERER'], '/operateurs/') !== false) 
                ? '../operateurs/utilisateurs_profile.php?id=' . $id_utilisateur
                : '../utilisateurs_profile.php?id=' . $id_utilisateur;
            header('Location: ' . $redirect_path);
            exit(0);
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour du profil";
            // Déterminer le chemin de redirection selon le referer
            $redirect_path = (strpos($_SERVER['HTTP_REFERER'], '/operateurs/') !== false) 
                ? '../operateurs/utilisateurs_profile.php?id=' . $id_utilisateur
                : '../utilisateurs_profile.php?id=' . $id_utilisateur;
            header('Location: ' . $redirect_path);
            exit(0);
        }

}



?>

<?php
// Connexion à la base de données (à adapter avec vos informations)
require_once '../../inc/functions/connexion.php'; 
require_once '../../inc/functions/verification_password.php';


//session_start();

// Récupération des données soumises via le formulaire
$id_utilisateur=$_POST['id'];
$old_password = $_POST['old_password'];
$hased_old_password=hash('sha256',$old_password);
$new_password = $_POST['new_password'];
$check_password = $_POST['check_password'];
$hasedpassword=hash('sha256',$new_password);

if ($new_password!==$check_password){
    $_SESSION['error'] = "Les mots de passe ne correspondent pas";
    // Déterminer le chemin de redirection selon le referer
    $redirect_path = (strpos($_SERVER['HTTP_REFERER'], '/operateurs/') !== false) 
        ? '../operateurs/utilisateurs_profile.php?id=' . $id_utilisateur
        : '../utilisateurs_profile.php?id=' . $id_utilisateur;
    header('Location: ' . $redirect_path);
    exit(0);
} 
elseif (!isPasswordComplex($new_password, $check_password)) {
    $_SESSION['error'] = "Le mot de passe ne respecte pas les critères de complexité";
    // Déterminer le chemin de redirection selon le referer
    $redirect_path = (strpos($_SERVER['HTTP_REFERER'], '/operateurs/') !== false) 
        ? '../operateurs/utilisateurs_profile.php?id=' . $id_utilisateur
        : '../utilisateurs_profile.php?id=' . $id_utilisateur;
    header('Location: ' . $redirect_path);
    exit(0);
} else {

    $userVerification = $conn->prepare("SELECT password FROM utilisateurs WHERE id = :id AND password = :old_password");
$userVerification->bindParam(':id', $id_utilisateur);
$userVerification->bindParam(':old_password', $hased_old_password);
$userVerification->execute();

if ($userVerification->rowCount() > 0) {
    $requeteUpdate = $conn->prepare("UPDATE utilisateurs SET password = :new_password WHERE id = :id");
    $requeteUpdate->bindParam(':new_password', $hasedpassword);
    $requeteUpdate->bindParam(':id', $id_utilisateur);
    $requeteUpdate->execute();

    if($requeteUpdate)
        {
            // Déterminer le chemin de redirection selon le referer
            $redirect_path = (strpos($_SERVER['HTTP_REFERER'], '/operateurs/') !== false) 
                ? '../operateurs/utilisateurs_profile.php?id=' . $id_utilisateur . '&success=password_changed'
                : '../utilisateurs_profile.php?id=' . $id_utilisateur . '&success=password_changed';
            header('Location: ' . $redirect_path);
            exit(0);
        }  else
        {
            $_SESSION['error'] = "Erreur lors de la mise à jour du mot de passe";
            // Déterminer le chemin de redirection selon le referer
            $redirect_path = (strpos($_SERVER['HTTP_REFERER'], '/operateurs/') !== false) 
                ? '../operateurs/utilisateurs_profile.php?id=' . $id_utilisateur
                : '../utilisateurs_profile.php?id=' . $id_utilisateur;
            header('Location: ' . $redirect_path);
            exit(0);
        }
} else {
    // Ancien mot de passe incorrect
    $_SESSION['error'] = "L'ancien mot de passe est incorrect";
    // Déterminer le chemin de redirection selon le referer
    $redirect_path = (strpos($_SERVER['HTTP_REFERER'], '/operateurs/') !== false) 
        ? '../operateurs/utilisateurs_profile.php?id=' . $id_utilisateur
        : '../utilisateurs_profile.php?id=' . $id_utilisateur;
    header('Location: ' . $redirect_path);
    exit(0);
}

}



/*$sql = "UPDATE utilisateurs
        SET nom = :nom, prenoms = :prenoms, contact = :contact
        WHERE id = :id_user";

// Préparation de la requête
$requete = $conn->prepare($sql);

// Exécution de la requête avec les nouvelles valeurs
$query_execute = $requete->execute(array(
    ':id_user' => $id_user,
    ':nom' => $nom,
    ':prenoms' => $prenoms,
    ':contact' => $contact
));

  
//var_dump($query_exec/die();
if($query_execute)
        {
           // $_SESSION['message'] = "Insertion reussie";
            $_SESSION['popup'] = true;
	       header('Location: ../clients_dashboard.php');
	       exit(0);

            // Redirigez l'utilisateur vers la page d'accueil
            //header("Location: home1.php");
           // exit();
        }*/

?>
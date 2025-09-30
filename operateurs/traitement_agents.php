<?php
require_once '../inc/functions/connexion.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajout d'un agent
    if (isset($_POST['add_agent'])) {
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $contact = $_POST['contact'];
        $id_chef = $_POST['id_chef'];
        $cree_par = $_SESSION['user_id']; // ID de l'utilisateur connecté

        try {
            $stmt = $conn->prepare("INSERT INTO agents (nom, prenom, contact, id_chef, cree_par, date_ajout) VALUES (?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([$nom, $prenom, $contact, $id_chef, $cree_par]);

            if ($result) {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Agent ajouté avec succès !";
                $_SESSION['status'] = "success";
            } else {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Erreur lors de l'ajout de l'agent";
                $_SESSION['status'] = "error";
            }
        } catch(PDOException $e) {
            $_SESSION['popup'] = true;
            $_SESSION['message'] = "Erreur : " . $e->getMessage();
            $_SESSION['status'] = "error";
        }
        
        header('Location: agents.php');
        exit;
    }

    // Modification d'un agent
    if (isset($_POST['update_agent'])) {
        $id_agent = $_POST['id_agent'];
        $nom = $_POST['nom'];
        $prenoms = $_POST['prenoms'];
        $contact = $_POST['contact'];

        try {
            $stmt = $conn->prepare("UPDATE agents SET nom = ?, prenom = ?, contact = ?, date_modification = NOW() WHERE id_agent = ?");
            $result = $stmt->execute([$nom, $prenoms, $contact, $id_agent]);

            if ($result) {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Agent modifié avec succès !";
                $_SESSION['status'] = "success";
            } else {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Erreur lors de la modification de l'agent";
                $_SESSION['status'] = "error";
            }
        } catch(PDOException $e) {
            $_SESSION['popup'] = true;
            $_SESSION['message'] = "Erreur : " . $e->getMessage();
            $_SESSION['status'] = "error";
        }

        header('Location: agents.php');
        exit;
    }

    // Changement de chef d'équipe
    if (isset($_POST['change_chef'])) {
        $id_agent = $_POST['id_agent'];
        $nouveau_chef = $_POST['nouveau_chef'];

        try {
            $stmt = $conn->prepare("UPDATE agents SET id_chef = ?, date_modification = NOW() WHERE id_agent = ?");
            $result = $stmt->execute([$nouveau_chef, $id_agent]);

            if ($result) {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Chef d'équipe modifié avec succès !";
                $_SESSION['status'] = "success";
            } else {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Erreur lors du changement de chef d'équipe";
                $_SESSION['status'] = "error";
            }
        } catch(PDOException $e) {
            $_SESSION['popup'] = true;
            $_SESSION['message'] = "Erreur : " . $e->getMessage();
            $_SESSION['status'] = "error";
        }

        header('Location: agents.php');
        exit;
    }

    // Suppression d'un agent (méthode POST)
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id_agent'])) {
        $id_agent = $_POST['id_agent'];
        
        // Debug
        file_put_contents('../debug_delete.txt', date('Y-m-d H:i:s') . " - Suppression POST ID: " . $id_agent . "\n", FILE_APPEND);

        try {
            // Vérifier si l'agent existe
            $stmt = $conn->prepare("SELECT * FROM agents WHERE id_agent = ?");
            $stmt->execute([$id_agent]);
            $agent = $stmt->fetch();

            if (!$agent) {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Agent non trouvé !";
                $_SESSION['status'] = "error";
            } else {
                // Suppression logique - marquer comme supprimé au lieu de supprimer physiquement
                $stmt = $conn->prepare("UPDATE agents SET date_suppression = NOW() WHERE id_agent = ?");
                $result = $stmt->execute([$id_agent]);
                
                // Debug détaillé
                $affected_rows = $stmt->rowCount();
                file_put_contents('../debug_delete.txt', "  - Résultat requête UPDATE: " . ($result ? 'true' : 'false') . "\n", FILE_APPEND);
                file_put_contents('../debug_delete.txt', "  - Lignes affectées: " . $affected_rows . "\n", FILE_APPEND);

                if ($result && $affected_rows > 0) {
                    $_SESSION['popup'] = true;
                    $_SESSION['message'] = "Agent supprimé avec succès !";
                    $_SESSION['status'] = "success";
                } else {
                    $_SESSION['popup'] = true;
                    $_SESSION['message'] = "Erreur lors de la suppression de l'agent (aucune ligne affectée)";
                    $_SESSION['status'] = "error";
                }
            }
        } catch(PDOException $e) {
            $_SESSION['popup'] = true;
            $_SESSION['message'] = "Erreur : " . $e->getMessage();
            $_SESSION['status'] = "error";
        }

        header('Location: agents.php');
        exit;
    }
}

// Suppression d'un agent (méthode GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_agent = $_GET['id'];
    
    // Debug - créer un fichier temporaire pour voir si on arrive ici
    file_put_contents('../debug_delete.txt', date('Y-m-d H:i:s') . " - Tentative suppression ID: " . $id_agent . "\n", FILE_APPEND);

    try {
        // Vérifier si l'agent existe
        $stmt = $conn->prepare("SELECT * FROM agents WHERE id_agent = ?");
        $stmt->execute([$id_agent]);
        $agent = $stmt->fetch();

        if (!$agent) {
            $_SESSION['popup'] = true;
            $_SESSION['message'] = "Agent non trouvé !";
            $_SESSION['status'] = "error";
        } else {
            // Supprimer l'agent
            $stmt = $conn->prepare("DELETE FROM agents WHERE id_agent = ?");
            $result = $stmt->execute([$id_agent]);

            if ($result) {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Agent supprimé avec succès !";
                $_SESSION['status'] = "success";
            } else {
                $_SESSION['popup'] = true;
                $_SESSION['message'] = "Erreur lors de la suppression de l'agent";
                $_SESSION['status'] = "error";
            }
        }
    } catch(PDOException $e) {
        $_SESSION['popup'] = true;
        $_SESSION['message'] = "Erreur : " . $e->getMessage();
        $_SESSION['status'] = "error";
    }

    header('Location: agents.php');
    exit;
}

// Redirection par défaut
header('Location: agents.php');
exit;
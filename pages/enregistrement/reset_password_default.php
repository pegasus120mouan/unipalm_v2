<?php
session_start();
require_once '../../inc/functions/connexion.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Get user ID from POST data
$id_utilisateur = $_POST['id'] ?? null;

if (!$id_utilisateur) {
    echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant']);
    exit;
}

try {
    // Default password
    $default_password = 'Unipalm@@2020';
    $hashed_password = hash('sha256', $default_password);
    
    // Update user password
    $sql = "UPDATE utilisateurs SET password = :password WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
    $stmt->bindParam(':id', $id_utilisateur, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Mot de passe réinitialisé avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Erreur lors de la mise à jour du mot de passe'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Erreur lors de la réinitialisation du mot de passe: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erreur générale lors de la réinitialisation: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur interne: ' . $e->getMessage()
    ]);
}
?>

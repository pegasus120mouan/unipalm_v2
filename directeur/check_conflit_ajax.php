<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_prix_unitaires.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$id_usine = $_POST['id_usine'] ?? null;
$date_debut = $_POST['date_debut'] ?? null;
$date_fin = $_POST['date_fin'] ?? null;
$exclude_id = $_POST['exclude_id'] ?? null; // Pour les modifications

if (!$id_usine || !$date_debut) {
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

try {
    // Récupérer le nom de l'usine pour debug
    $sql_usine = "SELECT nom_usine FROM usines WHERE id_usine = :id_usine";
    $stmt_usine = $conn->prepare($sql_usine);
    $stmt_usine->execute([':id_usine' => $id_usine]);
    $usine_info = $stmt_usine->fetch(PDO::FETCH_ASSOC);
    $nom_usine = $usine_info ? $usine_info['nom_usine'] : 'Usine inconnue';
    
    // Debug: Vérifier tous les prix existants pour cette usine
    $sql_debug = "SELECT id, prix, date_debut, date_fin FROM prix_unitaires WHERE id_usine = :id_usine ORDER BY date_debut";
    $stmt_debug = $conn->prepare($sql_debug);
    $stmt_debug->execute([':id_usine' => $id_usine]);
    $prix_existants = $stmt_debug->fetchAll(PDO::FETCH_ASSOC);
    
    // Vérifier les conflits
    $overlaps = checkPeriodOverlap($conn, $id_usine, $date_debut, $date_fin, $exclude_id);
    
    if ($overlaps && count($overlaps) > 0) {
        echo json_encode([
            'conflit' => true,
            'message' => "⚠️ CONFLIT DÉTECTÉ pour l'usine {$nom_usine}",
            'details' => "Un prix unitaire existe déjà pour cette période :",
            'conflits' => $overlaps,
            'usine' => $nom_usine,
            'debug' => [
                'date_debut_recu' => $date_debut,
                'date_fin_recu' => $date_fin,
                'prix_existants' => $prix_existants
            ]
        ]);
    } else {
        echo json_encode([
            'conflit' => false,
            'message' => '✅ Aucun conflit détecté',
            'debug' => [
                'date_debut_recu' => $date_debut,
                'date_fin_recu' => $date_fin,
                'prix_existants' => $prix_existants,
                'usine' => $nom_usine
            ]
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
}
?>

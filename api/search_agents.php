<?php
header('Content-Type: application/json');
require_once '../inc/functions/connexion.php';

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

try {
    // Rechercher les agents dont le nom ou prénom contient la requête
    $sql = "SELECT id_agent, 
                   CONCAT(COALESCE(nom, ''), ' ', COALESCE(prenom, '')) AS nom_complet,
                   nom,
                   prenom
            FROM agents 
            WHERE (UPPER(nom) LIKE UPPER(:query1) OR UPPER(prenom) LIKE UPPER(:query2) 
                   OR UPPER(CONCAT(COALESCE(nom, ''), ' ', COALESCE(prenom, ''))) LIKE UPPER(:query3))
            ORDER BY nom, prenom
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $searchTerm = '%' . $query . '%';
    $stmt->bindParam(':query1', $searchTerm);
    $stmt->bindParam(':query2', $searchTerm);
    $stmt->bindParam(':query3', $searchTerm);
    $stmt->execute();
    
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: ajouter des informations de debug si aucun résultat
    if (empty($agents) && isset($_GET['debug'])) {
        // Test direct pour voir si KINDO existe
        $debugSql = "SELECT id_agent, nom, prenom FROM agents WHERE nom = 'KINDO' OR nom LIKE '%KINDO%'";
        $debugStmt = $conn->prepare($debugSql);
        $debugStmt->execute();
        $debugResults = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'debug' => true,
            'query' => $query,
            'searchTerm' => $searchTerm,
            'sql' => $sql,
            'directSearch' => $debugResults,
            'results' => []
        ]);
        exit();
    }
    
    // Formater les résultats pour l'autocomplétion
    $results = [];
    foreach ($agents as $agent) {
        $results[] = [
            'id' => $agent['id_agent'],
            'text' => trim($agent['nom_complet']),
            'nom' => $agent['nom'],
            'prenom' => $agent['prenom']
        ];
    }
    
    echo json_encode($results);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données']);
}
?>

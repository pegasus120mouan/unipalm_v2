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
    // D'abord, essayer de détecter la structure de la table usines
    $tables_to_try = [
        // Première tentative avec la structure standard
        [
            'table' => 'usines',
            'id' => 'id_usine',
            'nom' => 'nom_usine',
            'localisation' => 'localisation'
        ],
        // Deuxième tentative avec une structure alternative
        [
            'table' => 'usines',
            'id' => 'id_usine',
            'nom' => 'nom_usine',
            'localisation' => null
        ],
        // Troisième tentative - structure vue dans l'image
        [
            'table' => 'usines',
            'id' => 'id_usine',
            'nom' => 'nom_usine',
            'localisation' => 'nom_usine' // Utiliser nom_usine comme localisation aussi
        ],
        // Quatrième tentative avec une autre structure possible
        [
            'table' => 'usines',
            'id' => 'id',
            'nom' => 'nom',
            'localisation' => 'ville'
        ]
    ];
    
    $results = [];
    $found = false;
    
    foreach ($tables_to_try as $structure) {
        try {
            $sql = "SELECT " . $structure['id'] . " as id_usine, 
                           " . $structure['nom'] . " as nom_usine";
            
            if ($structure['localisation']) {
                $sql .= ", " . $structure['localisation'] . " as localisation";
            } else {
                $sql .= ", '' as localisation";
            }
            
            $sql .= " FROM " . $structure['table'] . " 
                     WHERE UPPER(" . $structure['nom'] . ") LIKE UPPER(:query1)";
            
            if ($structure['localisation']) {
                $sql .= " OR UPPER(" . $structure['localisation'] . ") LIKE UPPER(:query2)";
            }
            
            $sql .= " ORDER BY " . $structure['nom'] . " LIMIT 10";
            
            $stmt = $conn->prepare($sql);
            $searchTerm = '%' . $query . '%';
            $stmt->bindParam(':query1', $searchTerm);
            
            if ($structure['localisation']) {
                $stmt->bindParam(':query2', $searchTerm);
            }
            
            $stmt->execute();
            $usines = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($usines) {
                $found = true;
                
                // Formater les résultats pour l'autocomplétion
                foreach ($usines as $usine) {
                    $displayText = $usine['nom_usine'];
                    if (!empty($usine['localisation'])) {
                        $displayText .= ' (' . $usine['localisation'] . ')';
                    }
                    
                    $results[] = [
                        'id' => $usine['id_usine'],
                        'text' => $displayText,
                        'nom_usine' => $usine['nom_usine'],
                        'localisation' => $usine['localisation']
                    ];
                }
                break;
            }
        } catch (PDOException $e) {
            // Continuer avec la structure suivante
            continue;
        }
    }
    
    if (!$found) {
        // Si aucune structure ne fonctionne, essayer de lister les tables
        $stmt = $conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode([
            'debug' => true,
            'error' => 'Structure de table non trouvée',
            'available_tables' => $tables,
            'query' => $query
        ]);
        exit();
    }
    
    echo json_encode($results);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données']);
}
?>

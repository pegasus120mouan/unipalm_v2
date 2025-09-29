<?php
require_once dirname(__FILE__) . '/../connexion.php';

// Fonction pour générer automatiquement un code pont professionnel
function generateCodePont($conn) {
    try {
        // Récupérer le dernier numéro utilisé avec le nouveau format
        $sql = "SELECT code_pont FROM pont_bascule WHERE code_pont REGEXP '^UNIPALM-PB-[0-9]{4}-[A-Z]{2}$' ORDER BY id_pont DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Extraire le numéro du dernier code (ex: UNIPALM-PB-0001-CI -> 0001)
            $lastCode = $result['code_pont'];
            $parts = explode('-', $lastCode);
            $lastNumber = intval($parts[2]); // Récupère le numéro séquentiel
            $newNumber = $lastNumber + 1;
        } else {
            // Premier pont-bascule
            $newNumber = 1;
        }
        
        // Générer le code professionnel
        $year = date('Y');
        $sequentialNumber = str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        $countryCode = 'CI'; // Côte d'Ivoire
        
        // Format: UNIPALM-PB-XXXX-CI (ex: UNIPALM-PB-0001-CI)
        return "UNIPALM-PB-{$sequentialNumber}-{$countryCode}";
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la génération du code pont: " . $e->getMessage());
        // En cas d'erreur, générer un code avec timestamp
        $timestamp = date('YmdHis');
        return "UNIPALM-PB-{$timestamp}-CI";
    }
}

// Fonction pour créer un nouveau pont-bascule (code généré automatiquement)
function createPontBascule($conn, $nom_pont, $latitude, $longitude, $gerant, $cooperatif = null, $statut = 'Actif') {
    try {
        // Générer automatiquement le code pont
        $code_pont = generateCodePont($conn);
        
        $sql = "INSERT INTO pont_bascule (code_pont, nom_pont, latitude, longitude, gerant, cooperatif, statut) VALUES (:code_pont, :nom_pont, :latitude, :longitude, :gerant, :cooperatif, :statut)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':code_pont', $code_pont);
        $stmt->bindParam(':nom_pont', $nom_pont);
        $stmt->bindParam(':latitude', $latitude);
        $stmt->bindParam(':longitude', $longitude);
        $stmt->bindParam(':gerant', $gerant);
        $stmt->bindParam(':cooperatif', $cooperatif);
        $stmt->bindParam(':statut', $statut);
        
        if ($stmt->execute()) {
            return $code_pont; // Retourner le code généré pour affichage
        }
        return false;
    } catch (PDOException $e) {
        error_log("Erreur lors de la création du pont-bascule: " . $e->getMessage());
        return false;
    }
}

// Fonction pour mettre à jour un pont-bascule
function updatePontBascule($conn, $id_pont, $code_pont, $nom_pont, $latitude, $longitude, $gerant, $cooperatif = null, $statut = 'Actif') {
    try {
        $sql = "UPDATE pont_bascule SET code_pont = :code_pont, nom_pont = :nom_pont, latitude = :latitude, longitude = :longitude, gerant = :gerant, cooperatif = :cooperatif, statut = :statut WHERE id_pont = :id_pont";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id_pont', $id_pont, PDO::PARAM_INT);
        $stmt->bindParam(':code_pont', $code_pont);
        $stmt->bindParam(':nom_pont', $nom_pont);
        $stmt->bindParam(':latitude', $latitude);
        $stmt->bindParam(':longitude', $longitude);
        $stmt->bindParam(':gerant', $gerant);
        $stmt->bindParam(':cooperatif', $cooperatif);
        $stmt->bindParam(':statut', $statut);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erreur lors de la mise à jour du pont-bascule: " . $e->getMessage());
        return false;
    }
}

// Fonction pour supprimer un pont-bascule
function deletePontBascule($conn, $id_pont) {
    try {
        $sql = "DELETE FROM pont_bascule WHERE id_pont = :id_pont";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id_pont', $id_pont, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erreur lors de la suppression du pont-bascule: " . $e->getMessage());
        return false;
    }
}

// Fonction pour récupérer un pont-bascule par son ID
function getPontBasculeById($conn, $id_pont) {
    try {
        $sql = "SELECT * FROM pont_bascule WHERE id_pont = :id_pont";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id_pont', $id_pont, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération du pont-bascule: " . $e->getMessage());
        return false;
    }
}

// Fonction pour récupérer tous les ponts-bascules
function getAllPontsBascules($conn) {
    try {
        $sql = "SELECT * FROM pont_bascule ORDER BY code_pont ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des ponts-bascules: " . $e->getMessage());
        return false;
    }
}

// Fonction pour récupérer un pont-bascule par son code
function getPontBasculeByCode($conn, $code_pont) {
    try {
        $sql = "SELECT * FROM pont_bascule WHERE code_pont = :code_pont";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':code_pont', $code_pont);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération du pont-bascule par code: " . $e->getMessage());
        return false;
    }
}

// Fonction pour vérifier si un code pont existe déjà
function codePontExists($conn, $code_pont, $exclude_id = null) {
    try {
        $sql = "SELECT COUNT(*) as count FROM pont_bascule WHERE code_pont = :code_pont";
        if ($exclude_id) {
            $sql .= " AND id_pont != :exclude_id";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':code_pont', $code_pont);
        if ($exclude_id) {
            $stmt->bindParam(':exclude_id', $exclude_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    } catch (PDOException $e) {
        error_log("Erreur lors de la vérification du code pont: " . $e->getMessage());
        return false;
    }
}

// Fonction pour récupérer les ponts-bascules par coopérative
function getPontsBasculesByCooperative($conn, $cooperatif) {
    try {
        $sql = "SELECT * FROM pont_bascule WHERE cooperatif = :cooperatif ORDER BY code_pont ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':cooperatif', $cooperatif);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des ponts-bascules par coopérative: " . $e->getMessage());
        return false;
    }
}

// Fonction pour récupérer les statistiques des ponts-bascules
function getPontsBasculeStats($conn) {
    try {
        $sql = "SELECT 
                    COUNT(*) as total_ponts,
                    COUNT(CASE WHEN cooperatif IS NOT NULL AND cooperatif != '' THEN 1 END) as ponts_avec_cooperative,
                    COUNT(DISTINCT cooperatif) as total_cooperatives
                FROM pont_bascule";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques: " . $e->getMessage());
        return false;
    }
}
?>

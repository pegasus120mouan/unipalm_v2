<?php
require_once 'inc/functions/connexion.php';

echo "<h2>Test d'ajout d'agent</h2>";

// Démarrer la session pour simuler un utilisateur connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // ID utilisateur de test
    echo "<p>Session utilisateur créée avec ID: 1</p>";
}

try {
    // 1. Vérifier la structure de la table
    echo "<h3>1. Structure de la table agents:</h3>";
    $stmt = $conn->prepare("SHOW CREATE TABLE agents");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<pre>" . htmlspecialchars($result['Create Table']) . "</pre>";
    
    // 2. Vérifier les chefs d'équipe disponibles
    echo "<h3>2. Chefs d'équipe disponibles:</h3>";
    $stmt = $conn->prepare("SELECT id_chef, nom, prenoms FROM chef_equipe LIMIT 5");
    $stmt->execute();
    $chefs = $stmt->fetchAll();
    
    if (empty($chefs)) {
        echo "<p style='color: red;'>❌ Aucun chef d'équipe trouvé ! Il faut d'abord créer des chefs d'équipe.</p>";
    } else {
        echo "<ul>";
        foreach ($chefs as $chef) {
            echo "<li>ID: {$chef['id_chef']} - {$chef['nom']} {$chef['prenoms']}</li>";
        }
        echo "</ul>";
        
        // 3. Test d'insertion
        if (isset($_POST['test_insert'])) {
            echo "<h3>3. Test d'insertion:</h3>";
            
            $nom = "Agent";
            $prenom = "Test";
            $contact = "0123456789";
            $id_chef = $chefs[0]['id_chef']; // Premier chef disponible
            $cree_par = $_SESSION['user_id'];
            
            echo "<p>Tentative d'insertion avec:</p>";
            echo "<ul>";
            echo "<li>Nom: $nom</li>";
            echo "<li>Prénom: $prenom</li>";
            echo "<li>Contact: $contact</li>";
            echo "<li>ID Chef: $id_chef</li>";
            echo "<li>Créé par: $cree_par</li>";
            echo "</ul>";
            
            try {
                $stmt = $conn->prepare("INSERT INTO agents (nom, prenom, contact, id_chef, cree_par, date_ajout) VALUES (?, ?, ?, ?, ?, NOW())");
                $result = $stmt->execute([$nom, $prenom, $contact, $id_chef, $cree_par]);
                
                if ($result) {
                    $last_id = $conn->lastInsertId();
                    echo "<p style='color: green;'>✅ Agent ajouté avec succès ! ID: $last_id</p>";
                    
                    // Vérifier l'insertion
                    $stmt = $conn->prepare("SELECT * FROM agents WHERE id_agent = ?");
                    $stmt->execute([$last_id]);
                    $agent = $stmt->fetch();
                    
                    echo "<h4>Agent créé:</h4>";
                    echo "<pre>" . print_r($agent, true) . "</pre>";
                } else {
                    echo "<p style='color: red;'>❌ Échec de l'insertion</p>";
                }
            } catch (PDOException $e) {
                echo "<p style='color: red;'>❌ Erreur PDO: " . $e->getMessage() . "</p>";
                echo "<p>Code d'erreur: " . $e->getCode() . "</p>";
            }
        }
        
        echo "<form method='post'>";
        echo "<button type='submit' name='test_insert' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px;'>Tester l'insertion d'un agent</button>";
        echo "</form>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur de connexion: " . $e->getMessage() . "</p>";
}

// 4. Vérifier si la table a une clé primaire auto-incrémentée
echo "<h3>4. Informations sur la clé primaire:</h3>";
try {
    $stmt = $conn->prepare("SELECT COLUMN_NAME, COLUMN_KEY, EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'agents' AND COLUMN_NAME = 'id_agent'");
    $stmt->execute([DB_NAME]);
    $key_info = $stmt->fetch();
    
    if ($key_info) {
        echo "<p>Colonne: {$key_info['COLUMN_NAME']}</p>";
        echo "<p>Clé: {$key_info['COLUMN_KEY']}</p>";
        echo "<p>Extra: {$key_info['EXTRA']}</p>";
        
        if ($key_info['COLUMN_KEY'] !== 'PRI') {
            echo "<p style='color: red;'>❌ La colonne id_agent n'est pas définie comme clé primaire !</p>";
        }
        
        if (strpos($key_info['EXTRA'], 'auto_increment') === false) {
            echo "<p style='color: red;'>❌ La colonne id_agent n'a pas l'attribut AUTO_INCREMENT !</p>";
            echo "<p style='color: orange;'>⚠️ Exécutez le script SQL fix_agents_table.sql pour corriger cela.</p>";
        }
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur lors de la vérification: " . $e->getMessage() . "</p>";
}
?>

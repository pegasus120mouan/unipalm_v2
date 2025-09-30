<?php
require_once '../inc/functions/connexion.php';

echo "<h2>Mise à Jour de la Structure des Ponts-Bascules</h2>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    // Vérifier si les colonnes existent déjà
    $stmt = $conn->query("DESCRIBE pont_bascule");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $nom_pont_exists = in_array('nom_pont', $columns);
    $statut_exists = in_array('statut', $columns);
    
    echo "<h3>État actuel de la table :</h3>";
    echo "<div class='info'>Colonnes existantes : " . implode(', ', $columns) . "</div>";
    
    // Ajouter la colonne nom_pont si elle n'existe pas
    if (!$nom_pont_exists) {
        echo "<div class='info'>Ajout de la colonne 'nom_pont'...</div>";
        $conn->exec("ALTER TABLE pont_bascule ADD COLUMN nom_pont VARCHAR(255) NULL AFTER code_pont");
        echo "<div class='success'>✅ Colonne 'nom_pont' ajoutée avec succès</div>";
    } else {
        echo "<div class='success'>✅ Colonne 'nom_pont' existe déjà</div>";
    }
    
    // Ajouter la colonne statut si elle n'existe pas
    if (!$statut_exists) {
        echo "<div class='info'>Ajout de la colonne 'statut'...</div>";
        $conn->exec("ALTER TABLE pont_bascule ADD COLUMN statut ENUM('Actif', 'Inactif') NOT NULL DEFAULT 'Inactif' AFTER cooperatif");
        echo "<div class='success'>✅ Colonne 'statut' ajoutée avec succès</div>";
    } else {
        echo "<div class='success'>✅ Colonne 'statut' existe déjà</div>";
    }
    
    // Mettre à jour les enregistrements existants
    echo "<h3>Mise à jour des données existantes :</h3>";
    
    // Mettre tous les ponts existants en statut 'Actif' par défaut
    $stmt = $conn->prepare("UPDATE pont_bascule SET statut = 'Actif' WHERE statut = 'Inactif' OR statut IS NULL");
    $stmt->execute();
    $updated = $stmt->rowCount();
    
    if ($updated > 0) {
        echo "<div class='success'>✅ {$updated} ponts mis à jour avec le statut 'Actif'</div>";
    }
    
    // Ajouter des noms par défaut pour les ponts sans nom
    $stmt = $conn->prepare("UPDATE pont_bascule SET nom_pont = CONCAT('Pont ', code_pont) WHERE nom_pont IS NULL OR nom_pont = ''");
    $stmt->execute();
    $updated_names = $stmt->rowCount();
    
    if ($updated_names > 0) {
        echo "<div class='success'>✅ {$updated_names} ponts ont reçu un nom par défaut</div>";
    }
    
    // Afficher la nouvelle structure
    echo "<h3>Nouvelle structure de la table :</h3>";
    $stmt = $conn->query("DESCRIBE pont_bascule");
    echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
    echo "<tr style='background:#f0f0f0;'><th>Champ</th><th>Type</th><th>Null</th><th>Défaut</th></tr>";
    
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td><strong>" . $row['Field'] . "</strong></td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . ($row['Null'] === 'YES' ? 'Oui' : 'Non') . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Afficher les données mises à jour
    echo "<h3>Ponts-bascules avec les nouveaux champs :</h3>";
    $stmt = $conn->query("SELECT * FROM pont_bascule ORDER BY code_pont");
    echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
    echo "<tr style='background:#f0f0f0;'><th>Code</th><th>Nom</th><th>Gérant</th><th>Coopérative</th><th>Statut</th></tr>";
    
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td><strong>" . $row['code_pont'] . "</strong></td>";
        echo "<td>" . ($row['nom_pont'] ?? 'Non défini') . "</td>";
        echo "<td>" . $row['gerant'] . "</td>";
        echo "<td>" . ($row['cooperatif'] ?? 'Non spécifiée') . "</td>";
        echo "<td><span style='color:" . ($row['statut'] === 'Actif' ? 'green' : 'orange') . ";'>●</span> " . $row['statut'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<div class='success'>✅ Mise à jour terminée ! La structure de la table est maintenant à jour.</div>";
    echo "<br><a href='ponts.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>→ Retour aux Ponts-Bascules</a>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur: " . $e->getMessage() . "</div>";
}
?>

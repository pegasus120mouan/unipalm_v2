<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_prix_unitaires.php';

echo "<h2>🔍 Test de validation VOP</h2>";

// Récupérer l'ID de l'usine VOP
$sql_vop = "SELECT id_usine FROM usines WHERE nom_usine = 'VOP'";
$stmt_vop = $conn->prepare($sql_vop);
$stmt_vop->execute();
$vop_info = $stmt_vop->fetch(PDO::FETCH_ASSOC);

if (!$vop_info) {
    echo "<p style='color: red;'>❌ Usine VOP non trouvée</p>";
    exit;
}

$id_usine_vop = $vop_info['id_usine'];
echo "<p>✅ ID usine VOP trouvé : <strong>{$id_usine_vop}</strong></p>";

// Afficher tous les prix existants pour VOP
echo "<h3>📋 Prix existants pour VOP :</h3>";
$sql_prix = "SELECT * FROM prix_unitaires WHERE id_usine = :id_usine ORDER BY date_debut";
$stmt_prix = $conn->prepare($sql_prix);
$stmt_prix->execute([':id_usine' => $id_usine_vop]);
$prix_vop = $stmt_prix->fetchAll(PDO::FETCH_ASSOC);

if (empty($prix_vop)) {
    echo "<p>Aucun prix trouvé pour VOP</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Prix</th><th>Date Début</th><th>Date Fin</th></tr>";
    foreach ($prix_vop as $prix) {
        echo "<tr>";
        echo "<td>{$prix['id']}</td>";
        echo "<td>{$prix['prix']} FCFA</td>";
        echo "<td>{$prix['date_debut']}</td>";
        echo "<td>" . ($prix['date_fin'] ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test de la fonction checkPeriodOverlap avec les dates problématiques
echo "<h3>🧪 Test de validation avec les dates problématiques :</h3>";

// Test 1: Date exacte du conflit (2025-01-10 à 2025-10-31)
echo "<h4>Test 1: 2025-01-10 à 2025-10-31</h4>";
$overlaps1 = checkPeriodOverlap($conn, $id_usine_vop, '2025-01-10', '2025-10-31');
echo "<p>Résultat: " . (($overlaps1 && count($overlaps1) > 0) ? "❌ CONFLIT DÉTECTÉ" : "✅ Aucun conflit") . "</p>";
if ($overlaps1 && count($overlaps1) > 0) {
    echo "<pre>" . print_r($overlaps1, true) . "</pre>";
}

// Test 2: Format ISO standard (2025-10-01 à 2025-10-31)
echo "<h4>Test 2: 2025-10-01 à 2025-10-31</h4>";
$overlaps2 = checkPeriodOverlap($conn, $id_usine_vop, '2025-10-01', '2025-10-31');
echo "<p>Résultat: " . (($overlaps2 && count($overlaps2) > 0) ? "❌ CONFLIT DÉTECTÉ" : "✅ Aucun conflit") . "</p>";
if ($overlaps2 && count($overlaps2) > 0) {
    echo "<pre>" . print_r($overlaps2, true) . "</pre>";
}

// Test 3: Même test que l'AJAX (selon l'image)
echo "<h4>Test 3: Simulation AJAX (2025-10-01 à 2025-10-31)</h4>";
$_POST['id_usine'] = $id_usine_vop;
$_POST['date_debut'] = '2025-10-01';
$_POST['date_fin'] = '2025-10-31';

$overlaps3 = checkPeriodOverlap($conn, $_POST['id_usine'], $_POST['date_debut'], $_POST['date_fin']);
echo "<p>Paramètres AJAX simulés:</p>";
echo "<ul>";
echo "<li>id_usine: {$_POST['id_usine']}</li>";
echo "<li>date_debut: {$_POST['date_debut']}</li>";
echo "<li>date_fin: {$_POST['date_fin']}</li>";
echo "</ul>";
echo "<p>Résultat: " . (($overlaps3 && count($overlaps3) > 0) ? "❌ CONFLIT DÉTECTÉ" : "✅ Aucun conflit") . "</p>";
if ($overlaps3 && count($overlaps3) > 0) {
    echo "<pre>" . print_r($overlaps3, true) . "</pre>";
}

echo "<br><br>";
echo "<a href='prix_unitaires.php' style='background: blue; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Retour aux Prix Unitaires</a>";
?>

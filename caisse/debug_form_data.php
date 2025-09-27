<?php
// Debug des données de formulaire
session_start();

echo "<h2>🔍 Debug des données de formulaire - UniPalm</h2>";

echo "<h3>Données POST reçues :</h3>";
if (!empty($_POST)) {
    echo "<pre>" . print_r($_POST, true) . "</pre>";
} else {
    echo "<p>Aucune donnée POST reçue</p>";
}

echo "<h3>Données GET reçues :</h3>";
if (!empty($_GET)) {
    echo "<pre>" . print_r($_GET, true) . "</pre>";
} else {
    echo "<p>Aucune donnée GET reçue</p>";
}

echo "<h3>Données de session :</h3>";
if (!empty($_SESSION)) {
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
} else {
    echo "<p>Aucune donnée de session</p>";
}

// Si c'est une requête de génération PDF, simuler le traitement
if (isset($_POST['generate_pdf'])) {
    echo "<h3>🎯 Traitement de génération PDF détecté !</h3>";
    
    $selectedAgents = isset($_POST['selected_agents']) ? $_POST['selected_agents'] : [];
    $chefId = $_POST['chef_id'] ?? null;
    $usineId = $_POST['usine_id'] ?? null;
    $dateDebut = $_POST['date_debut'] ?? null;
    $dateFin = $_POST['date_fin'] ?? null;
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>Données extraites :</h4>";
    echo "<ul>";
    echo "<li><strong>Chef ID :</strong> " . ($chefId ? $chefId : "❌ Non défini") . "</li>";
    echo "<li><strong>Agents sélectionnés :</strong> " . (count($selectedAgents) > 0 ? implode(', ', $selectedAgents) : "❌ Aucun") . "</li>";
    echo "<li><strong>Usine :</strong> " . ($usineId ? $usineId : "Toutes") . "</li>";
    echo "<li><strong>Date début :</strong> " . ($dateDebut ? $dateDebut : "Non définie") . "</li>";
    echo "<li><strong>Date fin :</strong> " . ($dateFin ? $dateFin : "Non définie") . "</li>";
    echo "</ul>";
    echo "</div>";
    
    if ($chefId && count($selectedAgents) > 0) {
        echo "<p style='color: green;'>✅ <strong>Données suffisantes pour générer le PDF !</strong></p>";
        echo "<p><a href='generate_bordereau_pdf_clean.php' target='_blank'>Tenter la génération PDF</a></p>";
    } else {
        echo "<p style='color: red;'>❌ <strong>Données insuffisantes pour générer le PDF</strong></p>";
        if (!$chefId) echo "<p>- Chef d'équipe manquant</p>";
        if (count($selectedAgents) == 0) echo "<p>- Aucun agent sélectionné</p>";
    }
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
h2, h3 { color: #333; }
</style>

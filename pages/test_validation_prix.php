<?php
// Script de test pour la validation des chevauchements de prix unitaires
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_prix_unitaires.php';

echo "<h2>Test de validation des chevauchements de prix unitaires</h2>";

// Test 1: Créer un premier prix unitaire
echo "<h3>Test 1: Création d'un prix unitaire de base</h3>";
$result1 = createPrixUnitaire($conn, 1, 150.00, '2025-01-01', '2025-01-31');
echo "<pre>";
print_r($result1);
echo "</pre>";

// Test 2: Essayer de créer un prix qui se chevauche
echo "<h3>Test 2: Tentative de création d'un prix qui se chevauche</h3>";
$result2 = createPrixUnitaire($conn, 1, 200.00, '2025-01-15', '2025-02-15');
echo "<pre>";
print_r($result2);
echo "</pre>";

// Test 3: Créer un prix pour une autre usine (devrait fonctionner)
echo "<h3>Test 3: Création d'un prix pour une autre usine</h3>";
$result3 = createPrixUnitaire($conn, 2, 200.00, '2025-01-15', '2025-02-15');
echo "<pre>";
print_r($result3);
echo "</pre>";

// Test 4: Créer un prix après la période existante (devrait fonctionner)
echo "<h3>Test 4: Création d'un prix après la période existante</h3>";
$result4 = createPrixUnitaire($conn, 1, 180.00, '2025-02-01', '2025-02-28');
echo "<pre>";
print_r($result4);
echo "</pre>";

// Test 5: Vérifier les chevauchements pour une période donnée
echo "<h3>Test 5: Vérification des chevauchements</h3>";
$overlaps = checkPeriodOverlap($conn, 1, '2025-01-10', '2025-01-20');
echo "<pre>";
print_r($overlaps);
echo "</pre>";

echo "<h3>Nettoyage des données de test</h3>";
// Nettoyer les données de test (optionnel)
$conn->exec("DELETE FROM prix_unitaires WHERE prix IN (150.00, 200.00, 180.00)");
echo "Données de test supprimées.";
?>

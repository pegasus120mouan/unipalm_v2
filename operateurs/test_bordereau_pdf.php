<?php
// Test du bordereau PDF avec données simulées
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simuler des données de test pour le PDF
$testData = [
    'chef' => [
        'id_chef' => 1,
        'nom' => 'KOUAME',
        'prenoms' => 'Jean Baptiste'
    ],
    'agents' => [1, 2],
    'agents_info' => [
        [
            'id_agent' => 1,
            'nom' => 'TRAORE',
            'prenom' => 'Mamadou'
        ],
        [
            'id_agent' => 2,
            'nom' => 'KONE',
            'prenom' => 'Fatou'
        ]
    ],
    'usine_id' => null,
    'date_debut' => '2025-01-01',
    'date_fin' => '2025-01-26',
    'tickets' => [
        [
            'id_ticket' => 1,
            'numero_ticket' => 'T001',
            'date_ticket' => '2025-01-25',
            'poids' => 1500,
            'nom_usine' => 'Usine A',
            'matricule_vehicule' => 'AB-123-CD',
            'id_agent' => 1
        ],
        [
            'id_ticket' => 2,
            'numero_ticket' => 'T002',
            'date_ticket' => '2025-01-25',
            'poids' => 2000,
            'nom_usine' => 'Usine B',
            'matricule_vehicule' => 'EF-456-GH',
            'id_agent' => 2
        ],
        [
            'id_ticket' => 3,
            'numero_ticket' => 'T003',
            'date_ticket' => '2025-01-26',
            'poids' => 1800,
            'nom_usine' => 'Usine A',
            'matricule_vehicule' => 'IJ-789-KL',
            'id_agent' => 1
        ]
    ]
];

echo "<h2>Test Bordereau PDF - UniPalm</h2>";

if (isset($_GET['test'])) {
    // Stocker les données de test dans la session
    $_SESSION['pdf_data'] = $testData;
    
    echo "<p>✅ Données de test stockées dans la session</p>";
    echo "<p><a href='generate_bordereau_pdf.php' target='_blank' class='btn btn-primary'>Générer PDF de test</a></p>";
    echo "<p><a href='debug_pdf.php' target='_blank' class='btn btn-secondary'>Débogage PDF</a></p>";
    
} else {
    echo "<p>Ce test va créer des données simulées et tenter de générer un bordereau PDF.</p>";
    echo "<p><strong>Données de test :</strong></p>";
    echo "<ul>";
    echo "<li>Chef : " . $testData['chef']['nom'] . " " . $testData['chef']['prenoms'] . "</li>";
    echo "<li>Agents : " . count($testData['agents_info']) . " agents</li>";
    echo "<li>Tickets : " . count($testData['tickets']) . " tickets</li>";
    echo "<li>Poids total : " . array_sum(array_column($testData['tickets'], 'poids')) . " Kg</li>";
    echo "</ul>";
    
    echo "<p><a href='?test=1' class='btn btn-success'>Lancer le test</a></p>";
}

// Afficher le contenu de la session actuelle
echo "<h3>Contenu actuel de la session :</h3>";
if (isset($_SESSION['pdf_data'])) {
    echo "<pre>" . print_r($_SESSION['pdf_data'], true) . "</pre>";
} else {
    echo "<p>Aucune donnée PDF dans la session</p>";
}
?>

<style>
.btn {
    display: inline-block;
    padding: 10px 20px;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin: 5px;
}
.btn-primary { background: #007bff; }
.btn-secondary { background: #6c757d; }
.btn-success { background: #28a745; }
.btn:hover { opacity: 0.8; }
</style>

<?php
// Test final du PDF propre
session_start();

// Données de test
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

if (isset($_GET['test'])) {
    $_SESSION['pdf_data'] = $testData;
    header('Location: generate_bordereau_pdf_clean.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test PDF Final - UniPalm</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .btn { 
            display: inline-block; 
            padding: 15px 30px; 
            background: #28a745; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
            font-size: 16px;
            margin: 10px;
        }
        .btn:hover { background: #218838; }
        .info { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 5px; 
            margin: 20px 0; 
        }
    </style>
</head>
<body>
    <h1>🧪 Test PDF Final - UniPalm</h1>
    
    <div class="info">
        <h3>Ce test va :</h3>
        <ul>
            <li>✅ Créer des données de test simulées</li>
            <li>✅ Les stocker dans la session</li>
            <li>✅ Générer un PDF propre sans erreurs</li>
            <li>✅ Afficher le bordereau dans votre navigateur</li>
        </ul>
        
        <p><strong>Données de test :</strong></p>
        <ul>
            <li>Chef : <?= $testData['chef']['nom'] . ' ' . $testData['chef']['prenoms'] ?></li>
            <li>Agents : <?= count($testData['agents_info']) ?> agents</li>
            <li>Tickets : <?= count($testData['tickets']) ?> tickets</li>
            <li>Poids total : <?= array_sum(array_column($testData['tickets'], 'poids')) ?> Kg</li>
        </ul>
    </div>
    
    <a href="?test=1" class="btn">🚀 Lancer le test PDF</a>
    
    <p><em>Si le PDF s'affiche correctement, le problème est résolu !</em></p>
</body>
</html>

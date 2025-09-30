<?php
// Test du bordereau avec le modèle exact
session_start();

// Données de test similaires au modèle
$testData = [
    'chef' => [
        'id_chef' => 1,
        'nom' => 'KINDO',
        'prenoms' => 'MOUMOUNI'
    ],
    'agents' => [1, 2],
    'agents_info' => [
        [
            'id_agent' => 1,
            'nom' => 'KINDO',
            'prenom' => 'MOUMOUNI'
        ]
    ],
    'usine_id' => null,
    'date_debut' => '2025-05-02',
    'date_fin' => '2025-05-06',
    'tickets' => [
        [
            'id_ticket' => 1,
            'numero_ticket' => 'AYG-25022',
            'date_ticket' => '2025-05-01',
            'poids' => 1800,
            'nom_usine' => 'SEHP',
            'matricule_vehicule' => '8923',
            'id_agent' => 1
        ],
        [
            'id_ticket' => 2,
            'numero_ticket' => 'AYG-25015',
            'date_ticket' => '2025-05-01',
            'poids' => 1660,
            'nom_usine' => 'SEHP',
            'matricule_vehicule' => '8923',
            'id_agent' => 1
        ],
        [
            'id_ticket' => 3,
            'numero_ticket' => 'AYG-25038',
            'date_ticket' => '2025-05-01',
            'poids' => 2020,
            'nom_usine' => 'SEHP',
            'matricule_vehicule' => '8923',
            'id_agent' => 1
        ],
        [
            'id_ticket' => 4,
            'numero_ticket' => 'AYG-25063',
            'date_ticket' => '2025-05-01',
            'poids' => 2080,
            'nom_usine' => 'SEHP',
            'matricule_vehicule' => '8923',
            'id_agent' => 1
        ],
        [
            'id_ticket' => 5,
            'numero_ticket' => 'AYG-25014',
            'date_ticket' => '2025-05-01',
            'poids' => 1240,
            'nom_usine' => 'SEHP',
            'matricule_vehicule' => '8923',
            'id_agent' => 1
        ],
        [
            'id_ticket' => 6,
            'numero_ticket' => 'ayg-25089',
            'date_ticket' => '2025-05-02',
            'poids' => 4640,
            'nom_usine' => 'SEHP',
            'matricule_vehicule' => '6750FB01',
            'id_agent' => 1
        ],
        [
            'id_ticket' => 7,
            'numero_ticket' => 'AYG-25248',
            'date_ticket' => '2025-05-05',
            'poids' => 4320,
            'nom_usine' => 'SEHP',
            'matricule_vehicule' => '6750FB01',
            'id_agent' => 1
        ],
        [
            'id_ticket' => 8,
            'numero_ticket' => 'AYG-25235',
            'date_ticket' => '2025-05-05',
            'poids' => 4280,
            'nom_usine' => 'SEHP',
            'matricule_vehicule' => '6750FB01',
            'id_agent' => 1
        ],
        [
            'id_ticket' => 9,
            'numero_ticket' => 'DIV1_53077',
            'date_ticket' => '2025-05-03',
            'poids' => 2060,
            'nom_usine' => 'VOP',
            'matricule_vehicule' => 'NEANT',
            'id_agent' => 1
        ],
        [
            'id_ticket' => 10,
            'numero_ticket' => 'DIV1_53155',
            'date_ticket' => '2025-05-04',
            'poids' => 4600,
            'nom_usine' => 'VOP',
            'matricule_vehicule' => '6750FB01',
            'id_agent' => 1
        ],
        [
            'id_ticket' => 11,
            'numero_ticket' => 'DIV1_53140',
            'date_ticket' => '2025-05-04',
            'poids' => 4560,
            'nom_usine' => 'VOP',
            'matricule_vehicule' => '6750FB01',
            'id_agent' => 1
        ]
    ]
];

if (isset($_GET['test'])) {
    $_SESSION['pdf_data'] = $testData;
    header('Location: generate_bordereau_pdf_model.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Bordereau Modèle - UniPalm</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 40px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }
        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            color: #333;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
        }
        .btn { 
            display: inline-block; 
            padding: 15px 30px; 
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white; 
            text-decoration: none; 
            border-radius: 10px; 
            font-size: 16px;
            margin: 10px;
            transition: transform 0.3s;
        }
        .btn:hover { 
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.4);
        }
        .info { 
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            padding: 20px; 
            border-radius: 15px; 
            margin: 20px 0; 
            border: 1px solid rgba(102, 126, 234, 0.2);
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-item {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.1));
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        .stat-value {
            font-size: 1.5em;
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Bordereau Modèle UniPalm</h1>
        
        <div class="info">
            <h3>📋 Ce test reproduit exactement le modèle que vous avez fourni :</h3>
            <ul>
                <li>✅ Logo UniPalm avec design exact</li>
                <li>✅ En-tête "UNIFALM COOP - CA" en vert</li>
                <li>✅ Numéro de bordereau format BORD-AAAAMMJJ-XXX-XXXX</li>
                <li>✅ Section "Informations du bordereau" avec bordures</li>
                <li>✅ Tableau avec colonnes : Date, N° Ticket, Usine, Véhicule, Poids (Kg)</li>
                <li>✅ Sous-totaux par usine</li>
                <li>✅ Design professionnel identique au modèle</li>
            </ul>
        </div>
        
        <div class="stats">
            <div class="stat-item">
                <div class="stat-value"><?= $testData['chef']['nom'] . ' ' . $testData['chef']['prenoms'] ?></div>
                <div>Agent</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= count($testData['tickets']) ?></div>
                <div>Tickets</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= array_sum(array_column($testData['tickets'], 'poids')) ?> Kg</div>
                <div>Poids Total</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">2</div>
                <div>Usines (SEHP, VOP)</div>
            </div>
        </div>
        
        <p><strong>📅 Période :</strong> <?= $testData['date_debut'] ?> au <?= $testData['date_fin'] ?></p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="?test=1" class="btn">🚀 Générer le PDF Modèle</a>
        </div>
        
        <p><em>Ce PDF sera identique au modèle que vous avez fourni avec toutes les données de test correspondantes.</em></p>
    </div>
</body>
</html>

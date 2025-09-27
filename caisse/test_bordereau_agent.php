<?php
// Test du bordereau avec colonne Agent
session_start();

// Données de test avec plusieurs agents
$testData = [
    'chef' => [
        'id_chef' => 1,
        'nom' => 'KINDO',
        'prenoms' => 'MOUMOUNI'
    ],
    'agents' => [1, 2, 3],
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
        ],
        [
            'id_agent' => 3,
            'nom' => 'OUATTARA',
            'prenom' => 'Ibrahim'
        ]
    ],
    'usine_id' => null,
    'date_debut' => '2025-05-02',
    'date_fin' => '2025-05-06',
    'tickets' => [
        [
            'id_ticket' => 1,
            'numero_ticket' => 'PABOG420250900050',
            'date_ticket' => '2025-09-05',
            'poids' => 15400,
            'nom_usine' => 'PALMCI BOUBO',
            'matricule_vehicule' => '1431hf01',
            'id_agent' => 1
        ],
        [
            'id_ticket' => 2,
            'numero_ticket' => 'PABOG420250900332',
            'date_ticket' => '2025-09-24',
            'poids' => 9140,
            'nom_usine' => 'PALMCI BOUBO',
            'matricule_vehicule' => '1431hf01',
            'id_agent' => 2
        ],
        [
            'id_ticket' => 3,
            'numero_ticket' => 'PABOG420250900296',
            'date_ticket' => '2025-09-21',
            'poids' => 5390,
            'nom_usine' => 'PALMCI BOUBO',
            'matricule_vehicule' => '3778KC01',
            'id_agent' => 3
        ],
        [
            'id_ticket' => 4,
            'numero_ticket' => 'PABOG220250900182',
            'date_ticket' => '2025-09-19',
            'poids' => 14020,
            'nom_usine' => 'PALMCI BOUBO',
            'matricule_vehicule' => '1431hf01',
            'id_agent' => 1
        ],
        [
            'id_ticket' => 5,
            'numero_ticket' => 'PABOG120250800111',
            'date_ticket' => '2025-08-06',
            'poids' => 15980,
            'nom_usine' => 'PALMCI BOUBO',
            'matricule_vehicule' => '1431hf01',
            'id_agent' => 2
        ],
        [
            'id_ticket' => 6,
            'numero_ticket' => 'PABOG220250200015',
            'date_ticket' => '2025-02-04',
            'poids' => 14700,
            'nom_usine' => 'PALMCI BOUBO',
            'matricule_vehicule' => '1431hf01',
            'id_agent' => 3
        ],
        [
            'id_ticket' => 7,
            'numero_ticket' => 'DIV1_53077',
            'date_ticket' => '2025-05-03',
            'poids' => 2060,
            'nom_usine' => 'VOP',
            'matricule_vehicule' => 'NEANT',
            'id_agent' => 1
        ],
        [
            'id_ticket' => 8,
            'numero_ticket' => 'DIV1_53155',
            'date_ticket' => '2025-05-04',
            'poids' => 4600,
            'nom_usine' => 'VOP',
            'matricule_vehicule' => '6750FB01',
            'id_agent' => 2
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
    <title>Test Bordereau avec Colonne Agent - UniPalm</title>
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
        .agents-list {
            background: rgba(23, 162, 184, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
        }
        .agent-item {
            display: inline-block;
            background: rgba(23, 162, 184, 0.2);
            padding: 5px 10px;
            margin: 3px;
            border-radius: 5px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Bordereau avec Colonne Agent</h1>
        
        <div class="info">
            <h3>📋 Nouveau tableau avec colonne Agent :</h3>
            <ul>
                <li>✅ <strong>Date</strong> - Date du ticket</li>
                <li>🆕 <strong>Agent</strong> - Nom de l'agent (format: NOM P.)</li>
                <li>✅ <strong>N° Ticket</strong> - Numéro du ticket</li>
                <li>✅ <strong>Usine</strong> - Nom de l'usine</li>
                <li>✅ <strong>Véhicule</strong> - Matricule du véhicule</li>
                <li>✅ <strong>Poids (Kg)</strong> - Poids du ticket</li>
            </ul>
        </div>
        
        <div class="agents-list">
            <h4>👥 Agents dans ce test :</h4>
            <?php foreach ($testData['agents_info'] as $agent): ?>
                <span class="agent-item">
                    <?= $agent['nom'] . ' ' . substr($agent['prenom'], 0, 1) . '.' ?>
                </span>
            <?php endforeach; ?>
        </div>
        
        <div class="stats">
            <div class="stat-item">
                <div class="stat-value"><?= $testData['chef']['nom'] . ' ' . $testData['chef']['prenoms'] ?></div>
                <div>Chef d'Équipe</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= count($testData['agents_info']) ?></div>
                <div>Agents</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= count($testData['tickets']) ?></div>
                <div>Tickets</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= array_sum(array_column($testData['tickets'], 'poids')) ?> Kg</div>
                <div>Poids Total</div>
            </div>
        </div>
        
        <p><strong>🏭 Usines :</strong> PALMCI BOUBO, VOP</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="?test=1" class="btn">🚀 Générer PDF avec Colonne Agent</a>
        </div>
        
        <p><em>Le PDF affichera maintenant le nom de l'agent pour chaque ticket dans une colonne dédiée.</em></p>
    </div>
</body>
</html>

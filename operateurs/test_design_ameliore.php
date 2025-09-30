<?php
// Test du design amélioré sans chevauchements
session_start();

// Données de test avec noms longs pour tester les limites
$testData = [
    'chef' => [
        'id_chef' => 1,
        'nom' => 'ABDOULAYE',
        'prenoms' => 'KONE'
    ],
    'agents' => [1, 2, 3],
    'agents_info' => [
        [
            'id_agent' => 1,
            'nom' => 'ABDOULAYE',
            'prenom' => 'Kone'
        ],
        [
            'id_agent' => 2,
            'nom' => 'TRAORE',
            'prenom' => 'Mamadou'
        ],
        [
            'id_agent' => 3,
            'nom' => 'OUATTARA',
            'prenom' => 'Ibrahim'
        ]
    ],
    'usine_id' => null,
    'date_debut' => '2025-03-01',
    'date_fin' => '2025-05-12',
    'tickets' => [
        [
            'id_ticket' => 1,
            'numero_ticket' => 'PABOG120250500085',
            'date_ticket' => '2025-05-12',
            'poids' => 25480,
            'nom_usine' => 'PALMCI BOUBO',
            'matricule_vehicule' => '733GS01',
            'id_agent' => 1
        ],
        [
            'id_ticket' => 2,
            'numero_ticket' => 'PABOG120250500073',
            'date_ticket' => '2025-05-09',
            'poids' => 15840,
            'nom_usine' => 'PALMCI BOUBO',
            'matricule_vehicule' => '1431hf01',
            'id_agent' => 1
        ],
        [
            'id_ticket' => 3,
            'numero_ticket' => 'pabog120250500050',
            'date_ticket' => '2025-05-06',
            'poids' => 12620,
            'nom_usine' => 'PALMCI BOUBO',
            'matricule_vehicule' => '1431hf01',
            'id_agent' => 1
        ],
        [
            'id_ticket' => 4,
            'numero_ticket' => 'PABOG120250500027',
            'date_ticket' => '2025-05-05',
            'poids' => 33060,
            'nom_usine' => 'PALMCI BOUBO',
            'matricule_vehicule' => '733GS01',
            'id_agent' => 2
        ],
        [
            'id_ticket' => 5,
            'numero_ticket' => 'PABOG120250400246',
            'date_ticket' => '2025-04-30',
            'poids' => 17700,
            'nom_usine' => 'PALMCI BOUBO',
            'matricule_vehicule' => '1431hf01',
            'id_agent' => 2
        ],
        [
            'id_ticket' => 6,
            'numero_ticket' => 'PABOG220250400057',
            'date_ticket' => '2025-04-19',
            'poids' => 17980,
            'nom_usine' => 'PALMCI BOUBO',
            'matricule_vehicule' => '1431hf01',
            'id_agent' => 3
        ],
        [
            'id_ticket' => 7,
            'numero_ticket' => 'DIV1_53077_LONG_NUMERO',
            'date_ticket' => '2025-03-15',
            'poids' => 15980,
            'nom_usine' => 'VOP USINE TRES LONGUE',
            'matricule_vehicule' => 'VEHICULE_TRES_LONG_123',
            'id_agent' => 3
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
    <title>Test Design Amélioré - UniPalm</title>
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
        .improvements {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.1));
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        .table-preview {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 0.85em;
            overflow-x: auto;
        }
        .column-width {
            display: inline-block;
            background: rgba(102, 126, 234, 0.1);
            padding: 3px 8px;
            margin: 2px;
            border-radius: 5px;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎨 Test Design Amélioré - Sans Chevauchements</h1>
        
        <div class="improvements">
            <h3>✨ Améliorations apportées :</h3>
            <ul>
                <li>🔧 <strong>Largeurs optimisées</strong> pour éviter les chevauchements</li>
                <li>📏 <strong>Limitation des textes longs</strong> avec "..." si nécessaire</li>
                <li>📐 <strong>Espacement équilibré</strong> entre toutes les colonnes</li>
                <li>🎯 <strong>Alignement parfait</strong> des sous-totaux et totaux</li>
                <li>📱 <strong>Responsive design</strong> adapté au format PDF</li>
            </ul>
        </div>
        
        <div class="info">
            <h3>📊 Nouvelles largeurs des colonnes :</h3>
            <div>
                <span class="column-width">Date: 22px</span>
                <span class="column-width">Agent: 35px</span>
                <span class="column-width">N° Ticket: 40px</span>
                <span class="column-width">Usine: 30px</span>
                <span class="column-width">Véhicule: 28px</span>
                <span class="column-width">Poids: 25px</span>
            </div>
            <p><strong>Total :</strong> 180px (largeur parfaite pour le PDF)</p>
        </div>
        
        <div class="table-preview">
            <strong>Aperçu du tableau :</strong><br>
            | Date     | Agent        | N° Ticket           | Usine      | Véhicule | Poids   |<br>
            |----------|--------------|---------------------|------------|----------|---------|<br>
            | 12/05/25 | ABDOULAYE K. | PABOG120250500085   | PALMCI...  | 733GS01  | 25,480  |<br>
            | 09/05/25 | ABDOULAYE K. | PABOG120250500073   | PALMCI...  | 1431hf01 | 15,840  |<br>
            | 15/03/25 | OUATTARA I.  | DIV1_53077_LONG...  | VOP USI... | VEHICU...| 15,980  |<br>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="?test=1" class="btn">🚀 Tester le Design Amélioré</a>
        </div>
        
        <p><em>Ce test inclut des noms longs et des numéros de tickets longs pour vérifier que tout s'affiche correctement sans chevauchement.</em></p>
    </div>
</body>
</html>

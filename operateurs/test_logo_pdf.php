<?php
// Test du PDF avec le vrai logo UniPalm
session_start();

// Vérifier si le logo existe
$logoPath = '../dist/img/logo.png';
$logoExists = file_exists($logoPath);

// Données de test simples
$testData = [
    'chef' => [
        'id_chef' => 1,
        'nom' => 'KINDO',
        'prenoms' => 'MOUMOUNI'
    ],
    'agents' => [1],
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
            'numero_ticket' => 'TEST-001',
            'date_ticket' => '2025-05-05',
            'poids' => 15000,
            'nom_usine' => 'PALMCI BOUBO',
            'matricule_vehicule' => '1431hf01',
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
    <title>Test Logo PDF - UniPalm</title>
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
        .status {
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            font-weight: 600;
        }
        .status.success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(144, 238, 144, 0.1));
            border: 1px solid #28a745;
            color: #28a745;
        }
        .status.error {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(255, 193, 7, 0.1));
            border: 1px solid #dc3545;
            color: #dc3545;
        }
        .info {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            border: 1px solid rgba(102, 126, 234, 0.2);
        }
        .logo-preview {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .logo-preview img {
            max-width: 200px;
            max-height: 100px;
            border: 2px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖼️ Test Logo PDF - UniPalm</h1>
        
        <div class="status <?= $logoExists ? 'success' : 'error' ?>">
            <?php if ($logoExists): ?>
                ✅ Logo trouvé : <code>dist/img/logo.png</code>
            <?php else: ?>
                ❌ Logo non trouvé : <code>dist/img/logo.png</code>
                <br><small>Le PDF utilisera un placeholder "LOGO" à la place</small>
            <?php endif; ?>
        </div>
        
        <?php if ($logoExists): ?>
        <div class="logo-preview">
            <h4>Aperçu du logo :</h4>
            <img src="../dist/img/logo.png" alt="Logo UniPalm" />
        </div>
        <?php endif; ?>
        
        <div class="info">
            <h3>📋 Configuration du logo dans le PDF :</h3>
            <ul>
                <li>📍 <strong>Position :</strong> X=15, Y=15 (coin supérieur gauche)</li>
                <li>📏 <strong>Dimensions :</strong> 35x25 pixels</li>
                <li>🎨 <strong>Format :</strong> PNG avec transparence</li>
                <li>🔄 <strong>Fallback :</strong> Texte "LOGO" si image non trouvée</li>
                <li>📱 <strong>Résolution :</strong> 300 DPI pour qualité optimale</li>
            </ul>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="?test=1" class="btn">🚀 Tester le PDF avec Logo</a>
        </div>
        
        <p><em>Le PDF affichera maintenant le vrai logo UniPalm dans l'en-tête au lieu du texte placeholder.</em></p>
        
        <?php if (!$logoExists): ?>
        <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 10px; color: #856404;">
            <strong>💡 Note :</strong> Pour que le logo s'affiche, assurez-vous que le fichier <code>dist/img/logo.png</code> existe dans votre projet UniPalm.
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

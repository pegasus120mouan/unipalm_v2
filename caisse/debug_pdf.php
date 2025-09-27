<?php
// Fichier de débogage pour identifier les problèmes PDF
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Débogage PDF - UniPalm</h2>";

// 1. Vérifier si TCPDF existe
echo "<h3>1. Vérification TCPDF</h3>";
$tcpdf_path = '../tcpdf/tcpdf.php';
if (file_exists($tcpdf_path)) {
    echo "✅ TCPDF trouvé à : " . realpath($tcpdf_path) . "<br>";
    try {
        require_once $tcpdf_path;
        echo "✅ TCPDF chargé avec succès<br>";
    } catch (Exception $e) {
        echo "❌ Erreur lors du chargement TCPDF : " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ TCPDF non trouvé à : " . $tcpdf_path . "<br>";
    echo "Chemin absolu testé : " . realpath($tcpdf_path) . "<br>";
    
    // Tester d'autres chemins possibles
    $alternative_paths = [
        '../vendor/tecnickcom/tcpdf/tcpdf.php',
        '../lib/tcpdf/tcpdf.php',
        'tcpdf/tcpdf.php',
        '../tcpdf/tcpdf_include.php'
    ];
    
    foreach ($alternative_paths as $alt_path) {
        if (file_exists($alt_path)) {
            echo "✅ TCPDF alternatif trouvé à : " . realpath($alt_path) . "<br>";
            break;
        }
    }
}

// 2. Vérifier les données de session
echo "<h3>2. Vérification des données de session</h3>";
if (isset($_SESSION['pdf_data'])) {
    echo "✅ Données PDF trouvées dans la session<br>";
    $data = $_SESSION['pdf_data'];
    echo "Nombre de tickets : " . count($data['tickets']) . "<br>";
    echo "Chef : " . ($data['chef'] ? $data['chef']['nom'] . ' ' . $data['chef']['prenoms'] : 'Non défini') . "<br>";
    echo "Nombre d'agents : " . count($data['agents']) . "<br>";
    echo "Usine ID : " . ($data['usine_id'] ?? 'Toutes') . "<br>";
    echo "Date début : " . ($data['date_debut'] ?? 'Non définie') . "<br>";
    echo "Date fin : " . ($data['date_fin'] ?? 'Non définie') . "<br>";
} else {
    echo "❌ Aucune donnée PDF dans la session<br>";
    echo "Contenu de la session : <pre>" . print_r($_SESSION, true) . "</pre>";
}

// 3. Vérifier les connexions de base de données
echo "<h3>3. Vérification de la base de données</h3>";
try {
    require_once '../inc/functions/connexion.php';
    echo "✅ Connexion à la base de données réussie<br>";
} catch (Exception $e) {
    echo "❌ Erreur de connexion à la base de données : " . $e->getMessage() . "<br>";
}

// 4. Vérifier les fonctions requises
echo "<h3>4. Vérification des fonctions</h3>";
$required_files = [
    '../inc/functions/requete/requete_chef_equipes.php',
    '../inc/functions/requete/requete_agents.php',
    '../inc/functions/requete/requete_usines.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "✅ " . basename($file) . " trouvé<br>";
        try {
            require_once $file;
        } catch (Exception $e) {
            echo "❌ Erreur lors du chargement de " . basename($file) . " : " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ " . basename($file) . " non trouvé<br>";
    }
}

// 5. Test de génération PDF simple
echo "<h3>5. Test de génération PDF simple</h3>";
if (class_exists('TCPDF')) {
    try {
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Test PDF UniPalm', 0, 1, 'C');
        
        // Tenter de générer le PDF
        ob_start();
        $pdf->Output('test.pdf', 'S');
        $pdf_content = ob_get_clean();
        
        if (strlen($pdf_content) > 1000) {
            echo "✅ PDF de test généré avec succès (" . strlen($pdf_content) . " bytes)<br>";
        } else {
            echo "❌ PDF de test trop petit ou vide<br>";
        }
    } catch (Exception $e) {
        echo "❌ Erreur lors de la génération du PDF de test : " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Classe TCPDF non disponible<br>";
}

// 6. Informations PHP
echo "<h3>6. Informations PHP</h3>";
echo "Version PHP : " . phpversion() . "<br>";
echo "Memory limit : " . ini_get('memory_limit') . "<br>";
echo "Max execution time : " . ini_get('max_execution_time') . "<br>";
echo "Output buffering : " . (ob_get_level() ? 'Activé (' . ob_get_level() . ')' : 'Désactivé') . "<br>";

?>

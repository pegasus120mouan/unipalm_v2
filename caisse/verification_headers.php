<?php
echo "<h2>🔍 Vérification des Headers dans le dossier Caisse</h2>";

// Fonction pour scanner les fichiers PHP
function scanPHPFiles($directory) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $relativePath = str_replace($directory . DIRECTORY_SEPARATOR, '', $file->getPathname());
            // Ignorer le dossier old et les fichiers de test/debug
            if (!str_contains($relativePath, 'old' . DIRECTORY_SEPARATOR) && 
                !str_contains($relativePath, 'debug_') && 
                !str_contains($relativePath, 'test_') &&
                !str_contains($relativePath, 'verification_')) {
                $files[] = $file->getPathname();
            }
        }
    }
    
    return $files;
}

// Scanner le dossier caisse
$caisseDir = __DIR__;
$phpFiles = scanPHPFiles($caisseDir);

echo "<h3>📊 Statistiques</h3>";
echo "<p><strong>Nombre total de fichiers PHP analysés :</strong> " . count($phpFiles) . "</p>";

$headerCaisseCount = 0;
$headerOldCount = 0;
$noHeaderCount = 0;
$headerCaisseFiles = [];
$headerOldFiles = [];
$noHeaderFiles = [];

echo "<h3>📋 Analyse détaillée</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f8f9fa;'>";
echo "<th style='padding: 10px;'>Fichier</th>";
echo "<th style='padding: 10px;'>Status</th>";
echo "<th style='padding: 10px;'>Header utilisé</th>";
echo "</tr>";

foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    $fileName = basename($file);
    $relativePath = str_replace($caisseDir . DIRECTORY_SEPARATOR, '', $file);
    
    $status = '';
    $headerType = '';
    $rowColor = '';
    
    if (strpos($content, "include('header_caisse.php');") !== false) {
        $status = '✅ Correct';
        $headerType = 'header_caisse.php';
        $rowColor = 'background: #d4edda;';
        $headerCaisseCount++;
        $headerCaisseFiles[] = $relativePath;
    } elseif (strpos($content, "include('header.php');") !== false) {
        $status = '❌ Ancien header';
        $headerType = 'header.php';
        $rowColor = 'background: #f8d7da;';
        $headerOldCount++;
        $headerOldFiles[] = $relativePath;
    } else {
        $status = '⚠️ Pas de header';
        $headerType = 'Aucun';
        $rowColor = 'background: #fff3cd;';
        $noHeaderCount++;
        $noHeaderFiles[] = $relativePath;
    }
    
    echo "<tr style='$rowColor'>";
    echo "<td style='padding: 8px;'>$relativePath</td>";
    echo "<td style='padding: 8px;'>$status</td>";
    echo "<td style='padding: 8px;'>$headerType</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>📈 Résumé</h3>";
echo "<div style='display: flex; gap: 20px; margin: 20px 0;'>";

echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;'>";
echo "<h4 style='color: #155724; margin: 0 0 10px 0;'>✅ Fichiers avec header_caisse.php</h4>";
echo "<p style='font-size: 24px; font-weight: bold; color: #155724; margin: 0;'>$headerCaisseCount</p>";
echo "</div>";

echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;'>";
echo "<h4 style='color: #721c24; margin: 0 0 10px 0;'>❌ Fichiers avec ancien header</h4>";
echo "<p style='font-size: 24px; font-weight: bold; color: #721c24; margin: 0;'>$headerOldCount</p>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;'>";
echo "<h4 style='color: #856404; margin: 0 0 10px 0;'>⚠️ Fichiers sans header</h4>";
echo "<p style='font-size: 24px; font-weight: bold; color: #856404; margin: 0;'>$noHeaderCount</p>";
echo "</div>";

echo "</div>";

if ($headerOldCount > 0) {
    echo "<h3 style='color: #dc3545;'>❌ Fichiers à corriger</h3>";
    echo "<ul>";
    foreach ($headerOldFiles as $file) {
        echo "<li style='color: #dc3545;'>$file</li>";
    }
    echo "</ul>";
}

if ($headerCaisseCount > 0) {
    echo "<h3 style='color: #28a745;'>✅ Fichiers correctement configurés</h3>";
    echo "<details>";
    echo "<summary>Voir la liste ($headerCaisseCount fichiers)</summary>";
    echo "<ul>";
    foreach ($headerCaisseFiles as $file) {
        echo "<li style='color: #28a745;'>$file</li>";
    }
    echo "</ul>";
    echo "</details>";
}

if ($noHeaderCount > 0) {
    echo "<h3 style='color: #ffc107;'>⚠️ Fichiers sans header (probablement normaux)</h3>";
    echo "<details>";
    echo "<summary>Voir la liste ($noHeaderCount fichiers)</summary>";
    echo "<ul>";
    foreach ($noHeaderFiles as $file) {
        echo "<li style='color: #856404;'>$file</li>";
    }
    echo "</ul>";
    echo "</details>";
}

$percentage = $headerCaisseCount > 0 ? round(($headerCaisseCount / ($headerCaisseCount + $headerOldCount)) * 100, 1) : 0;

echo "<div style='background: #e9ecef; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center;'>";
echo "<h3 style='margin: 0 0 10px 0;'>🎯 Progression de la Migration</h3>";
echo "<div style='background: #fff; border-radius: 25px; padding: 5px; margin: 10px 0;'>";
echo "<div style='background: linear-gradient(90deg, #28a745, #20c997); height: 30px; border-radius: 20px; width: {$percentage}%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;'>";
echo "{$percentage}%";
echo "</div>";
echo "</div>";
echo "<p style='margin: 10px 0 0 0; color: #6c757d;'>Migration terminée !</p>";
echo "</div>";

echo "<hr style='margin: 30px 0;'>";
echo "<p style='text-align: center; color: #6c757d; font-style: italic;'>";
echo "Vérification effectuée le " . date('d/m/Y à H:i:s');
echo "</p>";
?>

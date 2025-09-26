<?php
// Test PDF simple pour UniPalm
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Test PDF Simple - UniPalm</h2>";

try {
    // Charger TCPDF
    require_once '../tcpdf/tcpdf.php';
    echo "✅ TCPDF chargé avec succès<br>";
    
    // Créer un PDF simple
    $pdf = new TCPDF();
    $pdf->SetCreator('UniPalm System');
    $pdf->SetAuthor('UniPalm COOP-CA');
    $pdf->SetTitle('Test PDF');
    $pdf->SetMargins(15, 30, 15);
    $pdf->SetAutoPageBreak(TRUE, 25);
    
    $pdf->AddPage();
    
    // Titre
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->SetTextColor(0, 128, 0);
    $pdf->Cell(0, 15, 'UNIPALM COOP - CA', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, 'Test de génération PDF', 0, 1, 'C');
    $pdf->Ln(10);
    
    $pdf->Cell(0, 10, 'Date: ' . date('d/m/Y H:i:s'), 0, 1, 'L');
    $pdf->Cell(0, 10, 'Statut: PDF généré avec succès', 0, 1, 'L');
    
    echo "✅ PDF créé avec succès<br>";
    echo "<a href='#' onclick='generateTestPDF()' class='btn btn-primary'>Générer PDF de test</a><br><br>";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "<br>";
}

// Si on demande la génération du PDF
if (isset($_GET['generate'])) {
    try {
        require_once '../tcpdf/tcpdf.php';
        
        $pdf = new TCPDF();
        $pdf->SetCreator('UniPalm System');
        $pdf->SetAuthor('UniPalm COOP-CA');
        $pdf->SetTitle('Test PDF');
        $pdf->SetMargins(15, 30, 15);
        $pdf->SetAutoPageBreak(TRUE, 25);
        
        $pdf->AddPage();
        
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetTextColor(0, 128, 0);
        $pdf->Cell(0, 15, 'UNIPALM COOP - CA', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 10, 'Test de génération PDF', 0, 1, 'C');
        $pdf->Ln(10);
        
        $pdf->Cell(0, 10, 'Date: ' . date('d/m/Y H:i:s'), 0, 1, 'L');
        $pdf->Cell(0, 10, 'Statut: PDF généré avec succès', 0, 1, 'L');
        
        // Générer le PDF
        $pdf->Output('test_unipalm.pdf', 'I');
        exit;
        
    } catch (Exception $e) {
        die("Erreur lors de la génération : " . $e->getMessage());
    }
}
?>

<script>
function generateTestPDF() {
    window.open('test_pdf_simple.php?generate=1', '_blank');
}
</script>

<style>
.btn {
    display: inline-block;
    padding: 10px 20px;
    background: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    border: none;
    cursor: pointer;
}
.btn:hover {
    background: #0056b3;
}
</style>

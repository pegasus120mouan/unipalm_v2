<?php
// Désactiver l'affichage des erreurs pour éviter toute sortie avant le PDF
error_reporting(0);
ini_set('display_errors', 0);

if (headers_sent()) {
    die("Impossible d'envoyer le PDF : des données ont déjà été envoyées au navigateur.");
}

ob_clean();

require_once '../inc/functions/connexion.php';
require_once '../fpdf/fpdf.php';
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die("Utilisateur non connecté");
}

// Vérifier si l'ID du reçu est fourni
if (!isset($_GET['id'])) {
    die("ID du reçu manquant");
}

// Récupérer les informations du reçu
$stmt = $conn->prepare("
    SELECT 
        r.*,
        d.montant as montant_total,
        d.montant_payer,
        d.numero_demande as numero_demande_original,
        d.date_demande,
        CONCAT(u.nom, ' ', u.prenoms) as caissier_name
    FROM recus_demandes r
    INNER JOIN demande_sortie d ON r.demande_id = d.id_demande
    INNER JOIN utilisateurs u ON r.caissier_id = u.id
    WHERE r.id = ?
");

$stmt->execute([$_GET['id']]);
$recu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recu) {
    die("Reçu non trouvé");
}

header('Cache-Control: private');
header('Content-Type: application/pdf');
header('Content-Transfer-Encoding: binary');
header('Content-Disposition: inline; filename="Recu_' . $recu['numero_recu'] . '.pdf"');

// Classe personnalisée
class PDF extends FPDF {
    protected function cleanText($text) {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
    }

    function Header() {
        if (file_exists('../dist/img/logo.png')) {
            $this->Image('../dist/img/logo.png', 15, 5, 35);
        }
        $this->SetY(15);
        $this->SetX(55);
        $this->SetFont('Arial', 'B', 18);
        $this->Cell(0, 8, $this->cleanText('UNIPALM COOP - CA'), 0, 1, 'C');

        $this->SetX(55);
        $this->SetFont('Arial', '', 11);
        $this->SetTextColor(68, 68, 68);
        $this->Cell(0, 6, $this->cleanText('Societe Cooperative Agricole Unie pour le Palmier'), 0, 1, 'C');

        $this->Ln(20);
        $this->SetFillColor(0, 0, 0);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, $this->cleanText('RECU DE PAIEMENT'), 0, 1, 'C', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(5);
    }

    function Footer() {
        // Footer complètement désactivé
    }

    function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {
        parent::Cell($w, $h, $this->cleanText($txt), $border, $ln, $align, $fill, $link);
    }

    function Text($x, $y, $txt) {
        parent::Text($x, $y, $this->cleanText($txt));
    }

    function Write($h, $txt, $link='') {
        parent::Write($h, $this->cleanText($txt), $link);
    }
}

// Fonction pour dessiner un reçu
function drawReceipt($pdf, $recu, $yStart) {
    $leftMargin = 15;
    $pdf->SetY($yStart);

    // Cadre unique pour toutes les informations
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->SetFillColor(249, 249, 249);
    $pdf->SetLineWidth(0.5);
    $pdf->Rect(10, $pdf->GetY(), 190, 52, 'DF');

    $pdf->SetY($pdf->GetY() + 3);
    $pdf->SetX($leftMargin);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(70, 7, 'Numéro de demande:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 7, $recu['numero_demande_original'], 0, 1);

    $pdf->SetX($leftMargin);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(70, 7, 'Date de la demande:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 7, date('d/m/Y', strtotime($recu['date_demande'])), 0, 1);

    $pdf->SetX($leftMargin);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(70, 7, 'Montant total:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 7, number_format($recu['montant_total'], 0, ',', ' ') . ' FCFA', 0, 1);

    $pdf->SetX($leftMargin);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(70, 7, 'Date de paiement:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 7, date('d/m/Y H:i', strtotime($recu['date_paiement'])), 0, 1);

    $pdf->SetX($leftMargin);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(70, 7, 'Montant payé:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 7, number_format($recu['montant'], 0, ',', ' ') . ' FCFA', 0, 1);

    $pdf->SetX($leftMargin);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(70, 7, 'Reste à payer:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $resteAPayer = $recu['montant_total'] - $recu['montant_payer'];
    $pdf->Cell(0, 7, number_format($resteAPayer, 0, ',', ' ') . ' FCFA', 0, 1);

    $pdf->Ln(3);

    // Cadre pour le caissier
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->SetFillColor(249, 249, 249);
    $pdf->Rect(10, $pdf->GetY(), 190, 15, 'DF');

    $pdf->SetY($pdf->GetY() + 3);
    $pdf->SetX($leftMargin);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(70, 7, 'Caissier:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 7, $recu['caissier_name'], 0, 1);

    // Signatures
    $pdf->Ln(8);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetX(15);
    $pdf->Cell(80, 8, 'Signature du caissier:', 0, 0, 'C');
    $pdf->SetX(115);
    $pdf->Cell(80, 8, 'Signature du receveur:', 0, 1, 'C');

    $pdf->SetDrawColor(150, 150, 150);
    $pdf->SetLineWidth(0.5);
    $pdf->Rect(15, $pdf->GetY(), 80, 25, 'D');
    $pdf->Rect(115, $pdf->GetY(), 80, 25, 'D');
    
    return $pdf->GetY() + 25; // Retourner la position finale
}

// Générer le PDF
$pdf = new PDF();
$pdf->SetMargins(10, 40, 10);
$pdf->SetAutoPageBreak(false); // Désactiver le saut de page automatique
$pdf->AddPage();

// Premier reçu en haut
$firstReceiptEnd = drawReceipt($pdf, $recu, 65);

// Ligne de séparation pointillée
$pdf->SetY($firstReceiptEnd + 5);
$pdf->SetDrawColor(150, 150, 150);
$pdf->SetLineWidth(0.3);
for ($x = 10; $x <= 200; $x += 3) {
    $pdf->Line($x, $pdf->GetY(), $x + 1.5, $pdf->GetY());
}

// Texte de découpe
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(150, 150, 150);
$pdf->SetXY(85, $pdf->GetY() - 2);
$pdf->Cell(40, 4, '--- DÉCOUPER ICI ---', 0, 0, 'C');

// Deuxième reçu en bas
$pdf->SetTextColor(0, 0, 0); // Remettre le texte en noir
drawReceipt($pdf, $recu, $firstReceiptEnd + 15);

// Générer le PDF avec gestion des erreurs
try {
    ob_clean();
    $pdf->Output('I', 'recu_' . $recu['numero_recu'] . '.pdf');
} catch (Exception $e) {
    die("Erreur lors de la génération du PDF : " . $e->getMessage());
}

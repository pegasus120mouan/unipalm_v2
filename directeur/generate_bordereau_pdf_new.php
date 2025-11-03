<?php
// Désactiver l'affichage des erreurs pour éviter les conflits avec le PDF
error_reporting(0);
ini_set('display_errors', 0);

// Nettoyer le buffer de sortie pour éviter les conflits
if (ob_get_level()) {
    ob_end_clean();
}

// Démarrer la session si elle n'est pas déjà active
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    // Inclure les fichiers requis
    require_once '../inc/functions/connexion.php';
    require_once '../inc/functions/requete/requete_bordereaux.php';
    
    // Vérifier si TCPDF existe
    $tcpdf_paths = [
        '../tcpdf/tcpdf.php',
        '../vendor/tecnickcom/tcpdf/tcpdf.php',
        '../lib/tcpdf/tcpdf.php',
        'tcpdf/tcpdf.php'
    ];
    
    $tcpdf_loaded = false;
    foreach ($tcpdf_paths as $tcpdf_path) {
        if (file_exists($tcpdf_path)) {
            $old_error_reporting = error_reporting(E_ERROR | E_WARNING | E_PARSE);
            require_once $tcpdf_path;
            error_reporting($old_error_reporting);
            $tcpdf_loaded = true;
            break;
        }
    }
    
    if (!$tcpdf_loaded) {
        throw new Exception("TCPDF non trouvé. Vérifiez l'installation de TCPDF.");
    }
    
} catch (Exception $e) {
    die("Erreur lors du chargement des dépendances : " . $e->getMessage());
}

// Vérifier l'ID du bordereau
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Erreur : ID du bordereau manquant.");
}

$id_bordereau = intval($_GET['id']);

try {
    // Récupérer les informations du bordereau
    $stmt = $conn->prepare("
        SELECT b.*, a.nom_complet_agent, a.telephone_agent
        FROM bordereau b
        LEFT JOIN agents a ON b.id_agent = a.id_agent
        WHERE b.id_bordereau = ?
    ");
    $stmt->execute([$id_bordereau]);
    $bordereau = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bordereau) {
        throw new Exception("Bordereau non trouvé.");
    }
    
    // Récupérer les tickets associés au bordereau
    $stmt = $conn->prepare("
        SELECT t.*, u.nom_usine, v.matricule_vehicule, v.type_vehicule, a.nom_complet_agent as agent_ticket
        FROM tickets t
        LEFT JOIN usines u ON t.id_usine = u.id_usine
        LEFT JOIN vehicules v ON t.vehicule_id = v.vehicules_id
        LEFT JOIN agents a ON t.id_agent = a.id_agent
        WHERE t.numero_bordereau = ?
        ORDER BY t.date_ticket ASC, t.numero_ticket ASC
    ");
    $stmt->execute([$bordereau['numero_bordereau']]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    die("Erreur lors de la récupération des données : " . $e->getMessage());
}

// Créer le PDF
class BordereauPDF extends TCPDF {
    public function Header() {
        // Logo et en-tête
        $this->SetFont('helvetica', 'B', 20);
        $this->SetTextColor(0, 128, 0);
        $this->Cell(0, 15, 'UNIPALM COOP - CA', 0, 1, 'C');
        
        $this->SetFont('helvetica', '', 12);
        $this->SetTextColor(100, 200, 100);
        $this->Cell(0, 8, 'Société Coopérative Agricole Unie pour le Palmier', 0, 1, 'C');
        $this->Ln(5);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

$pdf = new BordereauPDF();
$pdf->SetCreator('UniPalm System');
$pdf->SetAuthor('UniPalm COOP-CA');
$pdf->SetTitle('Bordereau - ' . $bordereau['numero_bordereau']);
$pdf->SetMargins(15, 30, 15);
$pdf->SetAutoPageBreak(TRUE, 25);

$pdf->AddPage();

// Titre du bordereau
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 10, 'BORDEREAU N° ' . $bordereau['numero_bordereau'], 0, 1, 'C');
$pdf->Ln(5);

// Informations du bordereau
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('helvetica', 'B', 10);

// Tableau des informations générales
$pdf->Cell(180, 8, 'INFORMATIONS DU BORDEREAU', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(90, 6, 'Date de création: ' . date('d/m/Y', strtotime($bordereau['date_creation_bordereau'])), 1, 0, 'L');
$pdf->Cell(90, 6, 'Agent responsable: ' . ($bordereau['nom_complet_agent'] ?? 'Non assigné'), 1, 1, 'L');

$pdf->Cell(90, 6, 'Date début: ' . ($bordereau['date_debut'] ? date('d/m/Y', strtotime($bordereau['date_debut'])) : 'Non définie'), 1, 0, 'L');
$pdf->Cell(90, 6, 'Date fin: ' . ($bordereau['date_fin'] ? date('d/m/Y', strtotime($bordereau['date_fin'])) : 'Non définie'), 1, 1, 'L');

$pdf->Cell(90, 6, 'Statut: ' . ucfirst($bordereau['statut_bordereau']), 1, 0, 'L');
$pdf->Cell(90, 6, 'Nombre de tickets: ' . count($tickets), 1, 1, 'L');

$pdf->Cell(90, 6, 'Poids total: ' . number_format($bordereau['poids_total'], 0, ',', ' ') . ' Kg', 1, 0, 'L');
$pdf->Cell(90, 6, 'Montant total: ' . number_format($bordereau['montant_total'], 0, ',', ' ') . ' FCFA', 1, 1, 'L');

$pdf->Ln(10);

// Tableau des tickets
if (!empty($tickets)) {
    // Organiser les tickets par usine
    $ticketsByUsine = [];
    foreach ($tickets as $ticket) {
        $usine = $ticket['nom_usine'] ?? 'Usine non définie';
        if (!isset($ticketsByUsine[$usine])) {
            $ticketsByUsine[$usine] = [];
        }
        $ticketsByUsine[$usine][] = $ticket;
    }
    
    foreach ($ticketsByUsine as $nomUsine => $ticketsUsine) {
        $sousTotal = 0;
        
        // En-tête de l'usine
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(200, 220, 255);
        $pdf->Cell(180, 8, 'USINE: ' . $nomUsine, 1, 1, 'C', true);
        
        // En-tête du tableau des tickets
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(25, 6, 'Date Ticket', 1, 0, 'C', true);
        $pdf->Cell(30, 6, 'N° Ticket', 1, 0, 'C', true);
        $pdf->Cell(35, 6, 'Véhicule', 1, 0, 'C', true);
        $pdf->Cell(40, 6, 'Agent', 1, 0, 'C', true);
        $pdf->Cell(25, 6, 'Poids (Kg)', 1, 0, 'C', true);
        $pdf->Cell(25, 6, 'Prix Unit.', 1, 1, 'C', true);
        
        // Données des tickets
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetFillColor(255, 255, 255);
        
        foreach ($ticketsUsine as $ticket) {
            $pdf->Cell(25, 5, date('d/m/Y', strtotime($ticket['date_ticket'])), 1, 0, 'C');
            $pdf->Cell(30, 5, $ticket['numero_ticket'], 1, 0, 'C');
            
            $vehicule = $ticket['matricule_vehicule'] ?? 'N/A';
            if ($ticket['type_vehicule']) {
                $vehicule .= ' (' . substr($ticket['type_vehicule'], 0, 5) . ')';
            }
            $pdf->Cell(35, 5, $vehicule, 1, 0, 'C');
            
            $agent = $ticket['agent_ticket'] ?? 'N/A';
            $pdf->Cell(40, 5, substr($agent, 0, 20), 1, 0, 'L');
            
            $pdf->Cell(25, 5, number_format($ticket['poids'], 0), 1, 0, 'R');
            $pdf->Cell(25, 5, number_format($ticket['prix_unitaire'] ?? 0, 0), 1, 1, 'R');
            
            $sousTotal += $ticket['poids'];
        }
        
        // Sous-total pour cette usine
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(220, 220, 220);
        $pdf->Cell(130, 6, 'Sous-total ' . $nomUsine, 1, 0, 'R', true);
        $pdf->Cell(25, 6, number_format($sousTotal, 0), 1, 0, 'R', true);
        $pdf->Cell(25, 6, '', 1, 1, 'R', true);
        
        $pdf->Ln(3);
    }
} else {
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(180, 10, 'Aucun ticket associé à ce bordereau', 1, 1, 'C');
}

// Résumé financier
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetFillColor(255, 235, 200);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(180, 8, 'RÉSUMÉ FINANCIER', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetFillColor(255, 255, 255);

$montant_paye = $bordereau['montant_payer'] ?? 0;
$montant_reste = $bordereau['montant_reste'] ?? ($bordereau['montant_total'] - $montant_paye);

$pdf->Cell(90, 6, 'Montant total: ' . number_format($bordereau['montant_total'], 0, ',', ' ') . ' FCFA', 1, 0, 'L');
$pdf->Cell(90, 6, 'Montant payé: ' . number_format($montant_paye, 0, ',', ' ') . ' FCFA', 1, 1, 'L');

$pdf->Cell(90, 6, 'Reste à payer: ' . number_format($montant_reste, 0, ',', ' ') . ' FCFA', 1, 0, 'L');
$pdf->Cell(90, 6, 'Statut: ' . ucfirst($bordereau['statut_bordereau']), 1, 1, 'L');

// Signatures
$pdf->Ln(15);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(60, 6, 'Agent responsable:', 0, 0, 'L');
$pdf->Cell(60, 6, 'Chef d\'équipe:', 0, 0, 'L');
$pdf->Cell(60, 6, 'Directeur:', 0, 1, 'L');

$pdf->Ln(15);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(60, 6, 'Signature: ________________', 0, 0, 'L');
$pdf->Cell(60, 6, 'Signature: ________________', 0, 0, 'L');
$pdf->Cell(60, 6, 'Signature: ________________', 0, 1, 'L');

$pdf->Ln(5);
$pdf->Cell(60, 6, 'Date: ___/___/______', 0, 0, 'L');
$pdf->Cell(60, 6, 'Date: ___/___/______', 0, 0, 'L');
$pdf->Cell(60, 6, 'Date: ___/___/______', 0, 1, 'L');

try {
    // Sortie du PDF
    $filename = 'Bordereau_' . str_replace(['/', '-', ' '], '_', $bordereau['numero_bordereau']) . '.pdf';
    $pdf->Output($filename, 'I');
    
} catch (Exception $e) {
    echo "<h2>Erreur lors de la génération du PDF</h2>";
    echo "<p>Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='bordereaux.php'>Retour à la liste des bordereaux</a></p>";
    
    error_log("Erreur PDF Bordereau : " . $e->getMessage());
}
?>

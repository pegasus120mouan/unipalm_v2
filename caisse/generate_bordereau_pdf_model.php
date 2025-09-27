<?php
// Générateur PDF selon le modèle UniPalm exact
error_reporting(0);
ini_set('display_errors', 0);

// Nettoyer tous les buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Démarrer la session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérifier les données de session
if (!isset($_SESSION['pdf_data'])) {
    header('Location: recherche_chef_equipe.php?error=no_data');
    exit;
}

$data = $_SESSION['pdf_data'];
$tickets = $data['tickets'];

if (empty($tickets)) {
    header('Location: recherche_chef_equipe.php?error=no_tickets');
    exit;
}

try {
    // Inclure les dépendances
    @require_once '../inc/functions/connexion.php';
    @require_once '../inc/functions/requete/requete_chef_equipes.php';
    @require_once '../inc/functions/requete/requete_agents.php';
    @require_once '../inc/functions/requete/requete_usines.php';
    @require_once '../tcpdf/tcpdf.php';
    
    // Récupérer les informations
    $chefInfo = $data['chef'] ?? null;
    $usineInfo = null;
    if (!empty($data['usine_id'])) {
        $stmt = $conn->prepare("SELECT * FROM usines WHERE id_usine = ?");
        $stmt->execute([$data['usine_id']]);
        $usineInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Organiser les tickets par usine
    $ticketsByUsine = [];
    $totalGeneral = 0;
    
    foreach ($tickets as $ticket) {
        $usine = $ticket['nom_usine'];
        if (!isset($ticketsByUsine[$usine])) {
            $ticketsByUsine[$usine] = [];
        }
        $ticketsByUsine[$usine][] = $ticket;
        $totalGeneral += $ticket['poids'];
    }
    
    // Générer un numéro de bordereau selon le modèle
    $numeroBordereau = 'BORD-' . date('Ymd') . '-' . rand(100, 999) . '-' . rand(1000, 9999);
    
    // Créer le PDF selon le modèle exact
    class BordereauPDFModel extends TCPDF {
        public function Header() {
            // Logo UniPalm depuis dist/img/logo.png
            $logoPath = '../dist/img/logo.png';
            if (file_exists($logoPath)) {
                $this->Image($logoPath, 15, 15, 35, 25, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            } else {
                // Fallback si le logo n'existe pas
                $this->SetFont('helvetica', 'B', 12);
                $this->SetTextColor(0, 100, 0);
                $this->SetXY(15, 15);
                $this->Cell(35, 25, 'LOGO', 1, 0, 'C');
            }
            
            // Titre principal centré
            $this->SetXY(60, 15);
            $this->SetFont('helvetica', 'B', 24);
            $this->SetTextColor(0, 150, 0);
            $this->Cell(120, 10, 'UNIPALM COOP - CA', 0, 1, 'C');
            
            // Sous-titre
            $this->SetXY(60, 25);
            $this->SetFont('helvetica', '', 12);
            $this->SetTextColor(100, 200, 100);
            $this->Cell(120, 8, 'Société Coopérative Agricole Unie pour le Palmier', 0, 1, 'C');
            
            $this->Ln(15);
        }
        
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        }
    }
    
    $pdf = new BordereauPDFModel();
    $pdf->SetCreator('UniPalm System');
    $pdf->SetAuthor('UniPalm COOP-CA');
    $pdf->SetTitle('Bordereau de Déchargement - ' . $numeroBordereau);
    $pdf->SetMargins(15, 50, 15);
    $pdf->SetAutoPageBreak(TRUE, 25);
    
    $pdf->AddPage();
    
    // Titre du bordereau selon le modèle
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, 'BORDEREAU DE DÉCHARGEMENT N° ' . $numeroBordereau, 0, 1, 'C');
    $pdf->Ln(10);
    
    // Section "Informations du bordereau" selon le modèle
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(180, 8, 'Informations sur le ticket', 1, 1, 'L', true);
    
    // Informations selon le modèle exact
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetFillColor(255, 255, 255);
    
    // Agent (selon le modèle, c'est le chef d'équipe)
    $agentName = $chefInfo ? $chefInfo['nom'] . ' ' . $chefInfo['prenoms'] : 'Non spécifié';
    $pdf->Cell(180, 8, 'Chef d\'equipe: ' . $agentName, 1, 1, 'L', true);
    
    // Période selon le modèle
    $periode = '';
    if (!empty($data['date_debut']) && !empty($data['date_fin'])) {
        $periode = date('d/m/Y', strtotime($data['date_debut'])) . ' Au: ' . date('d/m/Y', strtotime($data['date_fin']));
    } else {
        $periode = date('d/m/Y') . ' Au: ' . date('d/m/Y');
    }
    $pdf->Cell(180, 8, 'Période du: ' . $periode, 1, 1, 'L', true);
    
    // Poids total selon le modèle
    $pdf->Cell(180, 8, 'Poids total: ' . number_format($totalGeneral, 0, ' ', ' ') . ' Kg', 1, 1, 'L', true);
    
    // Date de création selon le modèle
    $pdf->Cell(180, 8, 'Date de création: ' . date('d/m/Y H:i'), 1, 1, 'L', true);
    
    $pdf->Ln(10);
    
    // Tableau principal selon le modèle exact
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(200, 220, 255);
    $pdf->SetTextColor(0, 0, 0);
    
    // En-têtes du tableau avec colonne Agent - largeurs optimisées
    $pdf->Cell(22, 8, 'Date', 1, 0, 'C', true);
    $pdf->Cell(35, 8, 'Agent', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'N° Ticket', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Usine', 1, 0, 'C', true);
    $pdf->Cell(28, 8, 'Véhicule', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Poids (Kg)', 1, 1, 'C', true);
    
    // Données par usine avec sous-totaux selon le modèle
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetFillColor(255, 255, 255);
    
    foreach ($ticketsByUsine as $nomUsine => $ticketsUsine) {
        $sousTotal = 0;
        
        // Tickets de cette usine
        foreach ($ticketsUsine as $ticket) {
            // Récupérer le nom de l'agent pour ce ticket
            $agentNom = 'N/A';
            if (!empty($data['agents_info'])) {
                foreach ($data['agents_info'] as $agent) {
                    if ($agent['id_agent'] == $ticket['id_agent']) {
                        // Limiter la longueur du nom pour éviter les débordements
                        $nom = substr($agent['nom'], 0, 12);
                        $prenom = substr($agent['prenom'], 0, 1);
                        $agentNom = $nom . ' ' . $prenom . '.';
                        break;
                    }
                }
            }
            
            // Limiter les textes pour éviter les débordements
            $numeroTicket = strlen($ticket['numero_ticket']) > 18 ? 
                           substr($ticket['numero_ticket'], 0, 15) . '...' : 
                           $ticket['numero_ticket'];
            
            $usineNom = strlen($nomUsine) > 12 ? 
                       substr($nomUsine, 0, 9) . '...' : 
                       $nomUsine;
            
            $vehicule = $ticket['matricule_vehicule'] ?? 'NEANT';
            $vehicule = strlen($vehicule) > 10 ? 
                       substr($vehicule, 0, 7) . '...' : 
                       $vehicule;
            
            $pdf->Cell(22, 6, date('d/m/Y', strtotime($ticket['date_ticket'])), 1, 0, 'C');
            $pdf->Cell(35, 6, $agentNom, 1, 0, 'C');
            $pdf->Cell(40, 6, $numeroTicket, 1, 0, 'C');
            $pdf->Cell(30, 6, $usineNom, 1, 0, 'C');
            $pdf->Cell(28, 6, $vehicule, 1, 0, 'C');
            $pdf->Cell(25, 6, number_format($ticket['poids'], 0), 1, 1, 'R');
            
            $sousTotal += $ticket['poids'];
        }
        
        // Sous-total selon le modèle
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(155, 6, 'Sous-total ' . $nomUsine, 1, 0, 'R', true);
        $pdf->Cell(25, 6, number_format($sousTotal, 0), 1, 1, 'R', true);
        
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetFillColor(255, 255, 255);
    }
    
    // Total général selon le modèle (pas affiché dans l'exemple mais logique)
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetFillColor(180, 180, 180);
    $pdf->Cell(155, 8, 'TOTAL GÉNÉRAL', 1, 0, 'R', true);
    $pdf->Cell(25, 8, number_format($totalGeneral, 0), 1, 1, 'R', true);
    
    // Nettoyer les données de session
    unset($_SESSION['pdf_data']);
    
    // Sortie du PDF
    $pdf->Output('Bordereau_' . $numeroBordereau . '.pdf', 'I');
    
} catch (Exception $e) {
    header('Location: recherche_chef_equipe.php?error=' . urlencode($e->getMessage()));
    exit;
}
?>

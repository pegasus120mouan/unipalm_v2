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
    // Inclure les fichiers requis avec gestion d'erreurs
    require_once '../inc/functions/connexion.php';
    require_once '../inc/functions/requete/requete_chef_equipes.php';
    require_once '../inc/functions/requete/requete_agents.php';
    require_once '../inc/functions/requete/requete_usines.php';
    
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
            // Supprimer temporairement les erreurs pour TCPDF
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

// Vérifier si les données sont disponibles
if (!isset($_SESSION['pdf_data'])) {
    die("Erreur : Aucune donnée PDF trouvée dans la session. <a href='recherche_chef_equipe.php'>Retour</a>");
}

$data = $_SESSION['pdf_data'];
$tickets = $data['tickets'];

// Récupérer les informations du chef d'équipe
$chefInfo = null;
if (!empty($data['chef'])) {
    $chefInfo = $data['chef'];
} else {
    // Récupérer les infos du chef depuis les tickets (premier ticket contient les infos)
    if (!empty($tickets)) {
        // Essayer de récupérer depuis les données des agents
        $chefs = getChefEquipesFull($conn);
        foreach ($chefs as $chef) {
            // Chercher le chef correspondant aux agents sélectionnés
            foreach ($data['agents'] as $agentId) {
                $stmtAgent = $conn->prepare("SELECT id_chef FROM agents WHERE id_agent = ?");
                $stmtAgent->execute([$agentId]);
                $agent = $stmtAgent->fetch(PDO::FETCH_ASSOC);
                if ($agent && $agent['id_chef'] == $chef['id_chef']) {
                    $chefInfo = $chef;
                    break 2;
                }
            }
        }
    }
}

// Récupérer les informations de l'usine si spécifiée
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

// Générer un numéro de bordereau unique
$numeroBordereau = 'BORD-' . date('Ymd') . '-' . substr(md5(uniqid()), 0, 8);

// Créer le PDF
class BordereauPDF extends TCPDF {
    public function Header() {
        // Logo
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
$pdf->SetTitle('Bordereau de Déchargement - ' . $numeroBordereau);
$pdf->SetMargins(15, 30, 15);
$pdf->SetAutoPageBreak(TRUE, 25);

$pdf->AddPage();

// Titre du bordereau
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 10, 'BORDEREAU DE DÉCHARGEMENT N° ' . $numeroBordereau, 0, 1, 'C');
$pdf->Ln(5);

// Informations du bordereau
$pdf->SetFillColor(240, 240, 240);

// Tableau des informations
$pdf->Cell(180, 8, 'Informations du bordereau', 1, 1, 'C', true);

// Usine si spécifiée
if (!empty($data['usine_id']) && $usineInfo) {
    $pdf->Cell(180, 6, 'Usine filtrée: ' . $usineInfo['nom_usine'], 1, 1, 'L');
} else {
    $pdf->Cell(180, 6, 'Usine: Toutes les usines', 1, 1, 'L');
}

// Chef d'équipe
$chefName = $chefInfo ? $chefInfo['nom'] . ' ' . $chefInfo['prenoms'] : 'Non spécifié';
$pdf->Cell(180, 6, 'Chef d\'équipe: ' . $chefName, 1, 1, 'L');

// Agents sélectionnés
$agentsNames = [];
if (!empty($data['agents_info'])) {
    foreach ($data['agents_info'] as $agent) {
        $agentsNames[] = $agent['nom'] . ' ' . $agent['prenom'];
    }
}
$agentsText = !empty($agentsNames) ? implode(', ', $agentsNames) : 'Tous les agents';
$pdf->Cell(180, 6, 'Agents: ' . $agentsText, 1, 1, 'L');

// Période
$periode = '';
if (!empty($data['date_debut']) && !empty($data['date_fin'])) {
    $periode = date('d/m/Y', strtotime($data['date_debut'])) . ' Au: ' . date('d/m/Y', strtotime($data['date_fin']));
} elseif (!empty($data['date_debut'])) {
    $periode = 'À partir du: ' . date('d/m/Y', strtotime($data['date_debut']));
} elseif (!empty($data['date_fin'])) {
    $periode = 'Jusqu\'au: ' . date('d/m/Y', strtotime($data['date_fin']));
} else {
    $periode = 'Toutes les dates';
}
$pdf->Cell(180, 6, 'Période du: ' . $periode, 1, 1, 'L');

// Nombre de tickets et poids total
$nombreTickets = count($tickets);
$pdf->Cell(90, 6, 'Nombre de tickets: ' . $nombreTickets, 1, 0, 'L');
$pdf->Cell(90, 6, 'Poids total: ' . number_format($totalGeneral, 0, ',', ' ') . ' Kg', 1, 1, 'L');

// Date de création
$pdf->Cell(180, 6, 'Date de création: ' . date('d/m/Y H:i'), 1, 1, 'L');

$pdf->Ln(10);

// Tableau des tickets par usine
foreach ($ticketsByUsine as $nomUsine => $ticketsUsine) {
    $sousTotal = 0;
    
    // En-tête de l'usine
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(200, 220, 255);
    $pdf->Cell(180, 8, $nomUsine, 1, 1, 'C', true);
    
    // En-tête du tableau
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Cell(25, 6, 'Date', 1, 0, 'C', true);
    $pdf->Cell(30, 6, 'N° Ticket', 1, 0, 'C', true);
    $pdf->Cell(25, 6, 'Usine', 1, 0, 'C', true);
    $pdf->Cell(30, 6, 'Véhicule', 1, 0, 'C', true);
    $pdf->Cell(25, 6, 'Poids (Kg)', 1, 1, 'C', true);
    
    // Données des tickets
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(255, 255, 255);
    
    foreach ($ticketsUsine as $ticket) {
        $pdf->Cell(25, 5, date('d/m/Y', strtotime($ticket['date_ticket'])), 1, 0, 'C');
        $pdf->Cell(30, 5, $ticket['numero_ticket'], 1, 0, 'C');
        $pdf->Cell(25, 5, substr($ticket['nom_usine'], 0, 8), 1, 0, 'C');
        $pdf->Cell(30, 5, $ticket['matricule_vehicule'] ?? 'N/A', 1, 0, 'C');
        $pdf->Cell(25, 5, number_format($ticket['poids'], 0), 1, 1, 'R');
        
        $sousTotal += $ticket['poids'];
    }
    
    // Sous-total pour cette usine
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(220, 220, 220);
    $pdf->Cell(110, 6, 'Sous-total ' . $nomUsine, 1, 0, 'R', true);
    $pdf->Cell(25, 6, number_format($sousTotal, 0), 1, 1, 'R', true);
    
    $pdf->Ln(5);
}

// Résumé par agent si plusieurs agents sélectionnés
if (!empty($data['agents_info']) && count($data['agents_info']) > 1) {
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetFillColor(255, 235, 200);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(180, 8, 'RÉSUMÉ PAR AGENT', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(90, 6, 'Agent', 1, 0, 'C', true);
    $pdf->Cell(45, 6, 'Nb Tickets', 1, 0, 'C', true);
    $pdf->Cell(45, 6, 'Poids Total (Kg)', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(255, 255, 255);
    
    foreach ($data['agents_info'] as $agent) {
        $agentTickets = array_filter($tickets, function($ticket) use ($agent) {
            return $ticket['id_agent'] == $agent['id_agent'];
        });
        
        $agentPoids = array_sum(array_column($agentTickets, 'poids'));
        $agentNbTickets = count($agentTickets);
        
        $pdf->Cell(90, 5, $agent['nom'] . ' ' . $agent['prenom'], 1, 0, 'L');
        $pdf->Cell(45, 5, $agentNbTickets, 1, 0, 'C');
        $pdf->Cell(45, 5, number_format($agentPoids, 0), 1, 1, 'R');
    }
    $pdf->Ln(5);
}

// Total général
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(100, 150, 200);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(110, 8, 'TOTAL GÉNÉRAL', 1, 0, 'R', true);
$pdf->Cell(25, 8, number_format($totalGeneral, 0), 1, 1, 'R', true);

try {
    // Nettoyer les données de session
    unset($_SESSION['pdf_data']);
    
    // Sortie du PDF
    $pdf->Output('Bordereau_' . $numeroBordereau . '.pdf', 'I');
    
} catch (Exception $e) {
    // En cas d'erreur lors de la génération du PDF
    echo "<h2>Erreur lors de la génération du PDF</h2>";
    echo "<p>Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='recherche_chef_equipe.php'>Retour à la recherche</a></p>";
    
    // Log l'erreur
    error_log("Erreur PDF UniPalm : " . $e->getMessage());
}
?>

<?php
// Désactiver la mise en mémoire tampon de sortie
ob_clean();

require('../fpdf/fpdf.php');
require_once '../inc/functions/connexion.php';

if (isset($_GET['numero'])) {
    $numero_bordereau = $_GET['numero'];

    // Récupérer les informations du bordereau
    $sql_bordereau = "SELECT b.*, 
                     CONCAT(COALESCE(a.nom, ''), ' ', COALESCE(a.prenom, '')) AS nom_complet_agent
                     FROM bordereau b
                     INNER JOIN agents a ON b.id_agent = a.id_agent
                     WHERE b.numero_bordereau = :numero_bordereau";

    $stmt_bordereau = $conn->prepare($sql_bordereau);
    $stmt_bordereau->bindParam(':numero_bordereau', $numero_bordereau);
    $stmt_bordereau->execute();
    $bordereau = $stmt_bordereau->fetch(PDO::FETCH_ASSOC);

    if ($bordereau) {
        // Calculer le montant total du bordereau
        $sql_montant = "SELECT SUM(t.poids * t.prix_unitaire) as montant_total
                        FROM tickets t
                        WHERE t.numero_bordereau = :numero_bordereau";
        
        $stmt_montant = $conn->prepare($sql_montant);
        $stmt_montant->bindParam(':numero_bordereau', $numero_bordereau);
        $stmt_montant->execute();
        $result_montant = $stmt_montant->fetch(PDO::FETCH_ASSOC);
        $montant_total_bordereau = $result_montant['montant_total'] ?? 0;

        class PDF extends FPDF {
            function Header() {
                if (file_exists('../dist/img/logo.png')) {
                    $this->Image('../dist/img/logo.png', 10, 10, 30);
                }
                
                // Titre de l'entreprise en vert
                $this->SetTextColor(0, 128, 0);
                $this->SetFont('Arial', 'B', 16);
                $this->Cell(0, 10, 'UNIPALM COOP - CA', 0, 1, 'C');
                
                // Sous-titre en vert clair
                $this->SetTextColor(144, 238, 144);
                $this->SetFont('Arial', '', 11);
                $this->Cell(0, 5, iconv('UTF-8', 'windows-1252', 'Société Coopérative Agricole Unie pour le Palmier'), 0, 1, 'C');
                
                $this->Ln(15);
            }

            function Footer() {
                $this->SetY(-20);
                
                // Ligne verte
                $this->SetDrawColor(144, 238, 144);
                $this->Line(10, $this->GetY(), 200, $this->GetY());
                
                // Informations de contact en vert clair
                $this->SetTextColor(144, 238, 144);
                $this->SetFont('Arial', '', 8);
                $this->Cell(0, 10, iconv('UTF-8', 'windows-1252', 'Contact: +225 XX XX XX XX XX - Email: contact@unipalm.ci'), 0, 0, 'C');
            }
        }

        $pdf = new PDF();
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 35);

        // Titre du bordereau
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 10, iconv('UTF-8', 'windows-1252', 'BORDEREAU DE DÉCHARGEMENT N° ') . $bordereau['numero_bordereau'], 0, 1, 'C');
        $pdf->Ln(5);

        // Informations du bordereau dans un cadre
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(190, 7, 'Informations du bordereau', 1, 1, 'L');
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(190, 7, 'Agent: ' . iconv('UTF-8', 'windows-1252', $bordereau['nom_complet_agent']), 1, 1, 'L');
        $pdf->Cell(190, 7, iconv('UTF-8', 'windows-1252', 'Période du: ') . date('d/m/Y', strtotime($bordereau['date_debut'])) . ' Au: ' . date('d/m/Y', strtotime($bordereau['date_fin'])), 1, 1, 'L');
        $pdf->Cell(190, 7, 'Poids total: ' . number_format($bordereau['poids_total'], 0, ',', ' ') . ' Kg', 1, 1, 'L');
        $pdf->Cell(190, 7, 'Montant total: ' . number_format($montant_total_bordereau, 0, ',', ' ') . ' FCFA', 1, 1, 'L');
        $pdf->Cell(190, 7, iconv('UTF-8', 'windows-1252', 'Date de création: ') . date('d/m/Y H:i', strtotime($bordereau['created_at'])), 1, 1, 'L');
        
        $pdf->Ln(10);

        // En-têtes du tableau des tickets
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(197, 217, 241); // Bleu clair comme dans l'image
        $pdf->Cell(25, 7, 'Date', 1, 0, 'C', true);
        $pdf->Cell(35, 7, iconv('UTF-8', 'windows-1252', 'N° Ticket'), 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Usine', 1, 0, 'C', true);
        $pdf->Cell(30, 7, iconv('UTF-8', 'windows-1252', 'Véhicule'), 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Poids (Kg)', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Montant (FCFA)', 1, 1, 'C', true);

        // Récupérer les tickets associés au bordereau avec les prix unitaires
        $sql = "SELECT t.*, u.nom_usine, 
                v.matricule_vehicule, 
                v.type_vehicule,
                t.prix_unitaire,
                (t.poids * t.prix_unitaire) as montant_ticket
                FROM tickets t
                INNER JOIN usines u ON t.id_usine = u.id_usine
                INNER JOIN vehicules v ON t.vehicule_id = v.vehicules_id
                WHERE t.numero_bordereau = :numero_bordereau
                ORDER BY u.nom_usine, t.date_ticket, t.created_at";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':numero_bordereau', $numero_bordereau);
        $stmt->execute();
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Données du tableau
        $pdf->SetFont('Arial', '', 10);
        $total_poids = 0;
        $total_montant = 0;
        $current_usine = '';
        $sous_total_poids = 0;
        $sous_total_montant = 0;

        foreach ($tickets as $ticket) {
            if ($current_usine != $ticket['nom_usine'] && $current_usine != '') {
                // Sous-total
                $pdf->SetFont('Arial', 'I', 10);
                $pdf->Cell(125, 7, 'Sous-total ' . iconv('UTF-8', 'windows-1252', $current_usine), 1, 0, 'R');
                $pdf->Cell(30, 7, number_format($sous_total_poids, 0, ',', ' '), 1, 0, 'R');
                $pdf->Cell(35, 7, number_format($sous_total_montant, 0, ',', ' '), 1, 1, 'R');
                $sous_total_poids = 0;
                $sous_total_montant = 0;
                $pdf->SetFont('Arial', '', 10);
            }

            $current_usine = $ticket['nom_usine'];
            $poids = $ticket['poids'];
            $montant = $ticket['montant_ticket'];

            $pdf->Cell(25, 7, date('d/m/Y', strtotime($ticket['date_ticket'])), 1, 0, 'C');
            $pdf->Cell(35, 7, $ticket['numero_ticket'], 1, 0, 'C');
            $pdf->Cell(35, 7, iconv('UTF-8', 'windows-1252', $ticket['nom_usine']), 1, 0, 'L');
            $pdf->Cell(30, 7, iconv('UTF-8', 'windows-1252', $ticket['matricule_vehicule']), 1, 0, 'C');
            $pdf->Cell(30, 7, number_format($poids, 0, ',', ' '), 1, 0, 'R');
            $pdf->Cell(35, 7, number_format($montant, 0, ',', ' '), 1, 1, 'R');

            $total_poids += $poids;
            $total_montant += $montant;
            $sous_total_poids += $poids;
            $sous_total_montant += $montant;
        }

        // Dernier sous-total
        if ($current_usine != '') {
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->Cell(125, 7, 'Sous-total ' . iconv('UTF-8', 'windows-1252', $current_usine), 1, 0, 'R');
            $pdf->Cell(30, 7, number_format($sous_total_poids, 0, ',', ' '), 1, 0, 'R');
            $pdf->Cell(35, 7, number_format($sous_total_montant, 0, ',', ' '), 1, 1, 'R');
        }

        // Total général
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(197, 217, 241);
        $pdf->Cell(125, 7, 'TOTAL GENERAL', 1, 0, 'R', true);
        $pdf->Cell(30, 7, number_format($total_poids, 0, ',', ' '), 1, 0, 'R', true);
        $pdf->Cell(35, 7, number_format($total_montant, 0, ',', ' '), 1, 1, 'R', true);

        // Zone de signatures
        $pdf->Ln(20);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(95, 7, "Signature de l'agent:", 0, 0, 'L');
        $pdf->Cell(95, 7, "Signature du responsable:", 0, 1, 'L');
        
        $pdf->Cell(95, 20, '', 'B', 0, 'L');
        $pdf->Cell(95, 20, '', 'B', 1, 'L');

        $pdf->Output('I', 'Bordereau_' . $numero_bordereau . '.pdf');
    } else {
        die("Bordereau non trouvé");
    }
} else {
    die("Numéro de bordereau non spécifié");
}
?>

<?php
require_once('../inc/functions/connexion.php');

// Récupérer l'ID de l'agent depuis l'URL
$id_agent = isset($_GET['id_agent']) ? intval($_GET['id_agent']) : 0;

// Requête pour obtenir les détails des financements de l'agent
$query = "SELECT f.numero_financement, a.nom as nom_agent, DATE_FORMAT(f.date_financement, '%d/%m/%Y') as date_financement, 
          f.montant, f.motif 
          FROM financement f 
          JOIN agents a ON f.id_agent = a.id_agent
          WHERE f.id_agent = :id_agent
          ORDER BY f.date_financement DESC";

$stmt = $conn->prepare($query);
$stmt->execute(['id_agent' => $id_agent]);

// Récupérer le nom de l'agent
$nom_agent = "";
$financements = $stmt->fetchAll(PDO::FETCH_ASSOC);
if(count($financements) > 0) {
    $nom_agent = $financements[0]['nom_agent'];
}

// Calculer le total
$total = array_sum(array_column($financements, 'montant'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Détails des Financements - <?= htmlspecialchars($nom_agent) ?> | UniPalm</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --dark-color: #1f2937;
            --light-gray: #f8fafc;
            --medium-gray: #64748b;
            --border-color: #e2e8f0;
            --shadow-light: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-medium: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --border-radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            background: linear-gradient(135deg, #f0f9ff 0%, #e0e7ff 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .document-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-medium);
            overflow: hidden;
        }

        /* Header Section */
        .document-header {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .document-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: rotate(45deg);
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        .company-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .logo-container {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .logo-text {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: white;
        }

        .document-title {
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .document-title h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .document-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            font-weight: 500;
        }

        /* Info Section */
        .document-info {
            padding: 1.5rem 2rem;
            background: var(--light-gray);
            border-bottom: 1px solid var(--border-color);
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            align-items: center;
        }

        .agent-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .agent-avatar {
            width: 50px;
            height: 50px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .agent-details h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }

        .agent-details p {
            color: var(--medium-gray);
            font-size: 0.9rem;
        }

        .print-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--medium-gray);
            font-size: 0.9rem;
        }

        /* Table Section */
        .table-container {
            padding: 2rem;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .table-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .records-count {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .professional-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-light);
            margin-bottom: 2rem;
        }

        .professional-table thead th {
            background: var(--primary-gradient);
            color: white;
            padding: 1rem;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }

        .professional-table tbody tr {
            background: white;
            transition: all 0.2s ease;
        }

        .professional-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .professional-table tbody tr:hover {
            background: #e0e7ff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .professional-table tbody td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.9rem;
        }

        .professional-table tbody tr:last-child td {
            border-bottom: none;
        }

        .amount-cell {
            font-weight: 600;
            color: var(--success-color);
            text-align: right;
        }

        .date-cell {
            color: var(--medium-gray);
            font-weight: 500;
        }

        .motif-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Summary Section */
        .summary-section {
            background: var(--light-gray);
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--border-color);
        }

        .summary-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            text-align: center;
            border-left: 4px solid var(--success-color);
        }

        .summary-label {
            color: var(--medium-gray);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .summary-amount {
            font-size: 2rem;
            font-weight: 700;
            color: var(--success-color);
            margin-bottom: 0.5rem;
        }

        .summary-currency {
            color: var(--medium-gray);
            font-size: 0.9rem;
        }

        /* Action Buttons */
        .action-buttons {
            padding: 1.5rem 2rem;
            background: white;
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: var(--shadow-light);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .btn-secondary {
            background: white;
            color: var(--medium-gray);
            border: 2px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--light-gray);
            border-color: var(--medium-gray);
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .document-container {
                box-shadow: none;
                border-radius: 0;
            }
            
            .no-print {
                display: none !important;
            }
            
            .professional-table tbody tr:hover {
                background: inherit;
                transform: none;
                box-shadow: none;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .document-header {
                padding: 1.5rem;
            }
            
            .document-title h1 {
                font-size: 1.5rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .table-container {
                padding: 1rem;
                overflow-x: auto;
            }
            
            .professional-table {
                min-width: 600px;
            }
        }
    </style>
</head>
<body>
    <div class="document-container">
        <!-- Header Section -->
        <div class="document-header">
            <div class="header-content">
                <div class="company-logo">
                    <div class="logo-container">
                        <i class="fas fa-leaf" style="font-size: 2rem;"></i>
                    </div>
                    <div class="logo-text">UNIPALM</div>
                </div>
                <div class="document-title">
                    <h1><i class="fas fa-file-invoice-dollar mr-2"></i>Détails des Financements</h1>
                    <div class="document-subtitle">Rapport détaillé des transactions financières</div>
                </div>
            </div>
        </div>

        <!-- Info Section -->
        <div class="document-info">
            <div class="info-grid">
                <div class="agent-info">
                    <div class="agent-avatar">
                        <?= strtoupper(substr($nom_agent, 0, 2)) ?>
                    </div>
                    <div class="agent-details">
                        <h3><?= htmlspecialchars($nom_agent) ?></h3>
                        <p>Agent / Chargé de Mission</p>
                    </div>
                </div>
                <div class="print-date">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Généré le <?= date('d/m/Y à H:i') ?></span>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-container">
            <div class="table-header">
                <div class="table-title">
                    <i class="fas fa-list-alt"></i>
                    Historique des Financements
                </div>
                <div class="records-count">
                    <?= count($financements) ?> enregistrement<?= count($financements) > 1 ? 's' : '' ?>
                </div>
            </div>

            <?php if (!empty($financements)): ?>
                <table class="professional-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag mr-1"></i>N° Financement</th>
                            <th><i class="fas fa-user mr-1"></i>Agent</th>
                            <th><i class="fas fa-calendar mr-1"></i>Date</th>
                            <th><i class="fas fa-money-bill-wave mr-1"></i>Montant</th>
                            <th><i class="fas fa-comment mr-1"></i>Motif</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($financements as $index => $f): ?>
                        <tr>
                            <td>
                                <span style="font-weight: 600; color: var(--primary-color);">
                                    #<?= htmlspecialchars($f['numero_financement']) ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 30px; height: 30px; background: var(--primary-gradient); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.7rem; font-weight: 600;">
                                        <?= strtoupper(substr($f['nom_agent'], 0, 2)) ?>
                                    </div>
                                    <?= htmlspecialchars($f['nom_agent']) ?>
                                </div>
                            </td>
                            <td class="date-cell">
                                <i class="fas fa-calendar-day mr-1"></i>
                                <?= htmlspecialchars($f['date_financement']) ?>
                            </td>
                            <td class="amount-cell">
                                <strong><?= number_format($f['montant'], 0, ',', ' ') ?></strong>
                                <span style="font-size: 0.8rem; color: var(--medium-gray);"> FCFA</span>
                            </td>
                            <td class="motif-cell" title="<?= htmlspecialchars($f['motif']) ?>">
                                <?= htmlspecialchars($f['motif'] ?: 'Aucun motif spécifié') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: var(--medium-gray);">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <h3>Aucun financement trouvé</h3>
                    <p>Aucune transaction financière n'a été enregistrée pour cet agent.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Summary Section -->
        <div class="summary-section">
            <div class="summary-card">
                <div class="summary-label">
                    <i class="fas fa-calculator mr-1"></i>
                    Montant Total des Financements
                </div>
                <div class="summary-amount">
                    <?= number_format($total, 0, ',', ' ') ?>
                </div>
                <div class="summary-currency">
                    Francs CFA (FCFA)
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons no-print">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i>
                Imprimer le Rapport
            </button>
            <button class="btn btn-secondary" onclick="window.close()">
                <i class="fas fa-times"></i>
                Fermer
            </button>
        </div>
    </div>

    <script>
        // Configuration d'impression automatique
        window.onload = function() {
            // Délai pour permettre le chargement des styles
            setTimeout(() => {
                window.print();
            }, 500);
        }

        // Animation d'apparition des lignes du tableau
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.professional-table tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>

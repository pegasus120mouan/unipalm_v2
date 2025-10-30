<?php
require_once '../inc/functions/connexion.php';

// Script pour corriger automatiquement tous les conflits de prix unitaires
// Garde le prix le plus récent (ID le plus élevé) et supprime les anciens

function corrigerConflitsAutomatique($conn) {
    try {
        $conn->beginTransaction();
        
        // Trouver tous les conflits
        $sql_conflits = "SELECT 
                            p1.id as id_ancien, 
                            p1.id_usine, 
                            u.nom_usine,
                            p1.prix as prix_ancien, 
                            p1.date_debut as debut1, 
                            p1.date_fin as fin1,
                            p2.id as id_recent, 
                            p2.prix as prix_recent, 
                            p2.date_debut as debut2, 
                            p2.date_fin as fin2
                        FROM prix_unitaires p1
                        INNER JOIN prix_unitaires p2 ON p1.id_usine = p2.id_usine AND p1.id < p2.id
                        INNER JOIN usines u ON p1.id_usine = u.id_usine
                        WHERE (
                            -- Cas 1: Périodes exactement identiques (PRIORITÉ - comme VOP)
                            (p1.date_debut = p2.date_debut AND COALESCE(p1.date_fin, '9999-12-31') = COALESCE(p2.date_fin, '9999-12-31'))
                            OR
                            -- Cas 2: p1 commence pendant p2
                            (p1.date_debut BETWEEN p2.date_debut AND COALESCE(p2.date_fin, '9999-12-31'))
                            OR 
                            -- Cas 3: p1 se termine pendant p2
                            (p1.date_fin IS NOT NULL AND p1.date_fin BETWEEN p2.date_debut AND COALESCE(p2.date_fin, '9999-12-31'))
                            OR
                            -- Cas 4: p1 englobe p2
                            (p1.date_debut <= p2.date_debut AND (p1.date_fin IS NULL OR p1.date_fin >= COALESCE(p2.date_fin, p1.date_debut)))
                            OR
                            -- Cas 5: p2 englobe p1
                            (p2.date_debut <= p1.date_debut AND (p2.date_fin IS NULL OR p2.date_fin >= COALESCE(p1.date_fin, p1.date_debut)))
                        )
                        ORDER BY u.nom_usine, p1.date_debut";
        
        $stmt_conflits = $conn->prepare($sql_conflits);
        $stmt_conflits->execute();
        $conflits = $stmt_conflits->fetchAll(PDO::FETCH_ASSOC);
        
        $ids_a_supprimer = [];
        $corrections = [];
        
        foreach ($conflits as $conflit) {
            // Garder le plus récent (ID le plus élevé), supprimer l'ancien
            $ids_a_supprimer[] = $conflit['id_ancien'];
            $corrections[] = [
                'usine' => $conflit['nom_usine'],
                'prix_supprime' => $conflit['prix_ancien'],
                'prix_garde' => $conflit['prix_recent'],
                'periode_supprimee' => date('d/m/Y', strtotime($conflit['debut1'])) . ' - ' . 
                                     ($conflit['fin1'] ? date('d/m/Y', strtotime($conflit['fin1'])) : 'Période ouverte'),
                'periode_gardee' => date('d/m/Y', strtotime($conflit['debut2'])) . ' - ' . 
                                  ($conflit['fin2'] ? date('d/m/Y', strtotime($conflit['fin2'])) : 'Période ouverte')
            ];
        }
        
        // Supprimer les anciens prix en conflit
        if (!empty($ids_a_supprimer)) {
            $placeholders = str_repeat('?,', count($ids_a_supprimer) - 1) . '?';
            $sql_delete = "DELETE FROM prix_unitaires WHERE id IN ($placeholders)";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->execute($ids_a_supprimer);
        }
        
        $conn->commit();
        
        return [
            'success' => true,
            'corrections' => $corrections,
            'nb_suppressions' => count($ids_a_supprimer)
        ];
        
    } catch (PDOException $e) {
        $conn->rollBack();
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Exécuter la correction si demandée
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['corriger'])) {
    $result = corrigerConflitsAutomatique($conn);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correction Automatique des Conflits</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            padding-top: 2rem;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .card-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
        }
        
        .correction-item {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .btn-corriger {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 10px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        .btn-corriger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-magic me-2"></i>
                            Correction Automatique des Conflits
                        </h4>
                        <p class="mb-0 mt-2 opacity-75">
                            Corrige automatiquement tous les conflits en gardant le prix le plus récent.
                        </p>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($result): ?>
                            <?php if ($result['success']): ?>
                                <div class="alert alert-success">
                                    <h5><i class="fas fa-check-circle me-2"></i>Correction réussie !</h5>
                                    <p class="mb-0"><?= $result['nb_suppressions'] ?> ancien(s) prix supprimé(s) avec succès.</p>
                                </div>
                                
                                <?php if (!empty($result['corrections'])): ?>
                                    <h6 class="mt-4 mb-3">Détail des corrections :</h6>
                                    <?php foreach ($result['corrections'] as $correction): ?>
                                        <div class="correction-item">
                                            <strong><?= htmlspecialchars($correction['usine']) ?></strong><br>
                                            <small class="text-muted">
                                                ❌ Supprimé: <?= number_format($correction['prix_supprime'], 0, ',', ' ') ?> FCFA (<?= $correction['periode_supprimee'] ?>)<br>
                                                ✅ Conservé: <?= number_format($correction['prix_garde'], 0, ',', ' ') ?> FCFA (<?= $correction['periode_gardee'] ?>)
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    <h5><i class="fas fa-times-circle me-2"></i>Erreur</h5>
                                    <p class="mb-0"><?= htmlspecialchars($result['error']) ?></p>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center">
                                <i class="fas fa-tools fa-4x text-primary mb-3"></i>
                                <h5>Correction Automatique</h5>
                                <p class="text-muted mb-4">
                                    Cette action va automatiquement supprimer tous les anciens prix en conflit 
                                    et garder uniquement les prix les plus récents pour chaque usine.
                                </p>
                                
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Attention :</strong> Cette action est irréversible. 
                                    Assurez-vous d'avoir une sauvegarde de votre base de données.
                                </div>
                                
                                <form method="POST">
                                    <button type="submit" name="corriger" class="btn btn-corriger" 
                                            onclick="return confirm('Êtes-vous sûr de vouloir corriger automatiquement tous les conflits ? Cette action est irréversible.')">
                                        <i class="fas fa-magic me-2"></i>
                                        Corriger Automatiquement
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-4">
                            <a href="detect_conflits_prix.php" class="btn btn-outline-primary me-2">
                                <i class="fas fa-search me-2"></i>
                                Détecter les Conflits
                            </a>
                            <a href="prix_unitaires.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Retour aux Prix
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

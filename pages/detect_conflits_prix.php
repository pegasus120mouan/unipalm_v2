<?php
require_once '../inc/functions/connexion.php';
require_once '../inc/functions/requete/requete_usines.php';

session_start();

// Fonction pour détecter les conflits existants
function detectConflitsPrixExistants($conn) {
    try {
        $sql = "SELECT 
                    p1.id as id1, 
                    p1.id_usine, 
                    u.nom_usine,
                    p1.prix as prix1, 
                    p1.date_debut as debut1, 
                    p1.date_fin as fin1,
                    p2.id as id2, 
                    p2.prix as prix2, 
                    p2.date_debut as debut2, 
                    p2.date_fin as fin2
                FROM prix_unitaires p1
                INNER JOIN prix_unitaires p2 ON p1.id_usine = p2.id_usine AND p1.id < p2.id
                INNER JOIN usines u ON p1.id_usine = u.id_usine
                WHERE (
                    -- Cas 1: Périodes exactement identiques (PRIORITÉ)
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
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la détection des conflits: " . $e->getMessage());
        return false;
    }
}

// Fonction pour résoudre un conflit (supprimer l'ancien prix)
function resoudreConflit($conn, $id_a_supprimer) {
    try {
        $sql = "DELETE FROM prix_unitaires WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id_a_supprimer, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erreur lors de la résolution du conflit: " . $e->getMessage());
        return false;
    }
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'resoudre_conflit') {
        $id_a_supprimer = $_POST['id_a_supprimer'];
        
        if (resoudreConflit($conn, $id_a_supprimer)) {
            $_SESSION['success'] = "Conflit résolu avec succès. L'enregistrement en doublon a été supprimé.";
        } else {
            $_SESSION['error'] = "Erreur lors de la résolution du conflit.";
        }
        
        header('Location: detect_conflits_prix.php');
        exit();
    }
}

$conflits = detectConflitsPrixExistants($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détection des Conflits de Prix Unitaires</title>
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
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
        }
        
        .conflict-item {
            background: linear-gradient(135deg, #fff5f5, #ffe0e0);
            border: 1px solid #ffcdd2;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .price-comparison {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1rem 0;
        }
        
        .price-box {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            flex: 1;
            margin: 0 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .price-box.older {
            border-left: 4px solid #ffc107;
        }
        
        .price-box.newer {
            border-left: 4px solid #28a745;
        }
        
        .btn-resolve {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-resolve:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .no-conflicts {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .no-conflicts i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Détection des Conflits de Prix Unitaires
                        </h4>
                        <p class="mb-0 mt-2 opacity-75">
                            Cette page détecte les prix unitaires en conflit pour la même usine et la même période.
                        </p>
                    </div>
                    
                    <div class="card-body">
                        <!-- Messages d'erreur et de succès -->
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-times-circle me-2"></i>
                                <?= htmlspecialchars($_SESSION['error']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?= htmlspecialchars($_SESSION['success']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>
                        
                        <?php if ($conflits && count($conflits) > 0): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong><?= count($conflits) ?> conflit(s) détecté(s)</strong> dans la base de données.
                                
                                <div class="mt-3">
                                    <a href="corriger_conflits_automatique.php" class="btn btn-success btn-sm">
                                        <i class="fas fa-magic me-2"></i>
                                        Corriger Automatiquement Tous les Conflits
                                    </a>
                                </div>
                            </div>
                            
                            <?php foreach ($conflits as $conflit): ?>
                                <div class="conflict-item">
                                    <h5 class="text-danger">
                                        <i class="fas fa-industry me-2"></i>
                                        Conflit pour l'usine : <?= htmlspecialchars($conflit['nom_usine']) ?>
                                    </h5>
                                    
                                    <div class="price-comparison">
                                        <div class="price-box older">
                                            <div class="text-muted small">ANCIEN PRIX (ID: <?= $conflit['id1'] ?>)</div>
                                            <div class="h4 text-warning mb-1"><?= number_format($conflit['prix1'], 0, ',', ' ') ?> FCFA</div>
                                            <div class="small">
                                                <?= date('d/m/Y', strtotime($conflit['debut1'])) ?> - 
                                                <?= $conflit['fin1'] ? date('d/m/Y', strtotime($conflit['fin1'])) : 'Période ouverte' ?>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center">
                                            <i class="fas fa-arrows-alt-h text-danger fa-2x"></i>
                                            <div class="small text-danger mt-1">CONFLIT</div>
                                        </div>
                                        
                                        <div class="price-box newer">
                                            <div class="text-muted small">NOUVEAU PRIX (ID: <?= $conflit['id2'] ?>)</div>
                                            <div class="h4 text-success mb-1"><?= number_format($conflit['prix2'], 0, ',', ' ') ?> FCFA</div>
                                            <div class="small">
                                                <?= date('d/m/Y', strtotime($conflit['debut2'])) ?> - 
                                                <?= $conflit['fin2'] ? date('d/m/Y', strtotime($conflit['fin2'])) : 'Période ouverte' ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-center mt-3">
                                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer l\'ancien prix (<?= number_format($conflit['prix1'], 0, ',', ' ') ?> FCFA) ?')">
                                            <input type="hidden" name="action" value="resoudre_conflit">
                                            <input type="hidden" name="id_a_supprimer" value="<?= $conflit['id1'] ?>">
                                            <button type="submit" class="btn btn-resolve">
                                                <i class="fas fa-trash-alt me-2"></i>
                                                Supprimer l'ancien prix
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline-block; margin-left: 10px;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer le nouveau prix (<?= number_format($conflit['prix2'], 0, ',', ' ') ?> FCFA) ?')">
                                            <input type="hidden" name="action" value="resoudre_conflit">
                                            <input type="hidden" name="id_a_supprimer" value="<?= $conflit['id2'] ?>">
                                            <button type="submit" class="btn btn-outline-danger">
                                                <i class="fas fa-trash-alt me-2"></i>
                                                Supprimer le nouveau prix
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                        <?php else: ?>
                            <div class="no-conflicts">
                                <i class="fas fa-check-circle"></i>
                                <h4>Aucun conflit détecté</h4>
                                <p class="text-muted">Tous les prix unitaires respectent les règles de non-chevauchement des périodes.</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-4">
                            <a href="prix_unitaires.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Retour aux Prix Unitaires
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

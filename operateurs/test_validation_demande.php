<?php
// Test de validation des demandes - Diagnostic
session_start();
require_once '../inc/functions/connexion.php';

// Simuler une session utilisateur pour le test
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // ID utilisateur de test
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Validation Demande - UniPalm</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
</head>
<body class='bg-light'>
<div class='container mt-5'>
    <h2><i class='fas fa-bug text-danger'></i> Test de Validation des Demandes</h2>";

// Test 1: Vérifier la structure de la base de données
echo "<div class='card mt-4'>
        <div class='card-header bg-primary text-white'>
            <h5><i class='fas fa-database'></i> Test 1: Structure de la base de données</h5>
        </div>
        <div class='card-body'>";

try {
    $stmt = $conn->query("DESCRIBE demande_sortie");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='alert alert-success'><i class='fas fa-check'></i> Table demande_sortie trouvée</div>";
    echo "<table class='table table-sm'>";
    echo "<thead><tr><th>Colonne</th><th>Type</th><th>Null</th><th>Défaut</th></tr></thead><tbody>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</tbody></table>";
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> Erreur: " . $e->getMessage() . "</div>";
}

echo "</div></div>";

// Test 2: Vérifier les demandes en attente
echo "<div class='card mt-4'>
        <div class='card-header bg-info text-white'>
            <h5><i class='fas fa-clock'></i> Test 2: Demandes en attente</h5>
        </div>
        <div class='card-body'>";

try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM demande_sortie WHERE date_approbation IS NULL");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i> {$result['total']} demande(s) en attente</div>";
    
    if ($result['total'] > 0) {
        $stmt = $conn->query("SELECT id_demande, numero_demande, montant, motif, statut FROM demande_sortie WHERE date_approbation IS NULL LIMIT 5");
        $demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table class='table table-striped'>";
        echo "<thead><tr><th>ID</th><th>Numéro</th><th>Montant</th><th>Motif</th><th>Statut</th><th>Action</th></tr></thead><tbody>";
        foreach ($demandes as $demande) {
            echo "<tr>
                    <td>{$demande['id_demande']}</td>
                    <td>{$demande['numero_demande']}</td>
                    <td>" . number_format($demande['montant'], 0, ',', ' ') . " FCFA</td>
                    <td>" . substr($demande['motif'], 0, 50) . "...</td>
                    <td><span class='badge bg-warning'>{$demande['statut']}</span></td>
                    <td><button class='btn btn-sm btn-success btn-test-valider' data-id='{$demande['id_demande']}'>
                        <i class='fas fa-check'></i> Tester Validation
                    </button></td>
                  </tr>";
        }
        echo "</tbody></table>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> Erreur: " . $e->getMessage() . "</div>";
}

echo "</div></div>";

// Test 3: Test de la session utilisateur
echo "<div class='card mt-4'>
        <div class='card-header bg-warning text-dark'>
            <h5><i class='fas fa-user'></i> Test 3: Session utilisateur</h5>
        </div>
        <div class='card-body'>";

if (isset($_SESSION['user_id'])) {
    echo "<div class='alert alert-success'><i class='fas fa-check'></i> Session utilisateur active: ID = {$_SESSION['user_id']}</div>";
} else {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> Aucune session utilisateur active</div>";
}

echo "</div></div>";

// Zone de résultats des tests
echo "<div class='card mt-4'>
        <div class='card-header bg-secondary text-white'>
            <h5><i class='fas fa-flask'></i> Résultats des tests</h5>
        </div>
        <div class='card-body'>
            <div id='test-results'></div>
        </div>
      </div>";

echo "</div>

<script>
$(document).ready(function() {
    console.log('Test page loaded');
    
    // Test de validation
    $(document).on('click', '.btn-test-valider', function(e) {
        e.preventDefault();
        
        var id_demande = $(this).data('id');
        var \$btn = $(this);
        var \$results = $('#test-results');
        
        \$btn.prop('disabled', true).html('<i class=\"fas fa-spinner fa-spin\"></i> Test...');
        
        \$results.append('<div class=\"alert alert-info\"><i class=\"fas fa-play\"></i> Test de validation directe (sans confirmation) pour la demande ID: ' + id_demande + '</div>');
        
        $.ajax({
            url: 'valider_demande.php',
            type: 'POST',
            data: { id_demande: id_demande },
            dataType: 'json',
            timeout: 10000,
            success: function(response) {
                console.log('Réponse:', response);
                if (response.success) {
                    \$results.append('<div class=\"alert alert-success\"><i class=\"fas fa-check\"></i> <strong>SUCCÈS:</strong> ' + response.message + '</div>');
                    // Recharger la page après 2 secondes
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    \$results.append('<div class=\"alert alert-danger\"><i class=\"fas fa-times\"></i> <strong>ÉCHEC:</strong> ' + response.message + '</div>');
                    \$btn.prop('disabled', false).html('<i class=\"fas fa-check\"></i> Tester Validation');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });
                
                var errorMsg = 'Erreur AJAX: ' + status;
                if (xhr.responseText) {
                    errorMsg += ' - Réponse: ' + xhr.responseText.substring(0, 200);
                }
                
                \$results.append('<div class=\"alert alert-danger\"><i class=\"fas fa-exclamation-triangle\"></i> <strong>ERREUR TECHNIQUE:</strong> ' + errorMsg + '</div>');
                \$btn.prop('disabled', false).html('<i class=\"fas fa-check\"></i> Tester Validation');
            }
        });
    });
});
</script>

</body>
</html>";
?>

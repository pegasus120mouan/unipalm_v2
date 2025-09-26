<?php
// Debug de la validation des demandes
session_start();
require_once '../inc/functions/connexion.php';

// Simuler une session utilisateur si nécessaire
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Debug Validation - UniPalm</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h2><i class='fas fa-bug text-danger'></i> Debug Validation des Demandes</h2>
    
    <div class='row mt-4'>
        <div class='col-md-6'>
            <div class='card'>
                <div class='card-header bg-primary text-white'>
                    <h5><i class='fas fa-list'></i> Demandes en attente</h5>
                </div>
                <div class='card-body'>";

// Récupérer une demande en attente pour test
try {
    $stmt = $conn->query("SELECT * FROM demande_sortie WHERE date_approbation IS NULL LIMIT 1");
    $demande = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($demande) {
        echo "<div class='alert alert-info'>
                <strong>Demande trouvée:</strong><br>
                ID: {$demande['id_demande']}<br>
                Numéro: {$demande['numero_demande']}<br>
                Montant: " . number_format($demande['montant'], 0, ',', ' ') . " FCFA<br>
                Statut: {$demande['statut']}
              </div>";
        
        echo "<button type='button' class='btn btn-danger btn-valider' data-id='{$demande['id_demande']}'>
                <i class='fas fa-check'></i> Valider (Test)
              </button>";
    } else {
        echo "<div class='alert alert-warning'>Aucune demande en attente trouvée</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Erreur: " . $e->getMessage() . "</div>";
}

echo "        </div>
            </div>
        </div>
        
        <div class='col-md-6'>
            <div class='card'>
                <div class='card-header bg-success text-white'>
                    <h5><i class='fas fa-terminal'></i> Console de debug</h5>
                </div>
                <div class='card-body'>
                    <div id='debug-console' style='background: #000; color: #0f0; padding: 10px; border-radius: 5px; font-family: monospace; height: 300px; overflow-y: auto;'>
                        <div>Console de debug initialisée...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class='row mt-4'>
        <div class='col-12'>
            <div class='card'>
                <div class='card-header bg-warning text-dark'>
                    <h5><i class='fas fa-tools'></i> Tests manuels</h5>
                </div>
                <div class='card-body'>
                    <button class='btn btn-primary me-2' onclick='testJQuery()'>Test jQuery</button>
                    <button class='btn btn-info me-2' onclick='testAjax()'>Test AJAX</button>
                    <button class='btn btn-secondary me-2' onclick='testSession()'>Test Session</button>
                    <button class='btn btn-warning' onclick='clearConsole()'>Clear Console</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function log(message) {
    const console = document.getElementById('debug-console');
    const time = new Date().toLocaleTimeString();
    console.innerHTML += '<div>[' + time + '] ' + message + '</div>';
    console.scrollTop = console.scrollHeight;
}

function clearConsole() {
    document.getElementById('debug-console').innerHTML = '<div>Console effacée...</div>';
}

function testJQuery() {
    if (typeof $ !== 'undefined') {
        log('✅ jQuery chargé - Version: ' + $.fn.jquery);
    } else {
        log('❌ jQuery non chargé');
    }
}

function testAjax() {
    log('🔄 Test AJAX vers valider_demande.php...');
    $.ajax({
        url: 'valider_demande.php',
        type: 'GET',
        timeout: 5000,
        success: function(response) {
            log('✅ Connexion AJAX OK - Réponse: ' + JSON.stringify(response).substring(0, 100));
        },
        error: function(xhr, status, error) {
            log('❌ Erreur AJAX: ' + status + ' - ' + error);
            log('Response: ' + xhr.responseText.substring(0, 100));
        }
    });
}

function testSession() {
    log('🔄 Test session utilisateur...');
    $.ajax({
        url: 'valider_demande.php',
        type: 'POST',
        data: { test_session: true },
        dataType: 'json',
        success: function(response) {
            log('✅ Session OK: ' + JSON.stringify(response));
        },
        error: function(xhr, status, error) {
            log('❌ Erreur session: ' + status);
        }
    });
}

$(document).ready(function() {
    log('📱 Document ready');
    testJQuery();
    
    // Test du sélecteur
    const buttons = $('.btn-valider');
    log('🔍 Boutons .btn-valider trouvés: ' + buttons.length);
    
    // Attacher l'événement avec debug
    $(document).on('click', '.btn-valider', function(e) {
        e.preventDefault();
        log('🖱️ Clic détecté sur bouton valider');
        
        const id_demande = $(this).data('id');
        log('📋 ID demande: ' + id_demande);
        
        const \$btn = $(this);
        \$btn.prop('disabled', true).html('<i class=\"fas fa-spinner fa-spin\"></i> Validation...');
        log('🔄 Bouton désactivé, début AJAX...');
        
        $.ajax({
            url: 'valider_demande.php',
            type: 'POST',
            data: { id_demande: id_demande },
            dataType: 'json',
            timeout: 10000,
            success: function(response) {
                log('✅ Réponse reçue: ' + JSON.stringify(response));
                if (response.success) {
                    log('🎉 Validation réussie!');
                    alert('Validation réussie: ' + response.message);
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    log('❌ Échec validation: ' + response.message);
                    alert('Erreur: ' + response.message);
                    \$btn.prop('disabled', false).html('Valider');
                }
            },
            error: function(xhr, status, error) {
                log('❌ Erreur AJAX: ' + status + ' - ' + error);
                log('Response text: ' + xhr.responseText.substring(0, 200));
                alert('Erreur technique: ' + status);
                \$btn.prop('disabled', false).html('Valider');
            }
        });
    });
    
    log('✅ Event listener attaché');
});
</script>

</body>
</html>";
?>

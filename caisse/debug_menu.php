<?php
require_once '../inc/functions/connexion.php';
session_start();

// Simuler une session utilisateur pour le test
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'caissiere';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Menu Caisse</title>
    
    <!-- CSS AdminLTE -->
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    
    <!-- Scripts -->
    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/adminlte.min.js"></script>
    
    <style>
        body {
            background: #343a40;
            color: white;
        }
        
        .debug-container {
            max-width: 400px;
            margin: 50px auto;
            background: #495057;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .debug-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            padding: 20px;
            text-align: center;
            color: white;
        }
        
        .debug-content {
            padding: 20px;
        }
        
        .nav-sidebar .nav-item .nav-treeview {
            display: none;
            padding-left: 1rem;
            background: rgba(0,0,0,0.1);
            border-radius: 8px;
            margin-top: 5px;
        }
        
        .nav-sidebar .nav-item.menu-open .nav-treeview {
            display: block;
        }
        
        .nav-sidebar .nav-item > a {
            cursor: pointer;
            transition: all 0.3s ease;
            color: rgba(255, 255, 255, 0.9) !important;
            border-radius: 8px;
            margin: 2px 0;
        }
        
        .nav-sidebar .nav-item > a:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: white !important;
        }
        
        .nav-sidebar .nav-item.menu-open > a {
            background-color: rgba(255, 255, 255, 0.15) !important;
        }
        
        .nav-sidebar .nav-item > a .right {
            transition: transform 0.3s ease;
        }
        
        .nav-sidebar .nav-item.menu-open > a .right {
            transform: rotate(-90deg);
        }
        
        .nav-treeview .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            padding: 8px 15px !important;
            font-size: 0.9rem !important;
        }
        
        .nav-treeview .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: white !important;
        }
        
        .debug-log {
            background: #212529;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .log-entry {
            margin: 2px 0;
            padding: 2px 5px;
            border-radius: 3px;
        }
        
        .log-info { background: rgba(23, 162, 184, 0.2); color: #17a2b8; }
        .log-success { background: rgba(40, 167, 69, 0.2); color: #28a745; }
        .log-warning { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
        .log-error { background: rgba(220, 53, 69, 0.2); color: #dc3545; }
    </style>
</head>
<body>
    <div class="debug-container">
        <div class="debug-header">
            <h3><i class="fas fa-bug"></i> Debug Menu Caisse</h3>
            <small>Diagnostic des menus déroulants</small>
        </div>
        
        <div class="debug-content">
            <!-- Menu de test -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    
                    <li class="nav-item">
                        <a href="javascript:void(0)" class="nav-link">
                            <i class="nav-icon fas fa-table"></i>
                            <p>
                                Listes des utilisateurs
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-male"></i>
                                    <p>Listes des utilisateurs</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-user-tie"></i>
                                    <p>Listes des admins</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a href="javascript:void(0)" class="nav-link">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>
                                Gestion
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-users"></i>
                                    <p>Gestion chef equipe</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-user-plus"></i>
                                    <p>Gestion des agents</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a href="javascript:void(0)" class="nav-link">
                            <i class="nav-icon fas fa-money-bill-alt"></i>
                            <p>
                                Gestion financière
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-tags"></i>
                                    <p>Prix unitaires</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="fas fa-receipt"></i>
                                    <p>Reçus</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                </ul>
            </nav>
            
            <!-- Log de débogage -->
            <div class="debug-log" id="debugLog">
                <div class="log-entry log-info">🚀 Initialisation du debug...</div>
            </div>
        </div>
    </div>

    <script>
    function addLog(message, type = 'info') {
        const log = document.getElementById('debugLog');
        const entry = document.createElement('div');
        entry.className = `log-entry log-${type}`;
        entry.innerHTML = `${new Date().toLocaleTimeString()} - ${message}`;
        log.appendChild(entry);
        log.scrollTop = log.scrollHeight;
    }

    $(document).ready(function() {
        addLog('✅ jQuery chargé: ' + (typeof $ !== 'undefined'), 'success');
        addLog('✅ AdminLTE chargé: ' + (typeof $.AdminLTE !== 'undefined'), 'success');
        addLog('📊 Nombre de menus trouvés: ' + $('.nav-sidebar .nav-item').length, 'info');
        addLog('📁 Nombre de sous-menus trouvés: ' + $('.nav-treeview').length, 'info');
        
        // Test de sélecteurs
        addLog('🔍 Test sélecteur .nav-sidebar: ' + $('.nav-sidebar').length, 'info');
        addLog('🔍 Test sélecteur .nav-item > a: ' + $('.nav-sidebar .nav-item > a').length, 'info');
        
        // Gestion des menus déroulants
        $('.nav-sidebar .nav-item > a').on('click', function(e) {
            var $this = $(this);
            var $parent = $this.parent('.nav-item');
            var $submenu = $this.next('.nav-treeview');
            var menuText = $this.find('p').first().text().trim();
            
            addLog('🖱️ Clic sur: ' + menuText, 'info');
            addLog('📁 Sous-menu trouvé: ' + ($submenu.length > 0), 'info');
            addLog('🔗 Href: ' + $this.attr('href'), 'info');
            
            // Si c'est un lien avec sous-menu
            if ($submenu.length > 0 && $this.attr('href') === 'javascript:void(0)') {
                e.preventDefault();
                addLog('🛑 Événement preventDefault() appelé', 'warning');
                
                // Fermer les autres menus ouverts
                var otherMenus = $('.nav-sidebar .nav-item.menu-open').not($parent);
                if (otherMenus.length > 0) {
                    addLog('🔒 Fermeture de ' + otherMenus.length + ' autre(s) menu(s)', 'warning');
                    otherMenus.each(function() {
                        $(this).removeClass('menu-open');
                        $(this).find('.nav-treeview').slideUp(300);
                    });
                }
                
                // Toggle du menu actuel
                if ($parent.hasClass('menu-open')) {
                    addLog('📤 Fermeture du menu: ' + menuText, 'warning');
                    $parent.removeClass('menu-open');
                    $submenu.slideUp(300);
                } else {
                    addLog('📥 Ouverture du menu: ' + menuText, 'success');
                    $parent.addClass('menu-open');
                    $submenu.slideDown(300);
                }
            } else {
                addLog('❌ Conditions non remplies pour le menu déroulant', 'error');
            }
        });
        
        // Test automatique après 3 secondes
        setTimeout(function() {
            addLog('🤖 Test automatique - clic sur le premier menu', 'info');
            $('.nav-sidebar .nav-item:first > a').trigger('click');
        }, 3000);
        
        // Test des erreurs JavaScript
        window.addEventListener('error', function(e) {
            addLog('❌ Erreur JS: ' + e.message, 'error');
        });
        
        addLog('✅ Initialisation terminée', 'success');
    });
    </script>
</body>
</html>

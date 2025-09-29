<?php
echo "<h2>🗑️ Nettoyage des Fichiers Ponts-Bascules</h2>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .warning{color:orange;}</style>";

// Liste des fichiers à supprimer
$files_to_delete = [
    // Pages principales
    'c:\laragon\www\unipalm\pages\ponts.php',
    'c:\laragon\www\unipalm\pages\geolocalisation_ponts.php',
    
    // Scripts utilitaires
    'c:\laragon\www\unipalm\pages\update_ponts_structure.php',
    'c:\laragon\www\unipalm\pages\test_pont_modification.php',
    'c:\laragon\www\unipalm\pages\upgrade_codes_ponts.php',
    'c:\laragon\www\unipalm\pages\ponts_clean.php',
    
    // Fonctions
    'c:\laragon\www\unipalm\inc\functions\requete\requete_ponts.php',
    
    // Ce script lui-même (à la fin)
    'c:\laragon\www\unipalm\pages\cleanup_ponts.php'
];

echo "<h3>📋 Fichiers à supprimer :</h3>";
echo "<ul>";
foreach ($files_to_delete as $file) {
    echo "<li>" . basename($file) . " - <small>" . $file . "</small></li>";
}
echo "</ul>";

echo "<hr>";

// Fonction pour supprimer un fichier
function deleteFile($filepath) {
    if (file_exists($filepath)) {
        if (unlink($filepath)) {
            echo "<div class='success'>✅ Supprimé: " . basename($filepath) . "</div>";
            return true;
        } else {
            echo "<div class='error'>❌ Erreur suppression: " . basename($filepath) . "</div>";
            return false;
        }
    } else {
        echo "<div class='warning'>⚠️ Fichier inexistant: " . basename($filepath) . "</div>";
        return false;
    }
}

// Confirmation de sécurité
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    echo "<h3>🚀 Suppression en cours...</h3>";
    
    $deleted_count = 0;
    $total_files = count($files_to_delete);
    
    // Supprimer tous les fichiers sauf ce script
    for ($i = 0; $i < $total_files - 1; $i++) {
        if (deleteFile($files_to_delete[$i])) {
            $deleted_count++;
        }
    }
    
    echo "<hr>";
    echo "<div class='info'>📊 Résumé: {$deleted_count} fichiers supprimés sur " . ($total_files - 1) . "</div>";
    
    // Vérification de la table de base de données
    echo "<h3>🗄️ Vérification de la base de données :</h3>";
    
    try {
        require_once '../inc/functions/connexion.php';
        
        // Vérifier si la table pont_bascule existe
        $stmt = $conn->query("SHOW TABLES LIKE 'pont_bascule'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='warning'>⚠️ La table 'pont_bascule' existe encore dans la base de données</div>";
            echo "<div class='info'>💡 Pour la supprimer complètement, exécutez: DROP TABLE pont_bascule;</div>";
            
            // Compter les enregistrements
            $stmt = $conn->query("SELECT COUNT(*) as count FROM pont_bascule");
            $result = $stmt->fetch();
            echo "<div class='info'>📊 Nombre d'enregistrements: " . $result['count'] . "</div>";
            
            echo "<br><a href='?confirm=yes&drop_table=yes' style='background:#dc3545;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;' onclick='return confirm(\"Êtes-vous sûr de vouloir supprimer la table pont_bascule ?\")'>🗑️ Supprimer aussi la table</a>";
        } else {
            echo "<div class='success'>✅ La table 'pont_bascule' n'existe pas</div>";
        }
        
        // Supprimer la table si demandé
        if (isset($_GET['drop_table']) && $_GET['drop_table'] === 'yes') {
            $conn->exec("DROP TABLE IF EXISTS pont_bascule");
            echo "<div class='success'>✅ Table 'pont_bascule' supprimée</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erreur base de données: " . $e->getMessage() . "</div>";
    }
    
    echo "<hr>";
    echo "<h3>🎯 Nettoyage terminé !</h3>";
    echo "<div class='success'>✅ Tous les fichiers liés aux ponts-bascules ont été supprimés</div>";
    
    // Auto-suppression de ce script après 5 secondes
    echo "<div class='info'>🕒 Ce script va s'auto-supprimer dans 5 secondes...</div>";
    echo "<script>
        setTimeout(function() {
            window.location.href = '?confirm=yes&self_delete=yes';
        }, 5000);
    </script>";
    
    // Auto-suppression
    if (isset($_GET['self_delete']) && $_GET['self_delete'] === 'yes') {
        deleteFile(__FILE__);
        echo "<div class='success'>✅ Script de nettoyage auto-supprimé</div>";
        echo "<br><a href='../index.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🏠 Retour à l'accueil</a>";
    }
    
} else {
    // Page de confirmation
    echo "<div class='warning'>⚠️ <strong>ATTENTION :</strong> Cette action va supprimer définitivement tous les fichiers liés aux ponts-bascules !</div>";
    echo "<br>";
    echo "<div class='info'>📋 Fichiers qui seront supprimés :</div>";
    echo "<ul>";
    echo "<li><strong>ponts.php</strong> - Page principale de gestion</li>";
    echo "<li><strong>geolocalisation_ponts.php</strong> - Carte interactive</li>";
    echo "<li><strong>requete_ponts.php</strong> - Fonctions de base de données</li>";
    echo "<li><strong>Scripts utilitaires</strong> - Tous les fichiers de test et mise à jour</li>";
    echo "</ul>";
    
    echo "<br>";
    echo "<div class='error'>🚨 Cette action est IRRÉVERSIBLE !</div>";
    echo "<br>";
    
    echo "<a href='?confirm=yes' style='background:#dc3545;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;font-weight:bold;' onclick='return confirm(\"Êtes-vous ABSOLUMENT sûr de vouloir supprimer tous les fichiers ponts-bascules ?\")'>🗑️ CONFIRMER LA SUPPRESSION</a>";
    echo "&nbsp;&nbsp;";
    echo "<a href='ponts.php' style='background:#6c757d;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;'>❌ Annuler</a>";
}
?>

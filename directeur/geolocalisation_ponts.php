<?php
include('header.php'); // Inclure l'en-tête contenant la connexion à la base de données
require_once '../inc/functions/connexion.php';

// Récupérer les ponts-bascules de la base de données avec PDO
$sql = "SELECT 
    id_pont,
    code_pont,
    nom_pont,
    latitude,
    longitude,
    gerant,
    cooperatif,
    statut
FROM pont_bascule 
ORDER BY code_pont ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();

// Récupérer les résultats
$ponts = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $ponts[] = [
        'id_pont' => $row['id_pont'],
        'code_pont' => $row['code_pont'],
        'nom_pont' => $row['nom_pont'] ?: 'Non défini',
        'latitude' => (float)$row['latitude'],
        'longitude' => (float)$row['longitude'],
        'gerant' => $row['gerant'],
        'cooperatif' => $row['cooperatif'] ?: 'Non spécifiée',
        'statut' => $row['statut']
    ];
}

// Statistiques
$total_ponts = count($ponts);
$ponts_actifs = count(array_filter($ponts, function($pont) { return $pont['statut'] === 'Actif'; }));
$ponts_inactifs = $total_ponts - $ponts_actifs;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Géolocalisation des Ponts-Bascules - UniPalm</title>
    <meta name="viewport" initial-scale=1,maximum-scale=1,user-scalable=no">
    <link href="https://api.mapbox.com/mapbox-gl-js/v3.8.0/mapbox-gl.css" rel="stylesheet">
    <script src="https://api.mapbox.com/mapbox-gl-js/v3.8.0/mapbox-gl.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', Arial, sans-serif;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 600;
        }
        
        .controls-container {
            background: white;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .btn-info {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .stats-container {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .stat-badge {
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            color: #495057;
        }
        
        #map-container {
            position: relative;
            width: 100%;
            height: calc(100vh - 160px);
        }
        
        #map {
            width: 100%;
            height: 100%;
        }
        
        .mapboxgl-popup-content {
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .popup-header {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }
        
        .popup-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .popup-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .status-active {
            color: #28a745;
            font-weight: 600;
        }
        
        .status-inactive {
            color: #dc3545;
            font-weight: 600;
        }
        
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 25px;
            padding: 10px 20px;
            text-decoration: none;
            color: #495057;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-decoration: none;
            color: #495057;
        }
        
        /* Indicateur de chargement */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            transition: opacity 0.5s ease;
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-text {
            color: #495057;
            font-size: 1.1rem;
            font-weight: 500;
            text-align: center;
        }
        
        .loading-progress {
            width: 200px;
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            margin-top: 15px;
            overflow: hidden;
        }
        
        .loading-progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 2px;
            animation: progress 3s ease-in-out;
        }
        
        @keyframes progress {
            0% { width: 0%; }
            30% { width: 30%; }
            60% { width: 70%; }
            90% { width: 95%; }
            100% { width: 100%; }
        }
        
        /* Curseur personnalisé pour la carte */
        #map {
            cursor: grab;
        }
        
        #map:active {
            cursor: grabbing;
        }
        
        .mapboxgl-canvas {
            cursor: inherit;
        }
        
        /* Animation d'apparition des marqueurs */
        .custom-marker {
            animation: markerAppear 0.6s ease-out;
        }
        
        @keyframes markerAppear {
            0% {
                transform: rotate(-45deg) scale(0) translateY(-20px);
                opacity: 0;
            }
            50% {
                transform: rotate(-45deg) scale(1.2) translateY(-10px);
                opacity: 0.7;
            }
            100% {
                transform: rotate(-45deg) scale(1) translateY(0);
                opacity: 1;
            }
        }
        
        /* Effet de pulsation pour les marqueurs actifs */
        .marker-active {
            animation: markerAppear 0.6s ease-out, pulse 2s infinite 0.6s;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 3px 12px rgba(0,0,0,0.25), 0 0 0 0 rgba(66, 133, 244, 0.6);
            }
            70% {
                box-shadow: 0 3px 12px rgba(0,0,0,0.25), 0 0 0 12px rgba(66, 133, 244, 0);
            }
            100% {
                box-shadow: 0 3px 12px rgba(0,0,0,0.25), 0 0 0 0 rgba(66, 133, 244, 0);
            }
        }
        
        /* Amélioration des popups */
        .mapboxgl-popup-content {
            animation: popupSlideIn 0.3s ease-out;
        }
        
        @keyframes popupSlideIn {
            0% {
                transform: translateY(-10px);
                opacity: 0;
            }
            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Tooltip au survol */
        .marker-tooltip {
            position: absolute;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.95), rgba(33, 37, 41, 0.95));
            color: white;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
            z-index: 1000;
            pointer-events: none;
            transform: translate(-50%, -100%);
            margin-top: -15px;
            opacity: 0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            min-width: 120px;
            text-align: center;
        }
        
        .marker-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -6px;
            border: 6px solid transparent;
            border-top-color: rgba(0, 0, 0, 0.95);
        }
        
        .marker-tooltip.show {
            opacity: 1;
            transform: translate(-50%, -100%) translateY(-5px);
        }
        
        .marker-tooltip strong {
            color: #ffc107;
            display: block;
            margin-bottom: 2px;
        }
        
        .marker-tooltip small {
            color: #adb5bd;
            font-size: 11px;
        }
        
        /* Styles pour le dropdown de style de carte */
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            border-radius: 8px;
            padding: 8px 0;
        }
        
        .dropdown-item {
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        
        .dropdown-item:hover {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #495057;
        }
        
        .dropdown-item i {
            width: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <a href="ponts.php" class="back-btn">
        <i class="fas fa-arrow-left mr-2"></i>Retour aux Ponts
    </a>

    <div class="page-header">
        <h1><i class="fas fa-map-marked-alt mr-3"></i>Géolocalisation des Ponts-Bascules</h1>
        <p class="mb-0">Visualisation cartographique de tous les ponts-bascules UniPalm</p>
    </div>

    <div class="controls-container">
        <div class="btn-group">
            <button type="button" class="btn btn-info" onclick="filterPonts('Actif')" style="background: linear-gradient(135deg, #4285f4, #1a73e8);">
                <i class="fas fa-check-circle"></i>Ponts Actifs (<?= $ponts_actifs ?>)
            </button>
            
            <button type="button" class="btn btn-secondary" onclick="filterPonts('Inactif')" style="background: linear-gradient(135deg, #9aa0a6, #80868b);">
                <i class="fas fa-pause-circle"></i>Ponts Inactifs (<?= $ponts_inactifs ?>)
            </button>
            
            <button type="button" class="btn btn-success" onclick="filterPonts('all')">
                <i class="fas fa-globe"></i>Tous les Ponts
            </button>
            
            <button type="button" class="btn btn-secondary" onclick="centerMap()">
                <i class="fas fa-crosshairs"></i>Centrer la Carte
            </button>
            
            <div class="dropdown" style="display: inline-block;">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="styleDropdown" data-toggle="dropdown">
                    <i class="fas fa-layer-group"></i>Style de Carte
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#" onclick="changeMapStyle('mapbox://styles/mapbox/streets-v12')">
                        <i class="fas fa-road mr-2"></i>Streets (Standard)
                    </a>
                    <a class="dropdown-item" href="#" onclick="changeMapStyle('mapbox://styles/mapbox/satellite-streets-v12')">
                        <i class="fas fa-satellite mr-2"></i>Satellite
                    </a>
                    <a class="dropdown-item" href="#" onclick="changeMapStyle('mapbox://styles/mapbox/light-v11')">
                        <i class="fas fa-sun mr-2"></i>Clair
                    </a>
                    <a class="dropdown-item" href="#" onclick="changeMapStyle('mapbox://styles/mapbox/dark-v11')">
                        <i class="fas fa-moon mr-2"></i>Sombre
                    </a>
                    <a class="dropdown-item" href="#" onclick="changeMapStyle('mapbox://styles/mapbox/outdoors-v12')">
                        <i class="fas fa-mountain mr-2"></i>Outdoor
                    </a>
                </div>
            </div>
        </div>
        
        <div class="stats-container">
            <div class="stat-badge">
                <i class="fas fa-database mr-1"></i>Total: <?= $total_ponts ?> ponts
            </div>
            <div class="stat-badge">
                <i class="fas fa-map-marker-alt mr-1"></i>Géolocalisés: <?= $total_ponts ?>
            </div>
        </div>
    </div>

    <div id="map-container">
        <div id="map"></div>
        <div id="loading-overlay" class="loading-overlay">
            <div class="loading-spinner"></div>
            <div class="loading-text">
                <div>Chargement de la carte...</div>
                <small id="loading-status">Initialisation de Mapbox</small>
            </div>
            <div class="loading-progress">
                <div class="loading-progress-bar"></div>
            </div>
        </div>
    </div>

    <script>
        // Fonction pour mettre à jour le statut de chargement
        function updateLoadingStatus(message) {
            const statusElement = document.getElementById('loading-status');
            if (statusElement) {
                statusElement.textContent = message;
            }
        }

        // Fonction pour masquer l'indicateur de chargement
        function hideLoading() {
            const loadingOverlay = document.getElementById('loading-overlay');
            if (loadingOverlay) {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                }, 500);
            }
        }

        // Clé API Mapbox
        mapboxgl.accessToken = 'pk.eyJ1IjoicGVnYXN1czEyMG1vdWFuIiwiYSI6ImNtNDFpOGR0bDExYncyanM1dTlneXN2angifQ.8aXSgctKqtdljXgahLakIA';
        
        updateLoadingStatus('Connexion à Mapbox...');
        
        // Initialiser la carte centrée sur la Côte d'Ivoire
        const map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v12',
            center: [-5.5471, 7.5399], // Coordonnées de la Côte d'Ivoire
            zoom: 6
        });

        // Événements de chargement de la carte
        map.on('styledata', () => {
            updateLoadingStatus('Chargement des tuiles...');
        });

        map.on('sourcedata', () => {
            updateLoadingStatus('Chargement des données...');
        });

        // Ajouter des contrôles de navigation
        map.addControl(new mapboxgl.NavigationControl());
        map.addControl(new mapboxgl.FullscreenControl());

        // Données des ponts depuis PHP
        const ponts = <?php echo json_encode($ponts); ?>;
        console.log('Ponts chargés:', ponts);

        // Stocker les marqueurs pour pouvoir les filtrer
        let markers = [];

        // Fonction pour créer un marqueur
        function createMarker(pont) {
            const { latitude, longitude, code_pont, nom_pont, gerant, cooperatif, statut } = pont;
            
            // Couleur du marqueur selon le statut (style bleu comme Google Maps)
            const markerColor = statut === 'Actif' ? '#4285f4' : '#9aa0a6'; // Bleu pour actif, gris pour inactif
            
            // Créer l'élément du marqueur personnalisé style Google Maps
            const el = document.createElement('div');
            el.className = `custom-marker ${statut === 'Actif' ? 'marker-active' : ''}`;
            el.style.cssText = `
                width: 32px;
                height: 44px;
                background: linear-gradient(135deg, ${markerColor}, ${adjustBrightness(markerColor, -20)});
                border-radius: 50% 50% 50% 0;
                transform: rotate(-45deg);
                border: 2px solid white;
                box-shadow: 0 3px 12px rgba(0,0,0,0.25), 0 1px 3px rgba(0,0,0,0.12);
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
            `;
            
            // Fonction pour ajuster la luminosité
            function adjustBrightness(hex, percent) {
                const num = parseInt(hex.replace("#", ""), 16);
                const amt = Math.round(2.55 * percent);
                const R = (num >> 16) + amt;
                const G = (num >> 8 & 0x00FF) + amt;
                const B = (num & 0x0000FF) + amt;
                return "#" + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
                    (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
                    (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1);
            }
            
            // Ajouter l'icône à l'intérieur du marqueur
            const icon = document.createElement('div');
            icon.innerHTML = '<i class="fas fa-weight-hanging" style="color: white; font-size: 10px;"></i>';
            icon.style.cssText = `
                position: absolute;
                top: 35%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(45deg);
                pointer-events: none;
                text-shadow: 0 1px 2px rgba(0,0,0,0.3);
            `;
            el.appendChild(icon);
            
            // Créer le tooltip
            const tooltip = document.createElement('div');
            tooltip.className = 'marker-tooltip';
            tooltip.innerHTML = `
                <strong>${code_pont}</strong>
                <div style="margin: 4px 0;">${nom_pont}</div>
                <small>Gérant: ${gerant}</small><br>
                <small style="color: ${statut === 'Actif' ? '#4285f4' : '#9aa0a6'};">● ${statut}</small>
            `;
            el.appendChild(tooltip);
            
            // Animation au survol avec tooltip
            el.addEventListener('mouseenter', () => {
                el.style.transform = 'rotate(-45deg) scale(1.15)'; // Style Google Maps
                el.style.boxShadow = '0 6px 20px rgba(0,0,0,0.35), 0 2px 6px rgba(0,0,0,0.2)';
                tooltip.classList.add('show');
            });
            
            el.addEventListener('mouseleave', () => {
                el.style.transform = 'rotate(-45deg) scale(1)';
                el.style.boxShadow = '0 3px 12px rgba(0,0,0,0.25), 0 1px 3px rgba(0,0,0,0.12)';
                tooltip.classList.remove('show');
            });

            // Créer le popup (s'ouvre seulement sur clic)
            const popup = new mapboxgl.Popup({ 
                offset: 25,
                closeButton: true,
                closeOnClick: false
            })
            .setHTML(`
                <div class="popup-header">${code_pont}</div>
                <div class="popup-info">
                    <div class="popup-row">
                        <i class="fas fa-building" style="color: #17a2b8;"></i>
                        <strong>${nom_pont}</strong>
                    </div>
                    <div class="popup-row">
                        <i class="fas fa-user-tie" style="color: #6f42c1;"></i>
                        Gérant: ${gerant}
                    </div>
                    <div class="popup-row">
                        <i class="fas fa-handshake" style="color: #fd7e14;"></i>
                        Coopérative: ${cooperatif}
                    </div>
                    <div class="popup-row">
                        <i class="fas fa-map-marker-alt" style="color: #6c757d;"></i>
                        ${latitude.toFixed(6)}, ${longitude.toFixed(6)}
                    </div>
                    <div class="popup-row">
                        <i class="fas fa-${statut === 'Actif' ? 'check-circle' : 'pause-circle'}" style="color: ${markerColor};"></i>
                        <span style="color: ${markerColor}; font-weight: 600;">${statut}</span>
                    </div>
                    <hr style="margin: 10px 0;">
                    <div style="text-align: center;">
                        <a href="https://maps.google.com/?q=${latitude},${longitude}" target="_blank" 
                           style="color: #007bff; text-decoration: none; font-weight: 500;">
                            <i class="fas fa-external-link-alt mr-1"></i>Ouvrir dans Google Maps
                        </a>
                    </div>
                </div>
            `);

            // Créer le marqueur
            const marker = new mapboxgl.Marker(el)
                .setLngLat([longitude, latitude])
                .addTo(map);
            
            // Ajouter l'événement de clic pour ouvrir le popup
            el.addEventListener('click', (e) => {
                e.stopPropagation();
                popup.setLngLat([longitude, latitude]).addTo(map);
            });
            
            // Stocker les données du pont avec le marqueur
            marker.pontData = pont;
            return marker;
        }

        // Fonction pour ajouter les marqueurs avec progression
        function addMarkersWithProgress() {
            updateLoadingStatus('Ajout des marqueurs...');
            
            ponts.forEach((pont, index) => {
                setTimeout(() => {
                    const marker = createMarker(pont);
                    markers.push(marker);
                    
                    // Mettre à jour le statut
                    const progress = Math.round(((index + 1) / ponts.length) * 100);
                    updateLoadingStatus(`Ajout des marqueurs... ${index + 1}/${ponts.length} (${progress}%)`);
                    
                    // Si c'est le dernier marqueur, finaliser le chargement
                    if (index === ponts.length - 1) {
                        setTimeout(() => {
                            updateLoadingStatus('Finalisation...');
                            centerMap();
                            setTimeout(hideLoading, 800);
                        }, 200);
                    }
                }, index * 50); // Délai progressif pour l'animation
            });
        }

        // Fonction pour filtrer les ponts
        function filterPonts(filter) {
            markers.forEach(marker => {
                const pont = marker.pontData;
                if (filter === 'all' || pont.statut === filter) {
                    marker.getElement().style.display = 'block';
                } else {
                    marker.getElement().style.display = 'none';
                    marker.getPopup().remove(); // Fermer le popup si ouvert
                }
            });
        }

        // Fonction pour centrer la carte
        function centerMap() {
            if (ponts.length > 0) {
                const bounds = new mapboxgl.LngLatBounds();
                ponts.forEach(pont => {
                    bounds.extend([pont.longitude, pont.latitude]);
                });
                map.fitBounds(bounds, { padding: 50 });
            }
        }

        // Événement de chargement complet de la carte
        map.on('load', () => {
            updateLoadingStatus('Carte chargée, préparation des marqueurs...');
            setTimeout(addMarkersWithProgress, 500);
        });

        // Gestion des erreurs de chargement
        map.on('error', (e) => {
            console.error('Erreur de chargement de la carte:', e);
            updateLoadingStatus('Erreur de chargement - Nouvelle tentative...');
            setTimeout(() => {
                location.reload();
            }, 3000);
        });

        // Ajouter un effet de zoom pour les marqueurs
        map.on('zoom', () => {
            const zoom = map.getZoom();
            markers.forEach(marker => {
                const scale = zoom > 10 ? 1.2 : 1;
                marker.getElement().style.transform = `rotate(-45deg) scale(${scale})`;
            });
        });

        // Fonction pour changer le style de la carte
        function changeMapStyle(styleUrl) {
            updateLoadingStatus('Changement de style...');
            
            // Afficher temporairement l'overlay de chargement
            const loadingOverlay = document.getElementById('loading-overlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'flex';
                loadingOverlay.style.opacity = '1';
            }
            
            map.setStyle(styleUrl);
            
            // Masquer l'overlay après le chargement
            map.once('styledata', () => {
                setTimeout(() => {
                    hideLoading();
                }, 1000);
            });
        }
    </script>
</body>
</html>

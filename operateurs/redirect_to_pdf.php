<?php
// Page de redirection vers le PDF
session_start();

// Vérifier si les données PDF existent
if (!isset($_SESSION['pdf_data'])) {
    header('Location: recherche_chef_equipe.php?error=no_data');
    exit;
}

// Afficher une page de redirection avec options
?>
<!DOCTYPE html>
<html>
<head>
    <title>Génération PDF - UniPalm</title>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            margin: 20px;
        }
        
        .logo {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }
        
        p {
            color: #6c757d;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 30px;
            margin: 10px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        
        .countdown {
            font-size: 1.2rem;
            color: #e74c3c;
            font-weight: 600;
            margin: 20px 0;
        }
        
        .loading {
            display: none;
            margin: 20px 0;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #e74c3c;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <i class="fas fa-file-pdf"></i>
        </div>
        
        <h1>📄 Bordereau de Déchargement Prêt</h1>
        
        <p>Votre bordereau PDF a été généré avec succès. Choisissez comment vous souhaitez l'ouvrir :</p>
        
        <div class="countdown" id="countdown">Ouverture automatique dans <span id="timer">5</span> secondes...</div>
        
        <div>
            <button onclick="openPDFNewTab()" class="btn btn-primary">
                <i class="fas fa-external-link-alt"></i> Nouvel Onglet
            </button>
            
            <button onclick="openPDFSameTab()" class="btn btn-secondary">
                <i class="fas fa-eye"></i> Même Onglet
            </button>
        </div>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Génération du PDF en cours...</p>
        </div>
        
        <p style="margin-top: 30px; font-size: 0.9rem;">
            <a href="recherche_chef_equipe.php" style="color: #667eea; text-decoration: none;">
                ← Retour à la recherche
            </a>
        </p>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        let countdown = 5;
        const timerElement = document.getElementById('timer');
        const countdownElement = document.getElementById('countdown');
        
        // Compte à rebours
        const countdownInterval = setInterval(() => {
            countdown--;
            timerElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                openPDFNewTab();
            }
        }, 1000);
        
        function openPDFNewTab() {
            clearInterval(countdownInterval);
            countdownElement.style.display = 'none';
            document.getElementById('loading').style.display = 'block';
            
            // Ouvrir dans un nouvel onglet
            const newWindow = window.open('generate_bordereau_pdf_model.php', '_blank');
            
            // Vérifier si le popup a été bloqué
            setTimeout(() => {
                if (!newWindow || newWindow.closed || typeof newWindow.closed == 'undefined') {
                    alert('⚠️ Le popup a été bloqué par votre navigateur. Cliquez sur "Même Onglet" pour voir le PDF.');
                    document.getElementById('loading').style.display = 'none';
                    countdownElement.style.display = 'block';
                } else {
                    // Retourner à la page de recherche après un délai
                    setTimeout(() => {
                        window.location.href = 'recherche_chef_equipe.php';
                    }, 2000);
                }
            }, 1000);
        }
        
        function openPDFSameTab() {
            clearInterval(countdownInterval);
            document.getElementById('loading').style.display = 'block';
            
            // Ouvrir dans le même onglet
            window.location.href = 'generate_bordereau_pdf_model.php';
        }
        
        // Arrêter le compte à rebours si l'utilisateur clique
        document.addEventListener('click', () => {
            clearInterval(countdownInterval);
            countdownElement.style.display = 'none';
        });
    </script>
</body>
</html>

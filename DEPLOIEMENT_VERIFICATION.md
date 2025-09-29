# 🚀 Guide de Déploiement - Système de Vérification QR Code

## 📋 Fichiers à Déployer sur le Serveur Externe

### Fichiers Obligatoires :
- `verification_pont.php` - Page principale de vérification
- `config_verification.php` - Configuration de la base de données

## 🗄️ Configuration de la Base de Données

### 1. Créer la Base de Données
```sql
CREATE DATABASE unipalm_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Créer un Utilisateur Dédié
```sql
CREATE USER 'unipalm_user'@'localhost' IDENTIFIED BY 'votre_mot_de_passe_securise';
GRANT SELECT ON unipalm_prod.pont_bascule TO 'unipalm_user'@'localhost';
GRANT INSERT ON unipalm_prod.verification_logs TO 'unipalm_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Créer la Table des Ponts-Bascules
```sql
USE unipalm_prod;

CREATE TABLE pont_bascule (
    id_pont INT AUTO_INCREMENT PRIMARY KEY,
    code_pont VARCHAR(50) NOT NULL UNIQUE,
    nom_pont VARCHAR(255),
    gerant VARCHAR(255) NOT NULL,
    cooperatif VARCHAR(255),
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    statut ENUM('Actif', 'Inactif') DEFAULT 'Actif',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code_pont (code_pont),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4. Table de Logs (Créée Automatiquement)
```sql
-- Cette table sera créée automatiquement par le script
CREATE TABLE verification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code_pont VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    date_verification DATETIME NOT NULL,
    INDEX idx_code_pont (code_pont),
    INDEX idx_date (date_verification)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## ⚙️ Configuration du Serveur

### 1. Modifier `config_verification.php`

Éditer la section PRODUCTION :
```php
if ($_SERVER['HTTP_HOST'] === 'unipalm.ci' || $_SERVER['HTTP_HOST'] === 'www.unipalm.ci') {
    $db_config = [
        'host' => 'localhost', // ou IP du serveur MySQL
        'dbname' => 'unipalm_prod',
        'username' => 'unipalm_user',
        'password' => 'VOTRE_MOT_DE_PASSE_SECURISE',
        'charset' => 'utf8mb4'
    ];
}
```

### 2. Permissions des Fichiers
```bash
chmod 644 verification_pont.php
chmod 600 config_verification.php  # Plus restrictif pour la config
```

### 3. Configuration Apache/Nginx

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

<Files "config_verification.php">
    Order allow,deny
    Deny from all
</Files>
```

#### Nginx
```nginx
location ~ ^/config_verification\.php$ {
    deny all;
    return 404;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

## 🔄 Synchronisation des Données

### Script de Synchronisation (optionnel)
Créer un script pour synchroniser les données depuis le serveur principal :

```php
<?php
// sync_ponts.php
require_once 'config_verification.php';

// Connexion au serveur principal
$source_config = [
    'host' => 'IP_SERVEUR_PRINCIPAL',
    'dbname' => 'unipalm',
    'username' => 'sync_user',
    'password' => 'sync_password'
];

try {
    $source_conn = new PDO(
        "mysql:host={$source_config['host']};dbname={$source_config['dbname']};charset=utf8mb4",
        $source_config['username'],
        $source_config['password']
    );
    
    // Récupérer les données du serveur principal
    $stmt = $source_conn->query("SELECT * FROM pont_bascule");
    $ponts = $stmt->fetchAll();
    
    // Vider et réinsérer dans la base locale
    $conn->exec("TRUNCATE TABLE pont_bascule");
    
    $insert_stmt = $conn->prepare("
        INSERT INTO pont_bascule 
        (code_pont, nom_pont, gerant, cooperatif, latitude, longitude, statut, date_creation) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($ponts as $pont) {
        $insert_stmt->execute([
            $pont['code_pont'],
            $pont['nom_pont'],
            $pont['gerant'],
            $pont['cooperatif'],
            $pont['latitude'],
            $pont['longitude'],
            $pont['statut'],
            $pont['date_creation']
        ]);
    }
    
    echo "Synchronisation réussie : " . count($ponts) . " ponts synchronisés\n";
    
} catch (Exception $e) {
    echo "Erreur de synchronisation : " . $e->getMessage() . "\n";
}
?>
```

### Cron Job pour Synchronisation Automatique
```bash
# Synchroniser toutes les heures
0 * * * * /usr/bin/php /path/to/sync_ponts.php >> /var/log/sync_ponts.log 2>&1
```

## 🧪 Tests de Déploiement

### 1. Test de Connexion
```bash
curl -I https://unipalm.ci/verification_pont.php?code=TEST
```

### 2. Test avec Code Valide
```bash
curl "https://unipalm.ci/verification_pont.php?code=UNIPALM-PB-0001-CI"
```

### 3. Test de Logs
```sql
SELECT * FROM verification_logs ORDER BY date_verification DESC LIMIT 10;
```

## 🔒 Sécurité

### 1. SSL/HTTPS Obligatoire
- Certificat SSL valide
- Redirection HTTP → HTTPS

### 2. Protection des Fichiers de Configuration
- Permissions restrictives
- Exclusion du contrôle de version

### 3. Validation des Entrées
- Sanitisation des paramètres GET
- Protection contre l'injection SQL

### 4. Rate Limiting (optionnel)
```php
// Dans config_verification.php
function checkRateLimit($ip) {
    // Implémenter une limitation de taux
    // Exemple : max 100 requêtes par heure par IP
}
```

## 📊 Monitoring

### 1. Logs d'Accès
```bash
tail -f /var/log/apache2/access.log | grep verification_pont
```

### 2. Statistiques de Vérification
```sql
SELECT 
    DATE(date_verification) as date,
    COUNT(*) as verifications
FROM verification_logs 
GROUP BY DATE(date_verification) 
ORDER BY date DESC;
```

### 3. Codes les Plus Vérifiés
```sql
SELECT 
    code_pont,
    COUNT(*) as count
FROM verification_logs 
GROUP BY code_pont 
ORDER BY count DESC 
LIMIT 10;
```

## 🚨 Dépannage

### Erreurs Communes :

1. **"Service temporairement indisponible"**
   - Vérifier la connexion à la base de données
   - Vérifier les logs d'erreur PHP

2. **"Aucun pont-bascule trouvé"**
   - Vérifier que les données sont synchronisées
   - Vérifier le format du code QR

3. **Page blanche**
   - Vérifier les logs d'erreur PHP
   - Vérifier les permissions des fichiers

### Commandes Utiles :
```bash
# Logs d'erreur PHP
tail -f /var/log/php_errors.log

# Logs d'erreur Apache
tail -f /var/log/apache2/error.log

# Test de connectivité base de données
mysql -h localhost -u unipalm_user -p unipalm_prod
```

## ✅ Checklist de Déploiement

- [ ] Base de données créée et configurée
- [ ] Utilisateur de base de données créé avec permissions limitées
- [ ] Fichiers uploadés avec bonnes permissions
- [ ] Configuration mise à jour avec bons paramètres
- [ ] SSL/HTTPS configuré
- [ ] Tests de fonctionnement réussis
- [ ] Monitoring en place
- [ ] Documentation équipe mise à jour

---

**URL de Test Final :** https://unipalm.ci/verification_pont.php?code=UNIPALM-PB-0001-CI

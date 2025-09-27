-- Script de mise à jour de la table transactions
-- Ajouter le champ 'source' à la table existante

-- Vérifier si la colonne 'source' n'existe pas déjà
ALTER TABLE `transactions` 
ADD COLUMN IF NOT EXISTS `source` varchar(255) DEFAULT NULL 
AFTER `motifs`;

-- Optionnel : Mettre à jour les enregistrements existants avec une valeur par défaut
UPDATE `transactions` 
SET `source` = 'Non spécifiée' 
WHERE `source` IS NULL OR `source` = '';

-- Vérification de la structure mise à jour
DESCRIBE `transactions`;

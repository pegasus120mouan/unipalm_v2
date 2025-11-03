-- Script pour corriger la table agents
-- Exécuter ces commandes dans phpMyAdmin ou votre client MySQL

-- 1. Ajouter la clé primaire et AUTO_INCREMENT
ALTER TABLE `agents` 
ADD PRIMARY KEY (`id_agent`),
MODIFY `id_agent` int NOT NULL AUTO_INCREMENT;

-- 2. Vérifier la structure après modification
-- DESCRIBE agents;

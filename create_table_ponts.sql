CREATE TABLE IF NOT EXISTS `pont_bascule` (
  `id_pont` int(11) NOT NULL AUTO_INCREMENT,
  `code_pont` varchar(50) NOT NULL UNIQUE,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `gerant` varchar(100) NOT NULL,
  `cooperatif` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pont`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insérer des données de test
INSERT INTO `pont_bascule` (`code_pont`, `latitude`, `longitude`, `gerant`, `cooperatif`) VALUES
('PB001', 5.9342400, -5.3260000, 'Agenor', 'Unicoop'),
('PB002', 6.1234567, -5.4567890, 'Marie Kouassi', 'COOPAG'),
('PB003', 5.8765432, -5.2345678, 'Jean Baptiste', NULL);

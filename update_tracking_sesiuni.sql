-- Script pentru crearea tabelului de tracking complet al sesiunilor
-- Rulează acest script în phpMyAdmin sau prin update_database.php

-- Tabel pentru tracking complet al sesiunilor
CREATE TABLE IF NOT EXISTS `tracking_sesiuni` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cod_cititor` VARCHAR(50) NOT NULL,
  `tip_actiune` ENUM('scanare_permis', 'scanare_carte_imprumut', 'scanare_carte_returnare', 'sesiune_expirata', 'sesiune_inchisa') NOT NULL,
  `cod_carte` VARCHAR(50) DEFAULT NULL COMMENT 'NULL pentru scanare_permis, codul cărții pentru scanare/returnare',
  `data_ora` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data și ora exactă a acțiunii',
  `data` DATE GENERATED ALWAYS AS (DATE(data_ora)) STORED COMMENT 'Data pentru rapoarte zilnice',
  `ora` TIME GENERATED ALWAYS AS (TIME(data_ora)) STORED COMMENT 'Ora pentru rapoarte',
  `sesiune_id` INT DEFAULT NULL COMMENT 'ID-ul sesiunii din sesiuni_utilizatori',
  `detalii` TEXT DEFAULT NULL COMMENT 'Detalii suplimentare (JSON sau text)',
  INDEX `idx_cititor` (`cod_cititor`),
  INDEX `idx_data_ora` (`data_ora`),
  INDEX `idx_data` (`data`),
  INDEX `idx_tip_actiune` (`tip_actiune`),
  INDEX `idx_sesiune` (`sesiune_id`),
  FOREIGN KEY (`cod_cititor`) REFERENCES `cititori`(`cod_bare`) ON DELETE CASCADE,
  FOREIGN KEY (`sesiune_id`) REFERENCES `sesiuni_utilizatori`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_romanian_ci;


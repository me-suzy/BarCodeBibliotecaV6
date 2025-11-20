-- Script pentru actualizarea bazei de date cu câmpurile necesare pentru sistemul de sesiuni

-- Adaugă câmpul blocat în tabelul cititori
ALTER TABLE `cititori` 
ADD COLUMN `blocat` TINYINT(1) DEFAULT 0 COMMENT '0=activ, 1=blocat (din cauza întârzierilor sau alte motive)' AFTER `email`,
ADD COLUMN `motiv_blocare` VARCHAR(255) DEFAULT NULL COMMENT 'Motivul blocării' AFTER `blocat`;

-- Adaugă câmpul data_scadenta în tabelul imprumuturi (dacă nu există deja)
ALTER TABLE `imprumuturi` 
ADD COLUMN `data_scadenta` DATE DEFAULT NULL COMMENT 'Data scadenței împrumutului (14 zile de la data_imprumut)' AFTER `data_imprumut`;

-- Actualizează data_scadenta pentru împrumuturile existente care nu au data_scadenta
UPDATE `imprumuturi` 
SET `data_scadenta` = DATE_ADD(DATE(`data_imprumut`), INTERVAL 14 DAY)
WHERE `data_scadenta` IS NULL AND `data_returnare` IS NULL;

-- Creează tabelul pentru sesiuni utilizatori
CREATE TABLE IF NOT EXISTS `sesiuni_utilizatori` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cod_cititor` VARCHAR(50) NOT NULL,
  `timestamp_start` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Momentul când utilizatorul a fost scanat',
  `timestamp_ultima_actiune` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Momentul ultimei acțiuni (scanare carte)',
  `status` ENUM('activ', 'expirat', 'inchis') DEFAULT 'activ' COMMENT 'Statusul sesiunii',
  `numar_carti_scanate` INT DEFAULT 0 COMMENT 'Numărul de cărți scanate în această sesiune',
  INDEX `idx_cititor` (`cod_cititor`),
  INDEX `idx_status` (`status`),
  INDEX `idx_timestamp` (`timestamp_ultima_actiune`),
  FOREIGN KEY (`cod_cititor`) REFERENCES `cititori`(`cod_bare`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_romanian_ci;


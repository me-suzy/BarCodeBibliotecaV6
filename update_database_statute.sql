-- Script pentru adăugarea sistemului de statute cititori
-- Rulează în phpMyAdmin sau MySQL Workbench

USE biblioteca;

-- ============================================
-- PASUL 1: Creează tabelă pentru statute cititori
-- ============================================
CREATE TABLE IF NOT EXISTS statute_cititori (
    cod_statut VARCHAR(2) PRIMARY KEY,
    nume_statut VARCHAR(100) NOT NULL,
    limita_depozit_carte INT DEFAULT 0,
    limita_depozit_periodice INT DEFAULT 0,
    limita_sala_lectura INT DEFAULT 0,
    limita_colectii_speciale INT DEFAULT 0,
    limita_totala INT DEFAULT 6,
    descriere TEXT,
    INDEX idx_cod_statut (cod_statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PASUL 2: Inserează statutele din PDF
-- ============================================
INSERT INTO statute_cititori (cod_statut, nume_statut, limita_totala, descriere) VALUES
('11', 'Personal Științific Academie', 10, 'Personal științific al Academiei Române'),
('12', 'Bibliotecari BARI', 15, 'Bibliotecari din rețeaua BARI'),
('13', 'Angajați ARFI', 8, 'Angajați ARFI'),
('14', 'Nespecifici cu domiciliu în Iași', 4, 'Cititori nespecificați cu domiciliu în Iași'),
('15', 'Nespecifici fără domiciliu în Iași', 2, 'Cititori nespecificați fără domiciliu în Iași'),
('16', 'Personal departamente', 6, 'Personal din departamente'),
('17', 'ILL - Împrumut interbibliotecar', 20, 'Împrumut interbibliotecar')
ON DUPLICATE KEY UPDATE 
    nume_statut = VALUES(nume_statut),
    limita_totala = VALUES(limita_totala),
    descriere = VALUES(descriere);

-- ============================================
-- PASUL 3: Adaugă coloană `statut` în tabela `cititori`
-- ============================================
-- Verifică dacă coloana există deja
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'biblioteca' 
    AND TABLE_NAME = 'cititori' 
    AND COLUMN_NAME = 'statut'
);

-- Adaugă coloana doar dacă nu există
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE cititori ADD COLUMN statut VARCHAR(2) DEFAULT ''14'' AFTER cod_bare, ADD INDEX idx_statut (statut)',
    'SELECT ''Coloana statut există deja'' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- PASUL 4: Actualizează statutul pentru cititorii existenți
-- ============================================
-- Extrage primele 2 cifre din cod_bare și actualizează statutul
UPDATE cititori 
SET statut = SUBSTRING(cod_bare, 1, 2)
WHERE LENGTH(cod_bare) >= 2 
AND SUBSTRING(cod_bare, 1, 2) REGEXP '^[0-9]{2}$'
AND SUBSTRING(cod_bare, 1, 2) BETWEEN '11' AND '17';

-- Pentru cititorii cu coduri care nu respectă formatul (ex: USER001), păstrează statutul implicit '14'
UPDATE cititori 
SET statut = '14'
WHERE statut IS NULL OR statut NOT BETWEEN '11' AND '17';

-- ============================================
-- VERIFICARE
-- ============================================
-- Verifică structura tabelului statute_cititori
SELECT 'Statute cititori:' AS info;
SELECT * FROM statute_cititori ORDER BY cod_statut;

-- Verifică câți cititori au fiecare statut
SELECT 'Distribuție cititori pe statut:' AS info;
SELECT statut, COUNT(*) as numar_cititori 
FROM cititori 
GROUP BY statut 
ORDER BY statut;


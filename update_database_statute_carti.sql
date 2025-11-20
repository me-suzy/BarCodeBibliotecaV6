-- Script pentru adăugarea sistemului de statute cărți
-- Rulează în phpMyAdmin sau MySQL Workbench

USE biblioteca;

-- ============================================
-- PASUL 1: Creează tabelă pentru statute cărți
-- ============================================
CREATE TABLE IF NOT EXISTS statute_carti (
    cod_statut VARCHAR(2) PRIMARY KEY,
    nume_statut VARCHAR(100) NOT NULL,
    poate_imprumuta_acasa BOOLEAN DEFAULT FALSE,
    poate_imprumuta_sala BOOLEAN DEFAULT FALSE,
    durata_imprumut_zile INT DEFAULT 14,
    descriere TEXT,
    INDEX idx_cod_statut (cod_statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PASUL 2: Inserează statutele cărților
-- ============================================
INSERT INTO statute_carti (cod_statut, nume_statut, poate_imprumuta_acasa, poate_imprumuta_sala, durata_imprumut_zile, descriere) VALUES
('01', 'Pentru împrumut acasă', TRUE, FALSE, 14, 'Se poate împrumuta acasă - durată standard 14 zile'),
('02', 'Se împr. numai la sală', FALSE, TRUE, 0, 'Se imprumuta doar la sala de lectură - nu se poate lua acasă'),
('03', 'Colecții speciale - sală 1 zi', FALSE, TRUE, 1, 'Colecții speciale - se imprumuta doar sala pentru 1 zi'),
('04', 'Nu există fizic', FALSE, FALSE, 0, 'Nu exista fizic cartea - deci nu se poate împrumuta'),
('05', 'Împrumut scurt 5 zile', TRUE, FALSE, 5, 'Se imprumuta doar 5 zile - împrumut scurt'),
('06', 'Regim special 6 luni - 1 an', TRUE, FALSE, 180, 'Regim special pentru cărți - se pot împrumuta pe o perioadă mare de timp 6 luni, maxim 1 an'),
('08', 'Ne circulat', FALSE, FALSE, 0, 'Nu se imprumuta - carte ne circulată'),
('90', 'În achiziție - depozit', FALSE, FALSE, 0, 'Cartea a fost primita, dar e inca in depozit, nu a ajuns la raft')
ON DUPLICATE KEY UPDATE 
    nume_statut = VALUES(nume_statut),
    poate_imprumuta_acasa = VALUES(poate_imprumuta_acasa),
    poate_imprumuta_sala = VALUES(poate_imprumuta_sala),
    durata_imprumut_zile = VALUES(durata_imprumut_zile),
    descriere = VALUES(descriere);

-- ============================================
-- PASUL 3: Adaugă coloană `statut` în tabela `carti`
-- ============================================
-- Verifică dacă coloana există deja
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'biblioteca' 
    AND TABLE_NAME = 'carti' 
    AND COLUMN_NAME = 'statut'
);

-- Adaugă coloana doar dacă nu există
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE carti ADD COLUMN statut VARCHAR(2) DEFAULT ''01'' AFTER cod_bare, ADD INDEX idx_statut_carte (statut)',
    'SELECT ''Coloana statut există deja'' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- PASUL 4: Actualizează statutul pentru cărțile existente
-- ============================================
-- Setează statutul implicit '01' pentru toate cărțile existente care nu au statut
UPDATE carti 
SET statut = '01'
WHERE statut IS NULL OR statut = '';

-- ============================================
-- VERIFICARE
-- ============================================
-- Verifică structura tabelului statute_carti
SELECT 'Statute cărți:' AS info;
SELECT * FROM statute_carti ORDER BY cod_statut;

-- Verifică câte cărți au fiecare statut
SELECT 'Distribuție cărți pe statut:' AS info;
SELECT statut, COUNT(*) as numar_carti 
FROM carti 
GROUP BY statut 
ORDER BY statut;


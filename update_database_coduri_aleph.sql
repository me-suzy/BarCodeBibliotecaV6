-- Script pentru actualizare structură baza de date - Suport coduri Aleph
-- Rulează acest script pentru a adăuga suport pentru coduri Aleph (12 caractere)

USE biblioteca;

-- Adaugă câmpul statut în tabelul cititori (dacă nu există deja)
-- Statutul va fi extras din codul Aleph (primele 2 cifre)
ALTER TABLE cititori 
ADD COLUMN IF NOT EXISTS statut VARCHAR(2) NULL COMMENT 'Statut cititor (extras din cod Aleph sau setat manual)',
ADD COLUMN IF NOT EXISTS tip_cod ENUM('user', 'aleph') DEFAULT 'user' COMMENT 'Tip cod de bare: user (USER001) sau aleph (12 caractere)',
ADD INDEX IF NOT EXISTS idx_statut (statut),
ADD INDEX IF NOT EXISTS idx_tip_cod (tip_cod);

-- Actualizează tipul codurilor existente (USER***)
UPDATE cititori 
SET tip_cod = 'user' 
WHERE cod_bare LIKE 'USER%' AND (tip_cod IS NULL OR tip_cod = '');

-- Dacă există deja coduri Aleph în baza de date, extrage statutul
-- (Această comandă va rula doar dacă există coduri de 12 caractere numerice)
UPDATE cititori 
SET 
    tip_cod = 'aleph',
    statut = SUBSTRING(cod_bare, 1, 2)
WHERE 
    LENGTH(cod_bare) = 12 
    AND cod_bare REGEXP '^[0-9]{12}$'
    AND (tip_cod IS NULL OR tip_cod = '');

-- Verificare: Afișează statistici
SELECT 
    tip_cod,
    COUNT(*) as numar_cititori,
    GROUP_CONCAT(DISTINCT statut ORDER BY statut) as statuturi_utilizate
FROM cititori
GROUP BY tip_cod;

-- Confirmare
SELECT 'Structura bazei de date actualizată cu succes pentru suport coduri Aleph!' as status;


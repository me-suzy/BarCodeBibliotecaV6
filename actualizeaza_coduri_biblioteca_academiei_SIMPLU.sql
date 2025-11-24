-- ============================================
-- Script SQL SIMPLU pentru actualizare coduri Biblioteca Academiei
-- Convertește: 014016xxx → 14016xxx
-- Exemplu: 014016038 → 14016838
-- ============================================

-- IMPORTANT: Fă backup la baza de date înainte de a rula acest script!

-- START TRANSACTION pentru siguranță
START TRANSACTION;

-- Actualizează codurile în tabelul cititori
-- Conversie: 014016038 → 14016838 (elimină primul 0 și al 4-lea 0)
UPDATE cititori 
SET cod_bare = CONCAT('14016', SUBSTRING(cod_bare, 7))
WHERE cod_bare LIKE '014016%'
AND LENGTH(cod_bare) = 9;

-- Actualizează codurile în tabelul imprumuturi
UPDATE imprumuturi 
SET cod_cititor = CONCAT('14016', SUBSTRING(cod_cititor, 7))
WHERE cod_cititor LIKE '014016%'
AND LENGTH(cod_cititor) = 9;

-- Verifică rezultatele
SELECT 'Coduri actualizate în cititori:' AS info, COUNT(*) AS total
FROM cititori 
WHERE cod_bare LIKE '14016%';

SELECT 'Coduri vechi rămase:' AS info, COUNT(*) AS total
FROM cititori 
WHERE cod_bare LIKE '014016%';

-- Dacă totul este OK, confirmă:
COMMIT;

-- Dacă ceva nu este OK, anulează:
-- ROLLBACK;


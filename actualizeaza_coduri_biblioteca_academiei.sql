-- ============================================
-- Script SQL pentru actualizare coduri Biblioteca Academiei
-- Convertește: 014016xxx → 14016xxx
-- Exemplu: 014016038 → 14016838
-- ============================================

-- IMPORTANT: Fă backup la baza de date înainte de a rula acest script!

-- START TRANSACTION pentru siguranță
START TRANSACTION;

-- ============================================
-- PASUL 1: Verifică codurile care vor fi actualizate
-- ============================================
-- Rulează această interogare pentru a vedea ce coduri vor fi modificate:
SELECT 
    cod_bare AS cod_vechi,
    CONCAT('14016', SUBSTRING(cod_bare, 7)) AS cod_nou,
    nume,
    prenume
FROM cititori 
WHERE cod_bare LIKE '014016%'
ORDER BY cod_bare;

-- ============================================
-- PASUL 2: Verifică dacă există duplicate
-- ============================================
-- Verifică dacă codurile noi ar crea duplicate:
SELECT 
    c1.cod_bare AS cod_vechi,
    CONCAT('14016', SUBSTRING(c1.cod_bare, 7)) AS cod_nou,
    COUNT(*) AS duplicate_count
FROM cititori c1
WHERE c1.cod_bare LIKE '014016%'
AND EXISTS (
    SELECT 1 
    FROM cititori c2 
    WHERE c2.cod_bare = CONCAT('14016', SUBSTRING(c1.cod_bare, 7))
)
GROUP BY c1.cod_bare;

-- Dacă interogarea de mai sus returnează rezultate, înseamnă că există duplicate!
-- În acest caz, NU continua cu actualizarea automată - rezolvă manual duplicatele.

-- ============================================
-- PASUL 3: Actualizează codurile în tabelul cititori
-- ============================================
-- Conversie: 014016038 → 14016838
-- Elimină primul 0 (poziția 0) și al 4-lea 0 (poziția 3)
UPDATE cititori 
SET cod_bare = CONCAT('14016', SUBSTRING(cod_bare, 7))
WHERE cod_bare LIKE '014016%'
AND LENGTH(cod_bare) = 9
AND SUBSTRING(cod_bare, 1, 1) = '0'
AND SUBSTRING(cod_bare, 4, 1) = '0';

-- ============================================
-- PASUL 4: Actualizează codurile în tabelul imprumuturi
-- ============================================
UPDATE imprumuturi 
SET cod_cititor = CONCAT('14016', SUBSTRING(cod_cititor, 7))
WHERE cod_cititor LIKE '014016%'
AND LENGTH(cod_cititor) = 9
AND SUBSTRING(cod_cititor, 1, 1) = '0'
AND SUBSTRING(cod_cititor, 4, 1) = '0';

-- ============================================
-- PASUL 5: Verifică rezultatele
-- ============================================
-- Verifică câte coduri au fost actualizate:
SELECT 
    COUNT(*) AS coduri_actualizate_cititori
FROM cititori 
WHERE cod_bare LIKE '14016%'
AND cod_bare NOT LIKE '014016%';

SELECT 
    COUNT(*) AS coduri_actualizate_imprumuturi
FROM imprumuturi 
WHERE cod_cititor LIKE '14016%'
AND cod_cititor NOT LIKE '014016%';

-- Verifică dacă mai există coduri vechi:
SELECT 
    COUNT(*) AS coduri_vechi_ramase
FROM cititori 
WHERE cod_bare LIKE '014016%';

-- ============================================
-- Dacă totul este OK, confirmă tranzacția:
-- ============================================
-- COMMIT;

-- Dacă ceva nu este OK, anulează tranzacția:
-- ROLLBACK;

-- ============================================
-- INSTRUCȚIUNI:
-- ============================================
-- 1. Rulează mai întâi PASUL 1 pentru a vedea ce coduri vor fi actualizate
-- 2. Rulează PASUL 2 pentru a verifica duplicate
-- 3. Dacă nu există duplicate, rulează PASUL 3 și PASUL 4
-- 4. Rulează PASUL 5 pentru a verifica rezultatele
-- 5. Dacă totul este OK, decomentează linia "COMMIT;" și rulează-o
-- 6. Dacă ceva nu este OK, decomentează linia "ROLLBACK;" și rulează-o


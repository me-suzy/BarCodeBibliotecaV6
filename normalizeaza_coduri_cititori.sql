-- Script pentru normalizare coduri cititori (11 cifre → 12 cifre)
-- Rulează în phpMyAdmin sau MySQL Workbench

USE biblioteca;

-- ============================================
-- BACKUP (IMPORTANT!)
-- ============================================
-- Creează backup înainte de modificări
CREATE TABLE IF NOT EXISTS cititori_backup_normalizare AS 
SELECT * FROM cititori;

-- ============================================
-- VERIFICARE: Găsește coduri problemă
-- ============================================
-- Coduri de 11 cifre
SELECT 
    'Coduri de 11 cifre' as tip,
    cod_bare,
    LENGTH(cod_bare) as lungime,
    SUBSTRING(cod_bare, 1, 2) as statut_detectat,
    CONCAT(cod_bare, '0') as cod_corectat,
    nume,
    prenume
FROM cititori 
WHERE LENGTH(cod_bare) = 11 
AND cod_bare REGEXP '^[0-9]{11}$'
AND SUBSTRING(cod_bare, 1, 2) BETWEEN '11' AND '17';

-- Coduri de 10 cifre
SELECT 
    'Coduri de 10 cifre' as tip,
    cod_bare,
    LENGTH(cod_bare) as lungime,
    SUBSTRING(cod_bare, 1, 2) as statut_detectat,
    CONCAT(cod_bare, '00') as cod_corectat,
    nume,
    prenume
FROM cititori 
WHERE LENGTH(cod_bare) = 10 
AND cod_bare REGEXP '^[0-9]{10}$'
AND SUBSTRING(cod_bare, 1, 2) BETWEEN '11' AND '17';

-- Coduri de 9 cifre
SELECT 
    'Coduri de 9 cifre' as tip,
    cod_bare,
    LENGTH(cod_bare) as lungime,
    SUBSTRING(cod_bare, 1, 2) as statut_detectat,
    CONCAT(cod_bare, '000') as cod_corectat,
    nume,
    prenume
FROM cititori 
WHERE LENGTH(cod_bare) = 9 
AND cod_bare REGEXP '^[0-9]{9}$'
AND SUBSTRING(cod_bare, 1, 2) BETWEEN '11' AND '17';

-- ============================================
-- NORMALIZARE: Corectează codurile
-- ============================================

-- 1. Coduri de 11 cifre → Adaugă '0' la final
UPDATE cititori 
SET cod_bare = CONCAT(cod_bare, '0')
WHERE LENGTH(cod_bare) = 11 
AND cod_bare REGEXP '^[0-9]{11}$'
AND SUBSTRING(cod_bare, 1, 2) BETWEEN '11' AND '17';

-- 2. Coduri de 10 cifre → Adaugă '00' la final
UPDATE cititori 
SET cod_bare = CONCAT(cod_bare, '00')
WHERE LENGTH(cod_bare) = 10 
AND cod_bare REGEXP '^[0-9]{10}$'
AND SUBSTRING(cod_bare, 1, 2) BETWEEN '11' AND '17';

-- 3. Coduri de 9 cifre → Adaugă '000' la final
UPDATE cititori 
SET cod_bare = CONCAT(cod_bare, '000')
WHERE LENGTH(cod_bare) = 9 
AND cod_bare REGEXP '^[0-9]{9}$'
AND SUBSTRING(cod_bare, 1, 2) BETWEEN '11' AND '17';

-- 4. Coduri de 8 cifre → Adaugă '0000' la final
UPDATE cititori 
SET cod_bare = CONCAT(cod_bare, '0000')
WHERE LENGTH(cod_bare) = 8 
AND cod_bare REGEXP '^[0-9]{8}$'
AND SUBSTRING(cod_bare, 1, 2) BETWEEN '11' AND '17';

-- ============================================
-- ACTUALIZARE STATUT (după normalizare)
-- ============================================
-- Actualizează statutul pentru codurile normalizate
UPDATE cititori 
SET statut = SUBSTRING(cod_bare, 1, 2)
WHERE LENGTH(cod_bare) = 12 
AND cod_bare REGEXP '^[0-9]{12}$'
AND SUBSTRING(cod_bare, 1, 2) BETWEEN '11' AND '17'
AND (statut IS NULL OR statut != SUBSTRING(cod_bare, 1, 2));

-- ============================================
-- VERIFICARE FINALĂ
-- ============================================
-- Verifică distribuția după normalizare
SELECT 
    LENGTH(cod_bare) as lungime,
    COUNT(*) as numar_cititori,
    GROUP_CONCAT(DISTINCT SUBSTRING(cod_bare, 1, 2) ORDER BY SUBSTRING(cod_bare, 1, 2)) as statuturi
FROM cititori 
WHERE cod_bare REGEXP '^[0-9]+$'
GROUP BY LENGTH(cod_bare)
ORDER BY lungime;

-- Verifică codurile normalizate
SELECT 
    cod_bare,
    LENGTH(cod_bare) as lungime,
    statut,
    SUBSTRING(cod_bare, 1, 2) as statut_din_cod,
    nume,
    prenume
FROM cititori 
WHERE LENGTH(cod_bare) = 12 
AND cod_bare REGEXP '^[0-9]{12}$'
AND SUBSTRING(cod_bare, 1, 2) BETWEEN '11' AND '17'
ORDER BY cod_bare
LIMIT 20;


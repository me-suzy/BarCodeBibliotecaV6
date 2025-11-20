-- Script pentru verificare și fixare encoding baza de date
-- Rulează aceste comenzi în phpMyAdmin sau MySQL CLI

-- 1. Verifică encoding-ul bazei de date
SHOW CREATE DATABASE biblioteca;

-- 2. Dacă nu e UTF-8, convertește-o
ALTER DATABASE biblioteca CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 3. Convertește toate tabelele importante
ALTER TABLE carti CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE cititori CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE imprumuturi CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE sesiuni_biblioteca CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE vizari_permise CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE tracking_actiuni CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE modele_email CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 4. Verifică encoding-ul după conversie
SHOW CREATE DATABASE biblioteca;
SHOW CREATE TABLE carti;
SHOW CREATE TABLE cititori;


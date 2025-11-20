-- Script SQL pentru crearea utilizatorului pma și acordarea permisiunilor
-- Rulează acest script în phpMyAdmin (tab-ul SQL)

-- Creează utilizatorul pma dacă nu există (fără parolă pentru XAMPP local)
CREATE USER IF NOT EXISTS 'pma'@'localhost';

-- Acordă toate permisiunile pe baza de date phpmyadmin
GRANT ALL PRIVILEGES ON `phpmyadmin`.* TO 'pma'@'localhost';

-- Actualizează permisiunile
FLUSH PRIVILEGES;

-- Verificare
SELECT '✅ Utilizatorul pma a fost creat și permisiunile au fost acordate!' AS Status;


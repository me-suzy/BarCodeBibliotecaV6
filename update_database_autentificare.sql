-- Script pentru crearea sistemului de autentificare
-- Rulează în phpMyAdmin sau MySQL Workbench

USE biblioteca;

-- ============================================
-- PASUL 1: Creează tabelă pentru utilizatori
-- ============================================
CREATE TABLE IF NOT EXISTS utilizatori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nume VARCHAR(100),
    email VARCHAR(100),
    activ BOOLEAN DEFAULT TRUE,
    data_creare TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_autentificare TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_activ (activ)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PASUL 2: Inserează utilizatorii default
-- ============================================
-- Utilizator 1: larisa2025 / admin2024
INSERT INTO utilizatori (username, password_hash, nume, activ) VALUES
('larisa2025', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Larisa', TRUE)
ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    nume = VALUES(nume);

-- Utilizator 2: bunica20 / iubire32
INSERT INTO utilizatori (username, password_hash, nume, activ) VALUES
('bunica20', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bunica', TRUE)
ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    nume = VALUES(nume);

-- ============================================
-- VERIFICARE
-- ============================================
SELECT 'Utilizatori creați:' AS info;
SELECT id, username, nume, activ, data_creare FROM utilizatori;


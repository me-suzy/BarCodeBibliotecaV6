-- Script complet de configurare bază de date bibliotecă
-- Rulează în phpMyAdmin sau MySQL Workbench

-- Creează baza de date
CREATE DATABASE IF NOT EXISTS biblioteca CHARACTER SET utf8mb4 COLLATE utf8mb4_romanian_ci;
USE biblioteca;

-- Tabelul pentru cărți (cu sistem de localizare)
CREATE TABLE IF NOT EXISTS carti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cod_bare VARCHAR(50) UNIQUE NOT NULL,
    titlu VARCHAR(255) NOT NULL,
    autor VARCHAR(255),
    isbn VARCHAR(20),
    cota VARCHAR(50),
    raft VARCHAR(10),
    nivel VARCHAR(10),
    pozitie VARCHAR(10),
    locatie_completa VARCHAR(100) GENERATED ALWAYS AS (CONCAT('Raft ', raft, ' - Nivel ', nivel, ' - Poziția ', pozitie)) STORED,
    sectiune VARCHAR(50),
    observatii_locatie TEXT,
    data_adaugare TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cod_bare (cod_bare),
    INDEX idx_locatie (raft, nivel, pozitie),
    INDEX idx_cota (cota)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_romanian_ci;

-- Tabelul pentru cititori
CREATE TABLE IF NOT EXISTS cititori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cod_bare VARCHAR(50) UNIQUE NOT NULL,
    nume VARCHAR(100) NOT NULL,
    prenume VARCHAR(100) NOT NULL,
    telefon VARCHAR(20),
    email VARCHAR(100),
    data_inregistrare TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cod_bare (cod_bare)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_romanian_ci;

-- Tabelul pentru împrumuturi
CREATE TABLE IF NOT EXISTS imprumuturi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cod_cititor VARCHAR(50) NOT NULL,
    cod_carte VARCHAR(50) NOT NULL,
    data_imprumut TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_returnare TIMESTAMP NULL,
    status ENUM('activ', 'returnat') DEFAULT 'activ',
    FOREIGN KEY (cod_cititor) REFERENCES cititori(cod_bare),
    FOREIGN KEY (cod_carte) REFERENCES carti(cod_bare),
    INDEX idx_status (status),
    INDEX idx_cititor (cod_cititor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_romanian_ci;

-- Tabel pentru istoricul mutărilor de cărți
CREATE TABLE IF NOT EXISTS istoric_locatii (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cod_carte VARCHAR(50) NOT NULL,
    raft_vechi VARCHAR(10),
    nivel_vechi VARCHAR(10),
    pozitie_veche VARCHAR(10),
    raft_nou VARCHAR(10),
    nivel_nou VARCHAR(10),
    pozitie_noua VARCHAR(10),
    data_mutare TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    utilizator VARCHAR(100),
    motiv VARCHAR(255),
    FOREIGN KEY (cod_carte) REFERENCES carti(cod_bare)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_romanian_ci;

-- Inserează date de test pentru cărți
INSERT INTO carti (cod_bare, titlu, autor, isbn, cota, raft, nivel, pozitie, sectiune) VALUES
('BOOK001', 'Amintiri din copilărie', 'Ion Creangă', '9789734640539', '821.135.1 CRE a', 'A', '1', '01', 'Literatură română'),
('BOOK002', 'Maitreyi', 'Mircea Eliade', '9789734640546', '821.135.1 ELI m', 'A', '1', '02', 'Literatură română'),
('BOOK003', 'Pădurea spânzuraților', 'Liviu Rebreanu', '9789734640553', '821.135.1 REB p', 'A', '1', '03', 'Literatură română'),
('BOOK004', 'Enigma Otiliei', 'George Călinescu', '9789734640560', '821.135.1 CAL e', 'A', '1', '04', 'Literatură română'),
('BOOK005', 'Moromeții', 'Marin Preda', '9789734640577', '821.135.1 PRE m', 'A', '1', '05', 'Literatură română'),
('BOOK006', 'Ion', 'Liviu Rebreanu', '9789734640584', '821.135.1 REB i', 'A', '2', '01', 'Literatură română'),
('BOOK007', 'Ultima noapte de dragoste, întâia noapte de război', 'Camil Petrescu', '9789734640591', '821.135.1 PET u', 'A', '2', '02', 'Literatură română'),
('BOOK008', 'La țigănci', 'Mircea Eliade', '9789734640607', '821.135.1 ELI l', 'A', '2', '03', 'Literatură română'),
('BOOK009', 'Baltagul', 'Mihail Sadoveanu', '9789734640614', '821.135.1 SAD b', 'A', '2', '04', 'Literatură română'),
('BOOK010', 'Dimineața pierdută', ' Gabriela Adameșteanu', '9789734640621', '821.135.1 ADA d', 'A', '2', '05', 'Literatură română');

-- Inserează date de test pentru cititori
INSERT INTO cititori (cod_bare, nume, prenume, telefon, email) VALUES
('USER001', 'Popescu', 'Ion', '0721123456', 'ion.popescu@email.ro'),
('USER002', 'Ionescu', 'Maria', '0722234567', 'maria.ionescu@email.ro'),
('USER003', 'Dumitrescu', 'Andrei', '0723345678', 'andrei.dumitrescu@email.ro'),
('USER004', 'Stan', 'Elena', '0724456789', 'elena.stan@email.ro'),
('USER005', 'Georgescu', 'Mihai', '0725567890', 'mihai.georgescu@email.ro');

-- Inserează câteva împrumuturi de test
INSERT INTO imprumuturi (cod_cititor, cod_carte, status) VALUES
('USER001', 'BOOK001', 'activ'),
('USER002', 'BOOK002', 'activ'),
('USER001', 'BOOK003', 'returnat');

-- Marchează un împrumut ca returnat
UPDATE imprumuturi SET data_returnare = DATE_SUB(NOW(), INTERVAL 5 DAY), status = 'returnat' WHERE cod_carte = 'BOOK003';

-- Confirmare
SELECT 'Baza de date biblioteca a fost creată cu succes!' as status;

-- Script pentru creare tabel modele email și inserare modele
USE biblioteca;

-- Setează encoding-ul bazei de date la UTF-8
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET CHARACTER SET utf8mb4;
SET character_set_connection=utf8mb4;

-- Creează tabelul pentru modele email
CREATE TABLE IF NOT EXISTS modele_email (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tip_notificare ENUM('imprumut', 'reminder', 'intarziere') NOT NULL,
    subiect VARCHAR(255) NOT NULL,
    template_html TEXT NOT NULL,
    template_text TEXT,
    variabile_utilizate TEXT COMMENT 'Listă variabile disponibile (JSON)',
    activ BOOLEAN DEFAULT TRUE,
    data_creare TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_actualizare TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tip (tip_notificare),
    INDEX idx_activ (activ)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_romanian_ci;

-- Șterge modelele existente (dacă există) pentru a le reînlocui
DELETE FROM modele_email;

-- Model 1: Email la Împrumut
INSERT INTO modele_email (tip_notificare, subiect, template_html, template_text, variabile_utilizate) VALUES
('imprumut', 
'📚 Confirmare Împrumut - Biblioteca Academiei Române - Iași',
'<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: ''Segoe UI'', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
        .email-container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px 20px; }
        .greeting { font-size: 16px; margin-bottom: 20px; color: #555; }
        .book-details { background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .book-item { margin: 10px 0; padding: 10px; background: white; border-radius: 5px; }
        .book-title { font-weight: bold; color: #667eea; font-size: 16px; }
        .book-info { color: #666; font-size: 14px; margin-top: 5px; }
        .info-box { background: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .info-box strong { color: #1976D2; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #ddd; }
        .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>📚 Confirmare Împrumut</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Biblioteca Academiei Române - Iași</p>
        </div>
        <div class="content">
            <div class="greeting">
                Bună ziua <strong>{{NUME_COMPLET}}</strong>,
            </div>
            
            <p>Vă mulțumim că sunteți cititor fidel al bibliotecii noastre și vă confirmăm că ați împrumutat cu succes {{FRAZA_CARTE}}:</p>
            
            <div class="book-details">
                {{LISTA_CARTI}}
            </div>
            
            <div class="info-box">
                <p><strong>📅 Data returnare recomandată:</strong> {{DATA_RETURNARE}}</p>
                <p><strong>📍 Locație bibliotecă:</strong> Biblioteca Academiei Române - Iași</p>
                <p><strong>⏰ Program:</strong> Luni - Vineri: 09:00 - 17:00</p>
            </div>
            
            <p>Vă rugăm să respectați termenul de returnare pentru a permite și altor cititori să beneficieze de {{FRAZA_BENEFICIEZE}}.</p>
            
            <p>Pentru întrebări sau prelungire termen, vă rugăm să ne contactați.</p>
            
            <p style="margin-top: 30px;">Cu respect,<br>
            <strong>Echipa Bibliotecii</strong><br>
            Biblioteca Academiei Române - Iași</p>
        </div>
        <div class="footer">
            <p>Acest email a fost generat automat de sistemul de notificări al bibliotecii.</p>
            <p>Pentru întrebări: bib.acadiasi@gmail.com</p>
        </div>
    </div>
</body>
</html>',
'Bună ziua {{NUME_COMPLET}},

Vă mulțumim că sunteți cititor fidel al bibliotecii noastre și vă confirmăm că ați împrumutat cu succes {{FRAZA_CARTE}}:

{{LISTA_CARTI_TEXT}}

Data returnare recomandată: {{DATA_RETURNARE}}
Locație bibliotecă: Biblioteca Academiei Române - Iași
Program: Luni - Vineri: 09:00 - 17:00

Vă rugăm să respectați termenul de returnare pentru a permite și altor cititori să beneficieze de {{FRAZA_BENEFICIEZE}}.

Cu respect,
Echipa Bibliotecii
Biblioteca Academiei Române - Iași',
'["NUME_COMPLET", "LISTA_CARTI", "LISTA_CARTI_TEXT", "DATA_RETURNARE", "FRAZA_CARTE", "FRAZA_BENEFICIEZE"]');

-- Model 2: Reminder Returnare (12-13 zile)
INSERT INTO modele_email (tip_notificare, subiect, template_html, template_text, variabile_utilizate) VALUES
('reminder',
'⏰ Reminder: Termen Returnare Aproape - Biblioteca Academiei Române - Iași',
'<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: ''Segoe UI'', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
        .email-container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); color: white; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px 20px; }
        .greeting { font-size: 16px; margin-bottom: 20px; color: #555; }
        .book-details { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .book-item { margin: 10px 0; padding: 10px; background: white; border-radius: 5px; }
        .book-title { font-weight: bold; color: #856404; font-size: 16px; }
        .book-info { color: #666; font-size: 14px; margin-top: 5px; }
        .warning-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .warning-box strong { color: #856404; }
        .info-box { background: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .info-box strong { color: #1976D2; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #ddd; }
        .button { display: inline-block; padding: 12px 30px; background: #ffc107; color: #333; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>⏰ Reminder Returnare</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Biblioteca Academiei Române - Iași</p>
        </div>
        <div class="content">
            <div class="greeting">
                Bună ziua <strong>{{NUME_COMPLET}}</strong>,
            </div>
            
            <p>Vă mulțumim că sunteți cititor fidel al bibliotecii noastre.</p>
            
            <p>Vă aducem la cunoștință că termenul de păstrare pentru {{FRAZA_CARTE}} se apropie de scadență:</p>
            
            <div class="book-details">
                {{LISTA_CARTI}}
            </div>
            
            <div class="warning-box">
                <p><strong>📅 Termen returnare:</strong> {{DATA_RETURNARE}}</p>
                <p><strong>⏳ Zile rămase:</strong> {{ZILE_RAMASE}} zile</p>
            </div>
            
            <p>Vă rugăm să returnați {{FRAZA_RETURNARE}} înainte de data scadenței pentru a permite și altor cititori să {{FRAZA_IMPRUMUTE}} împrumute pentru studiu personal.</p>
            
            <div class="info-box">
                <p><strong>📍 Locație bibliotecă:</strong> Biblioteca Academiei Române - Iași</p>
                <p><strong>⏰ Program:</strong> Luni - Vineri: 09:00 - 17:00</p>
                <p><strong>📞 Contact:</strong> Pentru prelungire termen sau întrebări, vă rugăm să ne contactați.</p>
            </div>
            
            <p style="margin-top: 30px;">Cu respect,<br>
            <strong>Echipa Bibliotecii</strong><br>
            Biblioteca Academiei Române - Iași</p>
        </div>
        <div class="footer">
            <p>Acest email a fost generat automat de sistemul de notificări al bibliotecii.</p>
            <p>Pentru întrebări: bib.acadiasi@gmail.com</p>
        </div>
    </div>
</body>
</html>',
'Bună ziua {{NUME_COMPLET}},

Vă mulțumim că sunteți cititor fidel al bibliotecii noastre.

Vă aducem la cunoștință că termenul de păstrare pentru {{FRAZA_CARTE}} se apropie de scadență:

{{LISTA_CARTI_TEXT}}

Termen returnare: {{DATA_RETURNARE}}
Zile rămase: {{ZILE_RAMASE}} zile

Vă rugăm să returnați {{FRAZA_RETURNARE}} înainte de data scadenței pentru a permite și altor cititori să {{FRAZA_IMPRUMUTE}} împrumute pentru studiu personal.

Locație bibliotecă: Biblioteca Academiei Române - Iași
Program: Luni - Vineri: 09:00 - 17:00

Cu respect,
Echipa Bibliotecii
Biblioteca Academiei Române - Iași',
'["NUME_COMPLET", "LISTA_CARTI", "LISTA_CARTI_TEXT", "DATA_RETURNARE", "ZILE_RAMASE", "FRAZA_CARTE", "FRAZA_RETURNARE", "FRAZA_IMPRUMUTE"]');

-- Model 3: Alertă Întârziere (14+ zile)
INSERT INTO modele_email (tip_notificare, subiect, template_html, template_text, variabile_utilizate) VALUES
('intarziere',
'🚨 URGENT: Cărți Întârziate - Acțiune Necesară - Biblioteca Academiei Române - Iași',
'<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: ''Segoe UI'', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
        .email-container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px 20px; }
        .greeting { font-size: 16px; margin-bottom: 20px; color: #555; }
        .book-details { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .book-item { margin: 10px 0; padding: 10px; background: white; border-radius: 5px; }
        .book-title { font-weight: bold; color: #721c24; font-size: 16px; }
        .book-info { color: #666; font-size: 14px; margin-top: 5px; }
        .urgent-box { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .urgent-box strong { color: #721c24; }
        .info-box { background: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .info-box strong { color: #1976D2; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #ddd; }
        .button { display: inline-block; padding: 12px 30px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>🚨 Alertă Întârziere</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Biblioteca Academiei Române - Iași</p>
        </div>
        <div class="content">
            <div class="greeting">
                Bună ziua <strong>{{NUME_COMPLET}}</strong>,
            </div>
            
            <p>Vă mulțumim că sunteți cititor fidel al bibliotecii noastre.</p>
            
            <p><strong>Vă aducem la cunoștință că a expirat termenul de păstrare</strong> pentru {{FRAZA_CARTE}}:</p>
            
            <div class="book-details">
                {{LISTA_CARTI}}
            </div>
            
            <div class="urgent-box">
                <p><strong>⚠️ Data returnare recomandată:</strong> {{DATA_RETURNARE}}</p>
                <p><strong>📅 Data expirare:</strong> {{DATA_EXPIRARE}}</p>
                <p><strong>⏰ Zile întârziere:</strong> {{ZILE_INTARZIERE}} zile</p>
            </div>
            
            <p><strong>Vă rugăm urgent să returnați {{FRAZA_RETURNARE}}</strong> pentru a permite și altor cititori să {{FRAZA_IMPRUMUTE}} împrumute pentru studiu personal.</p>
            
            <p>Înțelegem că pot apărea situații neprevăzute, dar vă rugăm să ne contactați cât mai curând pentru a discuta soluții.</p>
            
            <div class="info-box">
                <p><strong>📍 Locație bibliotecă:</strong> Biblioteca Academiei Române - Iași</p>
                <p><strong>⏰ Program:</strong> Luni - Vineri: 09:00 - 17:00</p>
                <p><strong>📞 Contact:</strong> Pentru întrebări sau prelungire termen, vă rugăm să ne contactați urgent.</p>
            </div>
            
            <p style="margin-top: 30px;">Cu respect,<br>
            <strong>Echipa Bibliotecii</strong><br>
            Biblioteca Academiei Române - Iași</p>
        </div>
        <div class="footer">
            <p>Acest email a fost generat automat de sistemul de notificări al bibliotecii.</p>
            <p>Pentru întrebări: bib.acadiasi@gmail.com</p>
        </div>
    </div>
</body>
</html>',
'Bună ziua {{NUME_COMPLET}},

Vă mulțumim că sunteți cititor fidel al bibliotecii noastre.

Vă aducem la cunoștință că a expirat termenul de păstrare pentru {{FRAZA_CARTE}}:

{{LISTA_CARTI_TEXT}}

Data returnare recomandată: {{DATA_RETURNARE}}
Data expirare: {{DATA_EXPIRARE}}
Zile întârziere: {{ZILE_INTARZIERE}} zile

Vă rugăm urgent să returnați {{FRAZA_RETURNARE}} pentru a permite și altor cititori să {{FRAZA_IMPRUMUTE}} împrumute pentru studiu personal.

Înțelegem că pot apărea situații neprevăzute, dar vă rugăm să ne contactați cât mai curând pentru a discuta soluții.

Locație bibliotecă: Biblioteca Academiei Române - Iași
Program: Luni - Vineri: 09:00 - 17:00

Cu respect,
Echipa Bibliotecii
Biblioteca Academiei Române - Iași',
'["NUME_COMPLET", "LISTA_CARTI", "LISTA_CARTI_TEXT", "DATA_RETURNARE", "DATA_EXPIRARE", "ZILE_INTARZIERE", "FRAZA_CARTE", "FRAZA_RETURNARE", "FRAZA_IMPRUMUTE"]');

-- Confirmare
SELECT 'Modele email create cu succes!' as status;


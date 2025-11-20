<?php
// sistem_notificari.php - Sistem de notificări automate
require_once 'config.php';
require_once 'send_email.php';

// Configurare SMTP (modifică cu datele tale)
$config_email = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_user' => 'YOUR-USER@gmail.com', // Email pentru notificări
    'smtp_pass' => 'xxxx xxxx xxxx xxxx',       // Parolă aplicație Gmail (fără spații)
    'from_email' => 'YOUR-USER@gmail.com',
    'from_name' => 'Biblioteca Academiei Române - Iași'
];

// Configurare SMS (exemplu Twilio)
$config_sms = [
    'enabled' => false, // Activează când ai cont SMS
    'provider' => 'twilio',
    'account_sid' => 'your_account_sid',
    'auth_token' => 'your_auth_token',
    'from_number' => '+40712345678'
];

// Salvare configurare
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_config') {
        // Salvează configurația în fișier sau baza de date
        $mesaj = "✅ Configurația a fost salvată!";
        $tip_mesaj = "success";
    } elseif ($_POST['action'] === 'test_email') {
        $email_test = $_POST['email_test'];
        $subiect = "Test Email Bibliotecă";
        $mesaj_email = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 10px 10px; }
                .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 0.9em; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📧 Test Email - Sistem Bibliotecă</h1>
                </div>
                <div class='content'>
                    <p>Acesta este un <strong>email de test</strong> din sistemul bibliotecii.</p>
                    <p>Dacă primești acest email, înseamnă că configurația SMTP funcționează corect! ✅</p>
                    <p><strong>Detalii test:</strong></p>
                    <ul>
                        <li>Data: " . date('d.m.Y H:i:s') . "</li>
                        <li>Server SMTP: " . $config_email['smtp_host'] . "</li>
                        <li>Port: " . $config_email['smtp_port'] . "</li>
                    </ul>
                </div>
                <div class='footer'>
                    <p>Biblioteca Academiei Române - Iași<br>
                    Sistem de Notificări Automate</p>
                </div>
            </div>
        </body>
        </html>";
        
        // Folosește funcția SMTP pentru trimitere
        $rezultat = trimiteEmailSMTP($email_test, $subiect, $mesaj_email, $config_email);
        
        if ($rezultat['success']) {
            $mesaj = "✅ Email de test trimis cu succes la: <strong>$email_test</strong><br><small>" . $rezultat['message'] . "</small>";
            $tip_mesaj = "success";
        } else {
            $mesaj = "❌ Eroare la trimiterea emailului: <strong>" . htmlspecialchars($rezultat['message']) . "</strong><br><small>Verifică configurația SMTP și parola aplicație Gmail.</small>";
            $tip_mesaj = "danger";
        }
    }
}

// Statistici notificări
$stats_notificari = [
    'trimise_azi' => $pdo->query("SELECT COUNT(*) FROM notificari WHERE DATE(data_trimitere) = CURDATE()")->fetchColumn(),
    'in_asteptare' => $pdo->query("SELECT COUNT(*) FROM imprumuturi WHERE status = 'activ' AND DATEDIFF(NOW(), data_imprumut) >= 12 AND DATEDIFF(NOW(), data_imprumut) < 14")->fetchColumn(),
    'intarzieri_active' => $pdo->query("SELECT COUNT(*) FROM imprumuturi WHERE status = 'activ' AND DATEDIFF(NOW(), data_imprumut) > 14")->fetchColumn(),
];

// Verifică dacă tabelul notificari există, dacă nu - creează-l
try {
    $pdo->query("SELECT 1 FROM notificari LIMIT 1");
} catch (PDOException $e) {
    // Creează tabelul
    $pdo->exec("
        CREATE TABLE notificari (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cod_cititor VARCHAR(50),
            tip_notificare ENUM('imprumut', 'reminder', 'intarziere') NOT NULL,
            canal ENUM('email', 'sms') NOT NULL,
            destinatar VARCHAR(255),
            subiect VARCHAR(255),
            mesaj TEXT,
            status ENUM('trimis', 'esuat', 'in_asteptare') DEFAULT 'in_asteptare',
            data_trimitere DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_cititor (cod_cititor),
            INDEX idx_data (data_trimitere)
        )
    ");
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Notificări</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 15px;
            padding: 20px 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #667eea;
            font-size: 2em;
        }

        .header-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }

        .btn-home {
            background: #28a745;
        }

        .btn-home:hover {
            background: #218838;
        }

        .btn-back {
            background: #667eea;
        }

        .btn-back:hover {
            background: #764ba2;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
        }

        .stat-card h3 {
            font-size: 2.5em;
            color: #667eea;
            margin-bottom: 10px;
        }

        .stat-card p {
            color: #666;
        }

        .section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        input, textarea, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 1.1em;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .notification-types {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .notif-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .notif-card h3 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .notif-card ul {
            margin-left: 20px;
            color: #666;
        }

        .test-section {
            background: #fff3cd;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #ffc107;
            margin-top: 20px;
        }

        .cron-info {
            background: #d1ecf1;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #17a2b8;
            margin-top: 20px;
        }

        .cron-info code {
            background: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            font-family: monospace;
            display: block;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Sistem Notificări Automate</h1>
            <div class="header-buttons">
                <a href="index.php" class="btn btn-home">🏠 Acasă</a>
                <a href="rapoarte.php" class="btn btn-back">← Înapoi</a>
            </div>
        </div>

        <?php if (isset($mesaj)): ?>
            <div class="alert alert-<?php echo $tip_mesaj; ?>">
                <?php echo $mesaj; ?>
            </div>
        <?php endif; ?>

        <!-- Statistici -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats_notificari['trimise_azi']; ?></h3>
                <p>Notificări trimise astăzi</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats_notificari['in_asteptare']; ?></h3>
                <p>Reminder-e programate (12-14 zile)</p>
            </div>
            <div class="stat-card">
                <h3 style="color: #dc3545;"><?php echo $stats_notificari['intarzieri_active']; ?></h3>
                <p>Alerte întârziere (14+ zile)</p>
            </div>
        </div>

        <!-- Tipuri de notificări -->
        <div class="section">
            <h2>📋 Tipuri de Notificări Automate</h2>
            <div class="notification-types">
                <div class="notif-card">
                    <h3>📚 Email la Împrumut</h3>
                    <ul>
                        <li>Trimis imediat la împrumut</li>
                        <li>Confirmare detalii carte</li>
                        <li>Data returnare recomandată</li>
                        <li>Locația cărții în bibliotecă</li>
                    </ul>
                </div>

                <div class="notif-card">
                    <h3>⏰ Reminder Returnare</h3>
                    <ul>
                        <li>Trimis după 12-13 zile</li>
                        <li>Reamintește termen returnare (14 zile)</li>
                        <li>Link harta bibliotecă</li>
                        <li>Opțiune prelungire</li>
                    </ul>
                </div>

                <div class="notif-card">
                    <h3>🚨 Alertă Întârziere</h3>
                    <ul>
                        <li>Trimis după 14 zile</li>
                        <li>Notificare urgentă returnare</li>
                        <li>Informații penalizări (opțional)</li>
                        <li>Repetată la fiecare 7 zile</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Configurare Email -->
        <div class="section">
            <h2>⚙️ Configurare Email (SMTP)</h2>
            <form method="POST">
                <input type="hidden" name="action" value="save_config">
                
                <div class="form-group">
                    <label>SMTP Host</label>
                    <input type="text" name="smtp_host" value="<?php echo $config_email['smtp_host']; ?>" required>
                    <small style="color: #666;">Exemplu: smtp.gmail.com, smtp.office365.com</small>
                </div>

                <div class="form-group">
                    <label>SMTP Port</label>
                    <input type="number" name="smtp_port" value="<?php echo $config_email['smtp_port']; ?>" required>
                    <small style="color: #666;">Port: 587 (TLS) sau 465 (SSL)</small>
                </div>

                <div class="form-group">
                    <label>Email Bibliotecă (Gmail)</label>
                    <input type="email" name="smtp_user" value="<?php echo $config_email['smtp_user']; ?>" required>
                    <small style="color: #666; display: block; margin-top: 5px;">
                        ⚠️ Pentru Gmail: Folosește "Parolă aplicație" (App Password), nu parola contului!
                    </small>
                </div>

                <div class="form-group">
                    <label>Parolă Email / Parolă Aplicație</label>
                    <input type="password" name="smtp_pass" placeholder="••••••••" required>
                    <small style="color: #666;">Pentru Gmail: folosește "Parolă aplicație" (App Password)</small>
                </div>

                <button type="submit" class="btn-primary">💾 Salvează Configurația</button>
            </form>

            <!-- Test Email -->
            <div class="test-section">
                <h3>🧪 Test Email</h3>
                <form method="POST" style="margin-top: 15px;">
                    <input type="hidden" name="action" value="test_email">
                    <div class="form-group">
                        <label>Email pentru test</label>
                        <input type="email" name="email_test" placeholder="ioan.fantanaru@gmail.com" value="ioan.fantanaru@gmail.com" required>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            💡 Email-ul va fi trimis imediat pentru testare
                        </small>
                    </div>
                    <button type="submit" class="btn-primary">📧 Trimite Email de Test Simplu</button>
                </form>
                
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <p><strong>🧪 Test Modele Email Personalizate:</strong></p>
                    <a href="test_modele_email.php" class="btn-primary" style="display: inline-block; margin-top: 10px; text-decoration: none;">
                        📧 Test Modele Email (Imprumut/Reminder/Întârziere)
                    </a>
                    <small style="display: block; margin-top: 10px; color: #666;">
                        Testează modelele de email cu date personalizate (nume cititor, cărți, etc.)
                    </small>
                </div>
            </div>
        </div>

        <!-- Configurare automată CRON -->
        <div class="section">
            <h2>🤖 Automatizare Notificări (CRON)</h2>
            <div class="cron-info">
                <h3>📅 Configurare CRON Job</h3>
                <p>Pentru ca notificările să fie trimise automat, trebuie să configurezi un CRON job care rulează zilnic.</p>
                
                <h4 style="margin-top: 20px;">Opțiunea 1: Linux/cPanel CRON</h4>
                <code>0 9 * * * php /path/to/biblioteca/cron_notificari.php</code>
                <small style="display: block; margin-top: 10px; color: #666;">
                    Rulează în fiecare zi la ora 09:00
                </small>

                <h4 style="margin-top: 20px;">Opțiunea 2: Windows Task Scheduler</h4>
                <code>php.exe "C:\xampp\htdocs\biblioteca\cron_notificari.php"</code>
                <small style="display: block; margin-top: 10px; color: #666;">
                    Creează task în Task Scheduler să ruleze zilnic
                </small>

                <h4 style="margin-top: 20px;">Opțiunea 3: Rulare Manuală</h4>
                <a href="cron_notificari.php" class="btn-primary" style="display: inline-block; margin-top: 10px; text-decoration: none;">
                    ▶️ Rulează Acum (Manual)
                </a>
                <small style="display: block; margin-top: 10px; color: #666;">
                    Click pentru a trimite toate notificările programate
                </small>
            </div>
        </div>

        <!-- Log notificări recente -->
        <div class="section">
            <h2>📜 Log Notificări Recente (Ultimele 50)</h2>
            <?php
            $stmt = $pdo->query("
                SELECT 
                    n.*,
                    CONCAT(c.nume, ' ', c.prenume) as cititor_nume
                FROM notificari n
                LEFT JOIN cititori c ON n.cod_cititor = c.cod_bare
                ORDER BY n.data_trimitere DESC
                LIMIT 50
            ");
            $log_notificari = $stmt->fetchAll();
            ?>
            
            <?php if (count($log_notificari) > 0): ?>
                <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                    <thead>
                        <tr style="background: #667eea; color: white;">
                            <th style="padding: 12px; text-align: left;">Data</th>
                            <th style="padding: 12px; text-align: left;">Cititor</th>
                            <th style="padding: 12px; text-align: left;">Tip</th>
                            <th style="padding: 12px; text-align: left;">Canal</th>
                            <th style="padding: 12px; text-align: left;">Destinatar</th>
                            <th style="padding: 12px; text-align: left;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($log_notificari as $log): ?>
                            <tr style="border-bottom: 1px solid #ddd;">
                                <td style="padding: 12px;"><?php echo date('d.m.Y H:i', strtotime($log['data_trimitere'])); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($log['cititor_nume'] ?? '-'); ?></td>
                                <td style="padding: 12px;">
                                    <?php
                                    $icons = ['imprumut' => '📚', 'reminder' => '⏰', 'intarziere' => '🚨'];
                                    echo $icons[$log['tip_notificare']] . ' ' . ucfirst($log['tip_notificare']);
                                    ?>
                                </td>
                                <td style="padding: 12px;"><?php echo $log['canal'] === 'email' ? '📧 Email' : '📱 SMS'; ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($log['destinatar']); ?></td>
                                <td style="padding: 12px;">
                                    <?php if ($log['status'] === 'trimis'): ?>
                                        <span style="background: #d4edda; color: #155724; padding: 5px 10px; border-radius: 5px;">✅ Trimis</span>
                                    <?php elseif ($log['status'] === 'esuat'): ?>
                                        <span style="background: #f8d7da; color: #721c24; padding: 5px 10px; border-radius: 5px;">❌ Eșuat</span>
                                    <?php else: ?>
                                        <span style="background: #fff3cd; color: #856404; padding: 5px 10px; border-radius: 5px;">⏳ Așteptare</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; padding: 40px; color: #999;">Nu există notificări trimise încă.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php
// trimite_notificare.php - Trimite notificări manuale către cititori
session_start();
require_once 'config.php';
require_once 'auth_check.php';

$cod_cititor = $_GET['cod_cititor'] ?? '';
$tip = $_GET['tip'] ?? 'email'; // email sau sms

if (empty($cod_cititor)) {
    die("❌ Cod cititor lipsă!");
}

// Obține date cititor și cărți împrumutate
$stmt = $pdo->prepare("
    SELECT 
        cit.*,
        GROUP_CONCAT(
            CONCAT(c.titlu, ' (', DATEDIFF(NOW(), i.data_imprumut), ' zile)')
            SEPARATOR ', '
        ) as carti_lista
    FROM cititori cit
    LEFT JOIN imprumuturi i ON cit.cod_bare = i.cod_cititor AND i.status = 'activ'
    LEFT JOIN carti c ON i.cod_carte = c.cod_bare
    WHERE cit.cod_bare = ?
    GROUP BY cit.cod_bare
");
$stmt->execute([$cod_cititor]);
$cititor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cititor) {
    die("❌ Cititor negăsit!");
}

// Obține listă detaliată împrumuturi
$stmt = $pdo->prepare("
    SELECT 
        c.titlu,
        c.autor,
        i.data_imprumut,
        DATEDIFF(NOW(), i.data_imprumut) as zile_imprumut
    FROM imprumuturi i
    JOIN carti c ON i.cod_carte = c.cod_bare
    WHERE i.cod_cititor = ? AND i.status = 'activ'
    ORDER BY i.data_imprumut ASC
");
$stmt->execute([$cod_cititor]);
$imprumuturi = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mesaj_trimis = '';
$tip_mesaj = '';

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mesaj_personalizat = $_POST['mesaj_personalizat'] ?? '';
    
    if ($tip === 'email' && !empty($cititor['email'])) {
        // Construiește lista cărți pentru email
        $carti_html = '';
        foreach ($imprumuturi as $imp) {
            $badge_color = $imp['zile_imprumut'] > 14 ? '#dc3545' : '#ffc107';
            $carti_html .= "
            <div style='background: #f8f9fa; padding: 12px; margin: 10px 0; border-left: 4px solid {$badge_color};'>
                <p><strong>📕 {$imp['titlu']}</strong></p>
                <p style='color: #666; font-size: 0.9em;'>✍️ {$imp['autor']}</p>
                <p style='color: #666; font-size: 0.9em;'>📅 Împrumutată: " . date('d.m.Y', strtotime($imp['data_imprumut'])) . "</p>
                <p><strong style='color: {$badge_color};'>⏰ {$imp['zile_imprumut']} zile împrumut</strong></p>
            </div>
            ";
        }
        
        $subiect = "📚 Reminder Returnare Cărți - Biblioteca";
        $mesaj_email = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <h2 style='color: #667eea;'>Bună {$cititor['prenume']},</h2>
            
            <p>Îți trimitem o reamintire legată de cărțile împrumutate de la bibliotecă:</p>
            
            {$carti_html}
            
            " . (!empty($mesaj_personalizat) ? "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;'>
                <p><strong>📝 Mesaj de la bibliotecar:</strong></p>
                <p>" . nl2br(htmlspecialchars($mesaj_personalizat)) . "</p>
            </div>" : "") . "
            
            <p><strong>Te așteptăm la bibliotecă pentru returnarea cărților!</strong></p>
            
            <hr style='margin: 30px 0;'>
            <p style='font-size: 0.9em; color: #666;'>
                <strong>Biblioteca Municipală</strong><br>
                Email: biblioteca@example.com<br>
                Telefon: 0231-123-456
            </p>
        </body>
        </html>
        ";
        
        $headers = "From: Biblioteca Municipală <biblioteca@example.com>\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        if (mail($cititor['email'], $subiect, $mesaj_email, $headers)) {
            // Salvează în log
            $pdo->prepare("INSERT INTO notificari (cod_cititor, tip_notificare, canal, destinatar, subiect, mesaj, status) VALUES (?, 'intarziere', 'email', ?, ?, ?, 'trimis')")
                ->execute([$cod_cititor, $cititor['email'], $subiect, strip_tags($mesaj_email)]);
            
            $mesaj_trimis = "✅ Email trimis cu succes către: <strong>" . htmlspecialchars($cititor['email']) . "</strong>";
            $tip_mesaj = "success";
        } else {
            $mesaj_trimis = "❌ Eroare la trimiterea emailului. Verifică configurația SMTP.";
            $tip_mesaj = "danger";
        }
        
    } elseif ($tip === 'sms' && !empty($cititor['telefon'])) {
        // SMS (implementare simplificată - necesită serviciu SMS)
        $mesaj_sms = "Biblioteca: Ai {count($imprumuturi)} carte(i) de returnat. Detalii: " . $cititor['carti_lista'];
        
        // AICI implementezi integrarea cu serviciul SMS (Twilio, etc)
        // Exemplu placeholder:
        $mesaj_trimis = "📱 Funcția SMS nu este încă configurată. Contactează cititorul la: <strong>" . htmlspecialchars($cititor['telefon']) . "</strong>";
        $tip_mesaj = "warning";
        
        // TODO: Integrare Twilio sau alt serviciu SMS
        /*
        require 'vendor/autoload.php';
        use Twilio\Rest\Client;
        
        $twilio = new Client($account_sid, $auth_token);
        $message = $twilio->messages->create(
            $cititor['telefon'],
            [
                'from' => '+40712345678',
                'body' => $mesaj_sms
            ]
        );
        */
    } else {
        $mesaj_trimis = "❌ Cititor fără date de contact valide!";
        $tip_mesaj = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trimite Notificare</title>
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
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        h1 {
            color: #667eea;
            margin-bottom: 30px;
            text-align: center;
        }

        .cititor-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }

        .cititor-info h2 {
            color: #333;
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: 600;
            color: #666;
            min-width: 120px;
        }

        .info-value {
            color: #333;
        }

        .carti-list {
            list-style: none;
            margin-top: 15px;
        }

        .carte-item {
            background: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 3px solid #dc3545;
        }

        .carte-item.ok {
            border-left-color: #28a745;
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

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
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

        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            font-family: inherit;
            min-height: 120px;
        }

        textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 1em;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-back {
            background: #6c757d;
            color: white;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }

        .btn-back:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $tip === 'email' ? '📧' : '📱'; ?> Trimite Notificare</h1>

        <?php if (!empty($mesaj_trimis)): ?>
            <div class="alert alert-<?php echo $tip_mesaj; ?>">
                <?php echo $mesaj_trimis; ?>
            </div>
        <?php endif; ?>

        <!-- Info cititor -->
        <div class="cititor-info">
            <h2>👤 <?php echo htmlspecialchars($cititor['nume'] . ' ' . $cititor['prenume']); ?></h2>
            <div class="info-row">
                <span class="info-label">📧 Email:</span>
                <span class="info-value"><?php echo htmlspecialchars($cititor['email'] ?: 'Lipsă'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">📞 Telefon:</span>
                <span class="info-value"><?php echo htmlspecialchars($cititor['telefon'] ?: 'Lipsă'); ?></span>
            </div>
            
            <h3 style="margin-top: 20px; color: #dc3545;">📚 Cărți împrumutate:</h3>
            <?php if (count($imprumuturi) > 0): ?>
                <ul class="carti-list">
                    <?php foreach ($imprumuturi as $imp): ?>
                        <li class="carte-item <?php echo $imp['zile_imprumut'] <= 14 ? 'ok' : ''; ?>">
                            <strong><?php echo htmlspecialchars($imp['titlu']); ?></strong><br>
                            <small style="color: #666;">
                                <?php echo htmlspecialchars($imp['autor']); ?> • 
                                <?php echo date('d.m.Y', strtotime($imp['data_imprumut'])); ?> • 
                                <strong style="color: <?php echo $imp['zile_imprumut'] > 14 ? '#dc3545' : '#28a745'; ?>">
                                    <?php echo $imp['zile_imprumut']; ?> zile
                                </strong>
                            </small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: #28a745; margin-top: 10px;">✅ Nu are cărți împrumutate momentan.</p>
            <?php endif; ?>
        </div>

        <!-- Formular trimitere -->
        <?php if (count($imprumuturi) > 0): ?>
            <form method="POST">
                <div class="form-group">
                    <label>📝 Mesaj personalizat (opțional)</label>
                    <textarea name="mesaj_personalizat" placeholder="Adaugă un mesaj personalizat pentru cititor..."></textarea>
                    <small style="color: #666;">Acest mesaj va fi adăugat în <?php echo $tip === 'email' ? 'email' : 'SMS'; ?>.</small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?php echo $tip === 'email' ? '📧 Trimite Email' : '📱 Trimite SMS'; ?>
                </button>
            </form>
        <?php endif; ?>

        <a href="raport_intarzieri.php" class="btn btn-back">← Înapoi la Raport Întârzieri</a>
    </div>
</body>
</html>
<?php
// cron_notificari.php - Script automat pentru trimitere notificări
// Rulează zilnic prin CRON sau Task Scheduler

require_once 'config.php';
require_once 'send_email.php';
require_once 'functions_email_templates.php';
require_once 'sistem_notificari.php'; // Pentru configurația email

// Log start
echo "=== CRON Notificări START: " . date('Y-m-d H:i:s') . " ===\n";

// 1. REMINDER RETURNARE (12-13 zile de la împrumut)
echo "\n1. Procesare REMINDER-e...\n";

$stmt = $pdo->query("
    SELECT 
        i.id as imprumut_id,
        i.cod_cititor,
        i.cod_carte,
        i.data_imprumut,
        c.titlu,
        c.autor,
        c.locatie_completa,
        cit.nume,
        cit.prenume,
        cit.email,
        cit.telefon,
        DATEDIFF(NOW(), i.data_imprumut) as zile_imprumut
    FROM imprumuturi i
    JOIN carti c ON i.cod_carte = c.cod_bare
    JOIN cititori cit ON i.cod_cititor = cit.cod_bare
    WHERE i.status = 'activ' 
    AND DATEDIFF(NOW(), i.data_imprumut) BETWEEN 12 AND 13
    AND NOT EXISTS (
        SELECT 1 FROM notificari 
        WHERE cod_cititor = i.cod_cititor 
        AND tip_notificare = 'reminder'
        AND DATE(data_trimitere) = CURDATE()
    )
");

// Grupează împrumuturile pe cititor
$imprumuturi_grupate = [];
while ($imp = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cod_cititor = $imp['cod_cititor'];
    if (!isset($imprumuturi_grupate[$cod_cititor])) {
        $imprumuturi_grupate[$cod_cititor] = [
            'cititor' => [
                'nume' => $imp['nume'],
                'prenume' => $imp['prenume'],
                'email' => $imp['email']
            ],
            'carti' => []
        ];
    }
    $imprumuturi_grupate[$cod_cititor]['carti'][] = [
        'titlu' => $imp['titlu'],
        'autor' => $imp['autor'],
        'cod_bare' => $imp['cod_carte'],
        'data_imprumut' => $imp['data_imprumut'],
        'locatie_completa' => $imp['locatie_completa']
    ];
}

$reminder_count = 0;
foreach ($imprumuturi_grupate as $cod_cititor => $data) {
    if (!empty($data['cititor']['email'])) {
        // Calculează data returnare (14 zile de la prima carte)
        $data_returnare = date('Y-m-d', strtotime($data['carti'][0]['data_imprumut'] . ' +14 days'));
        
        // Trimite email folosind funcția personalizată
        $rezultat = trimiteEmailPersonalizat(
            $data['cititor']['email'],
            'reminder',
            $data['cititor'],
            $data['carti'],
            $config_email,
            $data_returnare
        );
        
        if ($rezultat['success']) {
            // Salvează în log
            $pdo->prepare("INSERT INTO notificari (cod_cititor, tip_notificare, canal, destinatar, subiect, mesaj, status) VALUES (?, 'reminder', 'email', ?, ?, ?, 'trimis')")
                ->execute([
                    $cod_cititor,
                    $data['cititor']['email'],
                    'Reminder Returnare',
                    'Email reminder trimis cu succes'
                ]);
            
            echo "  ✅ Reminder trimis: {$data['cititor']['nume']} {$data['cititor']['prenume']} - {$data['cititor']['email']} (" . count($data['carti']) . " cărți)\n";
            $reminder_count++;
        } else {
            echo "  ❌ EROARE trimitere: {$data['cititor']['email']} - {$rezultat['message']}\n";
        }
    }
}
echo "Total reminder-e trimise: $reminder_count\n";

// 2. ALERTE ÎNTÂRZIERE (14+ zile)
echo "\n2. Procesare ALERTE ÎNTÂRZIERE...\n";

$stmt = $pdo->query("
    SELECT 
        i.id as imprumut_id,
        i.cod_cititor,
        i.cod_carte,
        i.data_imprumut,
        c.titlu,
        c.autor,
        cit.nume,
        cit.prenume,
        cit.email,
        cit.telefon,
        DATEDIFF(NOW(), i.data_imprumut) as zile_intarziere
    FROM imprumuturi i
    JOIN carti c ON i.cod_carte = c.cod_bare
    JOIN cititori cit ON i.cod_cititor = cit.cod_bare
    WHERE i.status = 'activ' 
    AND DATEDIFF(NOW(), i.data_imprumut) > 14
    AND (
        NOT EXISTS (
            SELECT 1 FROM notificari 
            WHERE cod_cititor = i.cod_cititor 
            AND tip_notificare = 'intarziere'
            AND DATE(data_trimitere) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        )
    )
");

// Grupează împrumuturile pe cititor
$imprumuturi_intarziate_grupate = [];
while ($imp = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cod_cititor = $imp['cod_cititor'];
    if (!isset($imprumuturi_intarziate_grupate[$cod_cititor])) {
        $imprumuturi_intarziate_grupate[$cod_cititor] = [
            'cititor' => [
                'nume' => $imp['nume'],
                'prenume' => $imp['prenume'],
                'email' => $imp['email']
            ],
            'carti' => []
        ];
    }
    $imprumuturi_intarziate_grupate[$cod_cititor]['carti'][] = [
        'titlu' => $imp['titlu'],
        'autor' => $imp['autor'],
        'cod_bare' => $imp['cod_carte'],
        'data_imprumut' => $imp['data_imprumut'],
        'zile_intarziere' => $imp['zile_intarziere']
    ];
}

$intarziere_count = 0;
foreach ($imprumuturi_intarziate_grupate as $cod_cititor => $data) {
    if (!empty($data['cititor']['email'])) {
        // Calculează data returnare (14 zile de la prima carte)
        $data_returnare = date('Y-m-d', strtotime($data['carti'][0]['data_imprumut'] . ' +14 days'));
        
        // Trimite email folosind funcția personalizată
        $rezultat = trimiteEmailPersonalizat(
            $data['cititor']['email'],
            'intarziere',
            $data['cititor'],
            $data['carti'],
            $config_email,
            $data_returnare
        );
        
        if ($rezultat['success']) {
            // Salvează în log
            $pdo->prepare("INSERT INTO notificari (cod_cititor, tip_notificare, canal, destinatar, subiect, mesaj, status) VALUES (?, 'intarziere', 'email', ?, ?, ?, 'trimis')")
                ->execute([
                    $cod_cititor,
                    $data['cititor']['email'],
                    'Alertă Întârziere',
                    'Email alertă întârziere trimis cu succes'
                ]);
            
            echo "  🚨 Alertă trimisă: {$data['cititor']['nume']} {$data['cititor']['prenume']} - {$data['cititor']['email']} (" . count($data['carti']) . " cărți)\n";
            $intarziere_count++;
        } else {
            echo "  ❌ EROARE trimitere: {$data['cititor']['email']} - {$rezultat['message']}\n";
        }
    }
}
echo "Total alerte trimise: $intarziere_count\n";

echo "\n=== CRON Notificări END: " . date('Y-m-d H:i:s') . " ===\n";
echo "TOTAL: $reminder_count reminder-e + $intarziere_count alerte = " . ($reminder_count + $intarziere_count) . " notificări\n";
?>
<?php
/**
 * Script pentru repararea automată a datelor corupte din baza de date
 * Scanează toate cărțile și le repară cu datele corecte din Aleph
 */

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

require_once 'config.php';
require_once 'aleph_api.php';

echo "<!DOCTYPE html>
<html lang='ro'>
<head>
    <meta charset='UTF-8'>
    <title>Reparare Date Corupte</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container { 
            max-width: 1000px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 10px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        h1 { 
            color: #667eea; 
            margin: 0;
        }
        .btn-home {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
            font-weight: 600;
            display: inline-block;
        }
        .btn-home:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }
        .success { color: #28a745; font-weight: bold; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #dc3545; font-weight: bold; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3; margin: 15px 0; border-radius: 5px; }
        .warning { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        tr:hover { background: #f5f5f5; }
        .progress { background: #e9ecef; border-radius: 10px; padding: 3px; margin: 10px 0; }
        .progress-bar { background: #28a745; height: 20px; border-radius: 10px; text-align: center; color: white; line-height: 20px; }
        .btn-repara {
            padding: 15px 30px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-repara:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🔧 Reparare Date Corupte din Baza de Date</h1>
            <a href='index.php' class='btn-home'>🏠 Acasă</a>
        </div>";

// Funcție pentru a verifica dacă datele sunt corupte
function verificaDateCorupte($carte) {
    if (!$carte) return false;
    $titlu = $carte['titlu'] ?? '';
    $autor = $carte['autor'] ?? '';
    return (preg_match('/\?{2,}/', $titlu) || preg_match('/\?{2,}/', $autor) || 
            (preg_match('/\?/', $titlu) && strlen($titlu) > 5) ||
            (preg_match('/\?/', $autor) && strlen($autor) > 3));
}

// Verifică dacă s-a făcut click pe butonul de reparare
if (isset($_POST['repara'])) {
    echo "<div class='info'><strong>🔄 Procesare reparare...</strong></div>";
    
    // Găsește toate cărțile cu date corupte
    $stmt = $pdo->query("SELECT * FROM carti ORDER BY id");
    $toate_carti = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total = count($toate_carti);
    $reparate = 0;
    $eroare = 0;
    $nu_gasite = 0;
    $ok = 0;
    
    echo "<table>";
    echo "<tr><th>#</th><th>Cod</th><th>Titlu (ÎNAINTE)</th><th>Titlu (DUPĂ)</th><th>Status</th></tr>";
    
    foreach ($toate_carti as $index => $carte) {
        $numar = $index + 1;
        
        // Verifică dacă are date corupte
        if (!verificaDateCorupte($carte)) {
            $ok++;
            continue; // Skip cărțile care sunt OK
        }
        
        $cod_cautare = !empty($carte['cod_bare']) ? $carte['cod_bare'] : $carte['cota'];
        $titlu_vechi = htmlspecialchars($carte['titlu'], ENT_QUOTES, 'UTF-8');
        
        if (empty($cod_cautare)) {
            echo "<tr><td>$numar</td><td>-</td><td>$titlu_vechi</td><td>-</td><td class='error'>❌ Fără cod de căutare</td></tr>";
            $eroare++;
            continue;
        }
        
        // Caută în Aleph
        try {
            $rezultat_aleph = cautaCarteInAleph($cod_cautare, 'AUTO');
            
            if ($rezultat_aleph['success']) {
                $date_carte = $rezultat_aleph['data'];
                
                // Pregătește datele pentru actualizare
                $titlu = !empty($date_carte['titlu']) ? 
                    (mb_check_encoding($date_carte['titlu'], 'UTF-8') ? 
                        $date_carte['titlu'] : 
                        mb_convert_encoding($date_carte['titlu'], 'UTF-8', 'ISO-8859-2')) : $carte['titlu'];
                
                $autor = !empty($date_carte['autor']) ? 
                    (mb_check_encoding($date_carte['autor'], 'UTF-8') ? 
                        $date_carte['autor'] : 
                        mb_convert_encoding($date_carte['autor'], 'UTF-8', 'ISO-8859-2')) : ($carte['autor'] ?? '');
                
                $isbn = !empty($date_carte['isbn']) ? $date_carte['isbn'] : ($carte['isbn'] ?? '');
                $cota = !empty($date_carte['cota']) ? $date_carte['cota'] : ($carte['cota'] ?? '');
                $sectiune = !empty($date_carte['sectiune']) ? $date_carte['sectiune'] : ($carte['sectiune'] ?? '');
                
                // Actualizează în baza de date
                $stmt_update = $pdo->prepare("
                    UPDATE carti 
                    SET titlu = ?, autor = ?, isbn = ?, cota = ?, sectiune = ?
                    WHERE id = ?
                ");
                $stmt_update->execute([
                    $titlu,
                    $autor,
                    $isbn,
                    $cota,
                    $sectiune,
                    $carte['id']
                ]);
                
                $titlu_nou = htmlspecialchars($titlu, ENT_QUOTES, 'UTF-8');
                echo "<tr><td>$numar</td><td>" . htmlspecialchars($cod_cautare, ENT_QUOTES, 'UTF-8') . "</td><td>$titlu_vechi</td><td>$titlu_nou</td><td class='success'>✅ Reparată</td></tr>";
                $reparate++;
                
                // Pauză mică pentru a nu suprasolicita Aleph
                usleep(500000); // 0.5 secunde
                
            } else {
                echo "<tr><td>$numar</td><td>" . htmlspecialchars($cod_cautare, ENT_QUOTES, 'UTF-8') . "</td><td>$titlu_vechi</td><td>-</td><td class='error'>❌ Nu găsită în Aleph</td></tr>";
                $nu_gasite++;
            }
        } catch (Exception $e) {
            echo "<tr><td>$numar</td><td>" . htmlspecialchars($cod_cautare, ENT_QUOTES, 'UTF-8') . "</td><td>$titlu_vechi</td><td>-</td><td class='error'>❌ Eroare: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</td></tr>";
            $eroare++;
        }
    }
    
    echo "</table>";
    
    // Rezumat
    echo "<h2>📊 Rezumat</h2>";
    echo "<div class='info'>";
    echo "<p><strong>Total cărți verificate:</strong> $total</p>";
    echo "<p class='success'><strong>✅ Reparate:</strong> $reparate</p>";
    echo "<p><strong>✅ OK (fără probleme):</strong> $ok</p>";
    echo "<p class='error'><strong>❌ Nu găsite în Aleph:</strong> $nu_gasite</p>";
    echo "<p class='error'><strong>❌ Erori:</strong> $eroare</p>";
    echo "</div>";
    
    if ($reparate > 0) {
        echo "<div class='success'><strong>🎉 Reparare completă! $reparate cărți au fost reparate cu succes.</strong></div>";
    }
    
} else {
    // Afișează lista cu cărțile corupte
    echo "<div class='info'><strong>ℹ️ Acest script va:</strong><br>";
    echo "1. Scana toate cărțile din baza de date<br>";
    echo "2. Identifica cărțile cu date corupte (semne de întrebare)<br>";
    echo "3. Căuta datele corecte în Aleph<br>";
    echo "4. Actualiza automat datele în baza de date<br>";
    echo "<strong>⚠️ Atenție:</strong> Procesul poate dura câteva minute pentru baze de date mari.</div>";
    
    // Găsește cărțile cu date corupte
    $stmt = $pdo->query("SELECT * FROM carti ORDER BY id");
    $toate_carti = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $corupte = [];
    foreach ($toate_carti as $carte) {
        if (verificaDateCorupte($carte)) {
            $corupte[] = $carte;
        }
    }
    
    $total = count($toate_carti);
    $numar_corupte = count($corupte);
    
    echo "<h2>📋 Statistici</h2>";
    echo "<div class='info'>";
    echo "<p><strong>Total cărți în baza de date:</strong> $total</p>";
    if ($numar_corupte > 0) {
        echo "<p class='warning'><strong>⚠️ Cărți cu date corupte:</strong> $numar_corupte</p>";
    } else {
        echo "<p class='success'><strong>✅ Nu există cărți cu date corupte!</strong></p>";
    }
    echo "</div>";
    
    if ($numar_corupte > 0) {
        echo "<h2>🔍 Cărți cu date corupte (primele 20)</h2>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Cod</th><th>Titlu</th><th>Autor</th></tr>";
        
        $afisat = 0;
        foreach ($corupte as $carte) {
            if ($afisat >= 20) break;
            echo "<tr>";
            echo "<td>" . $carte['id'] . "</td>";
            echo "<td>" . htmlspecialchars($carte['cod_bare'] ?? $carte['cota'] ?? '-', ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td>" . htmlspecialchars($carte['titlu'], ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td>" . htmlspecialchars($carte['autor'] ?? '-', ENT_QUOTES, 'UTF-8') . "</td>";
            echo "</tr>";
            $afisat++;
        }
        echo "</table>";
        
        if ($numar_corupte > 20) {
            echo "<p><em>... și încă " . ($numar_corupte - 20) . " cărți</em></p>";
        }
        
        echo "<form method='POST' style='margin-top: 30px;'>";
        echo "<button type='submit' name='repara' class='btn-repara'>";
        echo "🔧 Repară toate cărțile corupte ($numar_corupte cărți)";
        echo "</button>";
        echo "</form>";
    }
}

echo "    </div>
</body>
</html>";
?>


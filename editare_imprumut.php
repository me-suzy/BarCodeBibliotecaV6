<?php
// editare_imprumut.php - Editare împrumut
session_start();
require_once 'config.php';
require_once 'auth_check.php';

$mesaj = '';
$tip_mesaj = '';

// Verifică dacă avem ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $base_url = 'http://' . $_SERVER['HTTP_HOST'];
    ?>
    <!DOCTYPE html>
    <html lang="ro">
    <head>
        <meta charset="UTF-8">
        <title>Eroare</title>
        <style>
            body { 
                font-family: 'Segoe UI', Arial; 
                text-align: center; 
                padding: 80px; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .error-box {
                background: white;
                padding: 40px;
                border-radius: 15px;
                max-width: 500px;
                margin: 0 auto;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            }
            h1 { color: #dc3545; margin-bottom: 20px; }
            p { color: #666; margin-bottom: 25px; }
            a { 
                display: inline-block;
                padding: 12px 30px; 
                background: #667eea; 
                color: white; 
                text-decoration: none; 
                border-radius: 8px;
                font-weight: 600;
            }
            a:hover { background: #764ba2; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h1>⚠️ ID împrumut lipsă</h1>
            <p>Nu ai selectat niciun împrumut pentru editare.</p>
            <a href="<?php echo $base_url; ?>/imprumuturi.php">📖 Înapoi la listă</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$id = (int)$_GET['id'];

// Obține datele împrumutului
$stmt = $pdo->prepare("
    SELECT
        i.*,
        c.titlu as carte_titlu,
        c.autor as carte_autor,
        cit.nume as cititor_nume,
        cit.prenume as cititor_prenume
    FROM imprumuturi i
    JOIN carti c ON i.cod_carte = c.cod_bare
    JOIN cititori cit ON i.cod_cititor = cit.cod_bare
    WHERE i.id = ?
");
$stmt->execute([$id]);
$imprumut = $stmt->fetch();

if (!$imprumut) {
    die('Împrumutul nu a fost găsit');
}

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CITITORUL ȘI CARTEA NU SE POT SCHIMBA - folosim valorile originale
    $cod_cititor = $imprumut['cod_cititor'];
    $cod_carte = $imprumut['cod_carte'];
    
    // Doar acestea se pot edita
    $data_imprumut = trim($_POST['data_imprumut']);
    $data_returnare = trim($_POST['data_returnare']);
    $status = trim($_POST['status']);

    try {
        // Logică automată: Dacă status = "returnat" și nu ai dată, pune data curentă
        if ($status === 'returnat' && empty($data_returnare)) {
            $data_returnare = date('Y-m-d H:i:s');
        }
        
        // Logică automată: Dacă ai dată returnare, statusul devine automat "returnat"
        if (!empty($data_returnare)) {
            $status = 'returnat';
        }

        $stmt = $pdo->prepare("
            UPDATE imprumuturi
            SET data_imprumut = ?, data_returnare = ?, status = ?
            WHERE id = ?
        ");

        // Convertește datele goale în NULL
        $data_returnare_null = empty($data_returnare) ? null : $data_returnare;

        $stmt->execute([$data_imprumut, $data_returnare_null, $status, $id]);

        $mesaj = "✅ Împrumutul a fost actualizat cu succes!";
        $tip_mesaj = "success";

        // Reîncarcă datele
        $stmt = $pdo->prepare("
            SELECT
                i.*,
                c.titlu as carte_titlu,
                c.autor as carte_autor,
                cit.nume as cititor_nume,
                cit.prenume as cititor_prenume
            FROM imprumuturi i
            JOIN carti c ON i.cod_carte = c.cod_bare
            JOIN cititori cit ON i.cod_cititor = cit.cod_bare
            WHERE i.id = ?
        ");
        $stmt->execute([$id]);
        $imprumut = $stmt->fetch();

    } catch (PDOException $e) {
        $mesaj = "❌ Eroare: " . $e->getMessage();
        $tip_mesaj = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editare Împrumut - Sistem Bibliotecă</title>
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
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        h1 {
            color: #667eea;
            margin-bottom: 30px;
            font-size: 2.2em;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 1em;
        }

        select, input[type="datetime-local"], input[type="date"], input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        select:focus, input:focus {
            outline: none;
            border-color: #667eea;
        }

        .required {
            color: #dc3545;
            font-weight: bold;
        }

        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
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

        .back-link, .home-link {
            display: inline-block;
            margin-top: 20px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .home-link {
            background: #28a745;
            margin-right: 10px;
        }

        .home-link:hover {
            background: #218838;
        }

        .back-link {
            background: #667eea;
            color: white;
        }

        .back-link:hover {
            background: #764ba2;
        }

        .info-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-box h3 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .info-box ul {
            margin-left: 20px;
            color: #856404;
        }

        .fixed-info-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            border: 2px solid #667eea;
        }

        .fixed-info-section h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.2em;
        }

        .fixed-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .fixed-info-item {
            background: white;
            padding: 12px;
            border-radius: 6px;
            border-left: 3px solid #28a745;
        }

        .fixed-info-item .label {
            font-size: 0.85em;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .fixed-info-item .value {
            font-size: 1.05em;
            font-weight: 600;
            color: #333;
        }

        .editable-section {
            background: #ffffff;
            border-radius: 8px;
            padding: 20px;
            border: 2px solid #28a745;
        }

        .editable-section h3 {
            color: #28a745;
            margin-bottom: 15px;
            font-size: 1.2em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📖 Editare Împrumut</h1>

        <div class="info-box">
            <h3>ℹ️ Informații importante</h3>
            <ul>
                <li><strong>Cititorul și cartea NU pot fi modificate</strong> - sunt fixe pentru acest împrumut</li>
                <li>Poți edita doar: data împrumutului, data returnării și statusul</li>
                <li>Statusul se actualizează automat când adaugi dată returnare</li>
            </ul>
        </div>

        <?php if (isset($mesaj)): ?>
            <div class="alert alert-<?php echo $tip_mesaj; ?>">
                <?php echo $mesaj; ?>
            </div>
        <?php endif; ?>

        <!-- Secțiune FIXĂ - cititor și carte -->
        <div class="fixed-info-section">
            <h3>🔒 Informații fixe (nu pot fi modificate)</h3>
            <div class="fixed-info-grid">
                <div class="fixed-info-item">
                    <div class="label">👤 Cititor</div>
                    <div class="value">
                        <?php echo htmlspecialchars($imprumut['cititor_nume'] . ' ' . $imprumut['cititor_prenume']); ?>
                        <br>
                        <span style="font-size: 0.9em; color: #667eea;">
                            <?php echo htmlspecialchars($imprumut['cod_cititor']); ?>
                        </span>
                    </div>
                </div>
                <div class="fixed-info-item">
                    <div class="label">📕 Carte</div>
                    <div class="value">
                        <?php echo htmlspecialchars($imprumut['carte_titlu']); ?>
                        <br>
                        <span style="font-size: 0.85em; color: #666;">
                            <?php echo htmlspecialchars($imprumut['carte_autor'] ?: 'Autor necunoscut'); ?>
                        </span>
                        <br>
                        <span style="font-size: 0.9em; color: #667eea;">
                            <?php echo htmlspecialchars($imprumut['cod_carte']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secțiune EDITABILĂ - date și status -->
        <div class="editable-section">
            <h3>✏️ Câmpuri editabile</h3>
            
            <form method="POST" id="imprumutForm">
                <div class="form-group">
                    <label>Data împrumut <span class="required">*</span></label>
                    <input type="datetime-local"
                           name="data_imprumut"
                           value="<?php echo date('Y-m-d\TH:i', strtotime($imprumut['data_imprumut'])); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label>Data returnare (opțional)</label>
                    <input type="datetime-local"
                           name="data_returnare"
                           value="<?php echo $imprumut['data_returnare'] ? date('Y-m-d\TH:i', strtotime($imprumut['data_returnare'])) : ''; ?>">
                    <small style="color: #6c757d; font-size: 0.9em;">💡 Lasă gol pentru împrumuturi active</small>
                </div>

                <div class="form-group">
                    <label>Status <span class="required">*</span></label>
                    <select name="status" required>
                        <option value="activ" <?php echo $imprumut['status'] == 'activ' ? 'selected' : ''; ?>>
                            📤 Activ (împrumutată)
                        </option>
                        <option value="returnat" <?php echo $imprumut['status'] == 'returnat' ? 'selected' : ''; ?>>
                            📥 Returnată
                        </option>
                    </select>
                </div>

                <button type="submit">💾 Salvează modificările</button>
            </form>
        </div>

        <a href="index.php" class="home-link">🏠 Acasă</a>
        <a href="imprumuturi.php" class="back-link">← Înapoi la lista împrumuturi</a>
    </div>

    <script>
        // LOGICĂ: Când schimbi statusul manual
        document.querySelector('select[name="status"]').addEventListener('change', function() {
            const dataReturnareInput = document.querySelector('input[name="data_returnare"]');
            
            // Dacă selectezi "returnat" și nu ai dată, completează automat cu data curentă
            if (this.value === 'returnat' && !dataReturnareInput.value) {
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                
                dataReturnareInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
                
                alert('ℹ️ Data returnării a fost completată automat cu data curentă.\nPoți să o modifici dacă vrei.');
            }
            
            // Dacă selectezi "activ", șterge data returnării
            if (this.value === 'activ') {
                dataReturnareInput.value = '';
            }
        });

        // Când modifici data returnării manual
        document.querySelector('input[name="data_returnare"]').addEventListener('input', function() {
            const statusSelect = document.querySelector('select[name="status"]');
            
            // Doar sugerează, nu forța!
            if (this.value && statusSelect.value === 'activ') {
                if (confirm('💡 Ai completat data returnării.\n\nVrei să schimbi automat statusul în "Returnat"?')) {
                    statusSelect.value = 'returnat';
                }
            }
        });
    </script>
</body>
</html>
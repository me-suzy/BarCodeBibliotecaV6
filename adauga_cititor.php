<?php
// adauga_cititor.php - Adaugă cititori noi în sistem
header('Content-Type: text/html; charset=UTF-8');
session_start();
require_once 'config.php';
require_once 'auth_check.php';
require_once 'functions_coduri_aleph.php';

// Pre-completare cod dacă vine din scanare
$cod_prestabilit = isset($_GET['cod']) ? strtoupper(trim($_GET['cod'])) : '';

// Variabile pentru păstrarea datelor la eroare
$form_data = [
    'cod_bare' => $cod_prestabilit,
    'nume' => '',
    'prenume' => '',
    'telefon' => '',
    'email' => ''
];

$mesaj = '';
$tip_mesaj = '';
$cod_duplicat = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Salvează toate datele
    $form_data = [
        'cod_bare' => strtoupper(trim($_POST['cod_bare'])),
        'nume' => trim($_POST['nume']),
        'prenume' => trim($_POST['prenume']),
        'telefon' => trim($_POST['telefon']),
        'email' => trim($_POST['email'])
    ];

    try {
        // Detectează tipul de cod și extrage informații
        $tip_cod = detecteazaTipCod($form_data['cod_bare']);
        $statut = null;
        
        if ($tip_cod === 'aleph') {
            $validare = valideazaCodAleph($form_data['cod_bare']);
            if ($validare) {
                $statut = $validare['statut'];
            }
        } elseif ($tip_cod === 'user') {
            // Format USER - nu are statut
            $statut = null;
        }
        
        // Inserează cititorul cu tip_cod și statut (dacă există câmpurile)
        // Verifică dacă câmpurile există înainte de a le folosi
        $stmt = $pdo->query("SHOW COLUMNS FROM cititori LIKE 'tip_cod'");
        $are_tip_cod = $stmt->rowCount() > 0;
        
        $stmt = $pdo->query("SHOW COLUMNS FROM cititori LIKE 'statut'");
        $are_statut = $stmt->rowCount() > 0;
        
        if ($are_tip_cod && $are_statut) {
            $stmt = $pdo->prepare("INSERT INTO cititori (cod_bare, nume, prenume, telefon, email, tip_cod, statut) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $form_data['cod_bare'],
                $form_data['nume'],
                $form_data['prenume'],
                $form_data['telefon'],
                $form_data['email'],
                $tip_cod ?: 'user',
                $statut
            ]);
        } else {
            // Fallback la metoda veche dacă câmpurile nu există încă
            $stmt = $pdo->prepare("INSERT INTO cititori (cod_bare, nume, prenume, telefon, email) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $form_data['cod_bare'],
                $form_data['nume'],
                $form_data['prenume'],
                $form_data['telefon'],
                $form_data['email']
            ]);
        }

        // SUCCES - Redirecționează către index.php cu codul cititorului pentru auto-verificare
        header('Location: index.php?cod_cititor=' . urlencode($form_data['cod_bare']) . '&nou=1');
        exit;
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $mesaj = "❌ Codul de bare <strong>{$form_data['cod_bare']}</strong> există deja în baza de date!";
            $tip_mesaj = "danger";
            $cod_duplicat = true;
        } else {
            $mesaj = "❌ Eroare la salvare: " . $e->getMessage();
            $tip_mesaj = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adaugă Cititor - Sistem Bibliotecă</title>
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
            max-width: 600px;
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

        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        input:focus {
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
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-box h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .preview-card {
            background: #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            border-left: 4px solid #28a745;
        }

        .preview-card h4 {
            margin-bottom: 8px;
            color: #28a745;
            font-size: 1em;
        }

        .card-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 0.9em;
        }

        .card-item {
            display: flex;
            justify-content: space-between;
        }

        .card-label {
            font-weight: 600;
            color: #666;
        }

        .card-value {
            color: #333;
        }

        .error-field {
            border-color: #dc3545 !important;
            background: #f8d7da !important;
        }

        .error-message {
            color: #dc3545;
            font-weight: 600;
            font-size: 0.9em;
            margin-top: 5px;
            display: block;
        }

        .success-indicator {
            color: #28a745;
            font-weight: 600;
            font-size: 0.9em;
            margin-top: 5px;
            display: block;
        }

        .check-link {
            text-align: center;
            margin-top: 15px;
            padding: 15px;
            background: #fff3cd;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
        }

        .check-link a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
            font-size: 1.1em;
        }

        .check-link a:hover {
            text-decoration: underline;
        }

        .scanned-code-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.1em;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .scanned-code-badge .code {
            font-size: 1.5em;
            margin-top: 5px;
            letter-spacing: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>👤 Adaugă cititor nou</h1>

        <?php if (!empty($cod_prestabilit) && !$cod_duplicat): ?>
        <div class="scanned-code-badge">
            📟 Cod scanat detectat
            <div class="code"><?php echo htmlspecialchars($cod_prestabilit); ?></div>
        </div>
        <?php endif; ?>

        <div class="info-box">
            <h3>💡 Informații utile</h3>
            <ul style="margin-left: 20px;">
                <li>Codul de bare trebuie să fie unic (ex: USER001, USER002)</li>
                <li>Va fi printat pe carnetul de membru al cititorului</li>
                <li>Contactele sunt importante pentru notificări</li>
            </ul>
        </div>

        <?php if (isset($mesaj)): ?>
            <div class="alert alert-<?php echo $tip_mesaj; ?>">
                <?php echo $mesaj; ?>
            </div>
            
            <?php if ($cod_duplicat): ?>
                <div class="check-link">
                    <a href="cititori.php" target="_blank">🔍 Vezi lista completă de cititori pentru a verifica codurile existente</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST" id="cititorForm">
            <div class="form-group">
                <label>Cod de bare carnet <span class="required">*</span></label>
                <input type="text" 
                       name="cod_bare" 
                       id="cod_bare_input"
                       placeholder="USER003 sau 1200000010" 
                       value="<?php echo htmlspecialchars($form_data['cod_bare']); ?>"
                       required
                       class="<?php echo $cod_duplicat ? 'error-field' : ''; ?>"
                       <?php echo (!empty($cod_prestabilit) && !$cod_duplicat) ? 'readonly style="background:#e9ecef;"' : ''; ?>>
                
                <small id="cod_info" style="display: block; margin-top: 5px; color: #666; font-size: 0.9em;">
                    💡 Formate acceptate: USER001 (testare) sau 12 cifre (Aleph)
                </small>
                
                <?php if (!empty($cod_prestabilit) && !$cod_duplicat): ?>
                    <small class="success-indicator">
                        ✅ Cod scanat: <?php echo htmlspecialchars($cod_prestabilit); ?>
                    </small>
                <?php endif; ?>
                
                <?php if ($cod_duplicat): ?>
                    <small class="error-message">
                        ⚠️ Acest cod există deja! Verifică lista de cititori sau folosește alt cod.
                    </small>
                <?php endif; ?>
            </div>
            
            <div class="form-group" id="statut_group" style="display: none;">
                <label>Statut cititor (pentru coduri Aleph)</label>
                <select name="statut" id="statut_select">
                    <option value="">-- Selectează statut --</option>
                    <option value="11">11 - Statut 11</option>
                    <option value="12">12 - Statut 12</option>
                    <option value="13">13 - Statut 13</option>
                    <!-- Adaugă aici statuturile reale din tabelul 31 Aleph -->
                </select>
                <small style="display: block; margin-top: 5px; color: #666; font-size: 0.9em;">
                    Statutul va fi extras automat din codul Aleph (primele 2 cifre)
                </small>
            </div>

            <div class="form-group">
                <label>Nume <span class="required">*</span></label>
                <input type="text" 
                       name="nume" 
                       placeholder="Popescu" 
                       value="<?php echo htmlspecialchars($form_data['nume']); ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Prenume <span class="required">*</span></label>
                <input type="text" 
                       name="prenume" 
                       placeholder="Maria" 
                       value="<?php echo htmlspecialchars($form_data['prenume']); ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Telefon</label>
                <input type="tel" 
                       name="telefon" 
                       placeholder="0721123456"
                       value="<?php echo htmlspecialchars($form_data['telefon']); ?>">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" 
                       name="email" 
                       placeholder="maria@email.ro"
                       value="<?php echo htmlspecialchars($form_data['email']); ?>">
            </div>

            <button type="submit">💾 Adaugă cititor</button>
        </form>

        <!-- Previzualizare card -->
        <div class="preview-card" id="previewCard" style="display: none;">
            <h4>👤 Previzualizare card cititor</h4>
            <div class="card-grid">
                <div class="card-item">
                    <span class="card-label">Cod:</span>
                    <span class="card-value" id="previewCod">-</span>
                </div>
                <div class="card-item">
                    <span class="card-label">Nume:</span>
                    <span class="card-value" id="previewNume">-</span>
                </div>
                <div class="card-item">
                    <span class="card-label">Prenume:</span>
                    <span class="card-value" id="previewPrenume">-</span>
                </div>
                <div class="card-item">
                    <span class="card-label">Telefon:</span>
                    <span class="card-value" id="previewTelefon">-</span>
                </div>
            </div>
        </div>

        <a href="index.php" class="home-link">🔙 Înapoi la scanare</a>
        <a href="cititori.php" class="back-link">👥 Vezi toți cititorii</a>
    </div>

    <script>
        // Funcție pentru actualizare previzualizare
        function updatePreview() {
            const codInput = document.getElementById('cod_bare_input');
            const cod = codInput ? codInput.value.trim() : '';
            const nume = document.querySelector('input[name="nume"]').value.trim();
            const prenume = document.querySelector('input[name="prenume"]').value.trim();
            const telefon = document.querySelector('input[name="telefon"]').value.trim();

            const preview = document.getElementById('previewCard');

            if (cod || nume || prenume) {
                preview.style.display = 'block';
                document.getElementById('previewCod').textContent = cod || '-';
                document.getElementById('previewNume').textContent = nume || '-';
                document.getElementById('previewPrenume').textContent = prenume || '-';
                document.getElementById('previewTelefon').textContent = telefon || '-';
            } else {
                preview.style.display = 'none';
            }
        }

        // Adaugă event listeners pentru actualizare în timp real
        const codInputForPreview = document.getElementById('cod_bare_input');
        if (codInputForPreview) {
            codInputForPreview.addEventListener('input', updatePreview);
        }
        document.querySelector('input[name="nume"]').addEventListener('input', updatePreview);
        document.querySelector('input[name="prenume"]').addEventListener('input', updatePreview);
        document.querySelector('input[name="telefon"]').addEventListener('input', updatePreview);

        // Validare email simplă
        document.querySelector('input[name="email"]').addEventListener('blur', function() {
            const email = this.value.trim();
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#ddd';
            }
        });

        // Actualizează previzualizarea la încărcare dacă sunt date
        window.addEventListener('load', updatePreview);
        
        // Auto-focus pe primul câmp gol
        window.addEventListener('load', function() {
            const codInput = document.getElementById('cod_bare_input');
            const numeInput = document.querySelector('input[name="nume"]');
            
            if (codInput && codInput.value.trim() !== '') {
                numeInput.focus();
            } else if (codInput) {
                codInput.focus();
            }
        });
        
        // Detectare automată tip cod și afișare informații
        const codInput = document.getElementById('cod_bare_input');
        if (codInput) {
            const codInfo = document.getElementById('cod_info');
            const statutGroup = document.getElementById('statut_group');
            const statutSelect = document.getElementById('statut_select');
            
            codInput.addEventListener('input', function() {
                const cod = this.value.trim();
                
                // Verifică format Aleph (12 cifre)
                if (/^\d{12}$/.test(cod)) {
                    const statut = cod.substring(0, 2);
                    codInfo.innerHTML = `✅ Format Aleph detectat | Statut: ${statut} | Număr: ${cod.substring(2, 11)}`;
                    codInfo.style.color = '#28a745';
                    
                    // Setează statutul în select (dacă există)
                    if (statutSelect && statutSelect.querySelector(`option[value="${statut}"]`)) {
                        statutSelect.value = statut;
                    }
                    if (statutGroup) statutGroup.style.display = 'block';
                }
                // Verifică format USER
                else if (/^USER\d+$/i.test(cod)) {
                    codInfo.innerHTML = '✅ Format USER detectat (pentru testare)';
                    codInfo.style.color = '#28a745';
                    if (statutGroup) statutGroup.style.display = 'none';
                }
                // Cod invalid sau incomplet
                else if (cod.length > 0) {
                    codInfo.innerHTML = '⚠️ Format necunoscut. Formate acceptate: USER001 sau 12 cifre (Aleph)';
                    codInfo.style.color = '#ffc107';
                    if (statutGroup) statutGroup.style.display = 'none';
                }
                // Câmp gol
                else {
                    codInfo.innerHTML = '💡 Formate acceptate: USER001 (testare) sau 12 cifre (Aleph)';
                    codInfo.style.color = '#666';
                    if (statutGroup) statutGroup.style.display = 'none';
                }
            });
            
            // Trigger la încărcare dacă există deja un cod
            if (codInput.value.trim() !== '') {
                codInput.dispatchEvent(new Event('input'));
            }
        }
    </script>
</body>
</html>
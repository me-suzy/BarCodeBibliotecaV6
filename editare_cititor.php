<?php
// editare_cititor.php - Editare date cititor
session_start();
require_once 'config.php';
require_once 'auth_check.php';

$mesaj = '';
$tip_mesaj = '';

// Funcție pentru verificarea parolei admin din baza de date
function verificaParolaAdmin($pdo, $parola_introdusa) {
    try {
        // Obține parola hash-uită pentru utilizatorul cu ID 1
        $stmt = $pdo->prepare("SELECT password_hash FROM utilizatori WHERE id = 1 AND activ = TRUE");
        $stmt->execute();
        $utilizator = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$utilizator) {
            return false;
        }
        
        // Verifică parola folosind password_verify()
        return password_verify($parola_introdusa, $utilizator['password_hash']);
    } catch (PDOException $e) {
        error_log("Eroare verificare parolă admin: " . $e->getMessage());
        return false;
    }
}

// Verifică dacă avem ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('ID cititor lipsă');
}

$id = (int)$_GET['id'];

// Obține datele cititorului
$stmt = $pdo->prepare("SELECT * FROM cititori WHERE id = ?");
$stmt->execute([$id]);
$cititor = $stmt->fetch();

if (!$cititor) {
    die('Cititorul nu a fost găsit');
}

// Procesare ștergere - CU VERIFICARE PAROLĂ
if (isset($_POST['delete']) && $_POST['delete'] === 'true') {
    // Verifică parola admin
    $parola_introdusa = $_POST['admin_password'] ?? '';
    
    if (!verificaParolaAdmin($pdo, $parola_introdusa)) {
        $mesaj = "🚫 Parolă incorectă! Nu ai permisiuni de administrator.";
        $tip_mesaj = "danger";
    } else {
        try {
            // Verifică dacă cititorul are împrumuturi în istoric
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM imprumuturi WHERE cod_cititor = ?");
            $check_stmt->execute([$cititor['cod_bare']]);
            $imprumuturi_count = $check_stmt->fetchColumn();

            // Verifică dacă cititorul are sesiuni în biblioteca
            $sesiuni_count = 0;
            try {
                $check_sesiuni = $pdo->prepare("SELECT COUNT(*) FROM sesiuni_biblioteca WHERE cod_cititor = ?");
                $check_sesiuni->execute([$cititor['cod_bare']]);
                $sesiuni_count = $check_sesiuni->fetchColumn();
            } catch (PDOException $e) {
                // Ignoră dacă tabelul nu există
            }

            // Verifică dacă cititorul are notificări
            $notificari_count = 0;
            try {
                $check_notificari = $pdo->prepare("SELECT COUNT(*) FROM notificari WHERE cod_cititor = ?");
                $check_notificari->execute([$cititor['cod_bare']]);
                $notificari_count = $check_notificari->fetchColumn();
            } catch (PDOException $e) {
                // Ignoră dacă tabelul nu există
            }

            $sterge_istoric = isset($_POST['sterge_istoric']) && $_POST['sterge_istoric'] === 'true';

            if ($imprumuturi_count > 0 && !$sterge_istoric) {
                $mesaj = "⚠️ Nu poți șterge acest cititor!\n\n" .
                         "Cititorul are {$imprumuturi_count} împrumuturi în istoric.\n\n" .
                         "Dacă vrei să ștergi cititorul împreună cu tot istoricul, " .
                         "apasă butonul 'Șterge cu tot istoricul' de jos.";
                $tip_mesaj = "danger";
            } else {
                // Șterge împrumuturile dacă există
                if ($imprumuturi_count > 0) {
                    $del_stmt = $pdo->prepare("DELETE FROM imprumuturi WHERE cod_cititor = ?");
                    $del_stmt->execute([$cititor['cod_bare']]);
                }
                
                // Șterge sesiunile din biblioteca (foreign key fără ON DELETE CASCADE)
                if ($sesiuni_count > 0) {
                    $del_sesiuni = $pdo->prepare("DELETE FROM sesiuni_biblioteca WHERE cod_cititor = ?");
                    $del_sesiuni->execute([$cititor['cod_bare']]);
                }
                
                // Șterge notificările (dacă există)
                if ($notificari_count > 0) {
                    $del_notificari = $pdo->prepare("DELETE FROM notificari WHERE cod_cititor = ?");
                    $del_notificari->execute([$cititor['cod_bare']]);
                }
                
                // Șterge cititorul
                $stmt = $pdo->prepare("DELETE FROM cititori WHERE id = ?");
                $stmt->execute([$id]);
                
                $msg_detalii = [];
                if ($imprumuturi_count > 0) $msg_detalii[] = "{$imprumuturi_count} împrumuturi";
                if ($sesiuni_count > 0) $msg_detalii[] = "{$sesiuni_count} sesiuni bibliotecă";
                if ($notificari_count > 0) $msg_detalii[] = "{$notificari_count} notificări";
                
                $msg_extra = !empty($msg_detalii) ? " și " . implode(", ", $msg_detalii) : "";
                echo "<script>alert('✅ Cititorul{$msg_extra} au fost șterse!'); window.location.href='cititori.php';</script>";
                exit;
            }
        } catch (PDOException $e) {
            $mesaj = "❌ Eroare la ștergere: " . $e->getMessage();
            $tip_mesaj = "danger";
        }
    }
}

// Procesare formular editare
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete'])) {
    $cod_bare = trim($_POST['cod_bare'] ?? '');
    $nume = trim($_POST['nume'] ?? '');
    $prenume = trim($_POST['prenume'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $email = trim($_POST['email'] ?? '');

    try {
        $stmt = $pdo->prepare("
            UPDATE cititori
            SET cod_bare = ?, nume = ?, prenume = ?, telefon = ?, email = ?
            WHERE id = ?
        ");
        $stmt->execute([$cod_bare, $nume, $prenume, $telefon, $email, $id]);

        $mesaj = "✅ Cititorul a fost actualizat cu succes!";
        $tip_mesaj = "success";

        // Reîncarcă datele
        $stmt = $pdo->prepare("SELECT * FROM cititori WHERE id = ?");
        $stmt->execute([$id]);
        $cititor = $stmt->fetch();

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
    <title>Editare Cititor - Sistem Bibliotecă</title>
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

        .delete-btn {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            margin-top: 15px;
        }

        .delete-btn:hover {
            background: linear-gradient(135deg, #c82333 0%, #a02622 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,0,0,0.2);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .action-buttons a {
            flex: 1;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            white-space: pre-line;
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
            text-align: center;
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

        /* Modal pentru parolă */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 50px rgba(0,0,0,0.3);
            min-width: 350px;
            z-index: 1001;
            animation: slideIn 0.3s;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translate(-50%, -60%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        .modal-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: #667eea;
            font-size: 1.5em;
            margin-bottom: 5px;
        }

        .modal-header p {
            color: #666;
            font-size: 0.9em;
        }

        .modal-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            margin-bottom: 20px;
        }

        .modal-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
        }

        .modal-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-confirm {
            background: #28a745;
            color: white;
        }

        .btn-confirm:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background: #545b62;
        }

        .warning-icon {
            font-size: 3em;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>👤 Editare Cititor</h1>

        <?php if ($mesaj): ?>
            <div class="alert alert-<?php echo $tip_mesaj; ?>">
                <?php echo $mesaj; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="cititorForm">
            <div class="form-group">
                <label>Cod de bare carnet <span class="required">*</span></label>
                <input type="text" name="cod_bare" value="<?php echo htmlspecialchars($cititor['cod_bare']); ?>" required>
            </div>

            <div class="form-group">
                <label>Nume <span class="required">*</span></label>
                <input type="text" name="nume" value="<?php echo htmlspecialchars($cititor['nume']); ?>" required>
            </div>

            <div class="form-group">
                <label>Prenume <span class="required">*</span></label>
                <input type="text" name="prenume" value="<?php echo htmlspecialchars($cititor['prenume']); ?>" required>
            </div>

            <div class="form-group">
                <label>Telefon</label>
                <input type="tel" name="telefon" value="<?php echo htmlspecialchars($cititor['telefon'] ?: ''); ?>">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($cititor['email'] ?: ''); ?>">
            </div>

            <button type="submit">💾 Salvează modificările</button>
        </form>

        <!-- Previzualizare card -->
        <div class="preview-card" id="previewCard">
            <h4>👤 Previzualizare card cititor</h4>
            <div class="card-grid">
                <div class="card-item">
                    <span class="card-label">Cod:</span>
                    <span class="card-value" id="previewCod"><?php echo htmlspecialchars($cititor['cod_bare']); ?></span>
                </div>
                <div class="card-item">
                    <span class="card-label">Nume:</span>
                    <span class="card-value" id="previewNume"><?php echo htmlspecialchars($cititor['nume']); ?></span>
                </div>
                <div class="card-item">
                    <span class="card-label">Prenume:</span>
                    <span class="card-value" id="previewPrenume"><?php echo htmlspecialchars($cititor['prenume']); ?></span>
                </div>
                <div class="card-item">
                    <span class="card-label">Telefon:</span>
                    <span class="card-value" id="previewTelefon"><?php echo htmlspecialchars($cititor['telefon'] ?: '-'); ?></span>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="index.php" class="home-link">🏠 Acasă</a>
            <a href="cititori.php" class="back-link">← Înapoi la lista cititori</a>
        </div>

        <!-- Butoane ștergere -->
        <button type="button" class="delete-btn" onclick="solicitaParola(false)">
            🗑️ Șterge cititorul (doar dacă nu are istoric)
        </button>

        <button type="button" class="delete-btn" onclick="solicitaParola(true)" 
                style="background: linear-gradient(135deg, #ff0000 0%, #990000 100%);">
            💥 Șterge cititorul CU TOT ISTORICUL
        </button>
    </div>

    <!-- Modal pentru parolă admin -->
    <div id="modalOverlay" class="modal-overlay" onclick="inchideModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="warning-icon">🔐</div>
            <div class="modal-header">
                <h3>Autentificare Administrator</h3>
                <p>Introdu parola pentru a continua</p>
            </div>
            <input type="password" id="parolaAdmin" class="modal-input" 
                   placeholder="Parolă administrator" 
                   onkeypress="if(event.key === 'Enter') confirmaParola()">
            <div class="modal-buttons">
                <button class="modal-btn btn-confirm" onclick="confirmaParola()">
                    ✓ Confirmă
                </button>
                <button class="modal-btn btn-cancel" onclick="inchideModal()">
                    ✗ Anulează
                </button>
            </div>
        </div>
    </div>

    <!-- Form ascuns pentru ștergere -->
    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="delete" value="true">
        <input type="hidden" name="admin_password" id="parolaHidden">
        <input type="hidden" name="sterge_istoric" id="stergeIstoric" value="false">
    </form>

    <script>
        let stergeIstoricFlag = false;

        function solicitaParola(cuIstoric) {
            stergeIstoricFlag = cuIstoric;
            
            // Mesaj de confirmare înainte de a cere parola
            let mesajConfirmare = cuIstoric 
                ? '🚨 ATENȚIE!\n\nVei șterge cititorul ȘI TOATE împrumuturile lui din istoric!\n\nAceastă acțiune NU poate fi anulată!\n\nEști absolut sigur?'
                : '⚠️ Ești sigur că vrei să ștergi acest cititor?\n\nAceastă acțiune NU poate fi anulată!';
            
            if (confirm(mesajConfirmare)) {
                document.getElementById('modalOverlay').style.display = 'block';
                setTimeout(() => {
                    document.getElementById('parolaAdmin').focus();
                }, 100);
            }
        }

        function confirmaParola() {
            const parola = document.getElementById('parolaAdmin').value;
            
            if (!parola) {
                alert('❌ Te rog să introduci parola!');
                return;
            }
            
            // Setează valorile în form
            document.getElementById('parolaHidden').value = parola;
            document.getElementById('stergeIstoric').value = stergeIstoricFlag ? 'true' : 'false';
            
            // Trimite formularul
            document.getElementById('deleteForm').submit();
        }

        function inchideModal() {
            document.getElementById('modalOverlay').style.display = 'none';
            document.getElementById('parolaAdmin').value = '';
        }

        // Închide modal cu ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                inchideModal();
            }
        });

        // Actualizare previzualizare în timp real
        function updatePreview() {
            const cod = document.querySelector('input[name="cod_bare"]').value.trim();
            const nume = document.querySelector('input[name="nume"]').value.trim();
            const prenume = document.querySelector('input[name="prenume"]').value.trim();
            const telefon = document.querySelector('input[name="telefon"]').value.trim();

            document.getElementById('previewCod').textContent = cod || '-';
            document.getElementById('previewNume').textContent = nume || '-';
            document.getElementById('previewPrenume').textContent = prenume || '-';
            document.getElementById('previewTelefon').textContent = telefon || '-';
        }

        // Adaugă event listeners pentru actualizare în timp real
        document.querySelector('input[name="cod_bare"]').addEventListener('input', updatePreview);
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
    </script>
</body>
</html>
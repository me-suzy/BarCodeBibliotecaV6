# Script PowerShell pentru rezolvarea problemelor MySQL în XAMPP
# Rulează ca Administrator: Right-click → "Run with PowerShell" → "Run as Administrator"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Fix MySQL XAMPP - Script Automat" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Verifică dacă rulează ca Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "⚠️  ATENȚIE: Scriptul trebuie rulat ca Administrator!" -ForegroundColor Yellow
    Write-Host "Right-click pe fișier → 'Run with PowerShell' → 'Run as Administrator'" -ForegroundColor Yellow
    Write-Host ""
    $response = Read-Host "Vrei să continui oricum? (y/n)"
    if ($response -ne 'y') {
        exit
    }
}

# Configurare căi
$xamppPath = "C:\xampp"
$mysqlDataPath = "$xamppPath\mysql\data"

Write-Host "📁 Căi configurate:" -ForegroundColor Green
Write-Host "   XAMPP: $xamppPath" -ForegroundColor Gray
Write-Host "   MySQL Data: $mysqlDataPath" -ForegroundColor Gray
Write-Host ""

# ============================================
# PASUL 1: Oprește procesele MySQL
# ============================================
Write-Host "1️⃣  Oprire procese MySQL..." -ForegroundColor Cyan

# Găsește procesele care ocupă port-ul 3306
$port3306 = netstat -ano | Select-String ":3306" | Select-String "LISTENING"

if ($port3306) {
    Write-Host "   ⚠️  Găsite procese pe port 3306" -ForegroundColor Yellow
    
    $pids = @()
    foreach ($line in $port3306) {
        if ($line -match 'LISTENING\s+(\d+)') {
            $pid = $matches[1]
            $pids += $pid
            
            # Obține numele procesului
            $process = Get-Process -Id $pid -ErrorAction SilentlyContinue
            if ($process) {
                Write-Host "   → PID $pid : $($process.ProcessName)" -ForegroundColor Gray
            } else {
                Write-Host "   → PID $pid : (proces terminat)" -ForegroundColor Gray
            }
        }
    }
    
    $response = Read-Host "   Oprește aceste procese? (y/n)"
    if ($response -eq 'y') {
        foreach ($pid in $pids) {
            try {
                Stop-Process -Id $pid -Force -ErrorAction SilentlyContinue
                Write-Host "   ✅ Proces $pid oprit" -ForegroundColor Green
            } catch {
                Write-Host "   ⚠️  Nu s-a putut opri proces $pid" -ForegroundColor Yellow
            }
        }
    }
} else {
    Write-Host "   ✅ Port 3306 este liber" -ForegroundColor Green
}

Write-Host ""

# ============================================
# PASUL 2: Oprește serviciile MySQL Windows
# ============================================
Write-Host "2️⃣  Verificare servicii MySQL Windows..." -ForegroundColor Cyan

$mysqlServices = Get-Service | Where-Object { $_.Name -like "*mysql*" -or $_.DisplayName -like "*mysql*" }

if ($mysqlServices) {
    Write-Host "   ⚠️  Găsite servicii MySQL:" -ForegroundColor Yellow
    foreach ($service in $mysqlServices) {
        Write-Host "   → $($service.Name) : $($service.DisplayName) [$($service.Status)]" -ForegroundColor Gray
    }
    
    $response = Read-Host "   Oprește și dezactivează aceste servicii? (y/n)"
    if ($response -eq 'y') {
        foreach ($service in $mysqlServices) {
            try {
                if ($service.Status -eq 'Running') {
                    Stop-Service -Name $service.Name -Force
                    Write-Host "   ✅ Serviciul $($service.Name) oprit" -ForegroundColor Green
                }
                Set-Service -Name $service.Name -StartupType Disabled -ErrorAction SilentlyContinue
                Write-Host "   ✅ Serviciul $($service.Name) dezactivat" -ForegroundColor Green
            } catch {
                Write-Host "   ⚠️  Eroare la serviciul $($service.Name): $_" -ForegroundColor Yellow
            }
        }
    }
} else {
    Write-Host "   ✅ Nu există servicii MySQL concurente" -ForegroundColor Green
}

Write-Host ""

# ============================================
# PASUL 3: Șterge fișiere .lock
# ============================================
Write-Host "3️⃣  Ștergere fișiere .lock..." -ForegroundColor Cyan

if (Test-Path $mysqlDataPath) {
    $lockFiles = Get-ChildItem -Path $mysqlDataPath -Filter "*.lock" -ErrorAction SilentlyContinue
    
    if ($lockFiles) {
        Write-Host "   ⚠️  Găsite $($lockFiles.Count) fișiere .lock:" -ForegroundColor Yellow
        foreach ($lockFile in $lockFiles) {
            Write-Host "   → $($lockFile.Name)" -ForegroundColor Gray
        }
        
        $response = Read-Host "   Șterge aceste fișiere? (y/n)"
        if ($response -eq 'y') {
            foreach ($lockFile in $lockFiles) {
                try {
                    Remove-Item -Path $lockFile.FullName -Force
                    Write-Host "   ✅ Șters: $($lockFile.Name)" -ForegroundColor Green
                } catch {
                    Write-Host "   ⚠️  Eroare la ștergerea $($lockFile.Name): $_" -ForegroundColor Yellow
                }
            }
        }
    } else {
        Write-Host "   ✅ Nu există fișiere .lock" -ForegroundColor Green
    }
} else {
    Write-Host "   ⚠️  Folder-ul data nu există: $mysqlDataPath" -ForegroundColor Yellow
}

Write-Host ""

# ============================================
# PASUL 4: Verifică ibdata1
# ============================================
Write-Host "4️⃣  Verificare fișier ibdata1..." -ForegroundColor Cyan

$ibdata1 = Join-Path $mysqlDataPath "ibdata1"

if (Test-Path $ibdata1) {
    $size = (Get-Item $ibdata1).Length
    $sizeMB = [math]::Round($size / 1MB, 2)
    
    Write-Host "   📊 Mărime: $sizeMB MB" -ForegroundColor Gray
    
    if ($size -eq 0) {
        Write-Host "   ❌ Fișierul ibdata1 are 0 bytes - CORUPT!" -ForegroundColor Red
        Write-Host "   ⚠️  Trebuie recreat (va șterge datele InnoDB)" -ForegroundColor Yellow
        
        $response = Read-Host "   Șterge ibdata1 și ib_logfile* pentru recreare? (y/n)"
        if ($response -eq 'y') {
            # Șterge ibdata1
            try {
                Remove-Item -Path $ibdata1 -Force
                Write-Host "   ✅ ibdata1 șters" -ForegroundColor Green
            } catch {
                Write-Host "   ⚠️  Eroare la ștergerea ibdata1: $_" -ForegroundColor Yellow
            }
            
            # Șterge ib_logfile*
            $logFiles = Get-ChildItem -Path $mysqlDataPath -Filter "ib_logfile*" -ErrorAction SilentlyContinue
            foreach ($logFile in $logFiles) {
                try {
                    Remove-Item -Path $logFile.FullName -Force
                    Write-Host "   ✅ Șters: $($logFile.Name)" -ForegroundColor Green
                } catch {
                    Write-Host "   ⚠️  Eroare la ștergerea $($logFile.Name): $_" -ForegroundColor Yellow
                }
            }
            
            Write-Host "   ✅ MySQL va recrea aceste fișiere la următorul start" -ForegroundColor Green
        }
    } else {
        Write-Host "   ✅ Fișierul ibdata1 este OK" -ForegroundColor Green
    }
} else {
    Write-Host "   ℹ️  Fișierul ibdata1 nu există (va fi creat la primul start)" -ForegroundColor Gray
}

Write-Host ""

# ============================================
# REZUMAT FINAL
# ============================================
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Rezumat" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "✅ Scriptul a terminat!" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Pași următori:" -ForegroundColor Yellow
Write-Host "   1. Repornește XAMPP Control Panel" -ForegroundColor White
Write-Host "   2. Încearcă să pornești MySQL" -ForegroundColor White
Write-Host "   3. Dacă tot nu merge, verifică log-urile:" -ForegroundColor White
Write-Host "      → XAMPP Control Panel → MySQL → Logs" -ForegroundColor Gray
Write-Host ""
Write-Host "💡 Dacă problema persistă:" -ForegroundColor Yellow
Write-Host "   → Deschide diagnosticare_mysql.php în browser" -ForegroundColor White
Write-Host "   → Verifică mysql_error.log manual" -ForegroundColor White
Write-Host ""
Write-Host "Apasă orice tastă pentru a închide..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")


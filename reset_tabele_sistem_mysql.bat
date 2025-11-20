@echo off
REM Script pentru reset tabele sistem MySQL (NUCLEAR OPTION)
REM ⚠️ ATENȚIE: Va reseta TOATE permisiunile, dar păstrează bazele de date!

echo ========================================
echo   Reset Tabele Sistem MySQL
echo   (NUCLEAR OPTION - Ultima Solutie)
echo ========================================
echo.
echo ⚠️  ATENTIE: Acest script va reseta tabelele sistem MySQL!
echo    - Va reseta TOATE permisiunile utilizatorilor
echo    - Va păstra TOATE bazele de date (biblioteca, etc.)
echo    - Va recrea tabelele sistem din backup XAMPP
echo.
echo 📋 Backup-ul va fi salvat in: C:\backup_mysql_system\
echo.
set /p confirm="Esti sigur ca vrei sa continui? (y/n): "
if /i not "%confirm%"=="y" (
    echo.
    echo Operatiune anulata.
    pause
    exit /b 0
)

cd /d C:\xampp\mysql\bin

if not exist "mysqld.exe" (
    echo.
    echo ❌ EROARE: mysqld.exe nu exista in C:\xampp\mysql\bin
    pause
    exit /b 1
)

echo.
echo 1. Opresc orice proces MySQL existent...
taskkill /F /IM mysqld.exe >nul 2>&1
timeout /t 2 /nobreak >nul

echo.
echo 2. Fac backup la tabelele sistem existente...
if exist "C:\xampp\mysql\data\mysql" (
    xcopy "C:\xampp\mysql\data\mysql" "C:\backup_mysql_system_%date:~-4,4%%date:~-7,2%%date:~-10,2%\" /E /I /Y >nul 2>&1
    if %ERRORLEVEL% EQU 0 (
        echo    ✅ Backup realizat cu succes!
    ) else (
        echo    ⚠️  Eroare la backup, dar continui...
    )
) else (
    echo    ℹ️  Folder-ul mysql nu exista (normal pentru prima instalare)
)

echo.
echo 3. Sterg tabelele sistem corupte...
if exist "C:\xampp\mysql\data\mysql" (
    rmdir /S /Q "C:\xampp\mysql\data\mysql"
    echo    ✅ Folder-ul mysql sters
) else (
    echo    ℹ️  Folder-ul mysql nu exista
)

echo.
echo 4. Restaurez tabelele sistem din backup XAMPP...
if exist "C:\xampp\mysql\backup\mysql" (
    xcopy "C:\xampp\mysql\backup\mysql" "C:\xampp\mysql\data\mysql\" /E /I /Y >nul 2>&1
    if %ERRORLEVEL% EQU 0 (
        echo    ✅ Tabelele sistem restaurate din backup XAMPP!
    ) else (
        echo    ❌ Eroare la restaurare din backup XAMPP
        echo.
        echo 💡 Backup-ul XAMPP nu exista. Trebuie sa:
        echo    1. Reinstalezi MySQL din XAMPP
        echo    SAU
        echo    2. Copiezi tabelele sistem dintr-o instalatie XAMPP fresh
        pause
        exit /b 1
    )
) else (
    echo    ❌ Backup XAMPP nu exista: C:\xampp\mysql\backup\mysql
    echo.
    echo 💡 Solutii:
    echo    1. Reinstaleaza MySQL din XAMPP (doar MySQL, nu tot XAMPP)
    echo    2. SAU copiaza tabelele sistem dintr-o instalatie XAMPP fresh
    echo.
    pause
    exit /b 1
)

echo.
echo ========================================
echo   Reset Finalizat!
echo ========================================
echo.
echo ✅ Tabelele sistem au fost resetate!
echo.
echo 📋 Urmatorii pasi:
echo    1. Deschide XAMPP Control Panel
echo    2. Incearca sa pornesti MySQL
echo    3. MySQL ar trebui sa porneasca acum!
echo.
echo ⚠️  IMPORTANT: Toate permisiunile utilizatorilor au fost resetate.
echo    Va trebui sa reconfigurezi utilizatorii daca ai avut altele.
echo.
pause


@echo off
REM Script de reparare tabele sistem MySQL
REM Rulează ca Administrator pentru permisiuni complete

echo ========================================
echo   Reparare Tabele Sistem MySQL
echo ========================================
echo.
echo Problema detectata:
echo   [ERROR] Fatal error: Can't open and lock privilege tables
echo   [ERROR] Incorrect file format 'db'
echo.
echo Solutie: Reparare tabele sistem cu mysql_upgrade
echo.
pause

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
echo 2. Pornesc MySQL in modul skip-grant-tables...
echo    (Modul special care ignora verificarea permisiunilor)
echo.
start /MIN mysqld.exe --skip-grant-tables --console > "%TEMP%\mysql_repair.log" 2>&1

echo 3. Astept 10 secunde ca MySQL sa porneasca...
timeout /t 10 /nobreak

echo.
echo 4. Verific daca MySQL ruleaza...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo    ✅ MySQL ruleaza!
) else (
    echo    ❌ MySQL nu a pornit! Verifica log-ul: %TEMP%\mysql_repair.log
    pause
    exit /b 1
)

echo.
echo 5. Rulez mysql_upgrade pentru reparare tabele...
echo    (Aceasta va repara toate tabelele sistem corupte)
echo.
mysql_upgrade.exe --force

if %ERRORLEVEL% EQU 0 (
    echo.
    echo    ✅ mysql_upgrade executat cu succes!
) else (
    echo.
    echo    ⚠️  mysql_upgrade a avut erori. Verifica output-ul de mai sus.
)

echo.
echo 6. Opresc MySQL...
taskkill /F /IM mysqld.exe >nul 2>&1
timeout /t 2 /nobreak >nul

echo.
echo ========================================
echo   Reparare finalizata!
echo ========================================
echo.
echo 📋 Urmatorii pasi:
echo    1. Deschide XAMPP Control Panel
echo    2. Incearca sa pornesti MySQL
echo    3. Daca tot nu merge, verifica log-ul: %TEMP%\mysql_repair.log
echo.
echo 💡 Daca problema persista, vezi SOLUTIE_RAPIDA_MYSQL.md
echo    pentru Opțiunea 3 (Reset tabele sistem)
echo.
pause


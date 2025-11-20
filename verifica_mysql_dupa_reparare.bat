@echo off
REM Script pentru verificare MySQL după reparare
REM Verifică dacă toate tabelele sistem sunt OK

echo ========================================
echo   Verificare MySQL dupa Reparare
echo ========================================
echo.

cd /d C:\xampp\mysql\bin

if not exist "mysql.exe" (
    echo ❌ EROARE: mysql.exe nu exista
    pause
    exit /b 1
)

echo 1. Verific daca MySQL ruleaza...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo    ✅ MySQL ruleaza!
) else (
    echo    ❌ MySQL NU ruleaza! Porneste-l din XAMPP Control Panel mai intai.
    pause
    exit /b 1
)

echo.
echo 2. Verific tabelele sistem MySQL...
echo.

mysql.exe -u root -e "USE mysql; CHECK TABLE db;" 2>&1 | findstr /C:"OK" /C:"Error" /C:"Corrupt"

echo.
echo 3. Verific status general...
echo.

mysql.exe -u root -e "SHOW DATABASES;" 2>&1

echo.
echo ========================================
echo   Verificare Finalizata
echo ========================================
echo.
echo ✅ Daca ai vazut "OK" la CHECK TABLE db, totul e bine!
echo ⚠️  Daca ai vazut "Error" sau "Corrupt", trebuie sa repari tabela.
echo.
pause


@echo off
REM Script pentru reparare manuală tabele MySQL
REM Pornește MySQL în modul skip-grant-tables și lasă utilizatorul să repare manual

echo ========================================
echo   Reparare Manuala Tabele MySQL
echo ========================================
echo.
echo Acest script va porni MySQL in modul special
echo (skip-grant-tables) care ignora verificarea permisiunilor.
echo.
echo Apoi poti rula comenzi MySQL manual pentru reparare.
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
echo.
echo ⚠️  IMPORTANT: NU inchide aceasta fereastra!
echo    MySQL ruleaza in aceasta fereastra.
echo.
echo 📋 In alta fereastra Command Prompt, ruleaza:
echo    cd C:\xampp\mysql\bin
echo    mysql.exe -u root
echo.
echo    Apoi in MySQL:
echo    USE mysql;
echo    REPAIR TABLE db;
echo    FLUSH PRIVILEGES;
echo    EXIT;
echo.
echo ========================================
echo   MySQL Ruleaza (NU inchide fereastra!)
echo ========================================
echo.

REM Rulează MySQL în consolă (nu în background)
mysqld.exe --skip-grant-tables --console

echo.
echo MySQL s-a oprit.
echo.
pause


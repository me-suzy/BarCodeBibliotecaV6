@echo off
REM Script pentru testare MySQL manual și capturare erori
REM Rulează ca Administrator pentru permisiuni complete

echo ========================================
echo   Test MySQL Manual - Capturare Erori
echo ========================================
echo.
echo Acest script va rula MySQL manual si va captura
echo toate erorile care apar in primele 15 secunde.
echo.
echo IMPORTANT: Nu inchide fereastra pana cand apare
echo mesajul "Test terminat" sau pana cand MySQL se opreste!
echo.
pause

cd /d C:\xampp\mysql\bin

if not exist "mysqld.exe" (
    echo.
    echo ❌ EROARE: mysqld.exe nu exista in C:\xampp\mysql\bin
    echo    Verifica calea XAMPP!
    pause
    exit /b 1
)

echo.
echo 🔄 Pornire MySQL in mod console...
echo.
echo ⏱️  Asteapta 15 secunde pentru a captura erorile...
echo.
echo ========================================
echo   OUTPUT MYSQL (Erori si mesaje):
echo ========================================
echo.

REM Rulează MySQL și capturează output-ul
mysqld.exe --console 2>&1 | (
    setlocal enabledelayedexpansion
    set /a count=0
    timeout /t 15 /nobreak >nul 2>&1
    echo.
    echo ========================================
    echo   Test terminat dupa 15 secunde
    echo ========================================
    echo.
    echo 📋 Daca ai vazut erori mai sus, copiaza-le
    echo    si trimite-le pentru analiza!
    echo.
)

REM Oprește MySQL dacă încă rulează
taskkill /F /IM mysqld.exe >nul 2>&1

echo.
echo ✅ MySQL oprit.
echo.
pause


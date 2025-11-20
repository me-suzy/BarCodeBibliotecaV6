@echo off
REM Script de test MySQL cu cale fixă (C:\xampp\mysql\bin)
REM Rulează MySQL manual și capturează erorile

echo ========================================
echo   Test MySQL Manual - Cale Fixa
echo ========================================
echo.
echo Cale MySQL: C:\xampp\mysql\bin
echo.
echo Acest script va rula MySQL manual si va captura
echo toate erorile care apar in primele 20 secunde.
echo.
echo IMPORTANT: Nu inchide fereastra pana cand apare
echo mesajul "Test terminat" sau pana cand MySQL se opreste!
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
echo ✅ mysqld.exe gasit!
echo.
echo 🔄 Pornire MySQL in mod console...
echo.
echo ⏱️  Asteapta 20 secunde pentru a captura erorile...
echo    (NU inchide fereastra!)
echo.
echo ========================================
echo   OUTPUT MYSQL (Erori si mesaje):
echo ========================================
echo.

REM Oprește orice proces MySQL existent
taskkill /F /IM mysqld.exe >nul 2>&1
timeout /t 2 /nobreak >nul

REM Rulează MySQL și afișează output-ul în consolă
REM Folosim start pentru a rula în background și a captura output-ul
start /B /MIN mysqld.exe --console > "%TEMP%\mysql_test_output.txt" 2>&1

REM Așteaptă 20 secunde pentru a captura erorile
echo Asteapta 20 secunde...
timeout /t 20 /nobreak

REM Oprește MySQL
taskkill /F /IM mysqld.exe >nul 2>&1

echo.
echo ========================================
echo   OUTPUT CAPTURAT:
echo ========================================
echo.

REM Afișează output-ul capturat
if exist "%TEMP%\mysql_test_output.txt" (
    type "%TEMP%\mysql_test_output.txt"
    echo.
    echo ========================================
    echo.
    echo 📋 Output-ul a fost salvat si in:
    echo    %TEMP%\mysql_test_output.txt
    echo.
    echo 💡 Deschide acest fisier pentru a vedea
    echo    toate erorile in detaliu!
    echo.
) else (
    echo.
    echo ⚠️  Nu s-a putut captura output-ul.
    echo.
    echo 💡 Incearca manual:
    echo    cd C:\xampp\mysql\bin
    echo    mysqld.exe --console
    echo.
)

echo.
echo ✅ Test terminat!
echo.
echo 📋 Daca ai vazut erori mai sus, copiaza-le
echo    si trimite-le pentru analiza!
echo.
pause


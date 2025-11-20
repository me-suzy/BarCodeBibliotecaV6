@echo off
REM Script pentru găsirea automată a căii MySQL în XAMPP
REM Detectează calea corectă și testează MySQL

echo ========================================
echo   Găsire Automată MySQL XAMPP
echo ========================================
echo.

REM Căi posibile pentru XAMPP
set PATHS[0]=C:\xampp\mysql\bin
set PATHS[1]=D:\xampp\mysql\bin
set PATHS[2]=E:\xampp\mysql\bin
set PATHS[3]=C:\Program Files\xampp\mysql\bin
set PATHS[4]=C:\Program Files (x86)\xampp\mysql\bin

set FOUND=0
set MYSQL_PATH=

echo 🔍 Cautare MySQL in cai comune...
echo.

REM Verifică fiecare cale
for /L %%i in (0,1,4) do (
    call set "TEST_PATH=%%PATHS[%%i]%%"
    if exist "!TEST_PATH!\mysqld.exe" (
        set MYSQL_PATH=!TEST_PATH!
        set FOUND=1
        echo ✅ GASIT: !TEST_PATH!
        goto :found
    ) else (
        echo ❌ Nu exista: !TEST_PATH!
    )
)

REM Dacă nu găsește, caută în toate unitățile
if %FOUND%==0 (
    echo.
    echo 🔍 Cautare in toate unitatile...
    for %%d in (C D E F G H) do (
        if exist "%%d:\xampp\mysql\bin\mysqld.exe" (
            set MYSQL_PATH=%%d:\xampp\mysql\bin
            set FOUND=1
            echo ✅ GASIT: %%d:\xampp\mysql\bin
            goto :found
        )
    )
)

:found
if %FOUND%==0 (
    echo.
    echo ❌ EROARE: MySQL nu a fost gasit in cai standard!
    echo.
    echo 📋 Verificari alternative:
    echo    1. XAMPP este instalat corect?
    echo    2. MySQL este instalat in XAMPP?
    echo    3. Calea XAMPP este diferita de standard?
    echo.
    echo 💡 Solutie: Spune-mi calea exacta a XAMPP-ului tau
    echo    (ex: D:\xampp sau C:\Program Files\xampp)
    pause
    exit /b 1
)

echo.
echo ========================================
echo   MySQL Gasit: %MYSQL_PATH%
echo ========================================
echo.

REM Verifică fișierele importante
echo 📋 Verificare fisiere importante:
echo.

if exist "%MYSQL_PATH%\mysqld.exe" (
    echo ✅ mysqld.exe - EXISTA
) else (
    echo ❌ mysqld.exe - LIPSA
)

if exist "%MYSQL_PATH%\mysql.exe" (
    echo ✅ mysql.exe - EXISTA
) else (
    echo ❌ mysql.exe - LIPSA
)

if exist "%MYSQL_PATH%\my.ini" (
    echo ✅ my.ini - EXISTA
) else (
    echo ❌ my.ini - LIPSA
)

echo.
echo ========================================
echo   Test MySQL Manual
echo ========================================
echo.
echo Acum vom rula MySQL manual pentru a vedea erorile.
echo.
echo ⏱️  Asteapta 20 secunde pentru a captura erorile...
echo    (NU inchide fereastra!)
echo.
pause

cd /d "%MYSQL_PATH%"

echo.
echo 🔄 Pornire MySQL in mod console...
echo.
echo ========================================
echo   OUTPUT MYSQL (Erori si mesaje):
echo ========================================
echo.

REM Rulează MySQL și capturează output-ul
start /B mysqld.exe --console > "%TEMP%\mysql_output.txt" 2>&1

REM Așteaptă 20 secunde
timeout /t 20 /nobreak >nul 2>&1

REM Oprește MySQL
taskkill /F /IM mysqld.exe >nul 2>&1

REM Afișează output-ul
if exist "%TEMP%\mysql_output.txt" (
    echo.
    echo ========================================
    echo   OUTPUT CAPTURAT:
    echo ========================================
    echo.
    type "%TEMP%\mysql_output.txt"
    echo.
    echo ========================================
    echo.
    echo 📋 Output-ul a fost salvat si in:
    echo    %TEMP%\mysql_output.txt
    echo.
) else (
    echo.
    echo ⚠️  Nu s-a putut captura output-ul.
    echo    Incearca manual: mysqld.exe --console
    echo.
)

echo.
echo ✅ Test terminat!
echo.
echo 📋 Daca ai vazut erori mai sus, copiaza-le
echo    si trimite-le pentru analiza!
echo.
pause


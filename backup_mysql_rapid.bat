@echo off
REM Script rapid de backup pentru MySQL data
REM Rulează ca Administrator pentru permisiuni complete

echo ========================================
echo   Backup Rapid MySQL Data
echo ========================================
echo.

set BACKUP_DIR=C:\backup_mysql_%date:~-4,4%%date:~-7,2%%date:~-10,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set BACKUP_DIR=%BACKUP_DIR: =0%
set MYSQL_DATA=C:\xampp\mysql\data

echo 📁 Folder backup: %BACKUP_DIR%
echo 📁 Sursa: %MYSQL_DATA%
echo.

if not exist "%MYSQL_DATA%" (
    echo ❌ EROARE: Folder-ul MySQL data nu exista: %MYSQL_DATA%
    pause
    exit /b 1
)

echo 🔄 Se face backup...
echo.

xcopy "%MYSQL_DATA%" "%BACKUP_DIR%" /E /I /Y /H /C

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ✅ Backup realizat cu succes!
    echo 📁 Locatie: %BACKUP_DIR%
    echo.
) else (
    echo.
    echo ❌ EROARE la backup! Verifica permisiunile.
    echo    Ruleaza scriptul ca Administrator!
    echo.
)

pause


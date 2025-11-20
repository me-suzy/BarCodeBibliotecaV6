@echo off
REM Script simplu - pornește MySQL și lasă utilizatorul să repare manual
REM Versiune simplificată pentru reparare rapidă

echo ========================================
echo   Reparare Tabela db - Mod Simplu
echo ========================================
echo.
echo Acest script va porni MySQL cu skip-grant-tables.
echo Apoi poti rula comenzi MySQL pentru reparare.
echo.
echo IMPORTANT: NU inchide aceasta fereastra!
echo.
pause

cd /d C:\xampp\mysql\bin

if not exist "mysqld.exe" (
    echo ❌ EROARE: mysqld.exe nu exista
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
echo ⚠️  MySQL ruleaza acum in aceasta fereastra!
echo    NU inchide aceasta fereastra!
echo.
echo 📋 In alta fereastra Command Prompt, ruleaza:
echo.
echo    cd C:\xampp\mysql\bin
echo    mysql.exe -u root
echo.
echo    Apoi copiaza si ruleaza aceste comenzi:
echo.
echo    USE mysql;
echo    DROP TABLE IF EXISTS db;
echo    CREATE TABLE db (Host char(60) NOT NULL DEFAULT '', Db char(64) NOT NULL DEFAULT '', User char(80) NOT NULL DEFAULT '', Select_priv enum('N','Y') NOT NULL DEFAULT 'N', Insert_priv enum('N','Y') NOT NULL DEFAULT 'N', Update_priv enum('N','Y') NOT NULL DEFAULT 'N', Delete_priv enum('N','Y') NOT NULL DEFAULT 'N', Create_priv enum('N','Y') NOT NULL DEFAULT 'N', Drop_priv enum('N','Y') NOT NULL DEFAULT 'N', Grant_priv enum('N','Y') NOT NULL DEFAULT 'N', References_priv enum('N','Y') NOT NULL DEFAULT 'N', Index_priv enum('N','Y') NOT NULL DEFAULT 'N', Alter_priv enum('N','Y') NOT NULL DEFAULT 'N', Create_tmp_table_priv enum('N','Y') NOT NULL DEFAULT 'N', Lock_tables_priv enum('N','Y') NOT NULL DEFAULT 'N', Create_view_priv enum('N','Y') NOT NULL DEFAULT 'N', Show_view_priv enum('N','Y') NOT NULL DEFAULT 'N', Create_routine_priv enum('N','Y') NOT NULL DEFAULT 'N', Alter_routine_priv enum('N','Y') NOT NULL DEFAULT 'N', Execute_priv enum('N','Y') NOT NULL DEFAULT 'N', Event_priv enum('N','Y') NOT NULL DEFAULT 'N', Trigger_priv enum('N','Y') NOT NULL DEFAULT 'N', PRIMARY KEY (Host,Db,User), KEY User (User)) ENGINE=Aria transactional=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Database privileges';
echo    FLUSH PRIVILEGES;
echo    EXIT;
echo.
echo    Apoi apasa Ctrl+C in aceasta fereastra pentru a opri MySQL.
echo    Apoi porneste MySQL din XAMPP Control Panel.
echo.
echo ========================================
echo   MySQL Ruleaza (NU inchide fereastra!)
echo ========================================
echo.

REM Rulează MySQL în consolă
mysqld.exe --skip-grant-tables --console

echo.
echo MySQL s-a oprit.
echo.
pause


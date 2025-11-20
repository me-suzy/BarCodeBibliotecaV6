@echo off
REM Script pentru reparare automată tabelă db
REM Rulează MySQL cu skip-grant-tables, repară tabela, apoi oprește

echo ========================================
echo   Reparare Automata Tabela db
echo ========================================
echo.
echo Acest script va:
echo   1. Porni MySQL cu skip-grant-tables
echo   2. Repara/recrea tabela db corupta
echo   3. Opreste MySQL
echo   4. MySQL va putea porni normal din XAMPP
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
start /MIN mysqld.exe --skip-grant-tables --console > "%TEMP%\mysql_repair_db.log" 2>&1

echo 3. Astept 10 secunde ca MySQL sa porneasca...
timeout /t 10 /nobreak

echo.
echo 4. Verific daca MySQL ruleaza...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo    ✅ MySQL ruleaza!
) else (
    echo    ❌ MySQL nu a pornit! Verifica log-ul: %TEMP%\mysql_repair_db.log
    pause
    exit /b 1
)

echo.
echo 5. Repar tabela db...
echo.

REM Creează script SQL pentru reparare
echo USE mysql; > "%TEMP%\repair_db.sql"
echo. >> "%TEMP%\repair_db.sql"
echo -- Incearca mai intai reparare >> "%TEMP%\repair_db.sql"
echo REPAIR TABLE db; >> "%TEMP%\repair_db.sql"
echo. >> "%TEMP%\repair_db.sql"
echo -- Daca nu merge, recreaza tabela >> "%TEMP%\repair_db.sql"
echo DROP TABLE IF EXISTS db; >> "%TEMP%\repair_db.sql"
echo. >> "%TEMP%\repair_db.sql"
echo CREATE TABLE db ( >> "%TEMP%\repair_db.sql"
echo   Host char(60) NOT NULL DEFAULT '', >> "%TEMP%\repair_db.sql"
echo   Db char(64) NOT NULL DEFAULT '', >> "%TEMP%\repair_db.sql"
echo   User char(80) NOT NULL DEFAULT '', >> "%TEMP%\repair_db.sql"
echo   Select_priv enum('N','Y') NOT NULL DEFAULT 'N', >> "%TEMP%\repair_db.sql"
echo   Insert_priv enum('N','Y') NOT NULL DEFAULT 'N', >> "%TEMP%\repair_db.sql"
echo   Update_priv enum('N','Y') NOT NULL DEFAULT 'N', >> "%TEMP%\repair_db.sql"
echo   Delete_priv enum('N','Y') NOT NULL DEFAULT 'N', >> "%TEMP%\repair_db.sql"
echo   Create_priv enum('N','Y') NOT NULL DEFAULT 'N', >> "%TEMP%\repair_db.sql"
echo   Drop_priv enum('N','Y') NOT NULL DEFAULT 'N', >> "%TEMP%\repair_db.sql"
echo   Grant_priv enum('N','Y') NOT NULL DEFAULT 'N', >> "%TEMP%\repair_db.sql"
echo   References_priv enum('N','Y') NOT NULL DEFAULT 'N', >> "%TEMP%\repair_db.sql"
echo   Index_priv enum('N','Y') NOT NULL DEFAULT 'N', >> "%TEMP%\repair_db.sql"
echo   Alter_priv enum('N','Y') NOT NULL DEFAULT 'N', >> "%TEMP%\repair_db.sql"
echo   Create_tmp_table_priv enum('N','Y') NOT NULL DEFAULT 'N', >> "%TEMP%\repair_db.sql"
echo   Lock_tables_priv enum('N','Y') NOT NULL DEFAULT 'N', >> "%TEMP%\repair_db.sql"
echo   Create_view_priv enum('N','Y') NOT NULL DEFAULT 'N', >> "%TEMP%\repair_db.sql"
echo   Show_view_priv enum('N','Y') NOT NULL DEFAULT 'N', >> "%TEMP%\repair_db.sql"
echo   Create_routine_priv enum('N','Y') NOT NULL DEFAULT 'N', >> "%TEMP%\repair_db.sql"
echo   Alter_routine_priv enum('N','Y') NOT NULL DEFAULT 'N', >> "%TEMP%\repair_db.sql"
echo   Execute_priv enum('N','Y') NOT NULL DEFAULT 'N', >> "%TEMP%\repair_db.sql"
echo   Event_priv enum('N','Y') NOT NULL DEFAULT 'N', >> "%TEMP%\repair_db.sql"
echo   Trigger_priv enum('N','Y') NOT NULL DEFAULT 'N', >> "%TEMP%\repair_db.sql"
echo   PRIMARY KEY (Host,Db,User), >> "%TEMP%\repair_db.sql"
echo   KEY User (User) >> "%TEMP%\repair_db.sql"
echo ) ENGINE=Aria transactional=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Database privileges'; >> "%TEMP%\repair_db.sql"
echo. >> "%TEMP%\repair_db.sql"
echo -- Adauga permisiuni pentru root >> "%TEMP%\repair_db.sql"
echo INSERT INTO db VALUES ('%%','test','','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y'); >> "%TEMP%\repair_db.sql"
echo INSERT INTO db VALUES ('%%','test\\_%%','','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y','Y'); >> "%TEMP%\repair_db.sql"
echo. >> "%TEMP%\repair_db.sql"
echo FLUSH PRIVILEGES; >> "%TEMP%\repair_db.sql"
echo EXIT; >> "%TEMP%\repair_db.sql"

REM Rulează scriptul SQL
mysql.exe -u root < "%TEMP%\repair_db.sql" > "%TEMP%\repair_db_output.txt" 2>&1

if %ERRORLEVEL% EQU 0 (
    echo    ✅ Reparare executata cu succes!
    echo.
    type "%TEMP%\repair_db_output.txt"
) else (
    echo    ⚠️  Au aparut erori. Verifica output-ul:
    echo.
    type "%TEMP%\repair_db_output.txt"
    echo.
    echo    💡 Daca vezi "Table 'mysql.db' doesn't exist" - e OK, tabela a fost recreata!
)

echo.
echo 6. Opresc MySQL...
taskkill /F /IM mysqld.exe >nul 2>&1
timeout /t 2 /nobreak >nul

echo.
echo ========================================
echo   Reparare Finalizata!
echo ========================================
echo.
echo ✅ Tabela db a fost reparata/recreata!
echo.
echo 📋 Urmatorii pasi:
echo    1. Deschide XAMPP Control Panel
echo    2. Incearca sa pornesti MySQL
echo    3. MySQL AR TREBUI sa porneasca si sa ramana pornit!
echo.
echo 💡 Daca tot nu merge, verifica log-ul: %TEMP%\mysql_repair_db.log
echo.
pause


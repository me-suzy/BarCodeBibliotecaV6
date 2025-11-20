@echo off
REM run_cron.bat - Rulează CRON notificări
cd /d C:\xampp\htdocs\biblioteca
C:\xampp\php\php.exe cron_notificari.php >> logs\cron_log.txt 2>&1
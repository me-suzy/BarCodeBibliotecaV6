@echo off
REM Script Batch pentru rularea backup-ului automat
REM Acest script este apelat de Task Scheduler Windows

REM Schimbă în directorul proiectului
cd /d "e:\Carte\BB\17 - Site Leadership\alte\Ionel Balauta\Aryeht\Task 1 - Traduce tot site-ul\Doar Google Web\Andreea\Meditatii\2023\BarCode Biblioteca"

REM Rulează scriptul PHP de backup
"C:\xampp\php\php.exe" backup_database.php

REM Verifică dacă backup-ul a reușit
if %ERRORLEVEL% EQU 0 (
    echo Backup realizat cu succes!
) else (
    echo EROARE la realizarea backup-ului!
)

REM Păstrează fereastra deschisă pentru debugging (opțional - poți comenta linia următoare)
REM pause


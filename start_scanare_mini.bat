@echo off
REM Pornește Chrome în mini-window mode

REM Închide Chrome dacă rulează deja (opțional)
taskkill /F /IM chrome.exe 2>nul

REM Așteaptă 2 secunde
timeout /t 2 /nobreak >nul

REM Deschide Chrome cu dimensiuni fixe și poziție
start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" ^
--new-window ^
--window-size=450,550 ^
--window-position=1450,50 ^
--app=http://localhost/scanare_mini.php

echo Mini Scanner pornit!
echo Fereastra ar trebui sa apara in coltul dreapta-sus.
timeout /t 3
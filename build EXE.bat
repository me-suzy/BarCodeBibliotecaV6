@echo off
echo ===================================
echo    BUILD EXE - Biblioteca
echo ===================================

echo.
echo [1/2] Building BarcodeScanner.exe...
pyinstaller --onefile --noconsole --name "BarcodeScanner" "barcode_scanner_dual FINAL.py"

echo.
echo [2/2] Building BarcodeGenerator.exe...
pyinstaller --onefile --noconsole --name "BarcodeGenerator" "Generator de coduri de bare Carti+Utilizatori FINAL.py"

echo.
echo ===================================
echo    BUILD COMPLETE!
echo    Check: dist\ folder
echo ===================================
pause
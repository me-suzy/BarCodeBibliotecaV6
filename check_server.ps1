# Script PowerShell pentru verificare server Linux
$password = "YOUR-PASSWORD"
$server = "root@65.176.121.45"

# Creează comanda SSH cu parola
$sshCommand = @"
df -h
echo "---SPATIU---"
mysql --version 2>&1 || echo "MySQL not in PATH"
echo "---MYSQL_VERSION---"
mysql -u root -e "SHOW DATABASES;" 2>&1 | head -20
echo "---DATABASES---"
mysql -u root -e "SELECT table_schema AS 'Database', ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' FROM information_schema.tables GROUP BY table_schema;" 2>&1
echo "---DB_SIZES---"
"@

# Folosește plink (PuTTY) dacă este disponibil, altfel sshpass
if (Get-Command plink -ErrorAction SilentlyContinue) {
    echo $password | plink -ssh -pw $password $server $sshCommand
} else {
    Write-Host "Plink (PuTTY) nu este instalat. Instalează PuTTY sau folosește WSL/Git Bash cu sshpass."
    Write-Host "Comanda manuală:"
    Write-Host "ssh -o StrictHostKeyChecking=no -o KexAlgorithms=+diffie-hellman-group-exchange-sha1 -o HostKeyAlgorithms=+ssh-rsa -o MACs=+hmac-sha1 root@65.176.121.45"
}


#!/bin/bash
# Script pentru verificare server Linux
# Rulare: bash check_server.sh sau ./check_server.sh

SERVER="root@65.176.121.45"
PASSWORD="YOUR-PASSWORD"

echo "=== Conectare la server Linux ==="
echo ""

# Verifică dacă sshpass este instalat
if ! command -v sshpass &> /dev/null; then
    echo "⚠️  sshpass nu este instalat. Instalează-l cu:"
    echo "   Ubuntu/Debian: sudo apt-get install sshpass"
    echo "   CentOS/RHEL: sudo yum install sshpass"
    echo ""
    echo "Sau conectează-te manual și rulează comenzile:"
    echo "ssh -o StrictHostKeyChecking=no -o KexAlgorithms=+diffie-hellman-group-exchange-sha1 -o HostKeyAlgorithms=+ssh-rsa -o MACs=+hmac-sha1 $SERVER"
    exit 1
fi

echo "1. Verificare spațiu liber pe disc:"
echo "-----------------------------------"
sshpass -p "$PASSWORD" ssh -o StrictHostKeyChecking=no \
    -o KexAlgorithms=+diffie-hellman-group-exchange-sha1 \
    -o HostKeyAlgorithms=+ssh-rsa \
    -o MACs=+hmac-sha1 \
    $SERVER "df -h"

echo ""
echo "2. Verificare versiune MySQL:"
echo "-----------------------------------"
sshpass -p "$PASSWORD" ssh -o StrictHostKeyChecking=no \
    -o KexAlgorithms=+diffie-hellman-group-exchange-sha1 \
    -o HostKeyAlgorithms=+ssh-rsa \
    -o MACs=+hmac-sha1 \
    $SERVER "mysql --version 2>&1 || echo 'MySQL nu este în PATH'"

echo ""
echo "3. Listează bazele de date existente:"
echo "-----------------------------------"
sshpass -p "$PASSWORD" ssh -o StrictHostKeyChecking=no \
    -o KexAlgorithms=+diffie-hellman-group-exchange-sha1 \
    -o HostKeyAlgorithms=+ssh-rsa \
    -o MACs=+hmac-sha1 \
    $SERVER "mysql -u root -e 'SHOW DATABASES;' 2>&1"

echo ""
echo "4. Verificare dimensiuni baze de date:"
echo "-----------------------------------"
sshpass -p "$PASSWORD" ssh -o StrictHostKeyChecking=no \
    -o KexAlgorithms=+diffie-hellman-group-exchange-sha1 \
    -o HostKeyAlgorithms=+ssh-rsa \
    -o MACs=+hmac-sha1 \
    $SERVER "mysql -u root -e \"SELECT table_schema AS 'Database', ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' FROM information_schema.tables GROUP BY table_schema ORDER BY table_schema;\" 2>&1"

echo ""
echo "5. Verificare configurație MySQL:"
echo "-----------------------------------"
sshpass -p "$PASSWORD" ssh -o StrictHostKeyChecking=no \
    -o KexAlgorithms=+diffie-hellman-group-exchange-sha1 \
    -o HostKeyAlgorithms=+ssh-rsa \
    -o MACs=+hmac-sha1 \
    $SERVER "mysql -u root -e 'SHOW VARIABLES LIKE \"datadir\";' 2>&1"

echo ""
echo "=== Verificare completă ==="


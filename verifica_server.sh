#!/bin/bash
# Script pentru verificarea serverului Linux
# Rulează: bash verifica_server.sh
# SAU copiază pe server și rulează direct

echo "🔍 VERIFICARE SERVER LINUX"
echo "═══════════════════════════════════════"
echo ""

echo "📊 SPATIU DISPONIBIL:"
echo "─────────────────────────────────"
df -h
echo ""

echo "📊 VERSIUNE MYSQL/MARIADB:"
echo "─────────────────────────────────"
mysql --version 2>&1 || mariadb --version 2>&1 || echo "MySQL/MariaDB nu este instalat sau nu este în PATH"
echo ""

echo "📊 BAZE DE DATE EXISTENTE:"
echo "─────────────────────────────────"
mysql -u root -p -e "SHOW DATABASES;" 2>&1 | grep -v "^Database$" | grep -v "^information_schema$" | grep -v "^performance_schema$" | grep -v "^mysql$" | grep -v "^sys$" || echo "Nu s-au putut lista bazele de date"
echo ""

echo "📊 SPATIU UTILIZAT DE MYSQL:"
echo "─────────────────────────────────"
du -sh /var/lib/mysql 2>/dev/null || du -sh /usr/local/mysql/data 2>/dev/null || du -sh /var/db/mysql 2>/dev/null || echo "Nu s-a găsit directorul MySQL"
echo ""

echo "📊 STATUS MYSQL:"
echo "─────────────────────────────────"
systemctl status mysql 2>&1 | head -10 || systemctl status mariadb 2>&1 | head -10 || service mysql status 2>&1 | head -10 || echo "Nu s-a putut verifica statusul"
echo ""

echo "📊 PROCESE MYSQL:"
echo "─────────────────────────────────"
ps aux | grep -i mysql | grep -v grep || echo "Nu s-au găsit procese MySQL"
echo ""

echo "📊 CONFIGURAȚIE MYSQL:"
echo "─────────────────────────────────"
mysql -u root -p -e "SHOW VARIABLES LIKE 'datadir';" 2>&1 | tail -1
echo ""

echo "✅ Verificare completă!"



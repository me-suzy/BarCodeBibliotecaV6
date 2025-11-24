#!/usr/bin/env python3
"""
Script pentru testarea stabilității serverului Linux și investigarea cauzelor întreruperilor.
Nu modifică nimic, doar verifică statusul sistemului.
"""

import paramiko
import time
from datetime import datetime

SERVER_IP = "65.176.121.45"
SSH_PORT = 22
SSH_USER = "root"
SSH_PASS = "YOUR-PASSWORD"

def execute_ssh_command(ssh, command):
    """Execută o comandă SSH și returnează output-ul"""
    try:
        stdin, stdout, stderr = ssh.exec_command(command)
        output = stdout.read().decode('utf-8', errors='ignore')
        error = stderr.read().decode('utf-8', errors='ignore')
        return output, error
    except Exception as e:
        return "", str(e)

def main():
    print(f"[{datetime.now()}] Conectare la serverul {SERVER_IP}...")
    
    try:
        # Configurare SSH pentru server vechi
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        
        # Configurare pentru algoritmi vechi - folosim o abordare mai simplă
        # Paramiko va negocia automat algoritmii disponibili
        ssh.connect(
            SERVER_IP, 
            port=SSH_PORT,
            username=SSH_USER, 
            password=SSH_PASS,
            timeout=10,
            allow_agent=False,
            look_for_keys=False
        )
        
        print(f"[{datetime.now()}] Conectat cu succes!\n")
        print("=" * 80)
        
        # 1. Verificare uptime și resurse de bază
        print("\n[1] STATUS GENERAL SISTEM")
        print("-" * 80)
        output, error = execute_ssh_command(ssh, "uptime")
        print(output)
        
        output, error = execute_ssh_command(ssh, "free -h")
        print("Memorie:")
        print(output)
        
        output, error = execute_ssh_command(ssh, "df -h")
        print("Spațiu disk:")
        print(output)
        
        # 2. Verificare procese Aleph
        print("\n[2] PROCESE ALEPH")
        print("-" * 80)
        output, error = execute_ssh_command(ssh, "ps aux | grep -i aleph | grep -v grep")
        if output.strip():
            print(output)
        else:
            print("Nu s-au găsit procese Aleph active!")
        
        # 3. Verificare port 8991
        print("\n[3] VERIFICARE PORT 8991")
        print("-" * 80)
        output, error = execute_ssh_command(ssh, "netstat -tuln | grep 8991 || ss -tuln | grep 8991")
        if output.strip():
            print(output)
        else:
            print("Portul 8991 nu este în ascultare!")
        
        # 4. Verificare servicii systemd
        print("\n[4] SERVICII SYSTEMD")
        print("-" * 80)
        output, error = execute_ssh_command(ssh, "systemctl list-units --type=service --state=running | grep -i aleph || echo 'Nu s-au găsit servicii Aleph'")
        print(output)
        
        # 5. Verificare loguri sistem recente
        print("\n[5] LOGURI SISTEM RECENTE (ultimele 50 linii)")
        print("-" * 80)
        output, error = execute_ssh_command(ssh, "journalctl -n 50 --no-pager 2>/dev/null || tail -50 /var/log/messages 2>/dev/null || tail -50 /var/log/syslog 2>/dev/null || echo 'Nu s-au găsit loguri'")
        print(output)
        
        # 6. Verificare loguri kernel (dmesg)
        print("\n[6] LOGURI KERNEL (dmesg - ultimele 30 linii)")
        print("-" * 80)
        output, error = execute_ssh_command(ssh, "dmesg | tail -30")
        print(output)
        
        # 7. Verificare erori în loguri
        print("\n[7] CĂUTARE ERORI ÎN LOGURI")
        print("-" * 80)
        output, error = execute_ssh_command(ssh, "journalctl -p err -n 20 --no-pager 2>/dev/null || grep -i error /var/log/messages 2>/dev/null | tail -20 || echo 'Nu s-au găsit erori'")
        print(output)
        
        # 8. Verificare load average și CPU
        print("\n[8] LOAD AVERAGE ȘI CPU")
        print("-" * 80)
        output, error = execute_ssh_command(ssh, "top -bn1 | head -20")
        print(output)
        
        # 9. Verificare procese care consumă resurse
        print("\n[9] PROCESE CU CONSUM RIDICAT")
        print("-" * 80)
        output, error = execute_ssh_command(ssh, "ps aux --sort=-%mem | head -10")
        print("Top 10 procese după memorie:")
        print(output)
        
        output, error = execute_ssh_command(ssh, "ps aux --sort=-%cpu | head -10")
        print("Top 10 procese după CPU:")
        print(output)
        
        # 10. Verificare cron jobs și task-uri programate
        print("\n[10] TASK-URI PROGRAMATE (cron)")
        print("-" * 80)
        output, error = execute_ssh_command(ssh, "crontab -l 2>/dev/null || echo 'Nu există cron jobs pentru root'")
        print(output)
        
        # 11. Verificare rețea și conexiuni
        print("\n[11] CONEXIUNI REȚEA ACTIVE")
        print("-" * 80)
        output, error = execute_ssh_command(ssh, "netstat -tn | head -20 || ss -tn | head -20")
        print(output)
        
        # 12. Verificare dacă există fișiere de log Aleph
        print("\n[12] CĂUTARE FIȘIERE LOG ALEPH")
        print("-" * 80)
        output, error = execute_ssh_command(ssh, "find /var/log /opt /usr/local /home -name '*aleph*' -type f 2>/dev/null | head -20")
        if output.strip():
            print("Fișiere găsite:")
            print(output)
            # Verificare ultimele linii din logurile Aleph
            for line in output.strip().split('\n')[:5]:
                if line.strip():
                    print(f"\nUltimele 20 linii din {line}:")
                    log_output, _ = execute_ssh_command(ssh, f"tail -20 '{line}' 2>/dev/null")
                    print(log_output)
        else:
            print("Nu s-au găsit fișiere log Aleph")
        
        # 13. Verificare dacă serverul a avut reboot-uri recente
        print("\n[13] ISTORIC REBOOT-URI")
        print("-" * 80)
        output, error = execute_ssh_command(ssh, "last reboot | head -10")
        print(output)
        
        output, error = execute_ssh_command(ssh, "who -b")
        print("Ultimul boot:")
        print(output)
        
        # 14. Verificare swap
        print("\n[14] STATUS SWAP")
        print("-" * 80)
        output, error = execute_ssh_command(ssh, "swapon --show")
        print(output)
        
        output, error = execute_ssh_command(ssh, "cat /proc/swaps")
        print(output)
        
        print("\n" + "=" * 80)
        print(f"[{datetime.now()}] Verificare completă!")
        
        ssh.close()
        
    except paramiko.AuthenticationException:
        print("Eroare de autentificare!")
    except Exception as e:
        print(f"Eroare: {e}")
        import traceback
        traceback.print_exc()

if __name__ == "__main__":
    main()


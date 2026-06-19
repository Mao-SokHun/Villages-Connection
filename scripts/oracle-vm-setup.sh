#!/usr/bin/env bash
# Oracle Cloud Always Free — first-time VM setup (Ubuntu 22.04/24.04).
# Run on the VM: sudo bash scripts/oracle-vm-setup.sh
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
    echo "Run as root: sudo bash scripts/oracle-vm-setup.sh"
    exit 1
fi

export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get upgrade -y
apt-get install -y ca-certificates curl git ufw

if ! command -v docker >/dev/null 2>&1; then
    install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
    chmod a+r /etc/apt/keyrings/docker.asc
    echo \
      "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu \
      $(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
      > /etc/apt/sources.list.d/docker.list
    apt-get update
    apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
fi

systemctl enable docker
systemctl start docker

ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

echo ""
echo "=== VM setup complete ==="
echo "1. Open OCI Security List: allow TCP 22, 80, 443 inbound"
echo "2. Clone repo: git clone https://github.com/Mao-SokHun/Villages-Connection.git"
echo "3. cp .env.example .env && nano .env  (Supabase DB_*, MAIL_*, APP_URL)"
echo "4. bash scripts/oracle-deploy.sh"

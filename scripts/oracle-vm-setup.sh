#!/usr/bin/env bash
# Oracle Cloud Free Tier — first-time VM setup (Ubuntu 22.04/24.04 ARM or AMD).
# Run as root on a fresh instance: sudo bash scripts/oracle-vm-setup.sh
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
    echo "Run as root: sudo bash scripts/oracle-vm-setup.sh"
    exit 1
fi

export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get upgrade -y
apt-get install -y ca-certificates curl git ufw

# Docker Engine + Compose plugin
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

# Firewall
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

echo ""
echo "=== Oracle VM setup complete ==="
echo "1. In OCI Console: Security List must allow ingress TCP 22, 80, 443"
echo "2. Clone repo and copy .env from .env.example"
echo "3. docker compose -f docker-compose.prod.yml up -d --build"
echo "4. docker compose -f docker-compose.prod.yml exec app php database/migrate.php"
echo "5. bash scripts/oracle-enable-https.sh yourdomain.com you@email.com"

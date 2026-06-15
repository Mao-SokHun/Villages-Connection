#!/usr/bin/env sh
set -e
CERT_DIR="$(cd "$(dirname "$0")/../docker/certs" && pwd)"
mkdir -p "$CERT_DIR"

if [ -f "$CERT_DIR/dev.key" ] && [ -f "$CERT_DIR/dev.crt" ]; then
    echo "SSL certs already exist in docker/certs/"
    exit 0
fi

openssl req -x509 -nodes -days 825 -newkey rsa:2048 \
    -keyout "$CERT_DIR/dev.key" \
    -out "$CERT_DIR/dev.crt" \
    -subj "/CN=localhost"

echo "Created docker/certs/dev.crt and dev.key"
echo "Use https://localhost:8443 after: docker compose up -d --build"

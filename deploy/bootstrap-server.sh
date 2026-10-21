#!/usr/bin/env bash
#
# Prepara un servidor Ubuntu/Debian para ejecutar FisioApp con Sail.
#
#     sudo ./deploy/bootstrap-server.sh
#
# ⚠️ IMPORTANTE: este servidor YA tiene nginx sirviendo otro sitio
#    (salvamartinez.es). Este script NO lo sustituye: añade un vhost nuevo
#    para el subdominio de la aplicación y deja el sitio raíz intacto.
#
# Qué hace:
#   1. Instala Docker + plugin Compose v2 (si no está).
#   2. Crea el usuario de despliegue y el directorio de la aplicación.
#   3. Instala Supervisor (para el worker de la cola).
#   4. Añade el vhost de nginx que hace de reverse proxy hacia Sail.
#   5. Instala certbot y emite el certificado TLS del subdominio.
#   6. Habilita el reinicio automático de los contenedores tras un reboot.
#
# Es idempotente: se puede volver a ejecutar sin romper nada.
#
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/fisioapp}"
SUBDOMAIN="${SUBDOMAIN:-fisio.salvamartinez.es}"
APP_PORT="${APP_PORT:-8080}"
DEPLOY_USER="${DEPLOY_USER:-deploy}"
ACME_EMAIL="${ACME_EMAIL:-}"

log()  { printf '\033[0;36m[bootstrap]\033[0m %s\n' "$*"; }
warn() { printf '\033[0;33m[bootstrap]\033[0m %s\n' "$*"; }
die()  { printf '\033[0;31m[bootstrap] ERROR:\033[0m %s\n' "$*" >&2; exit 1; }

[ "$(id -u)" -eq 0 ] || die "Ejecuta este script como root (sudo)."
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

# ---------------------------------------------------------------------------
# 1. Docker
# ---------------------------------------------------------------------------
log "Instalando Docker…"
if ! command -v docker >/dev/null 2>&1; then
    apt-get update -qq
    apt-get install -y -qq ca-certificates curl gnupg git unzip
    install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
        | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    chmod a+r /etc/apt/keyrings/docker.gpg
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
        > /etc/apt/sources.list.d/docker.list
    apt-get update -qq
    apt-get install -y -qq docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
fi

systemctl enable --now docker
log "Docker: $(docker --version)"

# ---------------------------------------------------------------------------
# 2. Usuario de despliegue y directorio de la aplicación
# ---------------------------------------------------------------------------
log "Configurando el usuario '$DEPLOY_USER'…"
if ! id -u "$DEPLOY_USER" >/dev/null 2>&1; then
    useradd -m -s /bin/bash "$DEPLOY_USER"
fi

mkdir -p "$APP_DIR"
chown -R "$DEPLOY_USER:$DEPLOY_USER" "$APP_DIR"

# El usuario de despliegue necesita usar Docker sin sudo.
usermod -aG docker "$DEPLOY_USER"
log "Usuario '$DEPLOY_USER' añadido al grupo docker."

# ---------------------------------------------------------------------------
# 3. Supervisor (worker de la cola)
# ---------------------------------------------------------------------------
log "Instalando Supervisor…"
apt-get update -qq
apt-get install -y -qq supervisor

if [ -f "$SCRIPT_DIR/supervisor/fisioapp-worker.conf" ]; then
    sed -e "s#{{APP_DIR}}#$APP_DIR#g" -e "s#^user=.*#user=$DEPLOY_USER#" \
        "$SCRIPT_DIR/supervisor/fisioapp-worker.conf" \
        > /etc/supervisor/conf.d/fisioapp-worker.conf
    log "Configuración del worker instalada."
else
    warn "No se encontró supervisor/fisioapp-worker.conf."
fi

systemctl enable --now supervisor

# ---------------------------------------------------------------------------
# 4. nginx: reverse proxy hacia Sail
# ---------------------------------------------------------------------------
log "Instalando el vhost de nginx para $SUBDOMAIN…"
if [ ! -d /etc/nginx/sites-available ]; then
    apt-get update -qq
    apt-get install -y -qq nginx
fi

sed -e "s#{{SUBDOMAIN}}#$SUBDOMAIN#g" -e "s#{{APP_PORT}}#$APP_PORT#g" \
    "$SCRIPT_DIR/nginx/fisioapp.conf" > /etc/nginx/sites-available/fisioapp.conf

ln -sf /etc/nginx/sites-available/fisioapp.conf /etc/nginx/sites-enabled/fisioapp.conf

# nginx ya sirve otro sitio: se comprueba la configuración antes de recargar.
if nginx -t 2>&1 | grep -q "syntax is ok\|test is successful"; then
    systemctl reload nginx
    log "nginx recargado (el sitio raíz no se ha tocado)."
else
    rm -f /etc/nginx/sites-enabled/fisioapp.conf
    die "La configuración de nginx no era válida: se ha revertido el vhost."
fi

# ---------------------------------------------------------------------------
# 5. HTTPS con certbot
# ---------------------------------------------------------------------------
log "Instalando certbot…"
apt-get install -y -qq certbot python3-certbot-nginx

# El dominio debe resolver ya a esta IP para que Let's Encrypt lo valide.
if [ -n "$ACME_EMAIL" ]; then
    log "Emitiendo certificado para $SUBDOMAIN…"
    certbot --nginx -d "$SUBDOMAIN" --non-interactive --agree-tos \
        --email "$ACME_EMAIL" --redirect \
        || warn "No se pudo emitir el certificado. Revisa que el DNS de $SUBDOMAIN apunte a esta IP."
else
    warn "ACME_EMAIL no indicado: emite el certificado a mano con:"
    warn "  certbot --nginx -d $SUBDOMAIN"
fi

systemctl enable --now certbot.timer

# ---------------------------------------------------------------------------
# 6. Los contenedores de Sail deben sobrevivir a un reinicio
# ---------------------------------------------------------------------------
log "Habilitando el reinicio automático de los contenedores…"
cat > /etc/systemd/system/fisioapp.service <<EOF
[Unit]
Description=FisioApp (Laravel Sail)
Requires=docker.service
After=docker.service network-online.target

[Service]
Type=oneshot
RemainAfterExit=yes
WorkingDirectory=$APP_DIR
ExecStart=/usr/bin/docker compose up -d
ExecStop=/usr/bin/docker compose down
TimeoutStartSec=0

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable fisioapp.service

# ---------------------------------------------------------------------------
# 7. Rotación de logs
# ---------------------------------------------------------------------------
log "Configurando la rotación de logs…"
cat > /etc/logrotate.d/fisioapp <<'EOF'
/var/log/fisioapp/*.log {
    weekly
    rotate 8
    compress
    delaycompress
    missingok
    notifempty
    copytruncate
}
EOF

mkdir -p /var/log/fisioapp
chown -R "$DEPLOY_USER:$DEPLOY_USER" /var/log/fisioapp

# ---------------------------------------------------------------------------
cat <<EOF

────────────────────────────────────────────────────────────────
 ✅ Servidor preparado

  Aplicación:    $APP_DIR
  Proxy (nginx): $SUBDOMAIN → 127.0.0.1:$APP_PORT
  Usuario:       $DEPLOY_USER
  Sitio raíz:    intacto (no se ha tocado el vhost de salvamartinez.es)

  Siguientes pasos:
    1. cd $APP_DIR && git clone <repo> . && cp .env.example .env
    2. Edita .env (APP_KEY, GEMINI_API_KEY, MAIL_MAILER, SESSION_SECURE_COOKIE=true)
    3. sudo -u $DEPLOY_USER ./vendor/bin/sail up -d
    4. ./vendor/bin/sail artisan migrate --force
    5. sudo supervisorctl start fisioapp-worker
────────────────────────────────────────────────────────────────
EOF

#!/usr/bin/env bash
#
# Script de despliegue de FisioApp en el servidor de producción.
#
# Se ejecuta EN el servidor, desde el propio repositorio:
#     cd /var/www/fisioapp && ./deploy/deploy.sh
#
# Es idempotente: se puede volver a ejecutar sin efectos secundarios.
# La primera vez hay que ejecutar antes deploy/bootstrap-server.sh.
#
set -euo pipefail

# ---------------------------------------------------------------------------
# Configuración (sobrescribible por entorno)
# ---------------------------------------------------------------------------
APP_DIR="${APP_DIR:-/var/www/fisioapp}"
BRANCH="${BRANCH:-main}"
HEALTH_PORT="${HEALTH_PORT:-18081}"
KEEP_RELEASES="${KEEP_RELEASES:-5}"
# SKIP_GIT=1 reejecuta el despliegue sin tocar el código (útil para recuperar
# un despliegue a medias sin volver a hacer pull).
SKIP_GIT="${SKIP_GIT:-0}"

log()  { printf '\033[0;36m[deploy]\033[0m %s\n' "$*"; }
warn() { printf '\033[0;33m[deploy]\033[0m %s\n' "$*"; }
die()  { printf '\033[0;31m[deploy] ERROR:\033[0m %s\n' "$*" >&2; exit 1; }

cd "$APP_DIR"

# ---------------------------------------------------------------------------
# 1. Lock: evita que dos despliegues simultáneos se pisen
# ---------------------------------------------------------------------------
LOCK_FILE="/tmp/fisioapp-deploy.lock"
exec 9>"$LOCK_FILE"
if ! flock -n 9; then
    die "Ya hay otro despliegue en curso."
fi

START_TS=$(date +%s)

# ---------------------------------------------------------------------------
# 2. Código
# ---------------------------------------------------------------------------
log "Obteniendo $BRANCH desde origin…"
if [ "$SKIP_GIT" = "1" ]; then
    warn "SKIP_GIT=1: no se toca el código. Commit actual: $(git rev-parse --short HEAD)"
else
    git fetch --prune origin "$BRANCH"
    git reset --hard "origin/$BRANCH"
    git clean -fd -e vendor -e node_modules -e public/build -e storage
    log "Commit desplegado: $(git rev-parse --short HEAD) ($(git log -1 --format=%s))"
fi

# ---------------------------------------------------------------------------
# 3. Contenedores
# ---------------------------------------------------------------------------
log "Levantando contenedores…"
docker compose up -d --remove-orphans

# Espera a que MySQL esté listo (Sail ya tiene healthcheck, pero el primer
# arranque de MySQL inicializa el datadir y tarda).
log "Esperando a MySQL…"
for i in $(seq 1 60); do
    if docker compose exec -T mysql mysqladmin ping -ppassword --silent >/dev/null 2>&1; then
        log "MySQL listo."
        break
    fi
    if [ "$i" -eq 60 ]; then
        die "MySQL no respondió a tiempo."
    fi
    sleep 2
done

# ---------------------------------------------------------------------------
# 4. Dependencias
# ---------------------------------------------------------------------------
log "Instalando dependencias PHP…"
docker compose exec -T laravel.test composer install \
    --no-dev --no-interaction --prefer-dist --optimize-autoloader

log "Compilando assets…"
docker compose exec -T laravel.test npm ci --no-audit --no-fund
docker compose exec -T laravel.test npm run build

# ---------------------------------------------------------------------------
# 5. Base de datos y cachés
# ---------------------------------------------------------------------------
log "Aplicando migraciones…"
docker compose exec -T laravel.test php artisan migrate --force

log "Optimizando cachés…"
docker compose exec -T laravel.test php artisan config:clear
docker compose exec -T laravel.test php artisan route:clear
docker compose exec -T laravel.test php artisan view:clear
docker compose exec -T laravel.test php artisan config:cache
docker compose exec -T laravel.test php artisan route:cache
docker compose exec -T laravel.test php artisan view:cache
docker compose exec -T laravel.test php artisan event:cache

# ---------------------------------------------------------------------------
# 6. Worker de la cola
# ---------------------------------------------------------------------------
# El análisis de IA del feedback se genera en segundo plano: si el worker no
# está vivo, el feedback se guarda pero el análisis nunca aparece.
if command -v supervisorctl >/dev/null 2>&1; then
    log "Reiniciando el worker de la cola…"
    supervisorctl restart fisioapp-worker >/dev/null 2>&1 \
        || warn "No se pudo reiniciar 'fisioapp-worker' (¿está instalado supervisor?)."
else
    warn "supervisor no está instalado: el worker de la cola no se ha reiniciado."
fi

# ---------------------------------------------------------------------------
# 7. Comprobación de salud
# ---------------------------------------------------------------------------
log "Comprobando salud de la aplicación…"
HEALTHY=0
for i in $(seq 1 20); do
    if curl -fsS "http://127.0.0.1:${HEALTH_PORT}/up" >/dev/null 2>&1; then
        HEALTHY=1
        break
    fi
    sleep 1
done

if [ "$HEALTHY" -ne 1 ]; then
    warn "La comprobación de salud falló. Últimas líneas del log:"
    docker compose logs --tail=30 laravel.test || true
    die "El despliegue no superó la comprobación de salud."
fi

# ---------------------------------------------------------------------------
# 8. Limpieza de imágenes viejas
# ---------------------------------------------------------------------------
log "Limpiando imágenes Docker sin uso…"
docker image prune -f --filter "until=168h" >/dev/null 2>&1 || true
docker compose exec -T laravel.test php artisan queue:restart >/dev/null 2>&1 || true

DURATION=$(( $(date +%s) - START_TS ))
log "✅ Despliegue completado en ${DURATION}s."

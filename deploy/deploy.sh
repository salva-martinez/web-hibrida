#!/usr/bin/env bash
#
# Script de despliegue de FisioApp en el servidor de producción.
#
# Se ejecuta EN el servidor, desde el propio repositorio:
#     cd /var/www/web-hibrida && ./deploy/deploy.sh
#
# Es idempotente: se puede volver a ejecutar sin efectos secundarios.
# La primera vez hay que ejecutar antes deploy/bootstrap-server.sh.
#
set -euo pipefail

# ---------------------------------------------------------------------------
# Configuración (sobrescribible por entorno)
# ---------------------------------------------------------------------------
APP_DIR="${APP_DIR:-/var/www/web-hibrida}"
BRANCH="${BRANCH:-main}"
HEALTH_PORT="${HEALTH_PORT:-18081}"
# SKIP_GIT=1 reejecuta el despliegue sin tocar el código (útil para recuperar
# un despliegue a medias sin volver a hacer pull).
SKIP_GIT="${SKIP_GIT:-0}"

log()  { printf '\033[0;36m[deploy]\033[0m %s\n' "$*"; }
warn() { printf '\033[0;33m[deploy]\033[0m %s\n' "$*"; }
die()  { printf '\033[0;31m[deploy] ERROR:\033[0m %s\n' "$*" >&2; exit 1; }

cd "$APP_DIR"

# Todos los "docker compose exec" necesitan stdin cerrado: si lo heredan de una
# sesión SSH abierta, se quedan esperando entrada y el despliegue se cuelga.
dc() { docker compose exec -T "$@" < /dev/null; }

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
if [ "$SKIP_GIT" = "1" ]; then
    warn "SKIP_GIT=1: no se toca el código. Commit actual: $(git rev-parse --short HEAD)"
else
    log "Obteniendo $BRANCH desde origin…"
    git fetch --prune origin "$BRANCH"
    git reset --hard "origin/$BRANCH"
    git clean -fd -e vendor -e node_modules -e public/build -e storage -e .env
    log "Commit desplegado: $(git rev-parse --short HEAD) ($(git log -1 --format=%s))"
fi

# ---------------------------------------------------------------------------
# 3. Contenedores
# ---------------------------------------------------------------------------
log "Levantando contenedores…"
docker compose up -d --remove-orphans

# ---------------------------------------------------------------------------
# 4. Usuario efectivo del servidor PHP dentro del contenedor
#
# Sail ejecuta el servidor web como el usuario "sail" (uid 1337, gid 0). Si los
# ficheros de storage/ o bootstrap/cache no son escribibles por ese usuario, el
# compilador de Blade no puede crear las vistas y TODAS las peticiones fallan
# con un 500 y el error "tempnam(): file created in the system's temporary
# directory".
#
# El chown usa el WWWUSER/WWWGROUP del .env (no el uid del contenedor): así el
# propietario sigue siendo el usuario del host y el contenedor puede escribir
# porque WWWGROUP=0 (el usuario "sail" pertenece al grupo root).
# ---------------------------------------------------------------------------
log "Detectando el usuario del contenedor…"
CONTAINER_UID="$(dc laravel.test id -u sail 2>/dev/null | tr -d '\r\n' || echo 1337)"
CONTAINER_GID="$(dc laravel.test id -g sail 2>/dev/null | tr -d '\r\n' || echo 0)"
[ -n "$CONTAINER_UID" ] || CONTAINER_UID=1337
[ -n "$CONTAINER_GID" ] || CONTAINER_GID=0
log "  el servidor web usa uid=$CONTAINER_UID gid=$CONTAINER_GID"

WWWUSER_VAL="$(grep -E '^WWWUSER=' .env 2>/dev/null | tail -1 | cut -d= -f2)"
WWWGROUP_VAL="$(grep -E '^WWWGROUP=' .env 2>/dev/null | tail -1 | cut -d= -f2)"
WWWUSER_VAL="${WWWUSER_VAL:-$CONTAINER_UID}"
WWWGROUP_VAL="${WWWGROUP_VAL:-$CONTAINER_GID}"
log "  los ficheros serán del usuario $WWWUSER_VAL:$WWWGROUP_VAL"

# Los directorios de trabajo deben existir ANTES de cachear la configuración:
# config/view.php resuelve 'compiled' con realpath(storage_path('framework/views'))
# y, si el directorio no existe en ese momento, se cachea false y la aplicación
# devuelve 500 en todas las peticiones.
log "Preparando los directorios de trabajo…"
dc -u root laravel.test sh -c '
    mkdir -p /var/www/html/storage/framework/views \
             /var/www/html/storage/framework/cache/data \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/testing \
             /var/www/html/storage/logs \
             /var/www/html/storage/app/public \
             /var/www/html/storage/app/private \
             /var/www/html/bootstrap/cache
' > /dev/null

# ---------------------------------------------------------------------------
# 5. Dependencias
# ---------------------------------------------------------------------------
log "Instalando dependencias PHP…"
dc laravel.test composer install \
    --no-dev --no-interaction --prefer-dist --optimize-autoloader

log "Compilando assets…"
dc laravel.test npm ci --no-audit --no-fund
dc laravel.test npm run build

# ---------------------------------------------------------------------------
# 6. Base de datos y cachés
# ---------------------------------------------------------------------------
log "Aplicando migraciones…"
dc laravel.test php artisan migrate --force

log "Optimizando cachés…"
dc laravel.test php artisan config:clear
dc laravel.test php artisan route:clear
dc laravel.test php artisan view:clear
dc laravel.test php artisan event:clear
dc laravel.test php artisan config:cache
dc laravel.test php artisan route:cache
dc laravel.test php artisan view:cache
dc laravel.test php artisan event:cache

# ---------------------------------------------------------------------------
# 7. Permisos
#
# Los comandos artisan se ejecutan dentro del contenedor como root, por lo que
# los ficheros de caché y de logs quedan como root. Sin este chown, el
# servidor web (que corre como "sail") no puede escribir el log ni compilar
# vistas, y la aplicación devuelve 500.
# ---------------------------------------------------------------------------
log "Ajustando permisos de caché y logs…"
dc -u root laravel.test chown -R "${WWWUSER_VAL}:${WWWGROUP_VAL}" \
    /var/www/html/bootstrap/cache /var/www/html/storage /var/www/html/node_modules /var/www/html/public/build > /dev/null
dc -u root laravel.test chmod -R ug+rwX \
    /var/www/html/bootstrap/cache /var/www/html/storage /var/www/html/node_modules /var/www/html/public/build > /dev/null

# ---------------------------------------------------------------------------
# 8. Portfolio del dominio raíz
#
# El dominio principal sirve una landing estática con los proyectos. Vive
# fuera del bind mount (en /var/www/portfolio), así que se copia desde el host.
# ---------------------------------------------------------------------------
if [ -f deploy/portfolio/index.html ]; then
    log "Publicando el portfolio…"
    mkdir -p /var/www/portfolio
    install -m 644 deploy/portfolio/index.html /var/www/portfolio/index.html
    chown www-data:www-data /var/www/portfolio/index.html 2>/dev/null || true
fi

# ---------------------------------------------------------------------------
# 9. Worker de la cola
# ---------------------------------------------------------------------------
# El análisis de IA del feedback se genera en segundo plano: si el worker no
# está vivo, el feedback se guarda pero el análisis nunca aparece.
if command -v supervisorctl > /dev/null 2>&1; then
    log "Reiniciando el worker de la cola…"
    supervisorctl restart fisioapp-worker > /dev/null 2>&1 \
        || warn "No se pudo reiniciar 'fisioapp-worker' (¿está instalado supervisor?)."
else
    warn "supervisor no está instalado: el worker de la cola no se ha reiniciado."
fi

# ---------------------------------------------------------------------------
# 10. Comprobación de salud
# ---------------------------------------------------------------------------
log "Comprobando salud de la aplicación…"
HEALTHY=0
for i in $(seq 1 20); do
    if curl -fsS "http://127.0.0.1:${HEALTH_PORT}/up" > /dev/null 2>&1; then
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
# 11. Limpieza
# ---------------------------------------------------------------------------
log "Limpiando imágenes Docker sin uso…"
docker image prune -f --filter "until=168h" > /dev/null 2>&1 || true

DURATION=$(( $(date +%s) - START_TS ))
log "✅ Despliegue completado en ${DURATION}s."

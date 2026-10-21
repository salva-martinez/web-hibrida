#!/usr/bin/env bash
#
# Copia de seguridad de la base de datos de FisioApp.
#
# Se ejecuta desde el cron del servidor:
#   17 3 * * *  /var/www/web-hibrida/deploy/backup-mysql.sh >> /var/log/fisioapp/backup.log 2>&1
#
# Genera un dump comprimido de la base de datos y una copia cifrada-permisos
# 600 del .env (contiene la APP_KEY y la clave de la IA: sin ella no se pueden
# descifrar las cookies de sesión ni recuperar la aplicación).
#
# Para restaurar:
#   gunzip -c /var/backups/fisioapp/fisioterapia-AAAA-MM-DD-HHMMSS.sql.gz | \
#     docker compose exec -T mysql mysql -usail -p<fisioterapia> fisioterapia
#
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/web-hibrida}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/fisioapp}"
KEEP_DAYS="${KEEP_DAYS:-14}"
# Se usa "docker compose" en lugar del nombre del contenedor: el nombre cambia
# según el nombre del directorio del proyecto (web_hibrida- vs web-hibrida-).
MYSQL_SERVICE="${MYSQL_SERVICE:-mysql}"
DB_NAME="${DB_NAME:-fisioterapia}"
DB_USER="${DB_USER:-sail}"
# La contraseña de root del contenedor: en Sail es la misma que DB_PASSWORD.
# Solo se usa para crear y borrar la base de datos temporal de verificación.
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-}"

log() { printf '[backup] %s\n' "$*"; }

# El cron no tiene a nadie delante: si algo falla, tiene que quedar escrito
# en el log con un mensaje claro, no solo un código de salida.
trap 'log "ERROR: el backup ha fallado (línea $LINENO, código $?). Revisa el log."' ERR

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

FECHA="$(date +%F)"
STAMP="$(date +%F-%H%M%S)"

# --- Base de datos -------------------------------------------------------
log "Volcando la base de datos $DB_NAME…"

# La contraseña se lee del .env: no se escribe en el cron ni en el historial.
if [ -f "$APP_DIR/.env" ]; then
    DB_PASSWORD="$(grep -E '^DB_PASSWORD=' "$APP_DIR/.env" | tail -1 | cut -d= -f2-)"
    [ -n "$DB_ROOT_PASSWORD" ] || DB_ROOT_PASSWORD="$DB_PASSWORD"
fi

DESTINO="$BACKUP_DIR/${DB_NAME}-${STAMP}.sql.gz"

docker compose exec -T "$MYSQL_SERVICE" \
    mysqldump -u"$DB_USER" -p"$DB_PASSWORD" \
    --single-transaction --routines --triggers --events \
    --no-tablespaces "$DB_NAME" 2>/dev/null | gzip -9 > "$DESTINO"

if [ ! -s "$DESTINO" ]; then
    log "ERROR: el dump ha quedado vacío. No se continúa."
    exit 1
fi

# Se comprueba que el dump contiene datos de verdad.
#
# OJO: no se usa "grep -q": al encontrar la coincidencia cierra la pipe y gzip
# recibe SIGPIPE, que con "set -o pipefail" hace que el pipeline devuelva
# error aunque el grep haya funcionado. Con "grep -c" se lee todo el fichero.
NUM_TABLAS="$(gzip -cd "$DESTINO" | grep -c "CREATE TABLE" || true)"

if [ "${NUM_TABLAS:-0}" -lt 1 ]; then
    log "ERROR: el dump no parece válido (0 tablas). Revísalo antes de continuar."
    exit 1
fi

log "El dump contiene $NUM_TABLAS tablas."

chmod 600 "$DESTINO"
log "Copia creada: $DESTINO ($(du -h "$DESTINO" | cut -f1))"

# --- .env (APP_KEY y configuración) ---------------------------------------
if [ -f "$APP_DIR/.env" ]; then
    cp "$APP_DIR/.env" "$BACKUP_DIR/env-${STAMP}.bak"
    chmod 600 "$BACKUP_DIR/env-${STAMP}.bak"
    log "Copia del .env creada."
fi

# --- Rotación ------------------------------------------------------------
log "Eliminando copias de más de $KEEP_DAYS días…"
find "$BACKUP_DIR" -name "${DB_NAME}-*.sql.gz" -type f -mtime "+$KEEP_DAYS" -delete
find "$BACKUP_DIR" -name "env-*.bak" -type f -mtime "+$KEEP_DAYS" -delete

# --- Comprobación de restauración (opcional) ------------------------------
# MySQL no tiene "restore --dry-run", pero sí se puede cargar el dump en una
# base de datos temporal para verificar que sirve.
if [ "${VERIFY:-0}" = "1" ]; then
    log "Verificando el dump (carga en una base de datos temporal)…"
    # La base de datos de verificación la crea root: el usuario de la aplicación
    # no tiene permiso para crear bases de datos.
    docker compose exec -T "$MYSQL_SERVICE" \
        mysql -uroot -p"$DB_ROOT_PASSWORD" \
        -e "DROP DATABASE IF EXISTS fisioapp_verify; CREATE DATABASE fisioapp_verify;" 2>/dev/null
    gzip -cd "$DESTINO" | docker compose exec -T "$MYSQL_SERVICE" \
        mysql -uroot -p"$DB_ROOT_PASSWORD" fisioapp_verify 2>/dev/null
    docker compose exec -T "$MYSQL_SERVICE" \
        mysql -uroot -p"$DB_ROOT_PASSWORD" -e "DROP DATABASE fisioapp_verify;" 2>/dev/null
    log "El dump se carga correctamente."
fi

log "Copia de seguridad completada."

#!/usr/bin/env bash
#
# Aplica los vhosts de nginx de FisioApp y del portfolio, con sus páginas de
# error propias.
#
# Es idempotente (se puede reejecutar) y seguro: si la configuración de nginx
# no valida, se restauran los ficheros anteriores y no se recarga nada.
#
# Cada subdominio sirve SUS errores desde el disco, no desde la aplicación:
#   - fisio.salvamartinez.es  -> public/errors/ de FisioApp
#   - salvamartinez.es        -> /var/www/portfolio/errors
#   - reservas.salvamartinez.es -> /opt/reservas/public/errors
#
# Así, si una aplicación está caída o en mantenimiento, el visitante sigue
# viendo una página con el estilo de ESA aplicación y, sobre todo, se queda en
# su subdominio (error_page es una redirección interna: no cambia la URL).
#
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/web-hibrida}"
NGINX_DIR="${NGINX_DIR:-/etc/nginx/conf.d}"
PORTFOLIO_DIR="${PORTFOLIO_DIR:-/var/www/portfolio}"
RESERVAS_DIR="${RESERVAS_DIR:-/opt/reservas}"
APP_PORT="${APP_PORT:-18081}"

log() { printf '[vhosts] %s\n' "$*"; }

cd "$APP_DIR"

# ---------------------------------------------------------------------------
# 1. Copia de seguridad
# ---------------------------------------------------------------------------
BACKUP="$(mktemp -d /tmp/nginx-backup-XXXXXX)"

for f in "$NGINX_DIR/fisioapp-sites.conf" "$NGINX_DIR/reservas-sites.conf" "$NGINX_DIR/portfolio-sites.conf"; do
    [ -f "$f" ] && cp "$f" "$BACKUP/$(basename "$f")"
done

log "Copia de seguridad en $BACKUP"

restaurar() {
    log "ERROR: no se aplica la configuración. Se restauran los vhosts anteriores."
    for f in "$BACKUP"/*.conf; do
        [ -f "$f" ] && cp "$f" "$NGINX_DIR/$(basename "$f")"
    done
    rm -rf "$BACKUP"
    exit 1
}

# ---------------------------------------------------------------------------
# 2. Páginas de error
# ---------------------------------------------------------------------------
log "Publicando las páginas de error…"

# FisioApp: las genera el comando artisan desde las vistas Blade, para que
# haya una sola fuente de verdad.
docker compose exec -T laravel.test php artisan errors:publicar > /dev/null

# Portfolio y reservas: HTML estático versionado en el repositorio.
mkdir -p "$PORTFOLIO_DIR/errors" "$RESERVAS_DIR/public/errors"
cp -f deploy/errors/portfolio/*.html "$PORTFOLIO_DIR/errors/"
cp -f deploy/errors/reservas/*.html "$RESERVAS_DIR/public/errors/"
chmod 644 "$PORTFOLIO_DIR/errors/"*.html "$RESERVAS_DIR/public/errors/"*.html

# ---------------------------------------------------------------------------
# 3. Vhosts
# ---------------------------------------------------------------------------
log "Generando los vhosts…"

sed -e "s#{{APP_PORT}}#$APP_PORT#g" deploy/nginx/fisioapp.conf > "$NGINX_DIR/fisioapp-sites.conf"

sed -e "s#{{PORTFOLIO_DIR}}#$PORTFOLIO_DIR#g" deploy/nginx/portfolio.conf > "$NGINX_DIR/portfolio-sites.conf"

# El vhost de "salvamartinez.es" pasa a vivir en su propio fichero: el de
# reservas se queda solo con el subdominio de reservas.
python3 - <<'PY' || exit 1
import re, sys

ruta = '/etc/nginx/conf.d/reservas-sites.conf'
contenido = open(ruta, encoding='utf-8').read()

# Se elimina el bloque "server { ... }" cuyo server_name es salvamartinez.es
# (el del 443 con el proxy a FisioApp). El del puerto 80 se conserva: es la
# redirección a HTTPS que gestiona certbot.
def eliminar_bloque(texto, objetivo):
    inicio = texto.find('server {')
    while inicio != -1:
        # Se busca el cierre del bloque contando llaves
        nivel, i = 0, inicio
        while i < len(texto):
            if texto[i] == '{':
                nivel += 1
            elif texto[i] == '}':
                nivel -= 1
                if nivel == 0:
                    break
            i += 1
        bloque = texto[inicio:i + 1]
        if objetivo in bloque:
            # Solo el bloque con SSL (el 80 se queda)
            if 'ssl_certificate' in bloque:
                return texto[:inicio] + texto[i + 1:]
            return texto
        inicio = texto.find('server {', i + 1)
    return texto

nuevo = eliminar_bloque(contenido, 'server_name salvamartinez.es;')

if nuevo != contenido:
    open(ruta, 'w', encoding='utf-8').write(nuevo)
    print('  · bloque de salvamartinez.es retirado de reservas-sites.conf')
else:
    print('  · el vhost de salvamartinez.es ya no estaba en reservas-sites.conf')
PY

# ---------------------------------------------------------------------------
# 4. Validación y recarga
# ---------------------------------------------------------------------------
# OJO: no se usa "grep -q" sobre un pipe. Al encontrar la coincidencia cierra la
# pipe, nginx recibe SIGPIPE y, con "set -o pipefail", el pipeline devuelve
# error aunque nginx haya validado la configuración bien.
SALIDA_NGINX="$(nginx -t 2>&1 || true)"

if ! printf '%s' "$SALIDA_NGINX" | grep -qE "syntax is ok|test is successful"; then
    printf '%s\n' "$SALIDA_NGINX" | tail -5
    restaurar
fi

log "nginx: configuración válida."
systemctl reload nginx

rm -rf "$BACKUP"
log "Vhosts aplicados."

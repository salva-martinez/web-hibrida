# Runtime de Sail propio (PHP 8.5)

Este directorio sustituye al runtime de Sail que viene en
`vendor/laravel/sail/runtimes/8.5/`. El `compose.yaml` construye la imagen
desde aquí:

```yaml
build:
    context: './deploy/sail-8.5'
```

## Por qué no se usa el de Sail

Sail instala PHP desde el PPA de Launchpad (`ondrej/php`). Ese PPA va con
retraso y se cae con facilidad. Con el parche de seguridad de PHP 8.5.12
publicado el 21/10/2026 (CVE-2026-109618 y CVE-2026-109619, RCE sin
autenticar, CVSS v4.0 9.2) el PPA seguía sirviendo 8.5.11 y su `InRelease`
devolvía errores 503.

Aquí se usa **packages.sury.org**, el repositorio que recomienda php.net para
Ubuntu: publica el parche el mismo día.

| | PPA de Launchpad | packages.sury.org |
|---|---|---|
| Publica 8.5.12 el mismo día | ❌ (servía 8.5.11) | ✅ |
| Clave de firma | `0xb8dc7e53946656efbce4c1dd71daeaab4ad4cab6` | `debsuryorg-archive-keyring` (`B188E2B695BD4743`) |
| Estabilidad | errores 503 frecuentes | estable |

Ventaja adicional: el runtime ya no depende de `vendor/`. `composer install`
borra `vendor/` y el Dockerfile de Sail cambia con cada versión de Sail; aquí
el runtime es estable y controlable.

## La capa de PHP se puede refrescar

La instalación de PHP está aislada en su propia capa, detrás de
`ARG PHP_REFRESH`. El despliegue la invalida en cada ejecución:

```bash
docker compose build --build-arg "PHP_REFRESH=$(date +%s)" laravel.test
```

Así, un despliegue normal se trae el último parche de seguridad de PHP sin
reconstruir la imagen entera (ffmpeg, playwright, node…). La capa anterior
(base, composer, node) queda cacheada.

El `Dockerfile` además **falla el build** si la versión instalada es anterior
a la mínima exigida, así que no se puede desplegar por error con un PHP
vulnerable.

## Mitigaciones en `php.ini`

Los avisos de php.net sobre los CVE de octubre de 2026 son explícitos:
*"no hay mitigaciones completas, actualizar es la única opción segura"*. Aun
así, estas medidas reducen la exposición mientras tanto:

| Ajuste | Para qué |
|---|---|
| `memory_limit = -1` | CVE-2026-109618: sin límite finito no hay fallo de memoria controlado por el atacante |
| `post_max_size = 8M`, `upload_max_filesize = 2M` | La aplicación no sube ficheros: reduce la superficie de la petición |
| `session.upload_progress.enabled = 0` | CVE-2026-109619: ruta confirmada |
| `fatal_error_backtraces = 0` | CVE-2026-109619: ruta confirmada en PHP 8.5 |

La frontera de disponibilidad (límite de memoria del contenedor) la pone el
cgroup; `memory_limit = -1` sin ese límite dejaría la memoria del contenedor
sin techo.

## Mantener PHP al día

El `ARG PHP_REFRESH` se refresca en cada despliegue, así que basta con un
`push` para traerse el último 8.5.x. Para forzar un parche concreto sin
desplegar nada más, en el servidor:

```bash
cd /var/www/web-hibrida
docker compose build --build-arg "PHP_REFRESH=$(date +%s)" laravel.test
docker compose up -d laravel.test
docker compose exec -T laravel.test php -v
```

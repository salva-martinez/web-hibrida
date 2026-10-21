# Guía de despliegue — FisioApp en el VPS

Despliegue automático: cada `push` a `main` despliega la aplicación en el VPS
mediante GitHub Actions.

```
push a main  →  GitHub Actions  →  SSH al VPS  →  deploy/deploy.sh
                                                      │
                            ┌─────────────────────────┴──────────────────────────┐
                            │  docker compose up -d                              │
                            │  composer install --no-dev                         │
                            │  npm ci && npm run build                            │
                            │  artisan migrate --force                           │
                            │  artisan config/route/view/event cache             │
                            │  supervisorctl restart fisioapp-worker             │
                            │  health check  http://127.0.0.1:8080/up             │
                            └────────────────────────────────────────────────────┘
```

---

## ⚠️ Nota importante sobre este servidor

`salvamartinez.es` **ya tiene un sitio en marcha** servido por nginx + PHP-FPM
con un certificado de Let's Encrypt. Por eso:

- **No se instala Caddy.** El proxy inverso es el nginx que ya está.
- El sitio raíz (`salvamartinez.es`) **no se toca**.
- La aplicación vive en un **subdominio**: `fisio.salvamartinez.es`.
- Sail publica la app en `127.0.0.1:8080`; nginx hace de proxy y termina el TLS.

---

## 1. Preparar el servidor (una sola vez)

```bash
# En el VPS, con el repositorio ya clonado:
cd /var/www/web-hibrida
git pull
sudo ./deploy/bootstrap-server.sh
```

El script instala Docker, crea el usuario `deploy`, instala Supervisor, añade el
vhost de nginx y emite el certificado TLS.

Variables que acepta (por entorno):

| Variable | Por defecto | Para qué |
|---|---|---|
| `APP_DIR` | `/var/www/web-hibrida` | Dónde vive la aplicación |
| `SUBDOMAIN` | `fisio.salvamartinez.es` | Dominio de la app |
| `APP_PORT` | `8080` | Puerto interno que publica Sail |
| `DEPLOY_USER` | `deploy` | Usuario de despliegue |
| `ACME_EMAIL` | *(vacío)* | Email para Let's Encrypt |

```bash
sudo ACME_EMAIL=tu@email.com SUBDOMAIN=fisio.salvamartinez.es ./deploy/bootstrap-server.sh
```

### DNS

Antes de emitir el certificado, crea el registro DNS:

| Tipo | Nombre | Valor |
|---|---|---|
| A | `fisio` | `217.160.128.72` |

Espera a que propague (`dig +short fisio.salvamartinez.es`) y luego emite el
certificado:

```bash
sudo certbot --nginx -d fisio.salvamartinez.es
```

---

## 2. Configurar la aplicación en el servidor

```bash
cd /var/www/web-hibrida
cp .env.production.example .env
```

Y a rellenar **obligatoriamente**:

| Variable | Valor |
|---|---|
| `APP_KEY` | `php artisan key:generate` |
| `APP_URL` | `https://fisio.salvamartinez.es` |
| `GEMINI_API_KEY` | De https://aistudio.google.com/apikey |
| `MAIL_MAILER` | Un SMTP real (con `log` los emails no se envían) |
| `SESSION_SECURE_COOKIE` | `true` |

> ⚠️ El `.env` **no está en el repositorio** (está en `.gitignore`), así que
> sobrevive a los despliegues. `APP_KEY` debe ser **estable**: si cambia, se
> invalidan las sesiones y los enlaces de verificación de email pendientes.

Primer arranque:

```bash
sudo -u deploy ./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --force
sudo supervisorctl start fisioapp-worker
```

---

## 3. Despliegue automático (GitHub Actions)

En **Settings → Secrets and variables → Actions** del repositorio:

### Secrets (valores privados)

| Secret | Valor |
|---|---|
| `DEPLOY_HOST` | `salvamartinez.es` |
| `DEPLOY_USER` | `deploy` |
| `DEPLOY_PORT` | `22` |
| `DEPLOY_SSH_KEY` | La clave privada de despliegue (con saltos de línea reales) |
| `DEPLOY_KNOWN_HOSTS` | La salida de `ssh-keyscan -H -p 22 salvamartinez.es` |

### Variables

| Variable | Valor |
|---|---|
| `APP_URL` | `https://fisio.salvamartinez.es` (para la comprobación post-deploy) |

La clave de despliegue se genera en el servidor:

```bash
# En el VPS, como el usuario deploy:
ssh-keygen -t ed25519 -N "" -C "github-actions-deploy" -f ~/.ssh/github_actions
cat ~/.ssh/github_actions.pub >> ~/.ssh/authorized_keys
cat ~/.ssh/github_actions          # ← esta clave PRIVADA va a GitHub Secrets
```

> La clave **privada** solo debe existir en GitHub Secrets. Si se filtra, quítala
> de `authorized_keys` y genera otra.

### Primer push

```bash
git add -A && git commit -m "Pipeline de despliegue" && git push origin main
```

El workflow se ejecutará solo. Se puede lanzar a mano desde **Actions → Deploy
producción → Run workflow**.

---

## 4. Verificar

```bash
# Desde tu máquina
curl -I https://fisio.salvamartinez.es/up

# En el VPS
cd /var/www/web-hibrida
./vendor/bin/sail ps                      # contenedores
./vendor/bin/sail logs -f laravel.test    # log de la app
sudo supervisorctl status                 # worker de la cola
sudo tail -f /var/log/fisioapp/worker.log # log del worker
```

### Si algo falla

```bash
# Ver el log del último despliegue
sudo journalctl -u fisioapp -n 100 --no-pager

# Revertir al commit anterior
cd /var/www/web-hibrida
git log --oneline -5
git reset --hard <commit-anterior>
./deploy/deploy.sh
```

---

## 5. Mantenimiento

### Actualizar el worker tras un cambio
`deploy.sh` ya lo hace (supervisor + `queue:restart`). Si toca hacerlo a mano:

```bash
sudo supervisorctl restart fisioapp-worker
```

### Copias de seguridad
MySQL corre en un volumen Docker (`sail-mysql`). Para respaldos:

```bash
cd /var/www/web-hibrida
./vendor/bin/sail exec mysql mysqldump -usail -ppassword fisioterapia \
    | gzip > /var/backups/fisioapp-$(date +%F).sql.gz
```

Conviene añadirlo a un cron diario.

### Logs
- Aplicación: `storage/logs/laravel.log` dentro del contenedor (`LOG_STACK=daily`).
- Worker: `/var/log/fisioapp/worker.log` (rotación por logrotate).
- nginx: `/var/log/nginx/`.

---

## 6. Pendientes para producción

- [ ] **Correo real**: `MAIL_MAILER` debe ser un SMTP de verdad. Sin él, el
      registro de fisioterapeutas no se puede activar (el enlace solo se escribe
      en el log).
- [ ] **Backup automático** de MySQL (cron + rotación).
- [ ] **Monitorización**: aviso si el worker de la cola se cae.
- [ ] **Migración de datos existentes**: la migración `2026_10_21_090200` asigna
      todo al primer fisio. Si hay varios profesionales con datos, hay que
      repartirlos a mano.

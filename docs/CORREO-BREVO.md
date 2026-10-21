# Configurar el envío de correo con Brevo

Brevo (antes Sendinblue) es el proveedor de correo saliente que usa FisioApp.

- **SMTP relay:** `smtp-relay.brevo.com`
- **Puertos:** `587` (TLS), `465` (SSL) o `2525`
- **Límite del plan gratuito:** 300 envíos al día (compartidos entre marketing y
  transaccionales). Para FisioApp sobra de largo: cada fisio nuevo recibe un
  único email.
- **Sin tarjeta de crédito** y sin caducidad.

---

## 1. Crear la cuenta y autenticar el dominio

En Brevo: **Transmitir → remitentes → remitentes de dominio** (o "Domain
authentication").

Añade `salvamartinez.es`. Brevo te dará dos registros DNS para crear en IONOS:

| Tipo | Nombre | Valor |
|---|---|---|
| `TXT` o `CNAME` | `brevo._domainkey` | el valor que te dé Brevo (suele ser un CNAME a `brevo._domainkey.brevo.com`) |
| `TXT` | `selector._domainkey` | la clave DKIM que te muestre Brevo |

Los **CNAME son lo más cómodo en IONOS** (no hay que soporta el valor con
comillas).

> Autenticar el dominio es mejor que verificar un email suelto: no depende de que
> alguien pulse un correo de confirmación y mejora la entregabilidad (SPF + DKIM
> propios del dominio).

## 2. Generar la SMTP key

En Brevo: **Configuración (⚙) → SMTP & API → Generar una nueva clave SMTP**.

> ⚠️ La contraseña SMTP es la **SMTP key**, **no** la API key ni la contraseña de
> tu cuenta de Brevo. Es el error más habitual.

Copia la clave: solo se muestra una vez.

## 3. Configurar el servidor

En `/var/www/web-hibrida/.env`:

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=tu-login-de-brevo
MAIL_PASSWORD=la-smtp-key
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS="no-reply@salvamartinez.es"
MAIL_FROM_NAME="FisioApp"
```

Después:

```bash
./vendor/bin/sail artisan config:clear
```

## 4. Comprobar que funciona

```bash
./vendor/bin/sail artisan mail:probar
```

Muestra la configuración detectada y envía un email de prueba. Si algo falla,
dice por qué (remitente sin verificar, SMTP key equivocada, DKIM pendiente…).

Para ver solo la configuración, sin enviar nada:

```bash
./vendor/bin/sail artisan mail:probar --mostrar
```

---

## Comprobación final

1. `mail:probar` dice "✅ Enviado".
2. El email llega (revisa también spam).
3. Registrar un fisio nuevo en `https://fisio.salvamartinez.es/register` y
   comprobar que recibe el enlace de verificación.
4. Al pulsar el enlace, la cuenta queda activada y puede entrar al panel.

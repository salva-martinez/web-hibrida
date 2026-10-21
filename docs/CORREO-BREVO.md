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

### 1.1. La cuenta de Brevo

Registrate en Brevo con el email donde quieras recibir la confirmacion:

- **`salvamartinez.es`** -> llega a tu buzon de IONOS (webmail de IONOS). Es la
  opcion ideal: el login SMTP sera ese mismo email.
- **Tu email personal** -> llega ahi. El remitente de FisioApp seguira siendo
  `no-reply@salvamartinez.es` gracias a la autenticacion de dominio.

### 1.2. Autenticar el dominio como remitente

En Brevo: **Transmitir -> remitentes -> remitentes de dominio** (o "Domain
authentication").

Anade `salvamartinez.es`. Brevo te dara dos registros DNS para crear en IONOS:

| Tipo | Nombre | Valor |
|---|---|---|
| `CNAME` | `brevo._domainkey` | el valor que te de Brevo (suele ser un CNAME a `brevo._domainkey.brevo.com`) |
| `TXT` | `selector._domainkey` | la clave DKIM que te muestre Brevo |

Los **CNAME son lo mas comodo en IONOS** (no hay que soporta el valor con
comillas).

### 1.3. Actualizar el SPF (no te lo saltes)

**Solo se permite un registro SPF por dominio**, asi que hay que **combinar** el
de Brevo con el que ya tienes, **no anadir un segundo registro**.

El SPF actual de `salvamartinez.es` es:

```
v=spf1 include:_spf-eu.ionos.com ~all
```

Brevo no esta autorizado -> los correos de verificacion **fallarian la
validacion SPF** y Gmail u Outlook los tirarian a spam (o los rechazarian
directamente).

**Debe quedar asi** (usa el `include` exacto que te de Brevo):

```
v=spf1 include:_spf-eu.ionos.com include:spf.brevo.com ~all
```

> Si el panel de IONOS te deja tener varios registros TXT, **borra el SPF
> antiguo** y deja solo el combinado. Dos registros SPF invalidan la comprobacion
> y el correo cae en spam.

### 1.4. Verificar el remitente

Si Brevo te pide verificar `no-reply@salvamartinez.es` por email, el mensaje
llega al **webmail de IONOS** (tu panel -> *Correo electronico* -> *Webmail*).
Ahi pinchas el enlace de confirmacion.

> Autenticar el dominio es mejor que verificar un email suelto: no depende de
> que alguien pulse un correo de confirmacion y mejora la entregabilidad (SPF +
> DKIM propios del dominio).

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

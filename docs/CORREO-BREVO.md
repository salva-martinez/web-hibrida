# Configurar el envío de correo con Brevo

Brevo (antes Sendinblue) es el proveedor de correo saliente que usa FisioApp.

- **SMTP relay:** `smtp-relay.brevo.com`
- **Puertos:** `587` (TLS), `465` (SSL) o `2525`
- **Límite del plan gratuito:** 300 envíos al día (compartidos entre marketing y
  transaccionales). Para FisioApp sobra de largo: cada fisio nuevo recibe un
  único email.
- **Sin tarjeta de crédito** y sin caducidad.
- Los correos del plan gratuito llevan la pegatina **"Sent with Brevo"**. Para
  quitarla hay que contratar el add-on de branding.

---

## Estado actual (ya configurado y funcionando)

La configuración está **hecha y probada**: `mail:probar` envía correctamente.

| Variable | Valor |
|---|---|
| `MAIL_MAILER` | `smtp` |
| `MAIL_HOST` | `smtp-relay.brevo.com` |
| `MAIL_PORT` | `587` |
| `MAIL_USERNAME` | `bf58c2001@smtp-brevo.com` (el identificador de cuenta que da Brevo, **no** tu email) |
| `MAIL_PASSWORD` | la SMTP key (⚠️ solo se muestra una vez al generarla) |
| `MAIL_SCHEME` | `null` |
| `MAIL_FROM_ADDRESS` | `salva@salvamartinez.es` |
| `MAIL_FROM_NAME` | `FisioApp` |

> ⚠️ `MAIL_SCHEME` **no es `tls`**. Laravel 12 solo acepta `smtp`, `smtps` o
> `null` (que negocia STARTTLS automáticamente). Con `tls` falla con
> `UnsupportedSchemeException`.

### Rotar la SMTP key

Brevo permite generar varias SMTP keys y revocar las antiguas. Rotarla es
trivial:

1. En Brevo: ⚙️ → *SMTP & API* → *Generar una nueva clave SMTP*.
2. En el servidor, cambiar **solo** la línea `MAIL_PASSWORD` del `.env`:

   ```bash
   cd /var/www/web-hibrida
   sed -i "s|^MAIL_PASSWORD=.*|MAIL_PASSWORD=<la clave nueva>|" .env
   docker compose exec -T laravel.test php artisan config:clear
   docker compose exec -T laravel.test php artisan mail:probar
   ```

3. Revocar la clave vieja desde el panel de Brevo.

> La clave **no** está en el repositorio (ni debe estarlo): vive solo en el
> `.env` del servidor y en el de tu máquina.

---

## Los registros DNS

Brevo pide estos registros. **Los cuatro ya están propagados** en
`salvamartinez.es`:

| Tipo | Nombre | Valor | Estado |
|---|---|---|---|
| CNAME | `brevo1._domainkey` | `b1.salvamartinez-es.dkim.brevo.com` | ✅ |
| CNAME | `brevo2._domainkey` | `b2.salvamartinez-es.dkim.brevo.com` | ✅ |
| CNAME | `em` | `em-salvamartinez-es.brand.brevosend.com` | ✅ (branding) |
| CNAME | `r.em` | `em-salvamartinez-es.r.brand.brevosend.com` | ✅ (branding) |
| CNAME | `img.em` | `em-salvamartinez-es.img.brand.brevosend.com` | ✅ (branding) |

### El SPF: **no se toca**

Brevo **no pide registro SPF** en su flujo de autenticación de dominio: el DKIM
va alineado al dominio (`brevo1._domainkey` sobre `salvamartinez.es`), y eso
hace que el mensaje **cumpla DMARC aunque el SPF no mentione a Brevo**.

El SPF actual se queda como está:

```
v=spf1 include:_spf-eu.ionos.com ~all
```

> Añadir `include:spf.brevo.com` a mano es opcional y tiene un riesgo: si el
> include no es el correcto, se rompe el SPF de IONOS y dejarían de llegar los
> correos del buzón. Con el DKIM alineado, el envío funciona sin tocarlo.

### El DMARC: el aviso de Brevo es cosmético

Brevo muestra un aviso rojo en el DMARC porque su valor esperado es

```
v=DMARC1; p=none; rua=mailto:rua@dmarc.brevo.com
```

y el que hay es el de IONOS (`v=DMARC1; p=none;`, sin la etiqueta `rua`).

**No hay que cambiar nada.** El registro `_dmarc` es un **CNAME a `dmarc.ionos.es`**, gestionado
por IONOS. Si consultas por DNS un CNAME, el resolvedor sigue la cadena y
devuelve el TXT del destino: por eso aparece `v=DMARC1; p=none;` aunque en el
panel de IONOS solo se ve el CNAME.

El aviso no afecta al envío:

- El DMARC de IONOS es `p=none` → solo monitoriza, no rechaza nada.
- El DKIM de Brevo **va alineado al dominio** (`brevo1._domainkey` sobre
  `salvamartinez.es`), así que el mensaje **cumple DMARC** aunque el SPF no
  mencione a Brevo.

Los correos llegan con normalidad. Si en algún momento quisieras silenciar el
aviso, habría que pedir a IONOS que añadiera la etiqueta `rua` a su DMARC
gestionado.

---

## Cómo comprobar que funciona

```bash
cd /var/www/web-hibrida

# Ver la configuración detectada (sin enviar nada)
./vendor/bin/sail artisan mail:probar --mostrar

# Enviar un email de prueba de verdad
./vendor/bin/sail artisan mail:probar
```

Si algo falla, el comando indica la causa: remitente sin verificar, SMTP key
equivocada, DKIM pendiente o un `MAIL_SCHEME` inválido.

---

## Comprobación final

1. `mail:probar` dice "✅ Enviado".
2. El email llega al buzón de IONOS (revisa también spam).
3. Registrar un fisio nuevo en `https://fisio.salvamartinez.es/register` y
   comprobar que recibe el enlace de verificación.
4. Al pulsar el enlace, la cuenta queda activada y puede entrar al panel.

### El enlace de verificación funciona sin sesión

Esto es importante, porque **la mayoría de la gente abre el email en el móvil**,
donde no tiene sesión iniciada en el navegador. Por eso el enlace firmado
verifica la cuenta y, solo si no hay nadie dentro, además inicia sesión.

Para comprobarlo sin depender del email (es decir, para no tener que esperar a
que llegue):

```bash
# En el servidor: generar el enlace firmado del usuario recién creado
docker compose exec -T laravel.test php artisan tinker --execute="
\$u = App\Models\User::where('email', 'tu@email.com')->first();
echo Illuminate\Support\Facades\URL::temporarySignedRoute(
    'verification.verify', now()->addHours(2),
    ['id' => \$u->id, 'hash' => sha1(\$u->email)]
);"

# Y abrirlo con curl, SIN ninguna cookie: debe redirigir al panel y activar la cuenta
curl -s -o /dev/null -w "%{http_code} → %{redirect_url}\n" "<el enlace>"
```

Lo esperado es `302 → https://fisio.salvamartinez.es/admin/dashboard`.

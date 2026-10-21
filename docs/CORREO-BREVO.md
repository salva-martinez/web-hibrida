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
| `MAIL_FROM_ADDRESS` | `no-reply@salvamartinez.es` |
| `MAIL_FROM_NAME` | `FisioApp` |

> ⚠️ `MAIL_SCHEME` **no es `tls`**. Laravel 12 solo acepta `smtp`, `smtps` o
> `null` (que negocia STARTTLS automáticamente). Con `tls` falla con
> `UnsupportedSchemeException`.

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

### El DMARC: hay que borrar un duplicado

Ahora mismo `_dmarc.salvamartinez.es` tiene **dos registros a la vez**:

| Tipo | Valor | Quién lo puso |
|---|---|---|
| CNAME | `dmarc.ionos.es` | IONOS (gestionado por el hosting) |
| TXT | `v=DMARC1; p=none;` | manual |

Con dos registros, algunos validadores toman uno y otros otro → el resultado es
inconsistente. Por eso Brevo muestra el aviso en rojo.

**Solución: borrar el TXT manual** y dejar solo el CNAME de IONOS.

- El DMARC de IONOS es `p=none` (solo monitoriza, no rechaza nada) ✅
- El DKIM de Brevo cumple el DMARC igualmente ✅
- Los correos de verificación llegan igual ✅

El aviso de Brevo sobre el `rua` es **cosmético**: solo afecta a los informes
de DMARC, no al envío.

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

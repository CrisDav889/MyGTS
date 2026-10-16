# Guía de despliegue: MyGTS en InfinityFree (gratis) con dominio mygts.tech

> Requisitos del proyecto: **PHP 8.0+**, **MySQL 8+/MariaDB 10.5+**, extensión **cURL**.
> InfinityFree ofrece **PHP 8.3** y **MariaDB 10.4 / MySQL 5.6** según el servidor. Por eso
> se incluye un esquema compatible (`esquema_completo_infinityfree.sql`) que no usa
> `DEFAULT (CURRENT_DATE)` (sintaxis solo de MySQL 8.0.13+/MariaDB 10.2.1+).

---

## Fase 1 — Crear la cuenta y el hosting

1. Entra a <https://www.infinityfree.com> y crea una cuenta **gratuita** (no pide tarjeta).
2. Ve a **Control Panel** (VistaPanel) → **Accounts** → **Create Account**.
3. Elige un nombre de cuenta (ej. `mygts`) y un **subdominio provisional** (ej. `mygts.rf.gd` o `.great-site.net`). Puedes empezar con el subdominio; el dominio propio se engancha después.
4. Anota el **nombre de usuario** del hosting (suele ser `ifastnet_xxxxxx` o el que elijas) y la contraseña.

## Fase 2 — Enganchar el dominio mygts.tech

5. En VistaPanel → **Domains** → **Add-on Domains** → añade `mygts.tech`.
6. En el panel de **Tech Domains** (tu registrador), cambia los **nameservers** del dominio a:
   - `NS1.BYET.org`
   - `NS2.BYET.org`
7. Espera la propagación DNS (puede tardar de 1 a 48 horas). Puedes comprobarlo con <https://dnschecker.org>.
8. Una vez propagado, en VistaPanel → **SSL** (o **Free SSL**) genera el certificado Let's Encrypt para `mygts.tech` (actívalo también para `www.mygts.tech` si lo configuras). Renovar automáticamente.

## Fase 3 — Crear la base de datos

9. En VistaPanel → **MySQL Databases** → **Create Database**.
   - El nombre de la BD **debe empezar con tu prefijo** de usuario (ej. `if0_42562019_mygts`). VistaPanel lo sugiere automáticamente.
10. Anota: **nombre de BD**, **usuario**, **contraseña** y el **host** (normalmente `sqlXXX.infinityfree.com` o `localhost`). Estos datos irán al `.env`.
11. Abre **phpMyAdmin** (botón junto a la BD) → pestaña **Import** → sube:
    `database/esquema_completo_infinityfree_db.sql`
    - ⚠️ Usa esta variante (sin `CREATE DATABASE` ni `USE`). La otra (`..._infinityfree.sql`) crea la BD `ExpoCarrera`, que tu usuario no tiene permitido operar y dará error `#1044 Acceso denegado`. phpMyAdmin importa dentro de la BD ya seleccionada.
12. (Opcional) Si no viniera el admin, importa también `database/crear_admin_produccion.sql`
    (crea `admin@mygts.com` / `admin123` solo si no existe).

> **Importante**: el `.env` local usa `DB_USER=root` y `DB_PASS=` (vacío) y apunta a
> `localhost`. **No subas ese `.env`**. Crea uno nuevo en el servidor con los datos del paso 10.

## Fase 4 — Subir los archivos

13. Entra al File Manager de VistaPanel (o usa FTP: host `ftpupload.net`, usuario y contraseña del hosting).
14. Sube el contenido del proyecto a la carpeta `htdocs/` (la raíz web). Debe quedar así:
    - `htdocs/index.php`, `htdocs/login.php`, `htdocs/admin.php` ...
    - `htdocs/api/`, `htdocs/config/`, `htdocs/css/`, `htdocs/js/`, `htdocs/database/`
    - `htdocs/.htaccess` y `htdocs/.env`
15. **NO subas**:
    - `backups/` (ocupa espacio y está bloqueada por `.htaccess` de todos modos)
    - los `.rar` / `.zip` de `backups/`
    - tu `.env` local con credenciales de XAMPP (ver paso 17)
16. Crea el archivo **`.env`** dentro de `htdocs/` con el contenido real del servidor:

```
DB_HOST=localhost
DB_NAME=if0_42562019_mygts
DB_USER=if0_42562019
DB_PASS=TU_CONTRASENA_BD
DB_CHARSET=utf8mb4
OPENROUTER_API_KEY=sk-or-v1-TU_CLAVE_REAL
```

> `DB_NAME` debe ser **exactamente el nombre de la BD que creaste en VistaPanel** (con tu prefijo).
> `DB_HOST` suele ser `localhost` en InfinityFree, pero confirma el valor exacto en el paso 10.
> `OPENROUTER_API_KEY` es la misma clave que tienes localmente (la de `.env` de tu máquina).

17. En VistaPanel → **PHP settings** (o al crear la cuenta) verifica que:
    - La versión de PHP sea **8.x** (8.3 recomendado).
    - La extensión **cURL** esté habilitada (la necesitan `api/asistente.php`).
    - `session`, `PDO` y `pdo_mysql` estén activos (vienen activados por defecto).

## Fase 5 — Configurar y probar

18. Visita `https://mygts.tech` y prueba:
    - Mapa carga con paradas, rutas y puntos de seguridad.
    - Búsqueda de paradas y ruteo (usa OSRM público, sin coste).
    - Registro / inicio de sesión.
    - Panel admin en `https://mygts.tech/admin.php` (admin@mygts.com / admin123).
    - Chat IA Gity (requiere cURL hacia OpenRouter).
19. Verificaciones de seguridad:
    - `https://mygts.tech/.env` debe responder **403 Forbidden** (lo bloquea el `.htaccess`).
    - `https://mygts.tech/database/` también debe dar 403.

## Fase 6 — Límites y consejos del plan gratis

- **~30.000 hits al día** (para un proyecto demo es suficiente).
- **~50 MB por base de datos** (tu dataset de rutas/paradas es pequeño, sin problema).
- Sin SSH, sin cron, sin correo saliente.
- Si algún día superas los límites, migra a Oracle Cloud Always Free (VPS) o a un hosting de pago.

---

## Notas del código preparadas para producción

- `api/asistente.php` ya envía `HTTP-Referer: https://mygts.tech` (antes `localhost`).
- `api/auth/registro.php`, `api/mis_rutas.php` y `api/admin/puntos_seguridad.php`
  ahora insertan `fecha` / `fecha_registro` con `CURRENT_DATE` explícito, de modo que
  funcionan aunque la columna no tenga default en MySQL 5.6.
- El `.htaccess` bloquea `.env`, `database/`, `backups/` y los archivos de respaldo.

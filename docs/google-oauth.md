# Google OAuth para Google Business Profile

Esta integración crea un flujo administrativo OAuth 2.0 para obtener, de forma segura, el `refresh_token` que se utilizará posteriormente al consultar cuentas, ubicaciones y reseñas de Google Business Profile.

No muestra reseñas ni modifica el frontend del sitio.

## Variables de entorno

Obligatorias para OAuth:

- `GOOGLE_CLIENT_ID`: identificador del cliente OAuth de Google Cloud.
- `GOOGLE_CLIENT_SECRET`: secreto del cliente OAuth.
- `GOOGLE_REDIRECT_URI`: debe ser exactamente `https://www.zentdigital.cl/google/oauth/callback/`.
- `GOOGLE_OAUTH_ADMIN_USER`: usuario administrativo requerido para abrir el endpoint de conexión.
- `GOOGLE_OAUTH_ADMIN_PASSWORD_HASH`: hash generado con `password_hash`; no guardar la contraseña en texto plano.
- `GOOGLE_OAUTH_TOKEN_FILE`: ruta absoluta de un archivo privado, fuera del directorio público del sitio. Su directorio debe existir y permitir escritura al proceso PHP.

Después de completar la autorización:

- `GOOGLE_REFRESH_TOKEN`: copiar aquí el `refresh_token` recuperado desde el archivo privado y luego eliminar dicho archivo temporal.

Las variables deben configurarse desde el panel del hosting, la configuración privada de PHP-FPM/Apache o el mecanismo de secretos del servidor. No deben guardarse en `.env`, `.htaccess` ni otros archivos versionados dentro de `public_html`.

Para generar el hash administrativo con PHP:

```bash
php -r "echo password_hash('UNA_CLAVE_ADMINISTRATIVA_LARGA', PASSWORD_DEFAULT), PHP_EOL;"
```

Solo el hash resultante se configura como `GOOGLE_OAUTH_ADMIN_PASSWORD_HASH`.

## Rutas

- Inicio protegido: `https://www.zentdigital.cl/google/oauth/connect/`
- Callback registrado: `https://www.zentdigital.cl/google/oauth/callback/`

## Procedimiento de autorización

1. Configurar todas las variables anteriores en el entorno del servidor.
2. Crear fuera de `public_html` el directorio indicado por `GOOGLE_OAUTH_TOKEN_FILE` y restringir sus permisos al usuario que ejecuta PHP.
3. Abrir `/google/oauth/connect/` mediante HTTPS.
4. Introducir las credenciales administrativas configuradas cuando el navegador las solicite.
5. Autorizar la cuenta administradora del Perfil de Negocio en Google.
6. Google redirigirá al callback. La página solo indicará si la autorización fue exitosa, si se recibió un refresh token, el scope concedido y la fecha/hora.
7. Acceder al archivo privado directamente desde el servidor, copiar el valor `refresh_token` a `GOOGLE_REFRESH_TOKEN` y eliminar el archivo temporal.

Si Google no devuelve un refresh token, revocar previamente el acceso de la aplicación desde la cuenta Google y repetir el proceso. El endpoint solicita `access_type=offline` y `prompt=consent`.

## Seguridad implementada

- HTTPS obligatorio.
- Inicio de autorización protegido con HTTP Basic y contraseña verificada mediante hash.
- `state` aleatorio de 256 bits, guardado como hash en sesión, de un solo uso y con duración máxima de 10 minutos.
- Cookies de sesión `Secure`, `HttpOnly` y `SameSite=Lax`.
- Scope limitado a `https://www.googleapis.com/auth/business.manage`.
- Intercambio de tokens mediante cURL con validación TLS, timeouts, sin redirecciones y únicamente por HTTPS.
- Validación del código HTTP y del JSON devuelto por Google.
- Tokens y secretos nunca se imprimen ni se incluyen en mensajes de error.
- El access token no se persiste.
- El refresh token solo se escribe en la ruta privada configurada, con permisos restrictivos cuando el sistema operativo lo permite.
- Respuestas administrativas con `Cache-Control: no-store` y política CSP restrictiva.

## Preparación para el siguiente paso

Una vez configurado `GOOGLE_REFRESH_TOKEN`, el backend podrá renovar access tokens y consumir, en una etapa posterior, las APIs oficiales para:

1. listar cuentas y obtener `account_id`;
2. listar ubicaciones y obtener `location_id`;
3. consultar las reseñas de la ubicación seleccionada.

Estas consultas y la presentación de reseñas no forman parte de esta implementación.

## Archivos

Creación:

- `src/GoogleOAuth.php`
- `google/oauth/connect/index.php`
- `google/oauth/callback/index.php`
- `docs/google-oauth.md`
- `.gitignore`

No se modifica ningún archivo visual, CSS o JavaScript.

## Validaciones realizadas

- Sintaxis comprobada con `php -l` en todos los archivos PHP del proyecto.
- Disponibilidad de las extensiones PHP `curl`, `json`, `openssl` y `session`.
- URI de callback comparada de forma exacta con la registrada en Google Cloud.
- URL de autorización validada con `response_type=code`, scope `business.manage`, `access_type=offline` y `prompt=consent`.
- Generación aleatoria de `state`, expiración a los 10 minutos, consumo único y rechazo de reutilización.
- Aceptación y rechazo de credenciales administrativas sin revelar datos internos.
- Manejo seguro de fallos del token endpoint usando valores ficticios, sin imprimir el código, secretos ni tokens.
- Escritura atómica del refresh token en un archivo temporal de prueba y validación de su estructura JSON; el archivo se eliminó al finalizar.
- Búsqueda de patrones de credenciales OAuth de Google en el árbol de trabajo.
- Confirmación mediante Git de que no se modificaron páginas, secciones visuales, CSS ni JavaScript.


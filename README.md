# Laravel 12 Secure API Starter

Este proyecto es un punto de partida para crear APIs seguras con Laravel 12 (branch `master` del framework) incluyendo autenticación con Laravel Sanctum, soporte para OTP por correo electrónico y endpoints listos para integrar passkeys/WebAuthn.

## Requisitos

- PHP 8.2+
- Composer 2.6+
- Extensiones PHP: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath
- Servidor de base de datos MySQL/MariaDB o PostgreSQL
- Redis para caché/sesiones (opcional pero recomendado)

## Instalación

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
```

Para desarrollo local se recomienda utilizar [Laravel Sail](https://laravel.com/docs/sail) o Docker Compose. También puedes configurar un entorno con Valet/Herd.

## Autenticación y seguridad

- **Sanctum** maneja los tokens de acceso personales para la API.
- **OTP**: se genera automáticamente al registrar cuentas nuevas o solicitar un inicio de sesión. Por defecto se envía vía correo electrónico utilizando el `Mail` configurado. Ajusta `OTP_DRIVER` y `OTP_TTL` en tu `.env` para controlar el canal y la caducidad (en segundos).
- **Passkeys/WebAuthn**: Los endpoints expuestos generan retos (`challenge`) para registro y autenticación. Implementan persistencia y validaciones básicas de contador. Para verificación criptográfica debes integrar un proveedor WebAuthn en el cliente y enviar la información de la respuesta. Recomendamos instalar `spomky-labs/webauthn-lib` y completar la validación en el servicio `PasskeyService`. Configura los valores `PASSKEY_RELYING_PARTY_*` y los parámetros de reto `PASSKEY_CHALLENGE_*` en tu `.env`.
- **Rate limiting**: las rutas API utilizan el throttle `api` de Laravel (por defecto 60 peticiones/minuto).

## Rutas principales

| Método | Ruta | Descripción |
| --- | --- | --- |
| POST | `/api/auth/register` | Registro de usuarios con contraseña y OTP opcional |
| POST | `/api/auth/login` | Inicio de sesión con contraseña y OTP |
| POST | `/api/auth/otp/request` | Generar un nuevo OTP (requiere token) |
| POST | `/api/auth/otp/verify` | Verificar OTP y obtener token |
| POST | `/api/auth/passkeys/options` | Obtener opciones WebAuthn para registrar passkey |
| POST | `/api/auth/passkeys/register` | Registrar passkey (requiere token) |
| POST | `/api/auth/passkeys/authenticate/options` | Obtener challenge para autenticarse con passkey |
| POST | `/api/auth/passkeys/authenticate` | Validar respuesta de passkey y emitir token |
| GET | `/api/me` | Perfil del usuario autenticado |

## OTP

El servicio `App\Services\OtpService` genera códigos de 6 dígitos válidos por defecto durante 5 minutos (configurable mediante `services.otp.ttl`). Se notifica al usuario via `App\Notifications\OtpCodeNotification`.

## Passkeys

El `PasskeyService` persiste credenciales y valida retos. Para habilitar verificación criptográfica completa:

1. Instala `spomky-labs/webauthn-lib` (`composer require spomky-labs/webauthn-lib`).
2. Completa la lógica dentro del servicio reemplazando la validación mínima por el flujo del servidor WebAuthn acorde a tu implementación.
3. Ajusta el frontend para enviar `attestation` y `assertion` en formato WebAuthn.

## Pruebas

```bash
php artisan test
```

## Scripts útiles

- `php artisan migrate:fresh --seed`
- `php artisan queue:work`

## Roadmap sugerido

- Añadir jobs en cola para envío de correo OTP.
- Integrar un servicio SMS para OTP alternativo.
- Completar validación WebAuthn usando la librería recomendada.
- Añadir documentación OpenAPI/Swagger.
- Configurar CI/CD con GitHub Actions.


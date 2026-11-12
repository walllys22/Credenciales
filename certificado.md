# Memoria técnica de Credencial

Actualizada: 12 de noviembre de 2026 (America/La_Paz).

## Alcance y referencia

Sistema de control de personas, países, grados, eventos, inscripciones, pagos,
credenciales y certificados. PHP, Laravel 12, Blade, Bootstrap y JavaScript local.
`E:/Desarrollo/swak/swak.md` se consultó como ejemplo de autenticación, permisos,
gestión de usuarios y documentación. Sus reglas y módulos pertenecen a SWAK;
no se trasladaron sus instrucciones operativas, datos ni jerarquías a este proyecto.
Para futuros cambios, revisar esta memoria y actualizar las secciones afectadas
y el historial con las verificaciones realmente ejecutadas.

## Autenticación y usuarios

- Inicio de sesión por **nombre de usuario**, no correo: GET/POST `/login`.
- POST `/logout` cierra la sesión, la invalida y regenera el token CSRF.
- AuthController valida usuario activo, regenera la sesión al ingresar y limita
  a cinco intentos fallidos por combinación de usuario/IP durante 60 segundos.
- Contraseñas con hash mediante el cast `hashed` del modelo User. Nunca se
  muestran contraseñas existentes ni se recuperan en formularios con errores.
- Roles fijos: `superadmin` e `impresiones`. No hay catálogo dinámico de roles.
- `users`: campos anteriores name/email/password/remember_token más username
  nullable/único (compatibilidad con registros anteriores), role por defecto
  impresiones y active booleano por defecto true. Las nuevas altas requieren username.
- El correo es obligatorio y único para gestionar usuarios; las cuentas iniciales
  usan direcciones locales `superadmin@credencial.local` e `impresiones@credencial.local`.
  No hay recuperación de contraseña por correo implementada en esta tarea.
- Migración: `2026_11_12_000001_add_user_access_fields.php`; conserva datos existentes.

### Usuarios iniciales

`database/seeders/UsuariosSeeder.php` crea las cuentas solicitadas:

| Usuario | Contraseña inicial | Rol | Entrada |
| --- | --- | --- | --- |
| superadmin | password1234 | superadmin | /dashboard |
| impresiones | imprime1234 | impresiones | /inscripciones |

El seeder usa firstOrCreate por username: repetirlo no reinicia contraseñas,
roles ni estado de cuentas existentes. DatabaseSeeder también lo invoca y
mantiene la creación del grado Sin grado. Para cambiar una contraseña existente,
usar Usuarios; el campo vacío al editar conserva el hash actual.

### Permisos y navegación

`routes/web.php` agrupa la gestión con `auth` y
`app/Http/Middleware/ProjectAccess.php`. Se comprueba active y role en cada petición.
Ocultar botones no reemplaza la autorización del servidor.

| Rol | Acceso |
| --- | --- |
| superadmin | Todos los módulos y operaciones, incluido Usuarios y limpiar caché |
| impresiones | Listado y filtros de inscritos; impresión individual de credencial y certificado; imágenes necesarias |

Impresiones ve únicamente los iconos de credencial y certificado por fila.
No ve pagos, edición, eliminación, nueva inscripción, impresión del reporte
general ni las columnas de tarifa/pagado/saldo. El menú muestra Impresiones
y Cerrar sesión. No puede consultar ni modificar otros módulos por URL directa.
Los filtros y la paginación del listado siguen disponibles.

Rutas GET permitidas a impresiones, por nombre:
`inscripciones.index`, `inscripciones.credencial`, `inscripciones.certificado`,
`personas.foto` y `eventos.imagen` (recursos gráficos de la impresión).
Los métodos de escritura de gestión están bloqueados para este rol.
La raíz y el acceso de un usuario ya autenticado redirigen según su rol.

Usuarios ofrece listado paginado a 25, alta, edición, contraseña opcional,
activar/desactivar y eliminar. Sólo superadmin puede operar este módulo.
Se impide eliminar la cuenta propia, quitarse el acceso y dejar sin un
superadmin activo; las operaciones críticas usan transacción y bloqueo.

## Certificados

- GET `/inscripciones/{inscripcion}/certificado`, nombre `inscripciones.certificado`.
- `CertificadoController::show` carga persona/grado/evento de la inscripción.
- Si el grado normalizado es DAN, antepone `Sensei ` al nombre completo.
- Vista: `resources/views/inscripciones/vercert.blade.php`.
- Plantilla: `public/images/Certificado.png`. Fuente: `public/fonts/agnesa-kyoto.ttf`.
- CSS: `public/css/certificados.css`. Nombre en top 47%, left 21%, width 58%;
  tamaño base 22 pt, alineado a la izquierda. Vista previa reducida en pantalla.
- Impresión: carta horizontal, margen CSS 0, ancho 279,4 mm y proporción original.
  Márgenes físicos y calidad final dependen del controlador de impresora.
- `public/js/certificados.js` espera fuentes e imágenes antes de window.print().
- No se genera un PDF persistido ni se registra entrega al imprimir.

## Credenciales, ficha y QR público

- GET `/inscripciones/{inscripcion}/credencial` utiliza la inscripción exacta.
- `CredencialController` genera QR SVG con bacon-qr-code y fondo transparente.
- Vista `resources/views/personas/vercred.blade.php`, parcial `personas/ficha.blade.php`.
- CSS `public/css/credenciales.css`; JS `public/js/credenciales.js`.
- Cada cara mide 62 × 100 mm. Impresión en A4 vertical con 8 mm de margen;
  anverso y reverso se imprimen, ficha se oculta mediante media print.
- La ficha también se usa en la verificación pública. Evitar cambios que rompan
  sus fotos firmadas o hagan depender esa pantalla de la sesión administrativa.
- GET `/personas/{persona}/credencial` sigue siendo una opción de superadmin.
- El QR apunta a `credenciales.verificar`: verificación pública con firma válida.
  Las rutas `credenciales.foto` y `credenciales.imagen` también requieren firma.
  Estas tres rutas quedan fuera del grupo auth para conservar lectura desde teléfonos.
- `CredencialPublicUrl::origin()` usa CREDENCIAL_PUBLIC_URL o el estado privado
  `storage/app/private/credencial-tunnel/active.json`; valida el origen HTTP/HTTPS.
- Las fotos y las imágenes de evento privadas se sirven mediante controladores.
- Las respuestas de gestión y documentos llevan Cache-Control private, no-store.

## Imágenes actualizadas por el operador

Se conservaron los archivos recibidos en public/images, modificados el 12/11/2026:
`anverso.png`, `reverso.png` y `Ficha1.png`. No se regeneraron ni sobrescribieron.
Las vistas ahora agregan `?v=filemtime(...)` a sus URLs: al reemplazar un archivo,
el navegador solicita su versión nueva, tanto en credencial como en ficha pública.
Las tres imágenes fueron inspeccionadas como archivos durante esta tarea.
La posición de campos/fotos/QR está en CSS, separada del arte raster; si cambia
la ubicación de espacios reservados, revisar impresión y alineación antes de entregar.

## Costos por grado

`Evento` relaciona grados con pivot costo (Bs) y costo_usd; tipo_cambio pertenece
al evento. El formulario captura dólares y calcula bolivianos con BigDecimal.
El listado de eventos muestra montos en $us y el tipo de cambio debajo.
Importes y tipo de cambio no registrados se señalan explícitamente; no se
deduce una cotización de mercado ni se reconvierten datos históricos por suposición.

## Comandos y pruebas

Instalación/actualización sin borrar datos:

```powershell
php artisan migrate --force
php artisan db:seed --class=UsuariosSeeder --force
php artisan view:clear
php artisan test --compact
```

Las pruebas usan SQLite en memoria según phpunit.xml. No usar migrate:fresh
contra la base operativa. `tests/AuthenticatedTestCase.php` proporciona un usuario
superadmin de prueba para las pruebas funcionales previas. AuthenticationTest
hereda directamente de TestCase y comprueba invitados, autenticación y roles reales.

Verificaciones del 12/11/2026: migración y UsuariosSeeder aplicados en la base
configurada; suite completa **67 pruebas / 724 aserciones aprobadas**. Incluye
credenciales, certificados, pagos y seis pruebas nuevas de autenticación/permisos:
invitados, QR firmado público, seeder repetible/hashes, ambos accesos/cierre,
credenciales incorrectas/inactividad/límite, restricciones de impresiones y CRUD
de usuarios con protección de la cuenta propia. Se corrigió un cierre condicional
Blade detectado en la primera ejecución dirigida antes de la suite completa.
Pint aplicado a los archivos PHP principales de esta tarea. Compilación de vistas
y caché de rutas correctas; ambas cachés se limpiaron al terminar. Las tres pruebas
JavaScript de costos aprobaron. En la base operativa se comprobó que ambos usuarios
están activos, con rol correcto y hash compatible con la contraseña solicitada.
No se hizo prueba
física de impresora ni revisión visual interactiva del navegador.

## Historial

| Fecha | Cambio | Archivos principales |
| --- | --- | --- |
| 2026-11-12 | Listado de costos en dólares con tipo de cambio debajo | resources/views/eventos/index.blade.php |
| 2026-11-12 | Inicio/cierre de sesión, dos roles, gestión de usuarios, seeder y autorización en servidor/interfaz | AuthController, UsuarioController, ProjectAccess, User, routes/web.php, bootstrap/app.php, migración de acceso, UsuariosSeeder, vistas auth/usuarios/layouts/inscripciones y pruebas |
| 2026-11-12 | URLs versionadas para arte modificado por operador y memoria técnica | personas/vercred.blade.php, personas/ficha.blade.php, certificado.md |

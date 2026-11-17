# Memoria técnica de Credencial

Actualizada: 12 de noviembre de 2026 (America/La_Paz).

## Actualización obligatoria de esta memoria

Por instrucción expresa del usuario, **cada cambio en el proyecto debe actualizar
este archivo dentro de la misma tarea, antes de darla por terminada**.
La regla también está registrada en `AGENTS.md` para futuras sesiones.
Cada actualización debe corregir las secciones afectadas y registrar fecha,
cambio, archivos principales, verificaciones y resultados en el historial.
Si no se ejecutan pruebas, indicar el motivo; no presentar verificaciones
históricas como nuevas. Mantener explícitas las limitaciones y pendientes reales.
Una explicación en el chat no reemplaza la actualización de esta memoria.

## Alcance y referencia

Sistema de control de personas, países, grados, eventos, inscripciones, pagos,
credenciales y certificados. PHP, Laravel 12, Blade, Bootstrap y JavaScript local.
`E:/Desarrollo/swak/swak.md` se consultó como ejemplo de autenticación, permisos,
gestión de usuarios y documentación. Sus reglas y módulos pertenecen a SWAK;
no se trasladaron sus instrucciones operativas, datos ni jerarquías a este proyecto.
Para futuros cambios, revisar esta memoria y actualizar las secciones afectadas
y el historial con las verificaciones realmente ejecutadas.

## Autenticación y usuarios

- Inicio de sesión por **correo electrónico**: GET/POST `/login`.
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
  usan direcciones locales `superadmin@credencial.com` e `impresiones@credencial.com`.
  No hay recuperación de contraseña por correo implementada en esta tarea.
- Migración: `2026_11_12_000001_add_user_access_fields.php`; conserva datos existentes.

### Usuarios iniciales

`database/seeders/UsuariosSeeder.php` crea las cuentas solicitadas:

| Usuario | Contraseña inicial | Rol | Entrada |
| --- | --- | --- | --- |
| superadmin@credencial.com | password1234 | superadmin | /dashboard |
| impresiones@credencial.com | imprime1234 | impresiones | /inscripciones |

El seeder usa firstOrCreate por username: repetirlo no reinicia contraseñas,
roles ni estado de cuentas existentes. Si una cuenta inicial conserva su correo
@credencial.local, el seeder lo actualiza a @credencial.com sin tocar su hash. DatabaseSeeder también lo invoca y
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

### 12/11/2026: imagen del perfil de impresiones

El perfil del rol impresiones muestra `public/images/Impresiones.png` en lugar
de las iniciales, con URL versionada por SHA-256. Superadmin conserva icono.png.
El menú desplegable y Cerrar sesión mantienen su funcionamiento.
Archivos: resources/views/layouts/app.blade.php y certificado.md.
Verificación: archivo de imagen presente y compilación/limpieza Blade correctas.
Sin pruebas nuevas por ser una sustitución visual reversible; sin QA interactiva.

Ajuste del 12/11/2026: UsuariosSeeder declara explícitamente los correos
superadmin@credencial.com e impresiones@credencial.com y las contraseñas iniciales
password1234 e imprime1234. Conserva los hashes de cuentas existentes y migra los
correos anteriores @credencial.local sin duplicar usuarios. Verificación dirigida:
pruebas de seeder repetible, conservación de contraseña y acceso con ambos correos.

Corrección posterior del 12/11/2026: por solicitud del usuario, la imagen junto
al título Credencial en la tarjeta de login usa public/images/icono.png con
versión SHA-256. Esta indicación reemplaza el uso de Logo.png sólo en ese bloque;
los demás logos mantienen sus referencias. Verificado mediante compilación Blade.

Ajuste del 12/11/2026: confirmado Logo.png en el encabezado de la tarjeta de
login. Esa imagen usa hash SHA-256 del archivo como versión de URL para cargar
el contenido actual incluso si el reemplazo conserva la fecha de modificación.
Verificación: compilación y limpieza de vistas Blade; sin cambios de autenticación.

El acceso incluye un botón de ojo a la derecha de Contraseña para alternar
entre texto visible y oculto. No envía el formulario; actualiza su etiqueta
Mostrar/Ocultar y aria-pressed. Script: public/js/password-toggle.js.
Verificación de este ajuste: sintaxis JavaScript y compilación Blade; no se
repitió la suite PHP por tratarse de un control visual sin cambios de autenticación.

| Fecha | Cambio | Archivos principales |
| --- | --- | --- |
| 2026-11-12 | Registrada la obligación de actualizar esta memoria en cada cambio. Revisión documental; no requiere pruebas de aplicación. | AGENTS.md, certificado.md |
| 2026-11-12 | Ojo para mostrar/ocultar contraseña en el acceso | auth/login.blade.php, public/js/password-toggle.js, certificado.md |
| 2026-11-12 | Listado de costos en dólares con tipo de cambio debajo | resources/views/eventos/index.blade.php |
| 2026-11-12 | Inicio/cierre de sesión, dos roles, gestión de usuarios, seeder y autorización en servidor/interfaz | AuthController, UsuarioController, ProjectAccess, User, routes/web.php, bootstrap/app.php, migración de acceso, UsuariosSeeder, vistas auth/usuarios/layouts/inscripciones y pruebas |
| 2026-11-12 | URLs versionadas para arte modificado por operador y memoria técnica | personas/vercred.blade.php, personas/ficha.blade.php, certificado.md |

## Actualización del acceso e identidad (12/11/2026)

Login con dos paneles: imagen public/images/Login.png a la izquierda (60%) y
formulario en tarjeta gris a la derecha (40%), botón verde y campos con foco verde.
En móviles se apilan. CSS en public/css/login.css. Conserva el ojo de contraseña.
Mantener sesión iniciada envía remember al guard de Laravel; CSRF y límite de
intentos permanecen. El acceso ahora exige email. Se omiten recuperación y
versión del ejemplo porque no hay flujo de recuperación ni versión propia definida.

Logo.png se conserva en el menú expandido, pie de credenciales y ficha pública.
icono.png se usa en el encabezado del login, favicon, perfil de superadmin y menú
contraído. Las URLs están versionadas para reflejar reemplazos. Los iconos de
acciones, fotos y plantillas conservan sus archivos específicos. El avatar de
superadmin usa icono.png y su botón abre un menú Bootstrap hacia abajo con
Cerrar sesión por POST y CSRF. No se regeneraron las imágenes del operador.

Se actualizó el seeder y se ejecutó en la base configurada para aplicar los
correos solicitados manteniendo contraseñas. Se ajustaron las pruebas de login y
ficha pública al correo y logo vigentes. Las verificaciones de esta actualización
se registran al terminar; los resultados anteriores son históricos.

Verificaciones finales de esta actualización: suite de 67 pruebas PHP / 724
aserciones aprobadas, seguida de 8 pruebas de autenticación / 103 aserciones
aprobadas tras agregar los casos de remember y cambio de correo sin alterar hash.
Blade compila correctamente; sintaxis PHP y JavaScript correctas. No quedan
referencias a images/icono.png en vistas. Ambos correos comprobados en la base
operativa: activos y con las contraseñas originales. Sin revisión visual
interactiva ni prueba física de impresora en esta actualización.

Ajuste del 12/11/2026: favicon de login, panel administrativo y ficha pública
usa public/images/icono.png, con versión SHA-256 para actualizar la caché.
Los logos dentro de las pantallas conservan sus referencias. Verificación:
compilación y limpieza de vistas Blade correctas.

### 12/11/2026: icono en perfil y menú contraído

El perfil de superadmin usa icono.png. El menú expandido conserva Logo.png;
al contraerlo en escritorio (desde 992 px), CSS muestra icono.png en su lugar.
En móviles se mantiene el logo expandido. No cambia el desplegable de sesión.
Archivos: resources/views/layouts/app.blade.php, public/css/dashboard.css y
certificado.md. Verificación: compilación y limpieza Blade correctas; sin nuevas
pruebas automatizadas por ser cambio reversible de imagen/CSS. Sin QA visual
interactiva en esta tarea.

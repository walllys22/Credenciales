# Memoria técnica de Credencial

Actualizada: 17 de noviembre de 2026 (America/La_Paz).

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
Los botones de acción usan un formato común de 38 × 38 px, radio de 5 px,
icono centrado, tooltip y nombre accesible. `botones.css` asigna 19 colores
distintos por función mediante data-action-style: la misma función conserva
su color en cada módulo. Cerrar usa gris medio (#808080), Ver amarillo,
Editar celeste, Nuevo verde, Pago otro tono verde y Eliminar rojo.
Imprimir usa azul (#3974ce). Las demás funciones usan tonos suaves propios;
Certifica usa lavanda con el archivo original images/diploma.png y Credencial
un tono arena. Esta paleta reemplaza los colores anteriores por pedido del usuario.
Crear, guardar, imprimir, volver, cancelar, buscar, filtrar, limpiar, pagos,
ver, inscripciones, gestionar, ingresar y menú tienen colores propios.
La etiqueta textual permanece accesible pero visualmente oculta. Se mantienen
los controles de navegación de cuenta/barra lateral, vista previa de fotos y
el ojo de contraseña como controles específicos de esas interfaces.
Los modales comparten el color de la acción que los abre: encabezado y borde
del mismo color y cuerpo/pie con un matiz claro. Se asigna la función en el HTML
y modal-colores.js la sincroniza con el botón al abrir; los botones internos,
como Cerrar, mantienen su propio color. Ver participante y Ver evento son
amarillos; las confirmaciones de eliminación son rojas, reemplazando el amarillo
anterior. Texto oscuro en amarillo/celeste para mejorar su legibilidad.
El contenedor del icono del perfil usa fondo transparente, dejando visible
el azul del botón de cuenta alrededor de la imagen. icono.png ya tiene canal
alfa transparente; no se alteró el archivo gráfico ni el desplegable.

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
- Los valores de nombre, cargo, grado y CI del anverso aumentaron 2 pt respecto
  de su base de 10 px (`calc(10px + 2pt)`). Debajo de la foto se muestra país y
  bandera local pequeña. Ese conjunto se amplió después un 30 %: texto 11,7 px,
  bandera de 6,5 × 4,29 mm y separación de 1,3 mm. Se conserva al ampliar e imprimir.
  Hay banderas SVG para Bolivia, Argentina, Chile y Estados Unidos; para países
  nuevos sin bandera incorporada se muestra sólo el nombre. Sin país no se
  muestra este bloque. La ficha pública y el reverso conservan sus tamaños.
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

## Registro y listado unificados (17/11/2026)

La navegación principal y el dashboard usan Inscripciones como entrada para
participantes. Se retiró el enlace independiente Persona. Nueva inscripción y
Editar inscripción incluyen datos personales, foto, grado, evento, cargo,
Jefe de Dojo e Invitado sin costo en un único formulario multipart.
La sección de grado dispone el encabezado a la izquierda y el selector con
el enlace Gestionar grados a la derecha; en pantallas pequeñas se apilan.
Debajo de esa sección aparece Organización del participante: texto opcional de
hasta 255 caracteres, guardado en personas.organizacion y compartido por las
inscripciones de la persona. Se conserva al editar/cargar el participante y se
muestra en el modal de detalle. La migración 2026_11_17_000003 agrega la columna
nullable y asigna Shorin Ryu Shorinkan Bolivia a todas las personas anteriores,
por confirmación del usuario. Los participantes nuevos pueden escribir su
organización; no se les asigna una automáticamente. Los envíos antiguos que
omiten el campo conservan el valor existente; el campo vacío permite borrarlo.
El selector País del participante está a la derecha del encabezado Datos de la
inscripción. Se guarda en personas.pais_id (nullable, FK a paises), se recupera
al editar o cargar un participante existente y valida que el país exista.
Las personas anteriores recibieron Bolivia por confirmación expresa del usuario;
las nuevas pueden elegir el país sin asumir uno automáticamente. El catálogo
impide eliminar países asignados a participantes.
Nueva inscripción comienza directamente en Datos personales: se retiraron el
selector Cargar participante existente y su texto de ayuda. El identificador
persona_id permanece oculto para edición y compatibilidad con enlaces que
carguen un participante existente; sin ese contexto, el alta crea uno nuevo.
La foto es obligatoria para participantes nuevos; al editar o reutilizar se
conserva si no se envía otra. Los datos personales y la foto son compartidos
por las inscripciones de la misma persona; el formulario de edición lo indica.
Se mantiene bloqueado el cambio de grado de personas inscritas para proteger
sus tarifas históricas y el cambio de persona desde la edición integrada.

El guardado integrado usa una transacción: persona e inscripción se confirman
juntas. Si falla la validación de inscripción se revierten los datos personales
y se elimina únicamente la foto nueva. Una foto anterior sólo se elimina tras
confirmar su reemplazo. No se modificaron tablas ni se migraron/eliminaron datos
operativos para esta unificación. Personas, inscripciones, pagos y rutas de
imágenes conservan sus relaciones e identificadores. Las rutas antiguas de
Persona y los envíos anteriores con persona_id siguen disponibles para
compatibilidad; no son el recorrido de la navegación principal.

El listado único reúne foto ampliable, nombre, sexo, documento, grado, teléfono,
cargo, país y observaciones junto a la foto. En Participante, el nombre va en la
primera línea; debajo, código y sexo seguidos de guion y documento; en la tercera
línea, cargo y país uno junto al otro. La columna Organización / Grado muestra
la organización de la persona arriba y su etiqueta de grado debajo; si no tiene
organización, indica Sin organización registrada. El documento no se duplica.
Los importes se agrupan
en una única columna Costo / Pagado / Saldo, en tres líneas etiquetadas. La tabla no muestra una columna Evento;
el evento se elige en el filtro superior y su nombre aparece a la derecha de
«Participantes inscritos». Sin un evento válido seleccionado, se muestra sólo
el título. Los nombres largos pueden pasar a otra línea. Superadmin conserva tarifa, pagado,
saldo y gestión; impresiones conserva sus restricciones y no ve la columna de
importes. La eliminación se confirma mediante modal rojo con nombre y evento,
explicación de irreversibilidad, conservación de datos/foto y bloqueo si hay pagos.
Cancelar no envía el formulario; sólo Sí, eliminar inscripción confirma el envío.
Arriba se muestra un
select de eventos que filtra automáticamente, junto con búsqueda de todos los
nombres/apellidos o documento. El filtro se conserva al paginar y al abrir una
nueva inscripción. Imprimir respeta el evento elegido y sus totales de pagos;
sin evento genera el reporte completo. El reporte no aplica el texto de búsqueda.
El selector del listado inicia en «Seleccione un evento»: sin selección no se
consultan ni muestran inscritos, tabla ni totales; se muestra una indicación.
Elegir un evento aplica el filtro automáticamente; buscar sin elegir evento
tampoco muestra personas. El botón Imprimir del listado sólo aparece al elegir
un evento válido. La ruta del reporte conserva la compatibilidad descrita arriba.
Las etiquetas de grados distinguen Dan (violeta) y Kyu (naranja), conservando
el nombre como identificación; se aplica en los listados unificado y antiguo.
En Acciones, superadmin dispone de Ver participante (ojo, color de la función
Ver). Abre un modal Bootstrap de sólo lectura con foto, país, código, nombres,
apellidos, documento, sexo, nacimiento, teléfono, grado y organización; también muestra la
inscripción exacta: código/fecha de registro, evento/fechas/lugar/ciudad, cargo,
Jefe de Dojo, invitado, observaciones, costo, pagado, descuento y saldo.
Se genera un modal por fila de la página actual, fuera de la tabla, con IDs
únicos y cierre accesible; en móvil el contenido se apila y puede desplazarse.
El encabezado muestra sólo el título; el botón de cierre está en el pie del
modal. Debajo de la foto, la bandera local pequeña aparece a la izquierda del
país, reutilizando el recurso de la credencial. Sin bandera disponible se
muestra sólo el nombre; sin país, País sin registrar.
Impresiones conserva únicamente sus acciones de credencial y certificado;
el botón y los datos adicionales del modal no se envían a ese rol.

### Invitados sin costo

El formulario de alta y edición de inscripciones ofrece la casilla **Invitado
sin costo** a la derecha de Jefe de Dojo (en móviles se apilan). Al marcarla,
la vista previa muestra tarifa cero y Observaciones, de sólo lectura, muestra
`invitado sin costo`. El servidor guarda esa observación en la inscripción.
La gratuidad tiene prioridad sobre Jefe de Dojo y el grado; no requiere tarifa
para ese grado ni tipo de cambio. Al desmarcarla se quita la observación automática
y vuelven las reglas y validaciones de tarifa de Jefe de Dojo o del grado.

`Inscripcion::tarifa()` devuelve `0.00` para invitados; listado, reporte y pagos
muestran costo y saldo pendiente cero. La observación aparece en el listado,
reporte y consulta de pagos. Se oculta el formulario de cobro y el servidor
rechaza pagos de invitados, comprobando el estado bajo bloqueo en la transacción.
No se crean pagos ficticios de monto cero. Los pagos anteriores de una inscripción
que se convierte en invitada se conservan y pueden producir saldo a favor;
no se implementan devoluciones automáticas. La regla de no duplicar inscripciones
y la protección de persona/evento cuando existen pagos siguen vigentes.

Migración aditiva `2026_11_17_000001_add_invitado_sin_costo_to_inscripciones.php`:
agrega el booleano `invitado_sin_costo` (false por defecto) y `observaciones`
nullable. Las inscripciones anteriores conservan su tarifa.

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

### 17/11/2026: columna Organización / Grado

Renombrada la columna Grado (anteriormente Documento / Grado) a Organización /
Grado. Cada celda muestra la organización del participante arriba y su grado
con etiqueta de color debajo, con mensaje explícito si la organización está vacía.
Archivos: resources/views/inscripciones/index.blade.php,
tests/Feature/InscripcionIntegradaTest.php y certificado.md.
Verificación: InscripcionIntegradaTest **9 pruebas / 115 aserciones aprobadas**;
compilación y limpieza Blade correctas; git diff --check sin errores.
Sin pruebas nuevas ni suite completa por ser un cambio
reversible de presentación. Sin revisión visual interactiva ni cambios de datos.

### 17/11/2026: reorganización de la columna Participante

La celda conserva la foto a la izquierda y muestra a la derecha nombre,
código/sexo - documento y, debajo, cargo junto al país. Se trasladó el documento
a esa segunda línea y se renombró Documento / Grado como Grado, conservando
su etiqueta de color. En pantallas estrechas, cargo y país pueden ajustar línea.
Archivos: resources/views/inscripciones/index.blade.php,
tests/Feature/InscripcionIntegradaTest.php y certificado.md.
Verificación: InscripcionIntegradaTest **9 pruebas / 115 aserciones aprobadas**;
compilación y limpieza Blade correctas; git diff --check sin errores.
Sin pruebas nuevas ni suite completa por ser un ajuste
reversible de presentación. Sin revisión visual interactiva ni cambios de datos.

### 17/11/2026: organización del participante y asignación inicial

Agregado Organización del participante debajo de la sección Grado del
participante en el formulario compartido. Guardado/validación, edición, carga
de datos existentes y modal de consulta incluyen la organización. Migración
aditiva aplicada en la base configurada; asignó Shorin Ryu Shorinkan Bolivia
a las **15 personas existentes**, con **0 inscritos sin esa asignación**.
Se conservaron **15 inscripciones y 20 pagos**. Las huellas SHA-256 de los datos
anteriores de personas (excluyendo únicamente la columna nueva), inscripciones
y pagos coinciden antes/después de la migración: no se alteraron los demás datos.

Archivos principales: migración 2026_11_17_000003_add_organizacion_to_personas.php,
Persona.php, DatosPersona.php, InscripcionController.php,
personas/datos.blade.php, inscripciones/form.blade.php y detalle.blade.php,
InscripcionIntegradaTest.php, inscripciones-invitado.test.cjs y certificado.md.
Verificaciones nuevas: **15 pruebas PHP / 188 aserciones aprobadas** (registro
integrado y fotos), **8 pruebas JavaScript aprobadas**, incluyendo carga/limpieza
de organización. Pruebas nuevas de persistencia, edición, límite de 255 caracteres,
conservación al omitir el campo y asignación inicial sin alterar atributos/foto/
inscripción ni sobrescribir organizaciones ya definidas. Compilación/limpieza
Blade correctas y git diff --check sin errores. Sin suite completa ni revisión
visual interactiva en esta tarea; las discrepancias históricas de logos siguen
pendientes. No se agregó la organización a la credencial ni al reporte impreso.

### 17/11/2026: colores indicados por acción y modales a juego

Actualizada la paleta común: Cerrar gris medio, Ver amarillo, Editar celeste,
Nuevo verde, Pago verde en otro tono, Eliminar rojo e Imprimir azul. El resto
usa colores suaves distintos. Certifica conserva diploma.png y pasa a lavanda
para no repetir el amarillo asignado a Ver en la nueva indicación.
Modales de participante/evento y eliminación de inscripciones/pagos comparten
color con el botón de apertura; encabezado y borde iguales y cuerpo/pie claros.
Se agregó encabezado al detalle de evento. La sincronización genérica al abrir
respeta el color propio de cada botón interior. No cambia la confirmación,
la selección de registro ni las validaciones de pagos.
Archivos: public/css/botones.css, public/js/modal-colores.js,
layouts/app.blade.php, inscripciones/index, detalle y pagos.blade.php,
eventos/index.blade.php y certificado.md.
Verificaciones: paleta de 19 colores distintos y colores solicitados comprobados;
sincronización modal/botón, apertura sin botón y exclusión de otros elementos
comprobadas con ejecución JavaScript; **8 pruebas JavaScript aprobadas**.
Pruebas PHP dirigidas: **35 aprobadas / 448 aserciones** (inscripción integrada,
pagos, gestión de eventos y permisos de impresiones). Compilación/limpieza Blade
correctas; git diff --check sin errores. Color azul de Imprimir y unicidad de la
paleta comprobados de nuevo tras la última indicación del usuario.
Sin revisión visual interactiva ni cambios de datos operativos; no se repitió
la suite PHP completa por tratarse de presentación y sincronización de color.

### 17/11/2026: bandera y cierre inferior en el modal de participante

Retirado el botón de cierre junto a Datos del participante en el encabezado;
se conserva el cierre inferior. Agregada bandera de 32 × 21 px a la izquierda
del nombre del país, debajo de la foto, con el mismo recurso de la credencial.
Archivos: resources/views/inscripciones/detalle.blade.php y certificado.md.
Verificación: compilación y limpieza de vistas Blade correctas; git diff --check
sin errores. Sin pruebas nuevas ni suite completa por ser un ajuste reversible
de presentación. Sin revisión visual interactiva ni cambios de datos operativos.

### 17/11/2026: consulta de participante mediante botón de ojo

Agregado Ver participante a las acciones del listado para superadmin, con
icono de ojo, estilo común y color de Ver. Modal de sólo lectura con foto,
datos personales completos y datos/importes de la inscripción de esa fila.
Datos opcionales vacíos se indican con raya o texto; se conserva la protección
del perfil impresiones. No se agregaron rutas ni consultas por participante:
se aprovechan las relaciones y sumas ya cargadas en el listado paginado.
Archivos: resources/views/inscripciones/index.blade.php y detalle.blade.php,
tests/Feature/InscripcionIntegradaTest.php, AuthenticationTest.php y certificado.md.
Verificaciones de esta tarea: **8 pruebas / 125 aserciones aprobadas** (pruebas
integradas y permisos del rol impresiones), incluyendo apertura dirigida al ID
correcto, datos personales y ausencia de modal/datos adicionales para impresiones.
Compilación y limpieza de vistas Blade correctas; git diff --check sin errores.
Sin revisión visual interactiva ni suite completa, dado el alcance de la vista;
persisten las discrepancias de logos previamente documentadas. Sin cambios de datos.

### 17/11/2026: botones uniformes, colores por grado y elección obligatoria de evento

Unificados 77 botones de acción de todos los módulos, formularios, modales,
login y cierre de ficha móvil. Iconos locales, formato compacto de 38 × 38 px,
fondo sólido, esquinas como el ejemplo de Editar, title y aria-label. Una paleta
de 19 colores asigna un color distinto a cada función, repetible sólo para la
misma acción en otros registros/módulos. Por indicaciones posteriores,
Certifica usa diploma.png y fondo amarillo. Los atributos de formulario,
confirmaciones, permisos y estados disabled se conservaron.

Agregado componente de etiqueta de grado: Dan violeta y Kyu naranja,
reconociendo mayúsculas/minúsculas y nombres con la palabra Dan o Kyu.
El listado exige elegir un evento: opción inicial Seleccione un evento,
consulta vacía por defecto, tabla/totales ocultos sin selección y botón de
impresión disponible al elegir un evento válido. Se conservan búsqueda y
paginación dentro del evento seleccionado. Pruebas anteriores ajustadas a
este requisito y al uso de iconos en los botones.

Archivos principales: public/css/botones.css y grados.css, componente
components/grado-badge.blade.php, vistas de auth, dashboard, eventos, grados,
inscripciones, países, personas, usuarios, partials/delete y layouts/app;
InscripcionController.php, pruebas Feature de listado/pagos/credenciales,
certificados/autenticación y certificado.md.
Verificaciones realizadas hasta el cierre de implementación:
- Paleta comprobada: 19 colores distintos y 77 botones con función asignada;
  todos los iconos usados existen en Font Awesome local.
- Compilación y limpieza de vistas Blade correctas; git diff --check sin errores.
- Suite anterior al requisito de elegir evento: **76 aprobadas, 2 fallidas /
  856 aserciones**; sólo las discrepancias de logos ya documentadas.
- Suite final con selección obligatoria de evento: **77 aprobadas, 2 fallidas /
  874 aserciones**. La prueba nueva confirma que sin evento, con sólo búsqueda
  o con evento inexistente no se muestran participantes; un evento válido filtra
  sus registros. Persisten únicamente los fallos previos de logos en
  AuthenticationTest y CredencialMovilTest.
Sin revisión visual interactiva ni prueba física de impresión; no se modificaron
datos operativos ni el arte raster del operador durante estas tareas.

### 17/11/2026: fondo transparente del icono de perfil

Sustituido el fondo blanco de .profile-avatar por transparent, para mostrar
el fondo azul del botón de usuario alrededor del icono. Se conserva el PNG
original: lectura con GD confirmó alfa 127 (transparente) en las esquinas.
Archivos: public/css/dashboard.css y certificado.md.
Verificaciones: regla CSS actualizada y comprobada; git diff --check sin errores.
Sin pruebas nuevas ni suite completa por ser un cambio reversible de CSS.
Sin revisión visual interactiva; no se modificaron imágenes ni datos.

### 17/11/2026: retirado el selector de participante existente

Quitados del formulario Nueva inscripción el selector Cargar participante
existente (opcional) y su ayuda; el primer bloque visible es Datos personales.
Se conserva persona_id oculto para que edición y enlaces existentes funcionen.
Archivos: resources/views/inscripciones/form.blade.php y certificado.md.
Verificación: compilación y limpieza Blade correctas; InscripcionIntegradaTest
aprobado con **7 pruebas / 88 aserciones**; git diff --check sin errores.
Sin pruebas nuevas por ser un cambio reversible de presentación. Sin revisión visual interactiva
ni cambios de datos operativos.

### 17/11/2026: datos de credencial más grandes y país bajo la foto

Incrementados en 2 pt los valores del anverso. Agregados país y bandera debajo
de la fotografía, con SVG locales de los cuatro países iniciales y nombre como
respaldo para países sin recurso. Por solicitud posterior, ampliado ese conjunto
un 30 %: texto de 9 a 11,7 px, bandera de 5 × 3,3 a 6,5 × 4,29 mm y separación
de 1 a 1,3 mm. Las dimensiones de la tarjeta y la foto no cambian.
Archivos: public/css/credenciales.css, resources/views/personas/vercred.blade.php,
app/Models/Pais.php, CredencialController.php, public/images/banderas/*.svg,
tests/Feature/CredencialTest.php y certificado.md.
Verificaciones: CredencialTest **7 pruebas / 69 aserciones aprobadas**, incluyendo
el país y recurso de bandera del anverso. Compilación y limpieza Blade correctas;
git diff --check sin errores. Sin revisión visual interactiva ni impresión física;
revisar el ajuste de nombres especialmente largos en el arte de la credencial.
No se modificaron datos operativos. No se repitió la suite completa para estos
cambios de presentación; siguen documentadas las discrepancias previas de logos.

### 17/11/2026: modal amarillo, país y resumen de importes

Reemplazada la confirmación nativa de eliminación de inscripciones por un modal
Bootstrap amarillo con participante, evento y explicación. Cancelar/cerrar
descarta el destino; confirmar envía únicamente el formulario de esa inscripción
y deshabilita el botón para evitar doble envío. La protección de pagos permanece.

Agregado selector País del participante a la derecha de Datos de la inscripción,
persistencia y carga al reutilizar/editar. Migración aditiva
2026_11_17_000002_add_pais_to_personas.php aplicada en la base configurada:
por instrucción del usuario asigna Bolivia a todos los participantes anteriores.
Comprobación real posterior: **15 personas, 15 con Bolivia, 0 sin país,
15 inscripciones y 20 pagos**. No se eliminaron registros ni fotos.

El listado muestra país debajo del cargo y reúne Costo, Pagado y Saldo en una
columna, con etiquetas y montos en líneas separadas. Se ajustó el colspan vacío;
el país se carga con eager loading. Los permisos de impresiones se conservan.
Archivos principales: inscripciones/index.blade.php y form.blade.php,
public/js/inscripciones-eliminar.js, InscripcionController.php, PaisController.php,
modelos Persona/Pais, DatosPersona.php, migración de país, pruebas JavaScript de
eliminación y formulario, InscripcionIntegradaTest.php, PagoTest.php y certificado.md.

Verificaciones ejecutadas:
- Modal: **2 pruebas JavaScript aprobadas** (destino correcto, cancelación,
  invalidación de formulario inexistente y bloqueo de doble envío); sintaxis JS correcta.
- Todos los tests JavaScript: **8 aprobados**, incluidos carga y limpieza de país.
- InscripcionIntegradaTest y PaisTest: **9 aprobados / 109 aserciones** tras corregir
  el enfoque de una prueba que intentaba quitar una FK con registros dependientes
  en SQLite. La prueba final verifica el paso de asignación de Bolivia y conservación
  de atributos/foto/inscripción; la migración completa se aplicó correctamente en la base real.
- InscripcionIntegradaTest, EventManagementTest y PaisTest después del ajuste del
  listado: **23 aprobados / 260 aserciones**. Verificación final de
  InscripcionIntegradaTest y PagoTest: **19 aprobados / 242 aserciones**, incluida
  la disposición de importes y el país debajo del cargo.
- Compilación y limpieza Blade correctas; git diff --check sin errores.
- Sin suite PHP completa ni revisión visual interactiva en estas tareas; las dos
  discrepancias históricas de logos siguen pendientes. No se modificó la impresión
  del reporte ni los cálculos de importes en este ajuste de columnas.

### 17/11/2026: selector de grado junto a su encabezado

Reorganizada la sección Grado del participante en dos columnas: encabezado
a la izquierda y selector/enlace de gestión a la derecha, apilados en móviles.
Archivos: resources/views/personas/datos.blade.php y certificado.md.
Verificación: compilación y limpieza Blade correctas; git diff --check sin errores.
Sin pruebas nuevas ni suite completa por tratarse de un ajuste reversible de
distribución visual. Sin revisión interactiva; no se modificaron datos ni validaciones.

### 17/11/2026: nombre del evento junto al título del listado

El encabezado muestra el nombre del evento filtrado a la derecha de
«Participantes inscritos». Se toma del catálogo de eventos cargado, con salida
escapada por Blade; sin filtro válido se conserva sólo el título.
Archivos: resources/views/inscripciones/index.blade.php y certificado.md.
Verificación: compilación y limpieza Blade correctas; git diff --check sin errores.
Sin pruebas nuevas ni suite completa por ser un cambio reversible de presentación.
Sin revisión visual interactiva en esta tarea; no se modificaron datos.

### 17/11/2026: retirada la columna Evento del listado

Se quitó la columna Evento de la tabla de inscritos, conservando el selector
superior y su filtrado. Cargo y observaciones se muestran debajo del nombre
del participante; se ajustó el colspan del estado vacío para ambos roles.
Archivos: resources/views/inscripciones/index.blade.php y certificado.md.
Verificación de esta tarea: compilación y limpieza de vistas Blade correctas.
Sin pruebas nuevas ni suite completa por ser un ajuste reversible de presentación;
sin revisión visual interactiva. No se modificaron datos ni reglas de inscripción.

### 17/11/2026: formulario y listado únicos de inscripciones

Unificados datos personales/foto y datos de inscripción. Reutilización opcional
de personas existentes, guardado transaccional y conservación de fotos/pagos.
Retirado Persona de menú y dashboard. Listado combinado con fotos y filtro
superior automático por evento; impresión filtrada por el mismo evento.
Archivos principales: InscripcionController.php, PersonaController.php,
app/Support/DatosPersona.php, personas/datos.blade.php y form.blade.php,
inscripciones/form.blade.php e index.blade.php, layouts/app.blade.php,
dashboard.blade.php, InscripcionIntegradaTest.php, PaisTest.php,
InscripcionesImprimirTest.php, inscripciones-invitado.test.cjs y certificado.md.

Verificaciones nuevas de esta tarea:
- Pruebas integradas nuevas: **5 aprobadas / 70 aserciones**. Alta conjunta,
  foto obligatoria/válida, rollback de datos/fotos por error, invitado gratuito,
  reutilización sin duplicados, conservación de pagos y foto, edición/reemplazo,
  protección de grado, filtro e impresión por evento.
- EventManagementTest: **14 aprobadas / 151 aserciones** durante la implementación.
- Suite PHP final: **74 aprobadas, 2 fallidas / 834 aserciones**. Persisten las dos
  discrepancias de logos en AuthenticationTest y CredencialMovilTest documentadas
  en la tarea anterior. Los tests de navegación e impresión se actualizaron al
  comportamiento vigente y ya no fallan.
- JavaScript final: **6 pruebas aprobadas**, incluidos carga de datos/foto de un
  participante existente, limpieza al elegir uno nuevo, duplicados y gratuidad.
- Sintaxis PHP de ambos controladores y DatosPersona correcta; compilación y
  limpieza Blade correctas; git diff --check sin errores.
- La revisión visual interactiva quedó sin completar: después de dos timeouts
  y reiniciar el controlador, Computer Use detuvo la captura porque no pudo
  determinar la URL actual del navegador con suficiente confianza para aplicar
  su política. No se realizaron más acciones de navegador. Sin prueba física
  de impresión. No se ejecutaron escrituras sobre datos operativos en esta tarea.

### 17/11/2026: invitado sin costo en inscripciones

Agregada casilla junto a Jefe de Dojo, tarifa cero con prioridad sobre otras
tarifas y observación automática `invitado sin costo`. Alta/edición, listado,
reporte y consulta de pagos reflejan la gratuidad. Cobros bloqueados en servidor.
Archivos principales: InscripcionController.php, PagoController.php,
app/Models/Inscripcion.php, vistas inscripciones/form, index, imprimir y pagos,
migración 2026_11_17_000001, tests/Feature/EventManagementTest.php,
tests/JavaScript/inscripciones-invitado.test.cjs y certificado.md.

Verificaciones ejecutadas en esta tarea:
- Migración aplicada correctamente a la base configurada, sin borrar datos.
- Compilación y limpieza de vistas Blade correctas.
- Pruebas dirigidas EventManagementTest: **14 aprobadas / 151 aserciones**.
  Incluyen alta y edición de invitados, observación persistida, prioridad sobre
  Jefe de Dojo, falta de tipo de cambio/tarifa, bloqueo de cobros Bs/USD,
  consulta de saldos, duplicados, validación booleana y retorno a tarifa normal.
- JavaScript: **5 pruebas aprobadas**, dos nuevas para la casilla, observaciones,
  carga inicial de invitado y restauración de la tarifa al desmarcarla.
- Suite PHP completa: **69 aprobadas, 2 fallidas / 761 aserciones**. Pendientes
  ajenos a este cambio en pruebas de identidad visual: AuthenticationTest espera
  Logo.png en login, cuya vista usa icono.png; CredencialMovilTest prohíbe icono.png
  aunque la ficha lo usa como favicon. Esos archivos no se modificaron en esta tarea.
- Sin revisión visual interactiva de navegador ni prueba física de impresión.

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
formulario en tarjeta gris a la derecha (40%), botón Ingresar con el estilo común
de acciones y campos con foco verde.
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

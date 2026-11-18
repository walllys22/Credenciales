# Memoria técnica de Credencial

Actualizada: 18 de noviembre de 2026 (America/La_Paz).

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
carga los 18 grados numerados de la lista solicitada. Para cambiar una contraseña existente,
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
El POST `inscripciones.credenciales-lote` también está permitido para preparar la
vista de impresión por lotes; no modifica registros.
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
- `public/images/FirmaRenshi.png` se superpone de forma proporcional sobre el
  nombre de Roberto Zabala Atipobo, tanto en pantalla como en impresión. El
  archivo original se conserva y su URL se versiona por fecha de modificación.
  Su posición vertical se ajusta con `top:75.5%` en `.certificate-signature`.
- CSS: `public/css/certificados.css`. Nombre en top 47%, left 21%, width 58%;
  tamaño base 22 pt, alineado a la izquierda. Vista previa reducida en pantalla.
- Impresión: carta horizontal, margen CSS 0, ancho 279,4 mm y proporción original.
  Márgenes físicos y calidad final dependen del controlador de impresora.
- `public/js/certificados.js` espera fuentes e imágenes antes de window.print().
- No se genera un PDF persistido ni se registra entrega al imprimir.

### 18/11/2026: firma flotante de Renshi

Se incorporó la imagen proporcionada encima del nombre de Roberto Zabala Atipobo
mediante posicionamiento proporcional a la plantilla. Archivos principales:
`resources/views/inscripciones/vercert.blade.php`, `public/css/certificados.css`,
`public/images/FirmaRenshi.png` y `certificado.md`. Verificaciones: CertificadoTest
**4 pruebas / 27 aserciones aprobadas**, compilación y limpieza Blade y
`git diff --check` correctos. No se agregaron pruebas para este ajuste visual;
sin revisión interactiva ni impresión física, por lo que la posición final debe
comprobarse en la impresora utilizada.

### 18/11/2026: ajuste vertical de la firma

Se bajó la firma de 74,5 % a 75,5 % de la altura del certificado y se dejó un
comentario sobre el valor que controla su posición. Archivos: `public/css/certificados.css`
y `certificado.md`. Verificación: `git diff --check` correcto. No se ejecutaron
pruebas automatizadas ni impresión física por ser un ajuste visual reversible;
la ubicación debe revisarse en pantalla e impresora.

## Impresión de credenciales por lotes (18/11/2026)

El listado incluye un botón de lote a la izquierda de Imprimir reporte y casillas
por inscrito, con selección de la página visible. La selección se conserva por
evento en sessionStorage al cambiar página o filtro y se puede limpiar.
El mensaje y el botón de limpieza respetan el margen izquierdo del encabezado
«Participantes inscritos», también en pantallas pequeñas.
Se requieren
entre 2 y 500 inscripciones distintas del evento; el servidor valida existencia,
unicidad y pertenencia. La vista se abre en la misma pestaña y permite volver al
listado del evento. Muestra anverso y reverso en una hoja A4 por inscrito, con QR
firmados individuales. También está disponible para el rol impresiones. Los flujos
individuales no se modificaron. Sin almacenamiento del navegador, la selección sólo
permanece en la página actual; si se elimina una inscripción seleccionada, debe
limpiarse o corregirse la selección.

### 18/11/2026: alineación del mensaje de selección

Se ajustó el margen horizontal del formulario de selección al del encabezado
del listado, con el mismo ajuste para pantallas pequeñas. Archivos:
`public/css/inscripciones-listado.css` y `certificado.md`. Verificación:
`git diff --check` correcto. No se ejecutaron pruebas automatizadas ni revisión
visual interactiva por tratarse de un ajuste de presentación reversible.

Archivos: routes/web.php, ProjectAccess.php, CredencialController.php,
inscripciones/index.blade.php, inscripciones/credenciales-lote.blade.php,
public/js/credenciales-lote-seleccion.js, tests/Feature/CredencialTest.php y
certificado.md. Verificaciones nuevas: CredencialTest **8 pruebas / 89 aserciones
aprobadas**, con mínimo, duplicados, evento, QR y acceso de impresiones; sintaxis
PHP y JavaScript correctas. Sin revisión visual interactiva ni impresión física;
la distribución final depende de los ajustes de impresión del navegador.
Tras cambiar la apertura a la misma pestaña, la prueba dirigida volvió a pasar
con 22 aserciones, incluida la ausencia de nueva pestaña y el control de regreso.

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

### 17/11/2026: separación visible de Kyu y Dan en el catálogo

El listado /grados muestra encabezados de grupo: Grados Kyu primero, seguido
por Grados Dan. Los números Kyu se ordenan de mayor a menor y los Dan de menor
a mayor. El orden numérico sustituye al orden por lista fija para ubicar
correctamente grados agregados después, como 8vo Kyu. Se mantienen búsqueda,
paginación y acciones de cada grado. No se modificaron registros ni tarifas.
Archivos: app/Models/Grado.php, resources/views/grados/index.blade.php y
certificado.md.
Verificaciones de este ajuste: **5 pruebas aprobadas / 131 aserciones** de
catálogo y gestión de grados; compilación/limpieza Blade y git diff --check
correctos. Consulta de sólo lectura en MySQL operativa confirmó los 19 grados:
10mo a 1er Kyu, incluyendo 8vo Kyu, seguidos por 1er a 10mo Dan, con los
números disponibles. El intento inicial con Tinker no se ejecutó por la ruta
de historial fuera del espacio permitido; se verificó con un script PHP local.
Sin inspección visual interactiva ni nueva suite completa por ser un ajuste
de orden y encabezados de grupos.

### Ajuste del 17/11/2026: orden y grupos del selector de grados

Primero se muestran los Kyu, de 10mo a 1er, y luego los Dan, de 1er a 10mo,
conservando únicamente los grados existentes en la lista del usuario. El
selector utiliza grupos separados Kyu y Dan. El orden también se comparte con
el catálogo y los formularios de eventos, sin depender del ID ni del orden
alfabético y sin modificar asignaciones ni tarifas.
El catálogo separa las filas con encabezados «Grados Kyu» y «Grados Dan».
La ordenación usa la categoría y el número del nombre, de modo que un grado
numerado agregado después también se ubica dentro de su grupo en el lugar
correspondiente, sin depender de la lista inicial de nombres.

Archivos: app/Models/Grado.php, controladores GradoController,
PersonaController, InscripcionController y EventoController,
resources/views/personas/datos.blade.php y certificado.md.
Verificación nueva: **14 pruebas aprobadas / 246 aserciones** de catálogo,
gestión de grados e inscripción integrada. Compilación y limpieza Blade
correctas; git diff --check sin errores. Sin revisión visual interactiva;
no se repitió la suite completa por ser un ajuste de orden y presentación.

El pie del reporte de inscritos usa texto HTML a **12 pt** en ambas líneas
(nombre del seminario y BOLIVIA 2026), en pantalla e impresión. La cabecera usa
public/images/Cabecera.png; el pie se compone con texto HTML y líneas negra
y roja. Los archivos gráficos originales no se modifican. El pie se
repite en cada hoja del PDF. El botón Imprimir utiliza ese mismo PDF en un
iframe para conservar cabecera y pie completos. La vista previa ancla el pie
a 10 mm del borde inferior y reserva su espacio al distribuir las filas.
Las líneas negra/roja del pie tienen el mismo ancho y los
mismos extremos que la tabla. En pantalla usan los márgenes interiores de
10 mm del reporte; al imprimir ocupan los 195,9 mm útiles del papel Carta vertical.
Cabecera.png ocupa una caja de 38 mm de alto, conserva su proporción mediante
object-fit:contain, alineada arriba con object-position:center top, y se repite
en cada hoja de vista previa y en el encabezado fijo del PDF. Su caja comienza a
10 mm del borde superior en pantalla; en impresión se sitúa arriba del área
útil, con los 10 mm que establece @page.
La URL usa un hash SHA-256 para
reflejar cambios del archivo. Se eliminó la línea roja adicional entre la
cabecera y el título; las líneas que forman parte de Cabecera.png se conservan.
El título Listado de Inscritos tiene padding superior de 2 mm para separarlo
de la cabecera, tanto en pantalla como en impresión.

`Evento` relaciona grados con pivot costo (Bs) y costo_usd; tipo_cambio pertenece
al evento. El formulario captura dólares y calcula bolivianos con BigDecimal.
El listado de eventos y su modal agrupan los costos por categoría: Kyu $us 30,
Dan $us 50 y Jefes de Dojo $us 100, sin repetir los grados numerados.
Se conserva el tipo de cambio debajo y, en el modal, el importe en Bs.
La columna Fechas del listado muestra únicamente inicio y final del evento;
se retiró la etiqueta de estado debajo de las fechas. El filtro Estado
permanece disponible para próximos, en curso y finalizados.
Evento::costos_agrupados reúne precios iguales por categoría sin modificar
los pivotes; los costos personalizados anteriores o distintos se conservan
identificados para no ocultar diferencias. La fila de Jefes de Dojo calcula
Bs con el tipo de cambio del evento y marca Sin registrar si falta.
El catálogo inicial solicitado contenía: 10mo Kyu, 9no Kyu, 7mo Kyu,
6to Kyu, 5to Kyu, 4to Kyu, 3er Kyu, 2do Kyu, 1er Kyu, 1er Dan, 2do Dan,
3er Dan, 4to Dan, 5to Dan, 7mo Dan, 8vo Dan, 9no Dan y 10mo Dan.
Esa imagen no incluía 8vo Kyu ni 6to Dan. En la última consulta operativa el
catálogo contiene 19 registros porque se agregó 8vo Kyu posteriormente.
El catálogo muestra los registros en una página, primero Kyu de mayor a menor y después Dan de menor
a mayor. El orden se aplica mediante Grado::ordenados() al catálogo y los
formularios de participantes, inscripciones y eventos. El selector del
participante separa visualmente ambas categorías con los grupos Kyu y Dan.
Las categorías tarifarias se deducen del nombre: Kyu cuesta $us 30 y Dan
$us 50, multiplicados por el tipo de cambio del evento. El formulario de
eventos muestra esos precios de sólo lectura y el servidor fija los mismos
valores aun si se manipula el envío. Jefe de Dojo es una opción separada de
la inscripción: sólo Dan **y** Jefe de Dojo paga $us 100; Kyu con jefatura
conserva $us 30. Invitado sin costo tiene prioridad y paga cero.
Los precios anteriores de grados personalizados siguen disponibles como
compatibilidad técnica; el formulario integrado exige un grado de la lista.
El certificado reconoce los Dan numerados para el prefijo Sensei.
La migración 2026_11_17_000004 permite personas.grado_id nulo y conserva el
nombre general en grado_anterior. CatalogoGrados reemplaza los grados generales
y sus pivotes por los 18 numerados y sus tarifas. No asigna números arbitrarios
a las personas anteriores: se muestran Dan/Kyu hasta editar y elegir un grado
numerado. Se permite concretar o cambiar el número dentro de la misma categoría;
se mantiene el bloqueo de cambio de categoría cuando hay inscripciones.
Las fotos, datos personales restantes, inscripciones y pagos se conservan.
Los eventos anteriores sin tipo de cambio requieren configurarlo antes de
cobrar con las tarifas en dólares; no se inventa una cotización.
Importes y tipo de cambio no registrados se señalan explícitamente; no se
deduce una cotización de mercado ni se reconvierten datos históricos por suposición.

## Registro y listado unificados (17/11/2026)

### 17/11/2026: PDF descargable, 15 participantes por hoja e impresión completa

Se agregó Descargar PDF inmediatamente a la izquierda de Imprimir, con el
filtro de evento vigente y las mismas restricciones de acceso del reporte.
La descarga devuelve application/pdf como adjunto. Imprimir solicita el mismo
documento y abre su impresión mediante un iframe; conserva el PDF cargado
durante la impresión y libera el anterior al preparar otro.

El PDF se genera con Dompdf 3.1.6 en PHP, sin Chrome en el servidor ni servicios
externos. Se deshabilitan recursos remotos y ejecución PHP en el documento.
Filas y totales usan parciales Blade compartidos con la vista. Carta vertical,
cabecera Cabecera.png, título, pie con sus líneas y «Página X de Y» se incorporan
en cada página. Las reservas internas de cabecera/pie dejan sus elementos
exteriores dentro del límite de 10 mm. La organización pasó de 8,5 a **9,5 pt**
por la última petición; el nombre conserva 10 pt. Las hojas completas muestran
**15 participantes**, con la última fila próxima al pie. Nombres u organizaciones
excepcionalmente largos pueden ocupar más alto y provocar una hoja adicional;
las reservas evitan que invadan la cabecera o el pie.

El resumen de deuda aparece a la izquierda, con un espacio superior de 6 mm.
Si las filas finales dejan poco espacio, los totales pasan juntos a una hoja
final. No se alteran saldos, cotizaciones, pagos ni datos personales.

Archivos principales: composer.json/lock, app/Support/ReportePdf.php,
InscripcionController.php, routes/web.php, inscripciones/imprimir.blade.php,
pdf.blade.php, reporte-filas.blade.php, reporte-totales.blade.php,
public/css/inscripciones-imprimir.css, botones.css, public/js/reporte-paginas.js,
tests/Feature/InscripcionesImprimirTest.php y pruebas JavaScript de paginación.

Verificaciones nuevas: prueba real de descarga PDF en PHPUnit (31 participantes,
3 páginas y MediaBox Carta), filtros, orden de botones, adjunto/vista inline y
rechazo al rol impresiones. **17 pruebas JavaScript aprobadas**, incluida la
partición 15/15/4 sin omitir filas. Se generó además un PDF de QA con 60 filas
clonadas sólo en memoria: 5 páginas, cuatro de 15 participantes y una de totales.
PDF.js verificó título y pie en todas; renderizado e inspección visual de las
hojas y recortes del pie confirmaron tamaños, colores, organización legible,
numeración y márgenes. Los importes de ese fixture conservan los totales reales
originales: sirve para disposición, no para validar sumas de las filas clonadas.
Sintaxis PHP/JavaScript y compilación/limpieza Blade correctas.

Pruebas dirigidas finales, sin otras suites simultáneas: **22 aprobadas / 269
aserciones** (reporte, inscripción integrada y fotos). Compilación y limpieza
Blade repetidas correctamente después del último ajuste de presentación.
Suite PHP completa final, ejecutada de forma aislada: **88 aprobadas y 2
fallidas / 1062 aserciones**. Sólo fallan las expectativas históricas de logos
en AuthenticationTest y CredencialMovilTest. Registro de esta ejecución:
storage/app/pruebas-pdf-final.txt. Una ejecución anterior superpuesta con otra
suite produjo interferencias en Storage::fake; sus fallos de archivos se
descartaron y no se atribuyen a esta ejecución final aislada. git diff --check
sin errores. La reserva inferior de la vista previa quedó en 26 mm para
mantener la última fila próxima al pie sin alargar artificialmente la hoja.

Limitaciones: no se hizo una impresión física en la impresora del usuario ni
una prueba interactiva del diálogo de impresión del iframe. Ctrl+P conserva
la impresión HTML del navegador; el botón Imprimir usa el PDF. La vista puede
dividir antes una fila alta según su ancho; el PDF utiliza el ancho Carta fijo.
Los dos fallos históricos de expectativas de logos siguen fuera de este ajuste.

### 17/11/2026: hojas de vista previa, navegación flotante y cabecera repetida

La cabecera Cabecera.png está fuera de la tabla, en cada hoja de vista previa.
La impresión desde el botón utiliza el PDF generado en el servidor con Dompdf,
que incorpora cabecera, título, pie y numeración en todas las páginas. Se
mantienen Carta vertical y los límites exteriores a 1 cm. Esta solución
sustituye la repetición automática de thead que fallaba en la impresora.

public/js/reporte-paginas.js crea hojas de vista previa midiendo encabezados,
filas y resumen de totales al cargar fuentes e imagen. Conserva cada fila
completa y el bloque de totales sólo en la última hoja; si no cabe, pasa junto
a otra. Recalcula al cambiar el ancho. Los botones flotantes Anterior/Siguiente
y el indicador Página X de Y aparecen sólo si la vista tiene más de una hoja,
se deshabilitan en los extremos y permiten desplazarse entre hojas. Respeta
la preferencia de movimiento reducido y actualiza el indicador al desplazarse.
Cada hoja completa admite hasta 15 participantes y distribuye sus filas hacia
el pie, según la última indicación del usuario; sustituye las cantidades
intermedias de 13 y 16. Las filas excepcionalmente altas pueden reducir esa
cantidad en la vista previa para mantener íntegro el contenido.
El botón de impresión prepara el PDF, sin imprimir los botones flotantes.
Si no carga JavaScript, el listado original permanece disponible.

Archivos: resources/views/inscripciones/imprimir.blade.php,
public/css/inscripciones-imprimir.css, public/js/reporte-paginas.js y
tests/JavaScript/reporte-paginas.test.cjs. Pruebas nuevas de una hoja, varias
hojas sin duplicar filas, totales juntos y filas sobredimensionadas. La cantidad
de hojas de pantalla puede diferir del resultado del controlador de impresión
si se cambia escala, fuentes, papel o ancho de vista. No se pudo verificar
visualmente el resultado con el intento headless de Edge (no devolvió DOM);
queda sin comprobación física la repetición en la impresora del usuario.

### 17/11/2026: default.jpg seleccionado y guardado en Nueva inscripción

Se precarga default.jpg como archivo en el input de foto, no sólo como imagen
de vista previa. No reemplaza una foto elegida por el usuario mientras descarga
la predeterminada y no precarga fotos al editar participantes existentes.
InscripcionController guarda la predeterminada cuando el participante es nuevo
y no llega otro archivo; el guardado conserva la transacción y su limpieza.
DatosPersona permite esa alternativa en el registro integrado. Las fotos
personalizadas mantienen su validación y las existentes se conservan al editar.
Archivos: datos.blade.php, inscripciones/form.blade.php, public/js/personas.js,
DatosPersona.php, InscripcionController.php y pruebas integradas/JavaScript de
foto predeterminada. Esta indicación sustituye el comportamiento anterior de
imagen de ejemplo sin archivo adjunto. No se modificaron participantes existentes.

### 17/11/2026: resumen de deuda y jerarquía de texto del reporte

Debajo de Pagado Bs.- y Pagado $us.- se muestran participantes con deuda,
deuda pendiente total en Bs, equivalente en dólares y tipo de cambio utilizado.
Se cuentan personas distintas con saldo positivo y se suman sólo saldos
pendientes, descontados pagos y descuentos. Para varios eventos, se convierte
el subtotal de deuda de cada evento con su propio tipo de cambio y se listan
las cotizaciones; no se divide toda la deuda por una cotización arbitraria.
Si falta un tipo de cambio necesario, el total en Bs sigue disponible y el
equivalente USD indica Sin tipo de cambio. El resumen se conserva junto al
resto de totales en la última hoja de la vista previa.

La columna Número pasa de 5 % a 6 % de ancho, con texto 9,5 pt y sin salto en
N.º. El nombre del participante usa 10 pt y la organización 9,5 pt en la línea
inferior. Se mantiene la distribución de siete columnas, colores y monedas.
Archivos: InscripcionController.php, inscripciones/imprimir.blade.php,
public/css/inscripciones-imprimir.css y tests/Feature/InscripcionesImprimirTest.php.

Verificaciones conjuntas nuevas: **20 pruebas PHP aprobadas / 250 aserciones**
(reporte, inscripción integrada y fotos), **16 pruebas JavaScript aprobadas**.
Incluyen foto predeterminada persistida, conservación al editar, prioridad de
foto personal durante la descarga, partición de filas/totales, deudas con
pagos mixtos, descuentos, invitados, tipos de cambio distintos y cotización
ausente. Después de ajustar fuentes, compilación/limpieza Blade y
git diff --check correctos. Sin inspección visual interactiva ni impresión física.

### 17/11/2026: cambio de orientación del reporte a vertical

Por nueva indicación del usuario, Carta vertical sustituye la orientación
horizontal anterior. Se ajustó @page a letter portrait, la hoja de vista
previa a 215,9 × 279,4 mm y el ancho imprimible de cabecera/pie a 195,9 mm.
Se mantienen los márgenes de 10 mm en los cuatro lados y «Página X de Y»
en la esquina inferior derecha con los contadores de página y total.
Archivos: public/css/inscripciones-imprimir.css y certificado.md.
Verificaciones nuevas: **3 pruebas del reporte aprobadas / 36 aserciones**;
compilación/limpieza Blade y git diff --check correctos. Sin prueba física ni
revisión interactiva del diálogo de impresión; no se repitió la suite completa
por ser un cambio de dimensiones CSS. Credenciales y certificados conservan
sus configuraciones de impresión independientes.

### 17/11/2026: invitados en rojo claro y organización bajo el nombre

En el reporte se resalta toda la fila de invitados sin costo con fondo rojo
claro #f8d7da, conservado al imprimir. El rojo tiene prioridad sobre el amarillo
de saldos liquidados, incluso si el invitado conserva pagos anteriores.
La organización se muestra debajo del nombre del participante y se retiró
la columna Cargo sólo del reporte. Se redistribuyó su ancho a Nombre completo
y se ajustaron los colspan de título, totales, pie y estado vacío a siete
columnas. Sin costo mantiene su celda combinada de tres columnas. Cargo
permanece guardado y disponible en los demás módulos.
Archivos: resources/views/inscripciones/imprimir.blade.php,
public/css/inscripciones-imprimir.css y certificado.md.
Verificaciones nuevas tras ambos ajustes: **4 pruebas aprobadas / 71 aserciones**
del reporte y gratuidad; compilación/limpieza Blade y git diff --check correctos.
Sin inspección visual interactiva ni impresión física; sin nuevas pruebas de
estilo ni suite completa por ser presentación, sin cambios de datos o cobros.

### 17/11/2026: configuración de página y numeración del reporte

El reporte de inscritos configura @page con size:letter portrait y margin:10mm:
hoja Carta vertical de 215,9 × 279,4 mm y 1 cm de margen en cada lado.
La vista de hoja en pantalla usa esas dimensiones; la cabecera y el pie de
impresión ocupan los 195,9 mm de ancho útil, conservando su alineación con
la tabla. Las reservas de cabecera y pie y los saltos entre filas se mantienen.
La esquina inferior derecha usa @bottom-right con «Página X de Y», mediante
counter(page) y counter(pages), que representan la página actual y el total
calculado al imprimir; no son números fijos ni contadores de elementos HTML.

Esta numeración requiere soporte de cajas de margen de CSS paginado (Chrome
131 o posterior). Referencia técnica oficial consultada:
https://developer.chrome.com/blog/print-margins?hl=es-419.
Si el diálogo/controlador impone otro tamaño, orientación, escala o márgenes,
puede reemplazar los valores CSS. La configuración solicitada corresponde al
reporte; no cambia la impresión de credenciales ni certificados.
Archivos: public/css/inscripciones-imprimir.css y certificado.md.

Verificaciones de esta configuración: **3 pruebas del reporte aprobadas / 36
aserciones**; compilación y limpieza Blade correctas; git diff --check sin
errores. Se revisaron las dimensiones y las reglas de margen/numeración CSS.
Sin prueba física ni revisión interactiva de la vista previa de impresión;
el número final de páginas y su representación en el controlador de impresora
no se comprobaron en esta tarea. No se repitió la suite completa porque no
se modificaron datos, cálculos ni controladores.

### 17/11/2026: cabecera y pie a 1 cm de los bordes de la hoja

Se ancló el pie al borde inferior con margen de 10 mm en pantalla, en lugar
de usar una posición superior fija. En impresión usa bottom:0 dentro del
área útil: el margen inferior de 10 mm lo aporta @page, evitando duplicarlo.
Se mantiene la reserva de 26 mm en cada página y se reservan 30 mm abajo en
pantalla para evitar superposición del listado y el pie.
La imagen Cabecera.png se alinea arriba en su caja, retirando el espacio
vertical que añadía el centrado de object-fit. La caja tiene top:10mm en
pantalla y top:0 del área imprimible, cuyo margen superior es 10 mm.
Archivos: public/css/inscripciones-imprimir.css y certificado.md.
Verificación nueva: compilación y limpieza Blade correctas y git diff --check
sin errores. Sin nuevas pruebas automatizadas por tratarse de posiciones CSS;
sin inspección visual interactiva ni prueba física de impresión. Los márgenes
físicos finales dependen de respetar la configuración de papel Carta y CSS
en el diálogo/controlador de impresión.

### 17/11/2026: retirada de la línea roja bajo la cabecera del reporte

Se eliminó la línea roja adicional entre Cabecera.png y Listado de Inscritos,
retirando su elemento HTML y sus reglas CSS de pantalla e impresión. Se
mantienen la imagen original, las líneas del pie y el espacio superior del
título. Archivos: resources/views/inscripciones/imprimir.blade.php,
public/css/inscripciones-imprimir.css y certificado.md.
Verificación nueva: compilación y limpieza Blade correctas; git diff --check
sin errores. Sin nuevas pruebas automatizadas ni suite completa por tratarse
de eliminar un elemento decorativo; sin inspección visual interactiva.

### 17/11/2026: columna Fechas sin etiqueta de estado

Se retiró el texto Próximo y la línea de estado de la columna Fechas en Eventos.
La celda muestra sólo las fechas inicial y final; el filtro Estado mantiene
su funcionamiento. Archivos: resources/views/eventos/index.blade.php y
certificado.md. Verificaciones nuevas: **4 pruebas aprobadas / 51 aserciones**
de eventos, incluido filtro por estado y paginación; compilación/limpieza Blade
y git diff --check correctos. Sin revisión visual interactiva ni nueva suite
completa por ser una eliminación de texto de presentación.

### 17/11/2026: Cabecera.png en el reporte

Se sustituyó Membrete.png por public/images/Cabecera.png como imagen superior
del reporte. Se muestra completa y sin deformar dentro de 38 mm de altura;
se conserva la línea roja inferior al mismo ancho de la tabla y la separación
del título. La cabecera se repite con posición fija al imprimir. Se mantiene
la espera de carga de la imagen antes de imprimir y la URL incluye hash.
Archivos: resources/views/inscripciones/imprimir.blade.php,
public/css/inscripciones-imprimir.css y certificado.md.
Verificaciones nuevas: imagen local presente e inspeccionada; **3 pruebas
del reporte aprobadas / 36 aserciones**. Compilación y limpieza Blade correctas;
git diff --check sin errores. Sin revisión visual interactiva del reporte ni
impresión física; no se repitió la suite completa por ser un cambio de imagen.

### 17/11/2026: vista de inscripciones compacta

Se subieron los controles y la tabla reduciendo la altura de la barra superior,
el encabezado de página, los márgenes superiores y los espacios del panel.
Se retiró la línea de resultados y tarifas mostrada debajo de Participantes
inscritos. Se conservan el título, evento elegido, filtros, acciones y
paginación. Los estilos sólo se cargan en /inscripciones.
Archivos: resources/views/inscripciones/index.blade.php,
public/css/inscripciones-listado.css y certificado.md.
Verificaciones nuevas: **24 pruebas aprobadas / 285 aserciones** de gestión
e inscripción integrada; compilación y limpieza Blade correctas;
git diff --check sin errores. Sin inspección visual interactiva ni suite
completa adicional por ser cambios de distribución y una línea de texto.

### 17/11/2026: líneas del membrete al ancho de tabla y separación del título

Las líneas del encabezado y del pie del reporte se alinean con los extremos
de la tabla. La línea roja superior se dibuja por CSS y las líneas del pie
se ampliaron al ancho completo del contenido. Se conserva el archivo original
Membrete.png y sus logos. Listado de Inscritos incorpora 2 mm de padding
superior para separarse de la línea roja; se aplica en pantalla e impresión.
Archivos: public/css/inscripciones-imprimir.css,
resources/views/inscripciones/imprimir.blade.php y certificado.md.
Verificaciones nuevas: **3 pruebas del reporte aprobadas / 36 aserciones**
tras alinear las líneas. Después del padding, compilación y limpieza Blade
correctas y git diff --check sin errores. No se añadieron pruebas de estilo
ni se repitió la suite completa por ser cambios reversibles de presentación.
Sin inspección visual interactiva ni impresión física en esta tarea.

### 17/11/2026: costos agrupados del evento y posición de la etiqueta Evento

El listado de eventos y el modal Ver evento muestran los costos agrupados por
categoría, sin repetir los grados numerados. Se comprobó en la base operativa:
Kyu $us 30 (Bs 351), Dan $us 50 (Bs 585) y Jefes de Dojo $us 100 (Bs 1170),
con el tipo de cambio vigente 11,70. El modal conserva los importes en Bs y
el listado conserva tipo de cambio y cantidad de inscripciones. Los precios
de pivotes e importes de pagos no se modificaron. En el filtro de Inscripciones,
la etiqueta Evento tiene un margen izquierdo de 1 rem (16 px) para desplazar
el texto hacia la derecha; el selector conserva su posición y ancho.
Archivos: app/Models/Evento.php, resources/views/eventos/index.blade.php,
resources/views/inscripciones/index.blade.php y certificado.md.
Verificaciones nuevas: **23 pruebas aprobadas / 256 aserciones** de eventos,
monedas y gestión de inscripciones para la agrupación. Consulta de sólo
lectura confirma exactamente tres categorías en el evento operativo.
Compilación/limpieza Blade y git diff --check correctos tras el ajuste de
etiqueta. Sin inspección visual interactiva ni nuevas pruebas para el margen,
por ser un cambio reversible de presentación.

### 17/11/2026: cabecera del reporte y celda Sin costo

El texto de los encabezados de las ocho columnas del reporte aumenta un 30 %,
de 6,5 pt a 8,45 pt, tanto en pantalla como al imprimir. No se amplió el
membrete ni el título Listado de Inscritos. Los invitados sin costo conservan
su cargo y muestran «Sin costo» centrado en una celda que ocupa las tres
columnas de importes; se omite la observación automática debajo del cargo.
No se modificaron cobros, registros ni totales del reporte.
Archivos: public/css/inscripciones-imprimir.css,
resources/views/inscripciones/imprimir.blade.php,
tests/Feature/EventManagementTest.php y certificado.md.
Verificaciones nuevas: **18 pruebas aprobadas / 204 aserciones**, incluyendo
la celda combinada del invitado, reporte y reglas de cobro. Compilación y
limpieza Blade correctas; git diff --check sin errores. Sin revisión visual
interactiva ni impresión física; no se repitió la suite completa por ser
un ajuste de presentación del reporte.

### 17/11/2026: encabezado Costo en el reporte de inscritos

En /inscripciones/imprimir el encabezado de la columna se cambió de Tarifa
a Costo, tanto en pantalla como en impresión. Se conservan importes y cálculos.
Archivos: resources/views/inscripciones/imprimir.blade.php,
tests/Feature/InscripcionesImprimirTest.php y certificado.md.
Verificaciones nuevas tras el cambio: **3 pruebas aprobadas / 36 aserciones**
del reporte; compilación/limpieza Blade y git diff --check correctos.
Sin revisión visual interactiva ni suite completa adicional por ser un texto.

Verificación final adicional de la retirada de nacimiento: **9 pruebas
JavaScript aprobadas**, sin referencias al campo en app, resources y public/js;
compilación/limpieza Blade correctas. Las referencias de migraciones históricas,
la migración de eliminación y las comprobaciones de ausencia en pruebas son
intencionales. Los respaldos históricos no forman parte del esquema activo.

### 17/11/2026: eliminación de la fecha de nacimiento

El proyecto ya no captura, valida, carga ni muestra fecha de nacimiento.
Se retiró de los datos personales, modal del participante, carga JavaScript,
modelo Persona y validación DatosPersona. Las pruebas y sus datos de ejemplo
se adaptaron; teléfono conserva su validación y carácter opcional.
La migración 2026_11_17_000005 elimina la columna de personas y se aplicó en
la base operativa. Se conservaron las migraciones históricas para que una
instalación desde cero o una actualización existente siga siendo reproducible.
La reversión sólo repone una columna vacía, no recupera fechas eliminadas.

Archivos principales: app/Models/Persona.php, app/Support/DatosPersona.php,
app/Http/Controllers/InscripcionController.php, vistas personas/datos e
inscripciones/form y detalle; migración 000005; pruebas PHP de participantes,
inscripciones, pagos, credenciales, certificados, acceso y reporte; pruebas
JavaScript de inscripción y certificado.md.

Verificaciones nuevas: **30 pruebas dirigidas aprobadas / 357 aserciones**.
Suite PHP completa: **84 aprobadas, 2 fallidas / 1018 aserciones**; persisten
las dos expectativas de logos anteriores en AuthenticationTest (Logo.png en
login) y CredencialMovilTest (icono.png en favicon). Se comprobó la ausencia
de la columna y el campo en el formulario y modal. Comparación SHA-256 antes
y después de la migración confirma los otros atributos de las **16 personas**,
las **16 inscripciones** y los **20 pagos** idénticos. No se borraron personas,
inscripciones ni pagos. Sin inspección visual interactiva.

### 17/11/2026: foto inicial de Nueva inscripción

La vista previa del formulario nuevo usa public/images/default.jpg en lugar
de persona-default.svg, tanto en src como en data-original. El cambio de
contexto a participante nuevo también restaura default.jpg. Al elegir una
foto se sigue mostrando el archivo elegido; al editar se conserva la foto
existente. No se modificó el archivo gráfico ni los datos de participantes.
Archivos: resources/views/personas/datos.blade.php,
resources/views/inscripciones/form.blade.php,
tests/JavaScript/inscripciones-invitado.test.cjs y certificado.md.
Verificaciones nuevas: default.jpg presente; **9 pruebas PHP aprobadas / 115
aserciones** de inscripción integrada y **4 pruebas JavaScript aprobadas**.
Compilación/limpieza Blade y git diff --check correctos. Sin revisión visual
interactiva ni nueva suite completa por ser una sustitución de vista previa.
La foto sigue siendo obligatoria para registrar un participante nuevo.

### 17/11/2026: pagos visibles en la moneda recibida

Pagado, en el listado de inscripciones y el modal del participante, muestra
los importes originales agrupados por moneda. Ejemplos: Bs 100,00; $us 20,00;
o Bs 100,00 + $us 20,00 cuando hay pagos mixtos. Sin pagos muestra Bs 0,00.
Se suman monto para BOB y monto_original para USD mediante agregados SQL,
sin consultas por fila. Costo, descuento y saldo permanecen en Bs; el saldo
interno conserva la conversión histórica de cada pago y sus descuentos.
El reporte impreso ya separaba ambas monedas y mantiene ese comportamiento.
No se alteraron pagos, tipos de cambio ni datos operativos.
Archivos: app/Http/Controllers/InscripcionController.php,
resources/views/inscripciones/index.blade.php, detalle.blade.php e
importe-pagado.blade.php; tests/Feature/PagoTest.php y certificado.md.
Verificaciones nuevas: **25 pruebas aprobadas / 316 aserciones** de pagos,
reporte e inscripción integrada. El caso nuevo comprueba pagos sólo USD,
pagos mixtos, ausencia de pagos, importes originales y saldo con descuento.
Compilación/limpieza Blade y git diff --check correctos. Sin inspección visual
interactiva ni repetición de la suite completa; sin cambios de cobro.

### 17/11/2026: separador y bandera en la fila del participante

La tercera línea de Participante ahora muestra «Participante - Bolivia» y,
a continuación, su bandera pequeña de 21 × 14 px. Usa el cargo y país reales
de cada fila. El espacio-guion-espacio separa el cargo del país, y el nombre
del país permanece alineado con su bandera. Se conserva la distribución de
foto y datos personales y se reutiliza Pais::bandera_url. Si no hay una
bandera local configurada, sólo se muestra el país.
Archivos: resources/views/inscripciones/index.blade.php y certificado.md.
Verificación nueva: InscripcionIntegradaTest, **9 pruebas aprobadas / 115
aserciones**; compilación y limpieza Blade correctas; git diff --check sin
errores. Sin nueva prueba visual interactiva ni suite completa por ser un
ajuste reversible de presentación. No se modificaron datos ni tarifas.

La navegación principal y el dashboard usan Inscripciones como entrada para
participantes. Se retiró el enlace independiente Persona. Nueva inscripción y
Editar inscripción incluyen datos personales, foto, grado, evento, cargo,
Jefe de Dojo e Invitado sin costo en un único formulario multipart.
La sección de grado dispone el encabezado a la izquierda y el selector con
el enlace Gestionar grados a la derecha; en pantallas pequeñas se apilan.
Cuando el participante conserva un grado general anterior, se informa el
nombre previo y se exige elegir el grado numerado al guardar la edición.
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
Al abrir Nueva inscripción, public/images/default.jpg se carga como archivo
seleccionado mediante fetch, File y DataTransfer, además de la vista previa.
Se puede sustituir por una foto personal. El servidor guarda una copia de la
imagen predeterminada si el nuevo participante no adjunta otra, incluso si
falla la precarga en el navegador. Se guarda con ruta propia en el disco local
y se elimina en caso de reversión de la transacción. Las fotos existentes se
conservan al editar sin adjuntar otra. La foto cargada se valida como imagen,
con el límite y las dimensiones anteriores. El formulario antiguo de Personas
conserva su requisito de foto al crear. Los datos personales y la foto son compartidos
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

El listado usa una distribución compacta propia: barra superior de 64 px,
6 px sobre el encabezado de página, encabezado de 54 px y 8 px antes del
panel; se reducen los espacios entre filtros, título y tabla. Se eliminó
la línea «N resultados · Tarifas vigentes por grado» por solicitud del usuario.
La paginación y los filtros se conservan. Estos estilos sólo se cargan en
el listado, mediante public/css/inscripciones-listado.css.
El listado único reúne foto ampliable, nombre, sexo, documento, grado, teléfono,
cargo, país y observaciones junto a la foto. En Participante, el nombre va en la
primera línea; debajo, código y sexo seguidos de guion y documento; en la tercera
línea, cargo, espacio-guion-espacio, país y una bandera pequeña a su derecha
(21 × 14 px, sin deformar). El país y su bandera se alinean como un bloque
en línea junto al cargo. Se reutilizan las banderas locales disponibles; si
el país no tiene bandera configurada, se conserva su nombre sin imagen rota.
La columna Organización / Grado muestra
la organización de la persona arriba y su etiqueta de grado debajo; si no tiene
organización, indica Sin organización registrada. El documento no se duplica.
Los importes se agrupan
en una única columna Costo / Pagado / Saldo, en tres líneas etiquetadas.
Pagado muestra Bs y/o $us según las monedas recibidas, separados por « + »
cuando hay pagos mixtos. Costo y Saldo permanecen en Bs. La tabla no muestra una columna Evento;
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
apellidos, documento, sexo, teléfono, grado y organización; también muestra la
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

El reporte muestra la organización debajo del nombre del participante y ya
no incluye la columna Cargo; tiene siete columnas. Si no tiene organización,
indica Sin organización registrada. Un invitado tiene la fila completa
de fondo rojo claro (#f8d7da), también en pantalla. Tiene
prioridad sobre el amarillo de los pagos con saldo cero, aunque conserve
pagos anteriores. Los otros participantes liquidados mantienen el amarillo.
La celda centrada «Sin costo» ocupa Costo, Pagado y Saldo (colspan 3). Se
omite allí la observación automática «invitado sin costo» y los importes cero.
El listado administrativo y el modal conservan sus datos y observaciones.

El formulario de alta y edición de inscripciones ofrece la casilla **Invitado
sin costo** a la derecha de Jefe de Dojo (en móviles se apilan). Al marcarla,
la vista previa muestra tarifa cero y Observaciones, de sólo lectura, muestra
`invitado sin costo`. El servidor guarda esa observación en la inscripción.
La gratuidad tiene prioridad sobre Jefe de Dojo y el grado; no requiere tarifa
para ese grado ni tipo de cambio. Al desmarcarla se quita la observación automática
y vuelven las reglas y validaciones de tarifa de Jefe de Dojo o del grado.

`Inscripcion::tarifa()` devuelve `0.00` para invitados; listado y pagos
muestran costo y saldo pendiente cero, mientras el reporte muestra Sin costo.
La observación aparece en el listado y consulta de pagos. Se oculta el formulario de cobro y el servidor
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

### 17/11/2026: catálogo numerado, tarifas por categoría y pie a 12 puntos

Se reemplazaron Dan, Kyu y Jefe Dojo del catálogo por los 18 grados exactos de
la imagen. Jefe de Dojo queda como opción independiente de inscripción. Se
aplicó la migración 2026_11_17_000004 a la base operativa y se configuraron las
18 tarifas del evento existente: Kyu $us 30, Dan $us 50; Dan con jefatura
$us 100, sin sumar tarifas. Los invitados continúan gratuitos. Los 15
participantes conservan su denominación general hasta editarse, por respuesta
expresa del usuario; no se asignó un número. El pie del reporte pasó a 12 pt.

Archivos principales: app/Support/CatalogoGrados.php, DatosPersona.php,
Models/Grado.php, Persona.php e Inscripcion.php, controladores de inscripciones,
eventos, grados y certificados; migración 000004, DatabaseSeeder, formularios
de personas/eventos/inscripciones, vista imprimir.blade.php,
public/css/inscripciones-imprimir.css y pruebas de catálogo, integración,
certificado, gestión de eventos y vista previa JavaScript.

Verificaciones nuevas de esta tarea:
- Respaldo previo local: storage/app/backups/grados-antes-20261117-153502.json.
- Comparación con ese respaldo: 15 inscripciones y 20 pagos idénticos; 15
  personas conservan todos los demás atributos y su nombre de grado anterior.
  Catálogo operativo comprobado contra la lista exacta de 18 nombres.
- Renderizado Blade con datos operativos de catálogo, inscritos, edición y
  reporte correcto. No fue una inspección visual en navegador.
- Pruebas dirigidas finales: **34 aprobadas / 452 aserciones**. Incluyen
  reemplazo repetible sin perder personas/pagos, conservación de grados sin
  número, elección posterior dentro de categoría, las 18 tarifas con y sin
  jefatura, invitados gratuitos, certificado Sensei y credencial con grado
  numerado, formularios integrados y reporte.
- JavaScript: **9 pruebas aprobadas**, incluida tarifa Kyu con Jefe de Dojo.
- Suite PHP completa ejecutada antes del último caso de certificado numerado:
  **82 aprobadas, 2 fallidas / 1008 aserciones**. Persisten los dos fallos
  anteriores de expectativas de logos: AuthenticationTest espera Logo.png en
  login y CredencialMovilTest prohíbe icono.png pese al favicon vigente.
- Compilación y limpieza Blade correctas; git diff --check sin errores.

Limitaciones: sin inspección visual interactiva ni prueba física de impresora;
no se infieren los números de grados de participantes anteriores. Se conserva
el bloqueo de cambio de categoría con inscripciones existentes. Las rutas
antiguas de gestión de grados aún permiten otros nombres; el formulario
integrado sólo acepta grados de la lista solicitada. La reversión de la
migración reconstruye las referencias generales, pero no restaura tarifas
eliminadas del catálogo: para ello existe el respaldo previo.

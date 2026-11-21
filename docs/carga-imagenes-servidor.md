# Límite de carga de imágenes de eventos

La aplicación permite imágenes de eventos de hasta 10 MB y 12000 × 12000
píxeles. El servidor web y PHP deben aceptar una petición mayor de 10 MB,
porque el formulario añade datos al archivo.

En el bloque `server` de Nginx que atiende el sitio, configurar por ejemplo:

```nginx
client_max_body_size 12m;
```

En el `php.ini` usado por PHP-FPM para ese sitio, configurar como mínimo:

```ini
upload_max_filesize = 10M
post_max_size = 12M
```

Comprobar que no haya un límite menor en un bloque `location` ni en una capa
intermedia. Validar la configuración con `sudo nginx -t` antes de recargar
Nginx y recargar el servicio PHP-FPM correspondiente para que lea sus cambios.
La versión y el nombre del servicio dependen del servidor. Después de desplegar
la aplicación, comprobar una carga real menor o igual a 10 MB y otra mayor a
10 MB. La configuración CLI de PHP no confirma la configuración de PHP-FPM.

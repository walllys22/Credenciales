# Ficha móvil por QR

El QR de cada inscripción abre su ficha (foto, documento, nombre, grado, cargo, teléfono y fechas), sin el panel administrativo. Usa una URL firmada. La imagen del evento y la foto también requieren sus propias firmas.

## Conexión por Internet

Se adapta el método de Kumidibol: Cloudflare Quick Tunnel, gateway PHP local limitado y estado privado del túnel. No requiere que el teléfono esté en la misma red Wi-Fi.

Desde la raíz del proyecto, ejecuta:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/start-mobile-tunnel.ps1
```

El ejecutable oficial `cloudflared.exe` está en `storage/app/private/credencial-tunnel/`. El inicio verifica su firma y el acceso HTTPS antes de guardar `active.json` en esa carpeta. Los procesos se ejecutan sin ventanas visibles. Ejecutar de nuevo reutiliza el túnel si responde; si expiró, inicia uno nuevo. No instala servicios ni modifica el firewall.

Deja `CREDENCIAL_PUBLIC_URL` vacío en `.env` para usar automáticamente el túnel activo. Una URL permanente configurada allí tiene prioridad. El panel conserva `APP_URL`.

Vuelve a abrir la credencial para obtener su QR actualizado. Un túnel temporal nuevo cambia el dominio: las impresiones anteriores deben actualizarse. Mantén el equipo, MySQL, el túnel e Internet activos.

Para detener únicamente los procesos registrados de este proyecto:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/stop-mobile-tunnel.ps1
```

El gateway solo permite GET/HEAD de las fichas firmadas, sus imágenes firmadas, dos hojas CSS y el icono del proyecto. Otras rutas y escrituras se rechazan.

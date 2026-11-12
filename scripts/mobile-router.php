<?php

// Read-only mobile entry point. Does not serve the administration panel.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    exit;
}
// Permite cargar el nuevo fondo de la ficha móvil junto con sus estilos e icono.
$static = ['/css/credenciales.css', '/css/credencial-movil.css', '/images/icono.png', '/images/Ficha1.png'];
if (in_array($path, $static, true)) {
    return false;
}
if (!preg_match('#^/credenciales/[1-9][0-9]*/(?:verificar|foto|imagen)$#D', $path)) {
    http_response_code(404);
    exit('No encontrado');
}
// Honor HTTPS only from the connector running on this computer.
if (in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
    && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = '443';
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';
$_ENV['APP_DEBUG'] = $_SERVER['APP_DEBUG'] = 'false';
putenv('APP_DEBUG=false');
require __DIR__.'/../public/index.php';

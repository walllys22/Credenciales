<?php
/** Creates the configured database if missing. Does not alter existing tables. */
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$config = config('database.connections.mysql');
if (!preg_match('/^[a-zA-Z0-9_]+$/', $config['database'])) {
    fwrite(STDERR, "El nombre de la base solo puede contener letras, números y guion bajo.\n");
    exit(1);
}
try {
    $pdo = new PDO("mysql:host={$config['host']};port={$config['port']};charset=utf8mb4", $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$config['database']}`");
    echo 'Base disponible: '.$config['database'].PHP_EOL;
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo 'Tablas existentes: '.($tables ? implode(', ', $tables) : '(ninguna)').PHP_EOL;
} catch (PDOException $e) {
    fwrite(STDERR, 'No se pudo preparar MySQL: '.$e->getMessage().PHP_EOL);
    exit(1);
}

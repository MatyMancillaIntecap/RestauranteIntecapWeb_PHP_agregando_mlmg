<?php
// Configuración global de la aplicación PHP MVC
/**
 * Configuracion global de la aplicacion PHP MVC.
 *
 * Carga variables de entorno, inicia la sesion y define las rutas y constantes
 * que comparten controladores, servicios y vistas.
 */
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('America/Guatemala');

if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value);

        if ($key === '' || $value === '') {
            continue;
        }

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
        }

        putenv("{$key}={$value}");
        $_SERVER[$key] = $value;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
if ($scriptDir === '.') {
    $scriptDir = '/';
}

$appDir = dirname($scriptDir);
if ($appDir === '.' || $appDir === '\\') {
    $appDir = '/';
}

define('BASE_URL', rtrim(str_replace('\\', '/', $appDir), '/'));
define('ROOT_PATH', dirname(__DIR__));

define('APP_NAME', getenv('APP_NAME') ?: 'Restaurante INTECAP');
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'intecap_proy_rest_m');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

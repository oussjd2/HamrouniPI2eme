<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
require ROOT . '/vendor/autoload.php';
$config = require ROOT . '/config/example.php';
if (is_file(ROOT . '/config/local.php')) {
    $config = array_replace($config, require ROOT . '/config/local.php');
}
// Tests use a separate database; never replace the normal application's database.
if (PHP_SAPI === 'cli' && getenv('LOCAMAT_TEST_DB')) {
    $config['db_name'] = getenv('LOCAMAT_TEST_DB');
}
date_default_timezone_set($config['timezone']);
App\Core\Database::configure($config);
require __DIR__ . '/helpers.php';
if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');
    session_name($config['session_name']);
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'path' => '/']);
    session_start();
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; frame-ancestors 'none'; form-action 'self'; base-uri 'self'");
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store');
}

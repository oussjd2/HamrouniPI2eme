<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/seed.php';

try {
    $dbName=$config['db_name'];
    if (!preg_match('/^[a-zA-Z0-9_]+$/D',$dbName)) { throw new RuntimeException('Nom de base incorrect.'); }
    $server=new PDO("mysql:host={$config['db_host']};port={$config['db_port']};charset=utf8mb4",$config['db_user'],$config['db_password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $server->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo=App\Core\Database::connection();
    if ((int) $pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()')->fetchColumn()!==0) { throw new RuntimeException('Base non vide : installation refusée pour préserver les données existantes.'); }
    $pdo->exec(file_get_contents(ROOT . '/database/schema.sql'));
    seedDemo();
    echo "Installation terminée : $dbName\nComptes : inventaire@locamat.test / agent@locamat.test / client@locamat.test\nMot de passe de démonstration : Demo2026!\n";
} catch (Throwable $e) { fwrite(STDERR,$e->getMessage().PHP_EOL); exit(1); }

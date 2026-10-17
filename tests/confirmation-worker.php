<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
$db=$argv[1] ?? '';
if (!preg_match('/^hamrouni_test_[a-zA-Z0-9_]+$/D',$db)) { exit(2); }
putenv('LOCAMAT_TEST_DB='.$db);
require dirname(__DIR__).'/app/bootstrap.php';
$agent=App\Models\Utilisateur::byEmail('agent@locamat.test');
echo "READY\n"; flush();
try {
    App\Models\Location::action((int)($argv[2] ?? 0),'confirmer',[],$agent);
    echo "CONFIRMED\n"; exit(0);
} catch (DomainException $e) { echo "REJECTED\n"; exit(0); }
catch (Throwable $e) { fwrite(STDERR,(string)$e); exit(1); }

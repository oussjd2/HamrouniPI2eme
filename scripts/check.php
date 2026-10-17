<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
// Use the same interpreter as Apache/XAMPP for every file.
$root=dirname(__DIR__); $errors=0; $count=0;
foreach (['app','public','config','scripts','tests'] as $folder) {
    $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$folder,FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->getExtension()!=='php') { continue; }
        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file->getPathname()).' 2>&1',$output,$exit);
        if ($exit!==0) { echo implode(PHP_EOL,$output).PHP_EOL; $errors++; } $output=[]; $count++;
    }
}
echo "$count fichiers PHP vérifiés; $errors erreur(s).\n";
exit($errors ? 1 : 0);

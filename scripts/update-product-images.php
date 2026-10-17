<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/bootstrap.php';
use App\Core\Database as DB;
try {
    if (DB::query("SHOW COLUMNS FROM equipements LIKE 'image_path'")->fetch()) {
        echo "Photos déjà configurées : aucune donnée modifiée.\n"; exit(0);
    }
    DB::connection()->exec('ALTER TABLE equipements ADD COLUMN image_path VARCHAR(190) NULL AFTER tarif_journalier');
    DB::transaction(static function () {
        foreach (require ROOT.'/config/product_images.php' as $name=>$file) {
            DB::query('UPDATE equipements SET image_path=? WHERE nom=? AND image_path IS NULL',['assets/products/'.$file,$name]);
        }
    });
    echo "Champ photo ajouté et photos des équipements de démonstration associées. Données existantes conservées.\n";
} catch (Throwable $e) { fwrite(STDERR,$e->getMessage().PHP_EOL); exit(1); }

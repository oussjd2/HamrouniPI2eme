<?php
namespace App\Controllers;
use App\Core\{Validation as V,ProductImage,HttpException,View};
use App\Models\Equipement;

final class ImageController {
    public function credits(): void {
        $raw=file_get_contents(ROOT.'/public/assets/products/credits.json');
        View::render('catalogue/credits',['title'=>'Crédits photos','credits'=>json_decode(ltrim($raw,"\xEF\xBB\xBF"),true,512,JSON_THROW_ON_ERROR)]);
    }
    public function show(): void {
        $equipment=Equipement::find(V::integer($_GET,'id','Équipement',1));
        $path=ProductImage::uploadedFile($equipment['image_path'] ?? null);
        if (!$path || !is_file($path)) { throw new HttpException(404,'Photo introuvable.'); }
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (!in_array($mime,['image/jpeg','image/png','image/webp'],true)) { throw new HttpException(404,'Photo introuvable.'); }
        header('Content-Type: '.$mime);
        header('Content-Length: '.filesize($path));
        header('Content-Disposition: inline; filename="equipement-'.(int)$equipment['id'].'.'.pathinfo($path,PATHINFO_EXTENSION).'"');
        readfile($path);
    }
}

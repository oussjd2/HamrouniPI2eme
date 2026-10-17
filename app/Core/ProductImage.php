<?php
namespace App\Core;
use DomainException;

final class ProductImage {
    public const MAX_BYTES = 2 * 1024 * 1024;
    public static function store(?array $file): ?string {
        if ($file===null) { return null; }
        $error=$file['error'] ?? null;
        if ($error===UPLOAD_ERR_NO_FILE) { return null; }
        if ($error===UPLOAD_ERR_INI_SIZE || $error===UPLOAD_ERR_FORM_SIZE) { throw new DomainException('Photo trop volumineuse : maximum 2 Mo.'); }
        if ($error!==UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])) { throw new DomainException('Le transfert de la photo a échoué. Sélectionnez à nouveau le fichier.'); }
        $temporary=$file['tmp_name'];
        $size=filesize($temporary);
        if ($size===false || $size<1 || $size>self::MAX_BYTES) { throw new DomainException('Photo trop volumineuse ou vide : maximum 2 Mo.'); }
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($temporary);
        $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        $dimensions=@getimagesize($temporary);
        if (!isset($extensions[$mime]) || !$dimensions || ($dimensions['mime'] ?? '')!==$mime) { throw new DomainException('Photo incorrecte : utilisez une véritable image JPG, PNG ou WebP.'); }
        if ($dimensions[0]>6000 || $dimensions[1]>6000 || $dimensions[0]*$dimensions[1]>24000000) { throw new DomainException('Photo trop grande : maximum 6 000 pixels par côté et 24 mégapixels.'); }
        $directory=ROOT.'/storage/product-images';
        if (!is_dir($directory) && !mkdir($directory,0775,true) && !is_dir($directory)) { throw new DomainException('Impossible de créer le dossier des photos.'); }
        $name=bin2hex(random_bytes(16)).'.'.$extensions[$mime];
        if (!move_uploaded_file($temporary,$directory.'/'.$name)) { throw new DomainException('Impossible d’enregistrer la photo.'); }
        return 'uploads/'.$name;
    }
    public static function uploadedFile(?string $image): ?string {
        if (!$image || !preg_match('~^uploads/([a-f0-9]{32}\.(?:jpg|png|webp))$~D',$image,$match)) { return null; }
        return ROOT.'/storage/product-images/'.$match[1];
    }
    public static function remove(?string $image): void {
        $file=self::uploadedFile($image);
        if ($file && is_file($file)) { unlink($file); }
    }
    public static function credit(?string $image): ?array {
        if (!$image || !str_starts_with($image,'assets/products/')) { return null; }
        static $credits=null;
        if ($credits===null) {
            $raw=file_get_contents(ROOT.'/public/assets/products/credits.json');
            $credits=json_decode(ltrim($raw,"\xEF\xBB\xBF"),true,512,JSON_THROW_ON_ERROR);
        }
        foreach ($credits as $credit) { if ('assets/products/'.$credit['file']===$image) { return $credit; } }
        return null;
    }
}

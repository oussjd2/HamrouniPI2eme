<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/bootstrap.php';
use App\Core\{Database as DB,ProductImage};
use App\Models\{Equipement,CategorieEquipement};

$base=$argv[1] ?? 'http://localhost/hamrouni/';
if (!preg_match('~^http://(?:localhost|127\.0\.0\.1)(?::\d+)?/[^?]*$~D',$base)) { throw new RuntimeException('Cible locale attendue.'); }
$base=rtrim($base,'/').'/';$tag='photo_'.bin2hex(random_bytes(6));$checks=0;$id=null;$category=null;
$temporary=ROOT.'/storage/'.$tag;mkdir($temporary,0775,true);$jar=$temporary.'/cookies';
function imageCheck(bool $condition,string $message): void { global $checks;if (!$condition) { throw new RuntimeException('ÉCHEC : '.$message); }$checks++;echo "OK $message\n"; }
function imageRequest(string $route,array $query=[],?array $post=null): array {
    global $base,$jar;
    $curl=curl_init($base.'index.php?'.http_build_query(['r'=>$route]+$query));
    curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_COOKIEFILE=>$jar,CURLOPT_COOKIEJAR=>$jar,CURLOPT_TIMEOUT=>30]);
    if ($post!==null) { curl_setopt($curl,CURLOPT_POST,true);curl_setopt($curl,CURLOPT_POSTFIELDS,$post); }
    $raw=curl_exec($curl);if ($raw===false) { throw new RuntimeException(curl_error($curl)); }
    $status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$size=curl_getinfo($curl,CURLINFO_HEADER_SIZE);curl_close($curl);
    return ['status'=>$status,'headers'=>substr($raw,0,$size),'body'=>substr($raw,$size)];
}
function imageToken(): string {
    $response=imageRequest('connexion');preg_match('/name="csrf" value="([a-f0-9]+)"/',$response['body'],$matches);
    if (empty($matches[1])) { throw new RuntimeException('Jeton absent.'); }return $matches[1];
}
function imageSave(array $data,?string $file=null,string $name='photo.jpg',string $type='image/jpeg'): array {
    global $id;
    $data['csrf']=imageToken();if ($file) { $data['photo']=new CURLFile($file,$type,$name); }
    return imageRequest('gestion-form',['entite'=>'equipements','id'=>$id],$data);
}
$exit=0;
try {
    $login=imageRequest('connexion',[],['csrf'=>imageToken(),'email'=>'inventaire@locamat.test','mot_de_passe'=>'Demo2026!']);imageCheck($login['status']===302,'Connexion inventaire pour transfert multipart');
    $category=CategorieEquipement::save(['nom'=>$tag,'description'=>'Test photo'],null);
    $data=['nom'=>$tag,'description'=>'Test','categorie_id'=>$category,'tarif_journalier'=>'12.00','quantite_totale'=>2,'quantite_maintenance'=>0,'quantite_endommagee'=>0,'seuil_alerte'=>0,'image_path'=>'../../config/local.php'];
    $id=Equipement::save($data,null);imageCheck(Equipement::find($id)['image_path']===null,'Chemin de photo fourni en POST ignoré');
    DB::query('UPDATE equipements SET image_path=? WHERE id=?',['assets/products/perceuse.jpg',$id]);
    $response=imageSave($data,ROOT.'/public/assets/products/perceuse.jpg','photo.php.jpg');
    $image=Equipement::find($id)['image_path'];$first=ProductImage::uploadedFile($image);
    imageCheck($response['status']===302 && $first && is_file($first),'Transfert JPG et stockage hors dossier public');
    imageCheck(is_file(ROOT.'/public/assets/products/perceuse.jpg'),'Remplacement conserve les photos de démonstration partagées');
    imageCheck((bool)preg_match('~^uploads/[a-f0-9]{32}\.jpg$~',$image),'Nom aléatoire et extension déduite du contenu');
    $served=imageRequest('image',['id'=>$id]);
    imageCheck($served['status']===200 && stripos($served['headers'],'Content-Type: image/jpeg')!==false && hash('sha256',$served['body'])===hash_file('sha256',$first),'Photo servie avec le bon contenu et type MIME');
    imageSave($data);imageCheck(Equipement::find($id)['image_path']===$image,'Enregistrement sans fichier conserve la photo');
    $png=$temporary.'/valid.png';file_put_contents($png,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6Z04AAAAASUVORK5CYII='));
    imageSave($data,$png,'replacement.png','image/png');$second=ProductImage::uploadedFile(Equipement::find($id)['image_path']);
    imageCheck($second && str_ends_with($second,'.png') && is_file($second) && !file_exists($first),'Remplacement PNG et suppression de l’ancien fichier');
    $invalid=$temporary.'/fake.jpg';file_put_contents($invalid,'<?php echo "not an image";');
    $response=imageSave($data,$invalid);
    imageCheck($response['status']===200 && str_contains($response['body'],'véritable image'),'Faux JPG refusé côté serveur');
    imageCheck(ProductImage::uploadedFile(Equipement::find($id)['image_path'])===$second && is_file($second),'Photo existante conservée après transfert invalide');
    $svg=$temporary.'/vector.svg';file_put_contents($svg,'<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
    imageCheck(str_contains(imageSave($data,$svg,'vector.svg','image/svg+xml')['body'],'véritable image'),'SVG actif refusé');
    $large=$temporary.'/large.jpg';file_put_contents($large,file_get_contents(ROOT.'/public/assets/products/perceuse.jpg').str_repeat('x',ProductImage::MAX_BYTES));
    imageCheck(str_contains(imageSave($data,$large)['body'],'maximum 2 Mo'),'Fichier supérieur à 2 Mo refusé côté serveur');
    $wrong=$data;$wrong['quantite_totale']='-1';$filesBefore=glob(ROOT.'/storage/product-images/*') ?: [];
    imageSave($wrong,ROOT.'/public/assets/products/perceuse.jpg');
    imageCheck(count(glob(ROOT.'/storage/product-images/*') ?: [])===count($filesBefore),'Erreur métier sans fichier orphelin');
    $remove=$data;$remove['remove_photo']='1';imageSave($remove);
    imageCheck(Equipement::find($id)['image_path']===null && !file_exists($second),'Suppression de la photo et nettoyage du fichier');
    imageCheck(str_contains(imageRequest('equipement',['id'=>$id])['body'],'assets/product-placeholder.svg'),'Image de remplacement pour produit sans photo');
    $webp=$temporary.'/valid.webp';file_put_contents($webp,base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA'));
    imageSave($data,$webp,'photo.webp','image/webp');$last=ProductImage::uploadedFile(Equipement::find($id)['image_path']);
    imageCheck($last && str_ends_with($last,'.webp') && is_file($last),'Transfert WebP accepté');
    imageCheck(imageRequest('image',['id'=>$id])['status']===200,'WebP servi par la route photo');
    unlink($last);imageCheck(productImage(Equipement::find($id))==='assets/product-placeholder.svg','Fichier manquant remplacé sans image cassée');
    imageSave($data,ROOT.'/public/assets/products/perceuse.jpg');$last=ProductImage::uploadedFile(Equipement::find($id)['image_path']);
    Equipement::delete($id);$id=null;imageCheck(!file_exists($last),'Suppression équipement nettoie sa photo');
    imageCheck(imageRequest('image',['id'=>99999])['status']===404,'Photo absente : 404');
    imageCheck(imageRequest('credits-photos')['status']===200,'Crédits accessibles au public');
    $seeded=DB::query("SELECT COUNT(*) FROM equipements WHERE image_path LIKE 'assets/products/%'")->fetchColumn();
    imageCheck((int)$seeded>=7,'Sept équipements de démonstration illustrés');
    echo "\n$checks contrôles photos réussis.\n";
} catch (Throwable $e) { fwrite(STDERR,(string)$e.PHP_EOL);$exit=1; }
finally {
    if ($id) { Equipement::delete($id); }
    if ($category) { CategorieEquipement::delete($category); }
    foreach (glob($temporary.'/*') ?: [] as $file) { unlink($file); }rmdir($temporary);
}
exit($exit);

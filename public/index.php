<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\{Auth,View,HttpException};
use App\Controllers\{AuthController,CatalogueController,ManagementController,LocationController,DocumentController};

$routes=[
    'catalogue'=>[CatalogueController::class,'index',['GET']],
    'equipement'=>[CatalogueController::class,'show',['GET']],
    'connexion'=>[AuthController::class,'login',['GET','POST']],
    'inscription'=>[AuthController::class,'register',['GET','POST']],
    'deconnexion'=>[AuthController::class,'logout',['POST']],
    'gestion'=>[ManagementController::class,'index',['GET']],
    'gestion-form'=>[ManagementController::class,'form',['GET','POST']],
    'gestion-detail'=>[ManagementController::class,'show',['GET']],
    'gestion-delete'=>[ManagementController::class,'delete',['POST']],
    'locations'=>[LocationController::class,'index',['GET']],
    'location-form'=>[LocationController::class,'form',['GET','POST']],
    'location'=>[LocationController::class,'show',['GET']],
    'location-action'=>[LocationController::class,'action',['POST']],
    'location-delete'=>[LocationController::class,'delete',['POST']],
    'document'=>[DocumentController::class,'download',['GET']],
];
try {
    $route=$_GET['r'] ?? 'catalogue';
    if (!is_string($route) || !isset($routes[$route])) { throw new HttpException(404,'Page introuvable.'); }
    [$class,$method,$verbs]=$routes[$route];
    if (!in_array($_SERVER['REQUEST_METHOD'],$verbs,true)) { header('Allow: ' . implode(', ',$verbs)); throw new HttpException(405,'Méthode non autorisée.'); }
    if ($_SERVER['REQUEST_METHOD']==='POST') { Auth::checkCsrf(); }
    (new $class())->$method();
} catch (HttpException $e) {
    View::render('error',['title'=>'Erreur ' . $e->status,'message'=>$e->getMessage()],$e->status);
} catch (DomainException $e) {
    View::render('error',['title'=>'Vérifiez votre saisie','message'=>$e->getMessage()],422);
} catch (Throwable $e) {
    if (!is_dir(ROOT . '/storage/logs')) { mkdir(ROOT . '/storage/logs',0775,true); }
    error_log(date('c') . ' ' . $e . PHP_EOL,3,ROOT . '/storage/logs/application.log');
    View::render('error',['title'=>'Erreur technique','message'=>'L’opération n’a pas pu être effectuée. Réessayez ou contactez le responsable.'],500);
}

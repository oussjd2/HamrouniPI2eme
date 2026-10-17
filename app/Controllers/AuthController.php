<?php
namespace App\Controllers;
use App\Core\{Auth,View,HttpException};
use App\Models\Utilisateur;
use DomainException;

final class AuthController {
    public function login(): void {
        $error=null;
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $attempts=$_SESSION['login_attempts'] ?? [];
            $attempts=array_values(array_filter($attempts,static fn($time)=>$time>time()-300));
            if (count($attempts)>=10) { $error='Trop de tentatives. Réessayez dans cinq minutes.'; }
            else {
                $email=is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
                $password=is_string($_POST['mot_de_passe'] ?? null) ? $_POST['mot_de_passe'] : '';
                $account=Utilisateur::byEmail($email);
                if ($account && password_verify($password,$account['mot_de_passe'])) { Auth::login($account); unset($_SESSION['login_attempts']); \redirect('catalogue'); }
                $attempts[]=time(); $_SESSION['login_attempts']=$attempts; $error='E-mail ou mot de passe incorrect.';
            }
        }
        View::render('auth/login',['title'=>'Connexion','error'=>$error]);
    }
    public function register(): void {
        $error=null;
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            try { Utilisateur::save($_POST,null,'client'); \flash('Votre compte a été créé. Vous pouvez vous connecter.'); \redirect('connexion'); }
            catch (DomainException $e) { $error=$e->getMessage(); }
        }
        View::render('auth/register',['title'=>'Créer un compte','error'=>$error]);
    }
    public function logout(): void {
        $_SESSION=[];
        setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
        session_destroy(); \redirect('connexion');
    }
}

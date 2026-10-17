<?php
namespace App\Controllers;
use App\Core\{Auth,View,Validation as V};
use App\Models\{Location,Equipement,Utilisateur};
use DomainException;

final class LocationController {
    public function index(): void {
        $actor=Auth::requireRoles(['client','agent','inventaire']);
        View::render('locations/index',['title'=>$actor['role']==='client' ? 'Mes locations' : 'Locations','rows'=>Location::all($actor,$_GET)]);
    }
    public function form(): void {
        $actor=Auth::requireRoles(['client','agent']);
        $id=!empty($_GET['id']) ? V::integer($_GET,'id','Location',1) : null;
        $row=$id ? Location::accessible($id,$actor) : ['equipement_id'=>$_GET['equipement_id'] ?? '', 'quantite'=>1,'date_debut'=>date('Y-m-d'),'date_fin'=>date('Y-m-d')];
        if ($id && $row['statut']!=='en_attente') { throw new DomainException('Cette location ne peut plus être modifiée.'); }
        $error=null;
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            try { $savedId=Location::save($_POST,$id,$actor); \flash('Demande enregistrée. La confirmation par l’agent réservera le stock.'); \redirect('location',['id'=>$savedId]); }
            catch (DomainException $e) { $error=$e->getMessage(); $row=$_POST+$row; }
        }
        View::render('locations/form',['title'=>$id ? 'Modifier la demande' : 'Nouvelle demande','row'=>$row,'id'=>$id,'error'=>$error,'equipements'=>Equipement::all(),'clients'=>Utilisateur::all(true)]);
    }
    public function show(): void {
        $actor=Auth::requireRoles(['client','agent','inventaire']);
        $row=Location::accessible(V::integer($_GET,'id','Location',1),$actor);
        $returnInput=[];
        if ((int) ($_SESSION['return_input']['id'] ?? 0)===(int) $row['id']) {
            $returnInput=$_SESSION['return_input']['data'];
            unset($_SESSION['return_input']);
        }
        View::render('locations/show',['title'=>'Location n°' . $row['id'],'row'=>$row,'returnInput'=>$returnInput]);
    }
    public function action(): void {
        $actor=Auth::requireRoles(['client','agent','inventaire']); $id=V::integer($_POST,'id','Location',1);
        $action=is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
        unset($_SESSION['return_input']);
        try { Location::action($id,$action,$_POST,$actor); \flash('Location mise à jour.'); }
        catch (DomainException $e) {
            if (in_array($action,['retour','valider_retour'],true)) {
                $input=[];
                foreach (['date_retour','frais_additionnels','retour_maintenance','retour_endommage','remarques_retour'] as $key) {
                    if (is_string($_POST[$key] ?? null)) { $input[$key]=mb_substr($_POST[$key],0,2000); }
                }
                $_SESSION['return_input']=['id'=>$id,'data'=>$input];
            }
            \flash($e->getMessage(),'error');
        }
        \redirect('location',['id'=>$id]);
    }
    public function delete(): void {
        $actor=Auth::requireRoles(['client','agent']);
        try { Location::delete(V::integer($_POST,'id','Location',1),$actor); \flash('Demande supprimée.'); }
        catch (DomainException $e) { \flash($e->getMessage(),'error'); }
        \redirect('locations');
    }
}

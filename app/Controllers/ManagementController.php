<?php
namespace App\Controllers;
use App\Core\{Auth,View,Validation as V,HttpException};
use App\Models\{Utilisateur,CategorieEquipement,Equipement};
use DomainException;

final class ManagementController {
    private function context(): array {
        $entity=$_GET['entite'] ?? '';
        $map=['utilisateurs'=>[Utilisateur::class,'Utilisateurs'],'categories'=>[CategorieEquipement::class,'Catégories'],'equipements'=>[Equipement::class,'Équipements']];
        if (!is_string($entity) || !isset($map[$entity])) { throw new HttpException(404,'Section introuvable.'); }
        $actor=Auth::requireRoles($entity==='utilisateurs' ? ['inventaire','agent'] : ['inventaire']);
        return [$entity,$map[$entity][0],$map[$entity][1],$actor];
    }
    public function index(): void {
        [$entity,$class,$title,$actor]=$this->context();
        $rows=$entity==='utilisateurs' ? $class::all($actor['role']==='agent') : ($entity==='equipements' ? $class::all($_GET) : $class::all());
        View::render('management/index',compact('entity','title','rows')+['categories'=>CategorieEquipement::all()]);
    }
    public function form(): void {
        [$entity,$class,$title,$actor]=$this->context();
        $id=!empty($_GET['id']) ? V::integer($_GET,'id','Identifiant',1) : null;
        $row=$id ? $class::find($id) : [];
        if ($id && !$row) { throw new HttpException(404,'Élément introuvable.'); }
        if ($entity==='utilisateurs' && $row && $actor['role']==='agent' && $row['role']!=='client') { throw new HttpException(403,'Ce compte n’est pas un compte client.'); }
        $error=null;
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            try {
                if ($entity==='utilisateurs') { $class::save($_POST,$id,$actor['role'],(int) $actor['id']); }
                else { $class::save($_POST,$id); }
                \flash('Enregistrement effectué.'); \redirect('gestion',['entite'=>$entity]);
            } catch (DomainException $e) { $error=$e->getMessage(); $row=$_POST+($row ?: []); }
        }
        $title=($id ? 'Modifier' : 'Ajouter') . ' — ' . $title;
        View::render('management/form',compact('entity','title','row','id','error')+['categories'=>CategorieEquipement::all()]);
    }
    public function show(): void {
        [$entity,$class,$title,$actor]=$this->context();
        $id=V::integer($_GET,'id','Identifiant',1); $row=$class::find($id);
        if (!$row) { throw new HttpException(404,'Élément introuvable.'); }
        if ($entity==='utilisateurs' && $actor['role']==='agent' && $row['role']!=='client') { throw new HttpException(403,'Ce compte n’est pas un compte client.'); }
        View::render('management/show',compact('entity','title','row','id'));
    }
    public function delete(): void {
        [$entity,$class,$title,$actor]=$this->context(); $id=V::integer($_POST,'id','Identifiant',1);
        try {
            if ($entity==='utilisateurs') { $class::delete($id,$actor['role'],(int) $actor['id']); }
            else { $class::delete($id); }
            \flash('Suppression effectuée.');
        } catch (DomainException $e) { \flash($e->getMessage(),'error'); }
        \redirect('gestion',['entite'=>$entity]);
    }
}

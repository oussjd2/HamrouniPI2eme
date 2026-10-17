<?php
namespace App\Controllers;
use App\Core\{View,Validation as V,HttpException};
use App\Models\{Equipement,CategorieEquipement};
final class CatalogueController {
    public function index(): void {
        View::render('catalogue/index',['title'=>'Catalogue des équipements','equipements'=>Equipement::all($_GET),'categories'=>CategorieEquipement::all()]);
    }
    public function show(): void {
        $equipment=Equipement::find(V::integer($_GET,'id','Équipement',1));
        if (!$equipment) { throw new HttpException(404,'Équipement introuvable.'); }
        $equipment['disponible']=Equipement::available($equipment,date('Y-m-d'),date('Y-m-d'));
        View::render('catalogue/show',['title'=>$equipment['nom'],'equipment'=>$equipment]);
    }
}

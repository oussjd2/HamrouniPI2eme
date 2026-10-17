<?php
use App\Core\Database as DB;

function seedDemo(): void {
    DB::transaction(static function () {
        foreach ([['Ben Ali','Sarra','inventaire'],['Hamrouni','Mohamed','agent'],['Mansour','Amine','client']] as [$nom,$prenom,$role]) {
            DB::query('INSERT INTO utilisateurs(nom,prenom,email,mot_de_passe,role) VALUES(?,?,?,?,?)',[$nom,$prenom,$role.'@locamat.test',password_hash('Demo2026!',PASSWORD_DEFAULT),$role]);
        }
        foreach ([['Bricolage','Outils pour travaux et petits chantiers.'],['Audiovisuel','Projection, son et prise de vue.'],['Événementiel','Matériel pour réunions et événements.']] as $category) { DB::query('INSERT INTO categories_equipement(nom,description) VALUES(?,?)',$category); }
        $products=[
            [1,'Perceuse à percussion','Perceuse avec coffret et jeu de forets pour les travaux courants.','15.00',10,1,1,2],
            [1,'Ponceuse électrique','Ponceuse compacte avec bac de récupération des poussières.','12.00',6,0,0,1],
            [2,'Vidéoprojecteur','Projecteur Full HD fourni avec câble HDMI et housse de transport.','45.00',5,1,0,1],
            [2,'Enceinte amplifiée','Enceinte portable avec alimentation et câble audio.','30.00',8,0,1,2],
            [2,'Appareil photo','Appareil photo avec objectif standard, batterie et chargeur.','40.00',4,0,0,1],
            [3,'Table pliante','Table pliante pour réunions et événements, à restituer propre.','8.00',20,0,0,4],
            [3,'Microphone sans fil','Microphone avec récepteur, alimentation et pochette.','18.00',3,1,0,2],
        ];
        $photos=require ROOT.'/config/product_images.php';
        foreach ($products as $product) {
            $product[]='assets/products/'.$photos[$product[1]];
            DB::query('INSERT INTO equipements(categorie_id,nom,description,tarif_journalier,quantite_totale,quantite_maintenance,quantite_endommagee,seuil_alerte,image_path) VALUES(?,?,?,?,?,?,?,?,?)',$product);
        }
    });
}

<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
$testDb='hamrouni_test_'.date('Ymd_His').'_'.bin2hex(random_bytes(3));
putenv('LOCAMAT_TEST_DB='.$testDb);
require dirname(__DIR__).'/app/bootstrap.php';
require ROOT.'/scripts/seed.php';

use App\Core\{Database as DB,Validation as V,HttpException};
use App\Models\{Utilisateur as U,CategorieEquipement as C,Equipement as E,Location as L};
use App\Controllers\DocumentController;

$checks=0;
function check(bool $condition,string $message): void { global $checks; if (!$condition) { throw new RuntimeException('ÉCHEC : '.$message); } $checks++; echo "OK $message\n"; }
function rejects(callable $work,string $message): void {
    try { $work(); } catch (DomainException | HttpException $e) { check(true,$message); return; }
    throw new RuntimeException('ÉCHEC : '.$message.' (exception attendue)');
}
$server=new PDO("mysql:host={$config['db_host']};port={$config['db_port']};charset=utf8mb4",$config['db_user'],$config['db_password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$server->exec("CREATE DATABASE `$testDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$exit=0;
try {
    DB::connection()->exec(file_get_contents(ROOT.'/database/schema.sql')); seedDemo();
    check(count(DB::query('SHOW TABLES')->fetchAll())===4,'Installation propre : quatre tables');
    $manager=U::byEmail('inventaire@locamat.test'); $agent=U::byEmail('agent@locamat.test'); $client=U::byEmail('client@locamat.test');
    check(password_verify('Demo2026!',$client['mot_de_passe']),'Mots de passe hachés');
    $u=['nom'=>'Test','prenom'=>'Client','email'=>'integration@locamat.test','mot_de_passe'=>'Password123!','role'=>'inventaire'];
    $uid=U::save($u,null,'client'); check(U::find($uid)['role']==='client','Inscription sans élévation de privilèges');
    rejects(fn()=>U::save($u,null,'client'),'E-mail unique');
    rejects(fn()=>U::save(array_replace($u,['email'=>'incorrect']),null,'client'),'Validation e-mail');
    rejects(fn()=>U::save(array_replace($u,['email'=>'short@locamat.test','mot_de_passe'=>'123']),null,'client'),'Validation mot de passe');
    rejects(fn()=>U::save(array_replace($manager,['role'=>'agent']), (int)$manager['id'],'inventaire',(int)$manager['id']),'Protection du dernier responsable et du propre rôle');
    rejects(fn()=>U::delete((int)$manager['id'],'agent',(int)$agent['id']),'Agent ne supprime pas le responsable');
    $u['email']='integration.updated@locamat.test'; $u['mot_de_passe']=''; U::save($u,$uid,'agent');
    check(U::find($uid)['email']===$u['email'],'Modification utilisateur');
    check(count(U::all(true))===2,'Liste des clients filtrée');
    $other=U::find($uid);
    $cid=C::save(['nom'=>'Test catégorie','description'=>'Texte'],null);
    C::save(['nom'=>'Test catégorie modifiée','description'=>'Suite'],$cid);
    check(C::find($cid)['description']==='Suite','CRUD catégorie : création, lecture, modification');
    rejects(fn()=>C::save(['nom'=>'Test catégorie modifiée','description'=>''],null),'Nom catégorie unique');
    $product=['categorie_id'=>$cid,'nom'=>'Produit de test','description'=>'Test de capacité','tarif_journalier'=>'10.25','quantite_totale'=>10,'quantite_maintenance'=>1,'quantite_endommagee'=>1,'seuil_alerte'=>2];
    $eid=E::save($product,null); $equipment=E::find($eid);
    check(E::available($equipment,date('Y-m-d'),date('Y-m-d'))===8,'Stock : retrait maintenance et endommagé');
    rejects(fn()=>E::save(array_replace($product,['quantite_totale'=>1]),$eid),'Stock incohérent refusé');
    rejects(fn()=>E::save(array_replace($product,['tarif_journalier'=>'-10']),$eid),'Tarif négatif refusé');
    rejects(fn()=>E::save(array_replace($product,['quantite_totale'=>'1.5']),$eid),'Quantité décimale refusée');
    rejects(fn()=>C::delete($cid),'Catégorie liée protégée');
    check(count(E::all(['q'=>'Produit de test','categorie_id'=>$cid,'prix_min'=>'10','prix_max'=>'11','etat'=>'disponible']))===1,'Recherche multicritères avec jointure');
    check(count(E::all(['q'=>"' OR 1=1 --"]))===0,'Recherche préparée contre injection SQL');
    check(count(E::all(['q'=>'Produit de test','etat'=>'maintenance']))===1,'Filtre état maintenance');
    $today=date('Y-m-d'); $tomorrow=date('Y-m-d',strtotime('+1 day'));
    $request=['client_id'=>$client['id'],'equipement_id'=>$eid,'quantite'=>3,'date_debut'=>$today,'date_fin'=>$tomorrow];
    $r=L::save($request,null,$client);
    check(L::find($r)['statut']==='en_attente','Création de demande');
    check(E::available($equipment,$today,$tomorrow)===8,'Demande en attente sans réservation');
    L::save(array_replace($request,['quantite'=>4]),$r,$client);
    check((int)L::find($r)['quantite']===4,'Modification de demande');
    rejects(fn()=>L::accessible($r,$other),'Protection des locations privées');
    rejects(fn()=>L::action($r,'confirmer',[],$client),'Client ne confirme pas une location');
    rejects(fn()=>L::save($request,null,$manager),'Responsable ne crée pas de location');
    rejects(fn()=>L::save(array_replace($request,['date_fin'=>date('Y-m-d',strtotime('-1 day'))]),null,$client),'Dates inversées refusées');
    rejects(fn()=>L::save(array_replace($request,['date_debut'=>'2026-02-30']),null,$client),'Date inexistante refusée');
    rejects(fn()=>L::save(array_replace($request,['quantite'=>9]),null,$client),'Quantité indisponible refusée');
    L::action($r,'confirmer',[],$agent);
    check(E::available($equipment,$today,$tomorrow)===4,'Confirmation réserve la quantité');
    rejects(fn()=>L::save($request,$r,$client),'Modification après confirmation refusée');
    rejects(fn()=>L::delete($r,$agent),'Historique confirmé protégé');
    rejects(fn()=>E::delete($eid),'Équipement lié protégé');
    rejects(fn()=>U::delete((int)$client['id'],'inventaire',(int)$manager['id']),'Client lié protégé');
    rejects(fn()=>E::save(array_replace($product,['quantite_totale'=>5]),$eid),'Réduction stock incompatible refusée');
    check(L::totalCents(L::find($r))===8200,'Prix quantité × jours inclusifs × tarif, centimes exacts');
    $html=DocumentController::html(L::find($r),'contrat'); check(strpos($html,'Contrat de location')!==false,'Contrat disponible après confirmation');
    rejects(fn()=>DocumentController::html(L::find($r),'recu'),'Reçu bloqué sans paiement');
    E::save(array_replace($product,['tarif_journalier'=>'20.00','nom'=>'Nouveau nom']),$eid);
    $html=DocumentController::html(L::find($r),'facture');
    check(strpos($html,'Produit de test')!==false && L::find($r)['tarif_applique']==='10.25','Identité et tarif du document figés');
    L::action($r,'depart',[],$agent); check(L::find($r)['statut']==='en_cours','Enregistrement départ');
    rejects(fn()=>L::action($r,'annuler',[],$client),'Annulation après départ refusée');
    rejects(fn()=>L::action($r,'retour',['date_retour'=>$today,'retour_maintenance'=>3,'retour_endommage'=>2,'frais_additionnels'=>0],$agent),'Quantités de retour incohérentes refusées');
    L::action($r,'retour',['date_retour'=>$today,'retour_maintenance'=>1,'retour_endommage'=>1,'frais_additionnels'=>'7.50','remarques_retour'=>'Câble endommagé.'],$agent);
    check(L::find($r)['statut']==='retour_a_controler','Retour à contrôler');
    check(E::available(E::find($eid),date('Y-m-d',strtotime('+10 days')),date('Y-m-d',strtotime('+10 days')))===4,'Retour non contrôlé bloque les réservations futures');
    rejects(fn()=>L::action($r,'payer',[],$agent),'Paiement avant clôture refusé');
    rejects(fn()=>L::action($r,'valider_retour',['retour_maintenance'=>1,'retour_endommage'=>1],$agent),'Inspection réservée à l’inventaire');
    L::action($r,'valider_retour',['retour_maintenance'=>1,'retour_endommage'=>1,'remarques_retour'=>'Contrôle validé.'],$manager);
    $closed=L::find($r); $equipment=E::find($eid);
    check($closed['statut']==='terminee' && (int)$equipment['quantite_maintenance']===2 && (int)$equipment['quantite_endommagee']===2,'Clôture et mise à jour des états de stock');
    check(E::available($equipment,$today,$tomorrow)===6,'Unités en bon état libérées');
    check(L::totalCents($closed)===8950,'Ajout exact des frais de retour');
    L::action($r,'payer',[],$agent); $paid=L::find($r);
    check($paid['montant_paye']==='89.50' && $paid['date_paiement']!==null,'Paiement intégral sauvegardé');
    rejects(fn()=>L::action($r,'payer',[],$agent),'Double paiement refusé');
    foreach (['contrat','facture','recu'] as $type) {
        $pdf=DocumentController::pdf($paid,$type);
        check(str_starts_with($pdf->output(),'%PDF-') && strlen($pdf->output())>1000,'Génération PDF '.$type);
    }
    // Non-overlapping reservations must not be summed together.
    $product['tarif_journalier']='10.25'; $product['quantite_totale']=10; $product['quantite_maintenance']=0; $product['quantite_endommagee']=0; E::save($product,$eid);
    $first=array_replace($request,['quantite'=>8,'date_debut'=>date('Y-m-d',strtotime('+10 days')),'date_fin'=>date('Y-m-d',strtotime('+11 days'))]);
    $second=array_replace($request,['quantite'=>8,'date_debut'=>date('Y-m-d',strtotime('+12 days')),'date_fin'=>date('Y-m-d',strtotime('+13 days'))]);
    $a=L::save($first,null,$agent); $b=L::save($second,null,$agent); L::action($a,'confirmer',[],$agent); L::action($b,'confirmer',[],$agent);
    check(E::available(E::find($eid),$first['date_debut'],$second['date_fin'])===2,'Capacité maximale simultanée, réservations successives');
    $pending=L::save(array_replace($first,['quantite'=>2]),null,$client);
    $pending2=L::save(array_replace($first,['quantite'=>2]),null,$client);
    L::action($pending,'confirmer',[],$agent);
    rejects(fn()=>L::action($pending2,'confirmer',[],$agent),'Confirmation revérifie le stock');
    L::action($pending2,'refuser',[],$agent); L::delete($pending2,$agent);
    check(L::find($pending2)===null,'Suppression de demande refusée');
    L::action($pending,'annuler',[],$client); check(L::find($pending)['statut']==='annulee','Annulation par propriétaire avant départ'); L::delete($pending,$agent);
    $temporary=L::save($request,null,$client); L::delete($temporary,$client); check(L::find($temporary)===null,'Suppression de demande par client');
    $returnOnBoundary=L::save(array_replace($request,['date_debut'=>$second['date_fin'],'date_fin'=>$second['date_fin'],'quantite'=>2]),null,$client); L::action($returnOnBoundary,'confirmer',[],$agent);
    check(E::available(E::find($eid),$second['date_fin'],$second['date_fin'])===0,'Date de fin inclusive dans les chevauchements');
    check(count(E::all(['q'=>'Produit de test','date_debut'=>$second['date_fin'],'date_fin'=>$second['date_fin'],'alertes'=>1]))===1,'Alerte de stock sur seuil atteint');
    rejects(fn()=>L::action($a,'depart',[],$agent),'Départ anticipé refusé');
    check(count(L::all($client))>0 && count(L::all($other))===0,'Listes limitées au propriétaire');
    $disposable=E::save(array_replace($product,['nom'=>'Jetable']),null); E::delete($disposable); check(E::find($disposable)===null,'Suppression équipement sans historique');
    $tempCategory=C::save(['nom'=>'À supprimer','description'=>''],null); C::delete($tempCategory); check(C::find($tempCategory)===null,'Suppression catégorie vide');
    U::delete($uid,'inventaire',(int)$manager['id']); check(U::find($uid)===null,'Suppression utilisateur sans historique');
    rejects(fn()=>V::cents('1.999'),'Montant à trois décimales refusé');
    rejects(fn()=>V::integer(['x'=>[]],'x','Quantité'),'Paramètre tableau malformé refusé');
    // An overdue departure holds stock until the return is checked.
    $overdueEquipment=E::save(array_replace($product,['nom'=>'Retard','quantite_totale'=>2]),null);
    $overdue=L::save(array_replace($request,['equipement_id'=>$overdueEquipment,'quantite'=>2,'date_fin'=>$today]),null,$client);
    L::action($overdue,'confirmer',[],$agent); L::action($overdue,'depart',[],$agent);
    $yesterday=date('Y-m-d',strtotime('-1 day'));
    DB::query('UPDATE locations SET date_debut=?,date_fin=? WHERE id=?',[$yesterday,$yesterday,$overdue]);
    check(E::available(E::find($overdueEquipment),$today,date('Y-m-d',strtotime('+30 days')))===0,'Retard non rendu bloque le stock futur');
    // Two separate PHP processes attempt to confirm the same remaining capacity.
    $raceEquipment=E::save(array_replace($product,['nom'=>'Concurrence','quantite_totale'=>2]),null);
    $raceRequest=array_replace($request,['equipement_id'=>$raceEquipment,'quantite'=>2,'date_fin'=>$today]);
    $raceA=L::save($raceRequest,null,$client); $raceB=L::save($raceRequest,null,$client);
    $pdo=DB::connection(); $pdo->beginTransaction(); E::find($raceEquipment,true);
    $processes=[];
    try {
        foreach ([$raceA,$raceB] as $raceId) {
            $pipes=[];
            $process=proc_open([PHP_BINARY,ROOT.'/tests/confirmation-worker.php',$testDb,(string)$raceId],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,ROOT);
            if (!is_resource($process)) { throw new RuntimeException('Impossible de lancer le test concurrent.'); }
            fclose($pipes[0]); $processes[]=[$process,$pipes];
        }
        foreach ($processes as [$process,$pipes]) { if (trim(fgets($pipes[1]))!=='READY') { throw new RuntimeException('Processus concurrent non prêt.'); } }
        usleep(250000); $pdo->commit();
        $outputs=[];
        foreach ($processes as [$process,$pipes]) {
            $outputs[]=trim(stream_get_contents($pipes[1])); $errors=stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            if (proc_close($process)!==0) { throw new RuntimeException('Échec du processus concurrent : '.$errors); }
        }
        sort($outputs);
        check($outputs===['CONFIRMED','REJECTED'],'Deux confirmations concurrentes : une seule acceptée');
        check((int)DB::query("SELECT SUM(quantite) FROM locations WHERE equipement_id=? AND statut='confirmee'",[$raceEquipment])->fetchColumn()===2,'Aucun dépassement de stock après concurrence');
    } finally {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
    }
    echo "\n$checks contrôles réussis. Base isolée : $testDb\n";
} catch (Throwable $e) { fwrite(STDERR,(string)$e.PHP_EOL); $exit=1; }
finally {
    if (!preg_match('/^hamrouni_test_[0-9_]+[a-f0-9]+$/D',$testDb)) { throw new RuntimeException('Protection de nettoyage.'); }
    $server->exec("DROP DATABASE `$testDb`");
}
exit($exit);

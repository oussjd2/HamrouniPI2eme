<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/bootstrap.php';
use App\Core\Database as DB;

$base=$argv[1] ?? 'http://localhost/hamrouni/';
if (!preg_match('~^http://(?:localhost|127\.0\.0\.1)(?::\d+)?/[^?]*$~D',$base)) { throw new RuntimeException('Les tests HTTP doivent cibler une installation locale.'); }
$base=rtrim($base,'/').'/'; $tag='qa_'.bin2hex(random_bytes(6)); $checks=0;
function verify(bool $condition,string $message): void { global $checks; if (!$condition) { throw new RuntimeException('ÉCHEC HTTP : '.$message); } $checks++; echo "OK $message\n"; }
final class HttpSession {
    private string $jar; private string $base;
    public function __construct(string $base,string $name) { $this->base=$base; $this->jar=ROOT.'/storage/'.$name.'.cookies'; }
    public function request(string $route,array $query=[],?array $post=null): array {
        $curl=curl_init($this->base.'index.php?'.http_build_query(['r'=>$route]+$query));
        curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_COOKIEFILE=>$this->jar,CURLOPT_COOKIEJAR=>$this->jar,CURLOPT_TIMEOUT=>30,CURLOPT_FOLLOWLOCATION=>false]);
        if ($post!==null) { curl_setopt($curl,CURLOPT_POST,true); curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($post)); }
        $raw=curl_exec($curl);
        if ($raw===false) { throw new RuntimeException(curl_error($curl)); }
        $status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE); $size=curl_getinfo($curl,CURLINFO_HEADER_SIZE); curl_close($curl);
        return ['status'=>$status,'headers'=>substr($raw,0,$size),'body'=>substr($raw,$size)];
    }
    public function token(string $route='catalogue',array $query=[]): string {
        $response=$this->request($route,$query);
        if (!preg_match('/name="csrf" value="([a-f0-9]+)"/',$response['body'],$match)) { throw new RuntimeException('Jeton introuvable : '.$route.' '.$response['status']); }
        return $match[1];
    }
    public function post(string $route,array $data=[],array $query=[],string $tokenRoute='catalogue'): array { return $this->request($route,$query,['csrf'=>$this->token($tokenRoute)]+$data); }
    public function login(string $email): void {
        $response=$this->post('connexion',['email'=>$email,'mot_de_passe'=>'Demo2026!'],[],'connexion');
        verify($response['status']===302,'Connexion '.$email);
    }
    public function close(): void { if (is_file($this->jar)) { unlink($this->jar); } }
}
$anon=new HttpSession($base,$tag.'_anon'); $client=new HttpSession($base,$tag.'_client'); $other=new HttpSession($base,$tag.'_other'); $agent=new HttpSession($base,$tag.'_agent'); $manager=new HttpSession($base,$tag.'_manager');
$equipmentId=null; $categoryId=null; $clientId=null; $otherId=null; $locationId=null; $exit=0;
try {
    verify($anon->request('catalogue')['status']===200,'Catalogue public via Apache');
    verify($anon->request('locations')['status']===302,'Visiteur redirigé vers connexion');
    verify($anon->request('inconnue')['status']===404,'Route inconnue : 404');
    verify($anon->request('deconnexion')['status']===405,'Mutation GET refusée');
    verify($anon->request('inscription',[],['nom'=>'Test'])['status']===403,'POST sans CSRF refusé');
    $bad=$anon->post('inscription',['nom'=>'Test','prenom'=>'QA','email'=>'incorrect','mot_de_passe'=>'123'],[],'inscription');
    verify($bad['status']===200 && str_contains($bad['body'],'Adresse e-mail incorrecte'),'Validation PHP sans HTML');
    foreach ([[$client,'client'],[$other,'other']] as [$session,$suffix]) {
        $email=$tag.'_'.$suffix.'@locamat.test';
        $response=$session->post('inscription',['nom'=>'QA','prenom'=>'Client','email'=>$email,'mot_de_passe'=>'Demo2026!','role'=>'inventaire'],[],'inscription');
        verify($response['status']===302,'Inscription HTTP '.$suffix);
        $record=DB::query('SELECT id,role FROM utilisateurs WHERE email=?',[$email])->fetch();
        verify($record && $record['role']==='client','Rôle client imposé côté serveur');
        if ($suffix==='client') { $clientId=(int)$record['id']; } else { $otherId=(int)$record['id']; }
        $session->login($email);
    }
    $agent->login('agent@locamat.test'); $manager->login('inventaire@locamat.test');
    verify($client->request('gestion',['entite'=>'equipements'])['status']===403,'Client sans accès inventaire');
    verify($agent->request('gestion',['entite'=>'categories'])['status']===403,'Agent sans accès catégories');
    verify($manager->request('location-form')['status']===403,'Responsable sans création location');
    $response=$manager->post('gestion-form',['nom'=>$tag,'description'=>'Catégorie de vérification'],['entite'=>'categories']);
    verify($response['status']===302,'Création catégorie via contrôleur');
    $categoryId=(int)DB::query('SELECT id FROM categories_equipement WHERE nom=?',[$tag])->fetchColumn();
    $product=['nom'=>$tag.' matériel','description'=>'<script>alert(1)</script>','categorie_id'=>$categoryId,'tarif_journalier'=>'12.50','quantite_totale'=>5,'quantite_maintenance'=>1,'quantite_endommagee'=>0,'seuil_alerte'=>1];
    $response=$manager->post('gestion-form',$product,['entite'=>'equipements']);
    verify($response['status']===302,'Création équipement via contrôleur');
    $equipmentId=(int)DB::query('SELECT id FROM equipements WHERE nom=?',[$product['nom']])->fetchColumn();
    $product['tarif_journalier']='13.00';
    verify($manager->post('gestion-form',$product,['entite'=>'equipements','id'=>$equipmentId])['status']===302,'Modification équipement via contrôleur');
    verify(DB::query('SELECT tarif_journalier FROM equipements WHERE id=?',[$equipmentId])->fetchColumn()==='13.00','Modification persistée');
    $response=$anon->request('equipement',['id'=>$equipmentId]);
    verify(str_contains($response['body'],'&lt;script&gt;') && !str_contains($response['body'],'<script>alert(1)</script>'),'Échappement HTML stocké');
    foreach (['utilisateurs','categories','equipements'] as $entity) {
        verify($manager->request('gestion',['entite'=>$entity])['status']===200,'Liste '.$entity);
        verify($manager->request('gestion-form',['entite'=>$entity])['status']===200,'Formulaire '.$entity);
        $id=['utilisateurs'=>$clientId,'categories'=>$categoryId,'equipements'=>$equipmentId][$entity];
        verify($manager->request('gestion-detail',['entite'=>$entity,'id'=>$id])['status']===200,'Détail '.$entity);
        verify($manager->request('gestion-form',['entite'=>$entity,'id'=>$id])['status']===200,'Formulaire édition '.$entity);
    }
    verify($agent->request('gestion',['entite'=>'utilisateurs'])['status']===200,'Liste clients de l’agent');
    verify($agent->request('gestion-form',['entite'=>'utilisateurs','id'=>1])['status']===403,'Agent ne modifie pas le responsable');
    verify($client->request('location-form',['equipement_id'=>$equipmentId])['status']===200,'Formulaire demande client');
    verify($agent->request('location-form')['status']===200,'Formulaire demande agent');
    $today=date('Y-m-d'); $request=['equipement_id'=>$equipmentId,'quantite'=>2,'date_debut'=>$today,'date_fin'=>$today];
    $response=$client->post('location-form',$request);
    verify($response['status']===302,'Demande client HTTP');
    $locationId=(int)DB::query('SELECT id FROM locations WHERE client_id=? ORDER BY id DESC LIMIT 1',[$clientId])->fetchColumn();
    verify($client->request('location',['id'=>$locationId])['status']===200,'Détail demande');
    verify($other->request('location',['id'=>$locationId])['status']===403,'Autre client bloqué sur détail');
    verify($other->request('document',['id'=>$locationId,'type'=>'contrat'])['status']===403,'Autre client bloqué sur PDF');
    verify($client->request('document',['id'=>$locationId,'type'=>'contrat'])['status']===403,'PDF bloqué avant confirmation');
    verify($client->post('location-form',$request,['id'=>$locationId])['status']===302,'Édition demande HTTP');
    $agent->post('location-action',['id'=>$locationId,'action'=>'confirmer']);
    verify(DB::query('SELECT statut FROM locations WHERE id=?',[$locationId])->fetchColumn()==='confirmee','Confirmation persistée via HTTP');
    $pdf=$client->request('document',['id'=>$locationId,'type'=>'contrat']);
    verify($pdf['status']===200 && str_starts_with($pdf['body'],'%PDF-') && stripos($pdf['headers'],'application/pdf')!==false,'Téléchargement contrat PDF');
    verify($client->request('document',['id'=>$locationId,'type'=>'recu'])['status']===403,'Reçu sans paiement HTTP refusé');
    $agent->post('location-action',['id'=>$locationId,'action'=>'depart']);
    verify(DB::query('SELECT statut FROM locations WHERE id=?',[$locationId])->fetchColumn()==='en_cours','Départ HTTP persisté');
    verify($agent->request('location',['id'=>$locationId])['status']===200,'Page retour agent');
    $agent->post('location-action',['id'=>$locationId,'action'=>'retour','date_retour'=>$today,'retour_maintenance'=>2,'retour_endommage'=>1,'frais_additionnels'=>'4.50','remarques_retour'=>'Saisie à conserver']);
    verify(DB::query('SELECT statut FROM locations WHERE id=?',[$locationId])->fetchColumn()==='en_cours','Retour invalide sans changement de statut');
    $invalidReturn=$agent->request('location',['id'=>$locationId]);
    verify(str_contains($invalidReturn['body'],'name="frais_additionnels" type="text" value="4.50"') && str_contains($invalidReturn['body'],'Saisie à conserver') && str_contains($invalidReturn['body'],'name="retour_maintenance" type="text" value="2"'),'Frais, quantités et remarques conservés après erreur retour');
    $agent->post('location-action',['id'=>$locationId,'action'=>'retour','date_retour'=>$today,'retour_maintenance'=>0,'retour_endommage'=>1,'frais_additionnels'=>'4.50','remarques_retour'=>'Contrôle HTTP']);
    verify(DB::query('SELECT statut FROM locations WHERE id=?',[$locationId])->fetchColumn()==='retour_a_controler','Retour HTTP persisté');
    verify($manager->request('location',['id'=>$locationId])['status']===200,'Page inspection responsable');
    $manager->post('location-action',['id'=>$locationId,'action'=>'valider_retour','retour_maintenance'=>2,'retour_endommage'=>1,'remarques_retour'=>'Inspection à conserver']);
    verify(DB::query('SELECT statut FROM locations WHERE id=?',[$locationId])->fetchColumn()==='retour_a_controler','Inspection invalide sans clôture');
    $invalidInspection=$manager->request('location',['id'=>$locationId]);
    verify(str_contains($invalidInspection['body'],'Inspection à conserver') && str_contains($invalidInspection['body'],'name="retour_maintenance" type="text" value="2"'),'Saisie inspection conservée après erreur');
    $manager->post('location-action',['id'=>$locationId,'action'=>'valider_retour','retour_maintenance'=>0,'retour_endommage'=>1,'remarques_retour'=>'Validé']);
    verify(DB::query('SELECT statut FROM locations WHERE id=?',[$locationId])->fetchColumn()==='terminee','Inspection HTTP persistée');
    $agent->post('location-action',['id'=>$locationId,'action'=>'payer']);
    $paid=DB::query('SELECT * FROM locations WHERE id=?',[$locationId])->fetch();
    verify($paid['montant_paye']==='30.50' && $paid['date_paiement']!==null,'Paiement et total HTTP sauvegardés');
    foreach (['facture','recu'] as $type) {
        $response=$client->request('document',['id'=>$locationId,'type'=>$type]);
        verify($response['status']===200 && str_starts_with($response['body'],'%PDF-'),'Téléchargement '.$type.' PDF');
    }
    verify($client->request('location',['id'=>$locationId])['status']===200,'Location après fermeture et nouvelle requête');
    foreach ([$client,$agent,$manager] as $session) { verify($session->request('locations')['status']===200,'Liste de locations pour chaque rôle'); }
    $temporary=$client->post('location-form',$request); $temporaryId=(int)DB::query('SELECT MAX(id) FROM locations WHERE client_id=?',[$clientId])->fetchColumn();
    $client->post('location-delete',['id'=>$temporaryId]);
    verify(!DB::query('SELECT id FROM locations WHERE id=?',[$temporaryId])->fetch(),'Suppression demande via HTTP');
    $response=$client->post('deconnexion'); verify($response['status']===302 && $client->request('locations')['status']===302,'Déconnexion effective');
    verify($anon->request('catalogue',['q'=>"' OR 1=1 --"])['status']===200,'Recherche SQL hostile sans erreur');
    verify($anon->request('catalogue',['date_debut'=>'2026-02-30'])['status']===422,'Filtre date malformé : 422');
    $response=$manager->post('gestion-delete',['id'=>$categoryId],['entite'=>'categories']);
    verify($response['status']===302 && DB::query('SELECT id FROM categories_equipement WHERE id=?',[$categoryId])->fetch(),'Suppression catégorie utilisée refusée');
    echo "\n$checks contrôles HTTP réussis sur $base\n";
} catch (Throwable $e) { fwrite(STDERR,(string)$e.PHP_EOL); $exit=1; }
finally {
    // Only remove records created by this run; demonstration and other data are preserved.
    foreach ([$clientId,$otherId] as $id) {
        if (!$id) { continue; }
        $owned=DB::query('SELECT id FROM utilisateurs WHERE id=? AND email LIKE ?',[$id,$tag.'_%@locamat.test'])->fetch();
        if ($owned) { DB::query('DELETE FROM locations WHERE client_id=?',[$id]); DB::query('DELETE FROM utilisateurs WHERE id=?',[$id]); }
    }
    if ($equipmentId) { DB::query('DELETE FROM equipements WHERE id=? AND nom=?',[$equipmentId,$tag.' matériel']); }
    if ($categoryId) { DB::query('DELETE FROM categories_equipement WHERE id=? AND nom=?',[$categoryId,$tag]); }
    foreach ([$anon,$client,$other,$agent,$manager] as $session) { $session->close(); }
}
exit($exit);

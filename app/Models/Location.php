<?php
namespace App\Models;
use App\Core\Database as DB;
use App\Core\Validation as V;
use App\Core\HttpException;
use DomainException;

final class Location {
    public const STATUSES = ['en_attente','confirmee','en_cours','retour_a_controler','terminee','refusee','annulee'];
    private const SELECT = 'SELECT l.*,e.nom AS equipement,c.nom AS categorie,e.quantite_totale,u.nom AS client_nom,u.prenom AS client_prenom,u.email AS client_email,CONCAT(a.prenom," ",a.nom) AS agent_nom FROM locations l JOIN equipements e ON e.id=l.equipement_id JOIN categories_equipement c ON c.id=e.categorie_id JOIN utilisateurs u ON u.id=l.client_id LEFT JOIN utilisateurs a ON a.id=l.agent_id';
    public static function find(int $id): ?array { return DB::query(self::SELECT . ' WHERE l.id=?',[$id])->fetch() ?: null; }
    public static function accessible(int $id, array $actor): array {
        $row = self::find($id);
        if (!$row) { throw new HttpException(404,'Location introuvable.'); }
        if ($actor['role'] === 'client' && (int) $row['client_id'] !== (int) $actor['id']) { throw new HttpException(403,'Cette location ne vous appartient pas.'); }
        return $row;
    }
    public static function all(array $actor, array $filters = []): array {
        $sql = self::SELECT . ' WHERE 1=1'; $params=[];
        if ($actor['role'] === 'client') { $sql .= ' AND l.client_id=?'; $params[]=$actor['id']; }
        if (in_array($filters['statut'] ?? '', self::STATUSES,true)) { $sql .= ' AND l.statut=?'; $params[]=$filters['statut']; }
        return DB::query($sql . ' ORDER BY l.id DESC',$params)->fetchAll();
    }
    public static function days(array $row): int { return (int) (new \DateTimeImmutable($row['date_debut']))->diff(new \DateTimeImmutable($row['date_fin']))->days + 1; }
    public static function totalCents(array $row): int { return V::cents($row['tarif_applique']) * (int) $row['quantite'] * self::days($row) + V::cents($row['frais_additionnels']); }
    public static function save(array $data, ?int $id, array $actor): int {
        if (!in_array($actor['role'],['client','agent'],true)) { throw new HttpException(403,'Création et modification réservées au client et à l’agent.'); }
        return DB::transaction(function () use ($data,$id,$actor) {
            $old = $id ? self::accessible($id,$actor) : null;
            if ($old && $old['statut'] !== 'en_attente') { throw new DomainException('Seules les demandes en attente peuvent être modifiées.'); }
            $equipmentId = V::integer($data,'equipement_id','Équipement',1);
            $equipment = Equipement::find($equipmentId,true);
            if (!$equipment) { throw new DomainException('Équipement introuvable.'); }
            // Re-read under row lock: a confirmation may have raced the initial read.
            if ($id) { $locked = DB::query('SELECT statut FROM locations WHERE id=? FOR UPDATE',[$id])->fetch(); if (!$locked || $locked['statut'] !== 'en_attente') { throw new DomainException('Cette demande vient de changer.'); } }
            $clientId = $actor['role'] === 'client' ? (int) $actor['id'] : V::integer($data,'client_id','Client',1);
            $client = Utilisateur::find($clientId);
            if (!$client || $client['role'] !== 'client') { throw new DomainException('Un compte client valide est nécessaire.'); }
            $quantity = V::integer($data,'quantite','Quantité',1,10000);
            $start = V::date($data['date_debut'] ?? ''); $end = V::date($data['date_fin'] ?? '');
            if ($start < date('Y-m-d') || $end < $start || self::days(['date_debut'=>$start,'date_fin'=>$end]) > 366) { throw new DomainException('Dates : début à partir d’aujourd’hui, fin après début, durée maximale 366 jours.'); }
            if ($quantity > Equipement::available($equipment,$start,$end,$id ?? 0)) { throw new DomainException('Quantité insuffisante pour les dates demandées.'); }
            $params = [$clientId,$equipmentId,$quantity,$start,$end,$equipment['tarif_journalier']];
            if ($id) { $params[]=$id; DB::query('UPDATE locations SET client_id=?,equipement_id=?,quantite=?,date_debut=?,date_fin=?,tarif_applique=? WHERE id=?',$params); }
            else { DB::query("INSERT INTO locations(client_id,equipement_id,quantite,date_debut,date_fin,tarif_applique,remarques_retour) VALUES(?,?,?,?,?,?, '')",$params); $id=(int) DB::connection()->lastInsertId(); }
            return $id;
        });
    }
    public static function action(int $id, string $action, array $data, array $actor): void {
        DB::transaction(function () use ($id,$action,$data,$actor) {
            $initial = self::accessible($id,$actor);
            $equipment = Equipement::find((int) $initial['equipement_id'],true);
            DB::query('SELECT id FROM locations WHERE id=? FOR UPDATE',[$id]);
            $row = self::accessible($id,$actor);
            $role = $actor['role']; $status = $row['statut'];
            if ($action === 'annuler' && in_array($status,['en_attente','confirmee'],true) && in_array($role,['client','agent'],true)) {
                DB::query("UPDATE locations SET statut='annulee' WHERE id=?",[$id]); return;
            }
            if ($action === 'refuser' && $role === 'agent' && $status === 'en_attente') { DB::query("UPDATE locations SET statut='refusee',agent_id=? WHERE id=?",[$actor['id'],$id]); return; }
            if ($action === 'confirmer' && $role === 'agent' && $status === 'en_attente') {
                if ($row['date_debut'] < date('Y-m-d')) { throw new DomainException('La date de début est passée. Modifiez la demande avant confirmation.'); }
                if ((int) $row['quantite'] > Equipement::available($equipment,$row['date_debut'],$row['date_fin'],$id)) { throw new DomainException('Stock insuffisant : une autre réservation a été confirmée.'); }
                $snapshot = json_encode(['equipement'=>$equipment['nom'],'categorie'=>$equipment['categorie'],'client_nom'=>$row['client_nom'],'client_prenom'=>$row['client_prenom'],'client_email'=>$row['client_email']], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                DB::query("UPDATE locations SET statut='confirmee',agent_id=?,tarif_applique=?,snapshot=? WHERE id=?",[$actor['id'],$equipment['tarif_journalier'],$snapshot,$id]); return;
            }
            if ($action === 'depart' && $role === 'agent' && $status === 'confirmee') {
                $today=date('Y-m-d');
                if ($today < $row['date_debut'] || $today > $row['date_fin']) { throw new DomainException('Le départ doit être enregistré pendant la période prévue.'); }
                // Overdue or unchecked returns can invalidate availability after confirmation.
                if ((int) $row['quantite'] > Equipement::available($equipment,$today,$row['date_fin'],$id)) { throw new DomainException('Stock indisponible au départ : vérifiez les retours et les autres locations.'); }
                DB::query("UPDATE locations SET statut='en_cours',agent_id=? WHERE id=?",[$actor['id'],$id]); return;
            }
            if ($action === 'retour' && $role === 'agent' && $status === 'en_cours') {
                $date=V::date($data['date_retour'] ?? '');
                if ($date < $row['date_debut'] || $date > date('Y-m-d')) { throw new DomainException('Date de retour : entre le début de la location et aujourd’hui.'); }
                [$maintenance,$damaged] = self::returnQuantities($data,(int) $row['quantite']);
                $fees=V::decimal(V::cents($data['frais_additionnels'] ?? ''));
                $remarks=V::text($data,'remarques_retour','Remarques',2000,false);
                DB::query("UPDATE locations SET statut='retour_a_controler',date_retour=?,retour_maintenance=?,retour_endommage=?,frais_additionnels=?,remarques_retour=?,agent_id=? WHERE id=?",[$date,$maintenance,$damaged,$fees,$remarks,$actor['id'],$id]); return;
            }
            if ($action === 'valider_retour' && $role === 'inventaire' && $status === 'retour_a_controler') {
                [$maintenance,$damaged] = self::returnQuantities($data,(int) $row['quantite']);
                $remarks=V::text($data,'remarques_retour','Remarques',2000,false);
                $unavailable=(int) $equipment['quantite_maintenance']+(int) $equipment['quantite_endommagee']+$maintenance+$damaged;
                if ($unavailable > (int) $equipment['quantite_totale']) { throw new DomainException('Les quantités inspectées dépassent le stock total.'); }
                DB::query('UPDATE equipements SET quantite_maintenance=quantite_maintenance+?,quantite_endommagee=quantite_endommagee+? WHERE id=?',[$maintenance,$damaged,$equipment['id']]);
                DB::query("UPDATE locations SET statut='terminee',retour_maintenance=?,retour_endommage=?,remarques_retour=? WHERE id=?",[$maintenance,$damaged,$remarks,$id]); return;
            }
            if ($action === 'payer' && $role === 'agent' && $status === 'terminee' && !$row['date_paiement']) {
                DB::query('UPDATE locations SET montant_paye=?,date_paiement=NOW() WHERE id=?',[V::decimal(self::totalCents($row)),$id]); return;
            }
            throw new DomainException('Action non autorisée pour ce rôle ou cet état.');
        });
    }
    private static function returnQuantities(array $data, int $quantity): array {
        $maintenance=V::integer($data,'retour_maintenance','Quantité en maintenance',0,$quantity);
        $damaged=V::integer($data,'retour_endommage','Quantité endommagée',0,$quantity);
        if ($maintenance+$damaged>$quantity) { throw new DomainException('Les quantités en maintenance et endommagées dépassent la quantité retournée.'); }
        return [$maintenance,$damaged];
    }
    public static function delete(int $id, array $actor): void {
        if (!in_array($actor['role'],['client','agent'],true)) { throw new HttpException(403,'Suppression non autorisée.'); }
        DB::transaction(function () use ($id,$actor) {
            DB::query('SELECT id FROM locations WHERE id=? FOR UPDATE',[$id]);
            $row=self::accessible($id,$actor);
            $allowed=$actor['role']==='client' ? ['en_attente'] : ['en_attente','refusee','annulee'];
            if (!in_array($row['statut'],$allowed,true)) { throw new DomainException('Cette location doit être conservée dans l’historique.'); }
            DB::query('DELETE FROM locations WHERE id=?',[$id]);
        });
    }
}

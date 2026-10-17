<?php
namespace App\Models;
use App\Core\Database as DB;
use App\Core\Validation as V;
use DomainException;

final class Equipement {
    public static function find(int $id, bool $lock = false): ?array {
        return DB::query('SELECT e.*,c.nom AS categorie FROM equipements e JOIN categories_equipement c ON c.id=e.categorie_id WHERE e.id=?' . ($lock ? ' FOR UPDATE' : ''),[$id])->fetch() ?: null;
    }
    public static function all(array $filters = []): array {
        $sql = 'SELECT e.*,c.nom AS categorie FROM equipements e JOIN categories_equipement c ON c.id=e.categorie_id WHERE 1=1'; $params = [];
        $q = V::text($filters,'q','Recherche',120,false);
        if ($q !== '') { $sql .= ' AND (e.nom LIKE ? OR e.description LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
        if (!empty($filters['categorie_id'])) { $sql .= ' AND e.categorie_id=?'; $params[] = V::integer($filters,'categorie_id','Catégorie',1); }
        foreach (['prix_min'=>'>=','prix_max'=>'<='] as $key=>$operator) {
            if (($filters[$key] ?? '') !== '') { $sql .= " AND e.tarif_journalier $operator ?"; $params[] = V::decimal(V::cents($filters[$key])); }
        }
        $start = !empty($filters['date_debut']) ? V::date($filters['date_debut']) : date('Y-m-d');
        $end = !empty($filters['date_fin']) ? V::date($filters['date_fin']) : $start;
        if ($end < $start) { throw new DomainException('La date de fin doit suivre la date de début.'); }
        $rows = DB::query($sql . ' ORDER BY e.nom',$params)->fetchAll();
        foreach ($rows as &$row) {
            $row['disponible'] = self::available($row,$start,$end);
            $row['en_location'] = (int) DB::query("SELECT COALESCE(SUM(quantite),0) FROM locations WHERE equipement_id=? AND statut IN ('en_cours','retour_a_controler')",[$row['id']])->fetchColumn();
        } unset($row);
        $state = $filters['etat'] ?? '';
        return array_values(array_filter($rows, static function ($row) use ($state,$filters) {
            if (!empty($filters['alertes']) && $row['disponible'] > $row['seuil_alerte']) { return false; }
            return match ($state) {
                'disponible' => $row['disponible'] > 0,
                'location' => $row['en_location'] > 0,
                'maintenance' => $row['quantite_maintenance'] > 0,
                'endommage' => $row['quantite_endommagee'] > 0,
                default => true,
            };
        }));
    }
    // Sweep date events to find the peak simultaneous reservation, including both end dates.
    public static function reservedPeak(int $equipmentId, string $start, string $end, int $exclude = 0): int {
        $rows = DB::query("SELECT quantite,date_debut,date_fin,statut FROM locations WHERE equipement_id=? AND id<>? AND statut IN ('confirmee','en_cours','retour_a_controler')",[$equipmentId,$exclude])->fetchAll();
        $events = []; $today = date('Y-m-d');
        foreach ($rows as $r) {
            $from = $r['date_debut']; $to = $r['date_fin'];
            if ($r['statut'] === 'retour_a_controler' || ($r['statut'] === 'en_cours' && $to < $today)) { $to = '2100-12-31'; }
            if ($to < $start || $from > $end) { continue; }
            $from = max($start,$from); $to = min($end,$to);
            $after = (new \DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d');
            $events[$from] = ($events[$from] ?? 0) + (int) $r['quantite'];
            $events[$after] = ($events[$after] ?? 0) - (int) $r['quantite'];
        }
        ksort($events); $running = 0; $peak = 0;
        foreach ($events as $delta) { $running += $delta; $peak = max($peak,$running); }
        return $peak;
    }
    public static function available(array $equipment, string $start, string $end, int $exclude = 0): int {
        return max(0,(int) $equipment['quantite_totale'] - (int) $equipment['quantite_maintenance'] - (int) $equipment['quantite_endommagee'] - self::reservedPeak((int) $equipment['id'],$start,$end,$exclude));
    }
    public static function save(array $data, ?int $id): int {
        return DB::transaction(function () use ($data,$id) {
            if ($id && !self::find($id,true)) { throw new DomainException('Équipement introuvable.'); }
            $nom = V::text($data,'nom','Nom',120);
            $description = V::text($data,'description','Description',4000,false);
            $category = V::integer($data,'categorie_id','Catégorie',1);
            if (!CategorieEquipement::find($category)) { throw new DomainException('Catégorie introuvable.'); }
            $price = V::decimal(V::cents($data['tarif_journalier'] ?? '',true));
            $total = V::integer($data,'quantite_totale','Stock total');
            $maintenance = V::integer($data,'quantite_maintenance','Quantité en maintenance');
            $damaged = V::integer($data,'quantite_endommagee','Quantité endommagée');
            $threshold = V::integer($data,'seuil_alerte',"Seuil d'alerte");
            if ($maintenance + $damaged > $total) { throw new DomainException('Les quantités indisponibles dépassent le stock total.'); }
            if ($id && $total - $maintenance - $damaged < self::reservedPeak($id,date('Y-m-d'),'2100-12-31')) { throw new DomainException('Ce stock ne permet pas de couvrir les locations réservées ou en cours.'); }
            $params = [$category,$nom,$description,$price,$total,$maintenance,$damaged,$threshold];
            if ($id) { $params[] = $id; DB::query('UPDATE equipements SET categorie_id=?,nom=?,description=?,tarif_journalier=?,quantite_totale=?,quantite_maintenance=?,quantite_endommagee=?,seuil_alerte=? WHERE id=?',$params); }
            else { DB::query('INSERT INTO equipements(categorie_id,nom,description,tarif_journalier,quantite_totale,quantite_maintenance,quantite_endommagee,seuil_alerte) VALUES(?,?,?,?,?,?,?,?)',$params); $id = (int) DB::connection()->lastInsertId(); }
            return $id;
        });
    }
    public static function delete(int $id): void {
        DB::transaction(function () use ($id) {
            if (!self::find($id,true)) { throw new DomainException('Équipement introuvable.'); }
            if (DB::query('SELECT id FROM locations WHERE equipement_id=? LIMIT 1',[$id])->fetch()) { throw new DomainException('Cet équipement est lié à des locations. Suppression impossible.'); }
            DB::query('DELETE FROM equipements WHERE id=?',[$id]);
        });
    }
}

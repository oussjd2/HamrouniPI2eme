<?php
namespace App\Models;
use App\Core\Database as DB;
use App\Core\Validation as V;
use DomainException;

final class CategorieEquipement {
    public static function all(): array { return DB::query('SELECT c.*,COUNT(e.id) AS nombre FROM categories_equipement c LEFT JOIN equipements e ON e.categorie_id=c.id GROUP BY c.id ORDER BY c.nom')->fetchAll(); }
    public static function find(int $id): ?array { return DB::query('SELECT * FROM categories_equipement WHERE id=?',[$id])->fetch() ?: null; }
    public static function save(array $data, ?int $id): int {
        $nom = V::text($data,'nom','Nom',100);
        $description = V::text($data,'description','Description',2000,false);
        if ($id && !self::find($id)) { throw new DomainException('Catégorie introuvable.'); }
        if (DB::query('SELECT id FROM categories_equipement WHERE nom=? AND id<>?',[$nom,$id ?? 0])->fetch()) { throw new DomainException('Ce nom de catégorie existe déjà.'); }
        if ($id) { DB::query('UPDATE categories_equipement SET nom=?,description=? WHERE id=?',[$nom,$description,$id]); }
        else { DB::query('INSERT INTO categories_equipement(nom,description) VALUES(?,?)',[$nom,$description]); $id = (int) DB::connection()->lastInsertId(); }
        return $id;
    }
    public static function delete(int $id): void {
        if (!self::find($id)) { throw new DomainException('Catégorie introuvable.'); }
        if (DB::query('SELECT id FROM equipements WHERE categorie_id=? LIMIT 1',[$id])->fetch()) { throw new DomainException('Cette catégorie contient des équipements.'); }
        DB::query('DELETE FROM categories_equipement WHERE id=?',[$id]);
    }
}

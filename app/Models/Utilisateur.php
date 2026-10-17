<?php
namespace App\Models;
use App\Core\Database as DB;
use App\Core\Validation as V;
use DomainException;

final class Utilisateur {
    public static function find(int $id): ?array { return DB::query('SELECT id,nom,prenom,email,role FROM utilisateurs WHERE id=?', [$id])->fetch() ?: null; }
    public static function byEmail(string $email): ?array { return DB::query('SELECT * FROM utilisateurs WHERE email=?', [$email])->fetch() ?: null; }
    public static function all(bool $clientsOnly = false): array { return DB::query('SELECT id,nom,prenom,email,role FROM utilisateurs' . ($clientsOnly ? " WHERE role='client'" : '') . ' ORDER BY nom,prenom')->fetchAll(); }
    public static function save(array $data, ?int $id, string $actorRole, ?int $actorId = null): int {
        return DB::transaction(function () use ($data, $id, $actorRole, $actorId) {
            // Serialise account changes so the last inventory manager cannot be removed concurrently.
            DB::query("SELECT id FROM utilisateurs WHERE role='inventaire' ORDER BY id FOR UPDATE")->fetchAll();
            $old = $id ? self::find($id) : null;
            if ($id && !$old) { throw new DomainException('Utilisateur introuvable.'); }
            if ($old && $actorRole !== 'inventaire' && $old['role'] !== 'client') { throw new DomainException('Seuls les comptes clients sont accessibles.'); }
            $nom = V::text($data, 'nom', 'Nom', 80);
            $prenom = V::text($data, 'prenom', 'Prénom', 80);
            $email = mb_strtolower(V::text($data, 'email', 'E-mail', 190));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new DomainException('Adresse e-mail incorrecte.'); }
            $role = $actorRole === 'inventaire' ? ($data['role'] ?? '') : 'client';
            if (!in_array($role, ['client','agent','inventaire'], true)) { throw new DomainException('Rôle incorrect.'); }
            if ($old && $old['role'] === 'inventaire' && $role !== 'inventaire') {
                if ($actorId === $id || (int) DB::query("SELECT COUNT(*) FROM utilisateurs WHERE role='inventaire'")->fetchColumn() <= 1) { throw new DomainException('Impossible de retirer votre rôle ou le dernier responsable.'); }
            }
            $duplicate = DB::query('SELECT id FROM utilisateurs WHERE email=? AND id<>?', [$email, $id ?? 0])->fetch();
            if ($duplicate) { throw new DomainException('Cette adresse e-mail est déjà utilisée.'); }
            if ($old && $old['role'] !== $role && DB::query('SELECT id FROM locations WHERE client_id=? OR agent_id=? LIMIT 1', [$id,$id])->fetch()) { throw new DomainException("Le rôle d'un utilisateur lié à des locations ne peut pas être modifié."); }
            $password = $data['mot_de_passe'] ?? '';
            if (!is_string($password) || ((!$id || $password !== '') && (strlen($password) < 8 || strlen($password) > 72))) { throw new DomainException('Mot de passe : entre 8 et 72 caractères.'); }
            if (!$id) {
                DB::query('INSERT INTO utilisateurs(nom,prenom,email,role,mot_de_passe) VALUES(?,?,?,?,?)', [$nom,$prenom,$email,$role,password_hash($password, PASSWORD_DEFAULT)]);
                return (int) DB::connection()->lastInsertId();
            }
            DB::query('UPDATE utilisateurs SET nom=?,prenom=?,email=?,role=? WHERE id=?', [$nom,$prenom,$email,$role,$id]);
            if ($password !== '') { DB::query('UPDATE utilisateurs SET mot_de_passe=? WHERE id=?', [password_hash($password, PASSWORD_DEFAULT),$id]); }
            return $id;
        });
    }
    public static function delete(int $id, string $actorRole, int $actorId): void {
        DB::transaction(function () use ($id,$actorRole,$actorId) {
            DB::query("SELECT id FROM utilisateurs WHERE role='inventaire' ORDER BY id FOR UPDATE")->fetchAll();
            $old = self::find($id);
            if (!$old) { throw new DomainException('Utilisateur introuvable.'); }
            if ($id === $actorId) { throw new DomainException('Vous ne pouvez pas supprimer votre compte.'); }
            if ($actorRole !== 'inventaire' && $old['role'] !== 'client') { throw new DomainException('Action réservée aux comptes clients.'); }
            if ($old['role'] === 'inventaire' && (int) DB::query("SELECT COUNT(*) FROM utilisateurs WHERE role='inventaire'")->fetchColumn() <= 1) { throw new DomainException('Le dernier responsable doit être conservé.'); }
            if (DB::query('SELECT id FROM locations WHERE client_id=? OR agent_id=? LIMIT 1', [$id,$id])->fetch()) { throw new DomainException('Cet utilisateur est lié à des locations. Suppression impossible.'); }
            DB::query('DELETE FROM utilisateurs WHERE id=?', [$id]);
        });
    }
}

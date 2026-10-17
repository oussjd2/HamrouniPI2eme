<?php
namespace App\Core;
use App\Models\Utilisateur;

final class Auth {
    public static function user(): ?array { return empty($_SESSION['user_id']) ? null : Utilisateur::find((int) $_SESSION['user_id']); }
    public static function requireRoles(array $roles): array {
        $user = self::user();
        if (!$user) { \redirect('connexion'); }
        if (!in_array($user['role'], $roles, true)) { throw new HttpException(403, 'Vous ne disposez pas des droits nécessaires.'); }
        return $user;
    }
    public static function checkCsrf(): void {
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']) || empty($_SESSION['csrf'])) { throw new HttpException(403, 'La session du formulaire a expiré. Rechargez la page.'); }
    }
    public static function login(array $user): void { session_regenerate_id(true); $_SESSION['user_id'] = $user['id']; unset($_SESSION['csrf']); }
}

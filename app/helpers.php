<?php
function e($value): string { return htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $route = 'catalogue', array $params = []): string {
    return 'index.php?' . http_build_query(['r' => $route] + $params);
}
function redirect(string $route, array $params = []): void { header('Location: ' . url($route, $params)); exit; }
function csrf(): string {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}
function flash(string $message, string $type = 'success'): void { $_SESSION['flash'] = ['message' => $message, 'type' => $type]; }
function money($value): string { global $config; return number_format((float) $value, 2, ',', ' ') . ' ' . $config['currency']; }
function label(string $value): string {
    return ['client'=>'Client', 'agent'=>'Agent de location', 'inventaire'=>'Responsable inventaire', 'en_attente'=>'En attente', 'confirmee'=>'Confirmée', 'en_cours'=>'En cours', 'retour_a_controler'=>'Retour à contrôler', 'terminee'=>'Terminée', 'refusee'=>'Refusée', 'annulee'=>'Annulée', 'disponible'=>'Disponible', 'maintenance'=>'En maintenance', 'endommage'=>'Endommagé', 'location'=>'En location'][$value] ?? $value;
}
function dateFr(?string $date): string { return $date ? date('d/m/Y', strtotime($date)) : '—'; }
function user(): ?array { return App\Core\Auth::user(); }
function isRole(string ...$roles): bool { return user() && in_array(user()['role'], $roles, true); }
function field(string $name, string $label, $value = '', string $type = 'text', string $hint = ''): void {
    echo '<label>' . e($label) . '<input name="' . e($name) . '" type="' . e($type) . '" value="' . e($value) . '">' . ($hint ? '<small>' . e($hint) . '</small>' : '') . '</label>';
}
function productImage(array $equipment): string {
    $image=$equipment['image_path'] ?? null;
    $uploaded=App\Core\ProductImage::uploadedFile($image);
    if ($uploaded && is_file($uploaded)) { return url('image',['id'=>$equipment['id']]); }
    if (is_string($image) && preg_match('~^assets/products/[a-z0-9-]+\.jpg$~D',$image)) { return $image; }
    return 'assets/product-placeholder.svg';
}

<?php
namespace App\Core;
final class View {
    public static function render(string $template, array $data = [], int $status = 200): void {
        http_response_code($status);
        extract($data, EXTR_SKIP);
        ob_start();
        require ROOT . '/app/Views/' . $template . '.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout.php';
    }
}

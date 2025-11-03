<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Http\Response;

class View
{
    public static function render(string $template, array $data = []): Response
    {
        $path = __DIR__ . '/../../views/' . $template . '.php';
        if (!file_exists($path)) {
            return Response::json(['message' => 'View not found'], 500);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $path;
        $content = (string) ob_get_clean();

        return Response::html($content);
    }
}

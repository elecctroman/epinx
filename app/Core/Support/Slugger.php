<?php
declare(strict_types=1);

namespace App\Core\Support;

class Slugger
{
    public static function slug(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        $value = strtolower((string) $value);
        $value = preg_replace('/[^a-z0-9]+/', '-', (string) $value) ?? '';
        $value = trim($value, '-');

        return $value ?: 'n-a';
    }
}

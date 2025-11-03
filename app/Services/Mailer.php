<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use RuntimeException;

class Mailer
{
    public function send(string $to, string $subject, string $body): void
    {
        $headers = [];
        $headers[] = 'From: ' . Env::get('MAIL_FROM_NAME') . ' <' . Env::get('MAIL_FROM_ADDRESS') . '>';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        $success = mail($to, $subject, $body, implode("\r\n", $headers));

        if (!$success) {
            throw new RuntimeException('Failed to send email.');
        }
    }
}

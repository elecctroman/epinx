<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use RuntimeException;

class SmsService
{
    public function send(string $number, string $message): void
    {
        $provider = Env::get('SMS_PROVIDER', 'log');

        if ($provider === 'log') {
            error_log(sprintf('SMS to %s: %s', $number, $message));

            return;
        }

        throw new RuntimeException('SMS provider not configured.');
    }
}

<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use JsonException;
use RuntimeException;

class ReCaptchaService
{
    private const VERIFY_ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';

    public function isEnabled(): bool
    {
        return (bool) Config::get('security.recaptcha.enabled', false);
    }

    public function verifyResponse(?string $token, string $ip): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        $token = trim((string) $token);
        if ($token === '') {
            return false;
        }

        $secret = (string) Config::get('security.recaptcha.secret_key', '');
        if ($secret === '') {
            throw new RuntimeException('reCAPTCHA is enabled but no secret key has been configured.');
        }

        $payload = http_build_query([
            'secret' => $secret,
            'response' => $token,
            'remoteip' => $ip,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 5,
            ],
        ]);

        $response = file_get_contents(self::VERIFY_ENDPOINT, false, $context);
        if ($response === false) {
            return false;
        }

        try {
            /** @var array{success?:bool,score?:float} $result */
            $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }
        if (($result['success'] ?? false) !== true) {
            return false;
        }

        if ($this->isV3()) {
            $score = (float) ($result['score'] ?? 0.0);
            $threshold = (float) Config::get('security.recaptcha.score_threshold', 0.5);
            return $score >= $threshold;
        }

        return true;
    }

    private function isV3(): bool
    {
        return strtolower((string) Config::get('security.recaptcha.type', 'v2')) === 'v3';
    }
}

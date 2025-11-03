<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class TwoFactorService
{
    private const TIME_STEP = 30;
    private const DIGITS = 6;

    public function generateSecret(int $length = 16): string
    {
        return rtrim(strtr(base64_encode(random_bytes($length)), '+/', '-_'), '=');
    }

    public function getProvisioningUri(string $email, string $secret, string $issuer): string
    {
        $label = rawurlencode($issuer . ':' . $email);
        $encodedIssuer = rawurlencode($issuer);

        return "otpauth://totp/{$label}?secret={$secret}&issuer={$encodedIssuer}&algorithm=SHA1&digits=" . self::DIGITS . '&period=' . self::TIME_STEP;
    }

    public function getQrCodeUrl(string $otpauthUri): string
    {
        $encoded = urlencode($otpauthUri);

        return 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . $encoded;
    }

    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $currentTimeSlice = (int) floor(time() / self::TIME_STEP);

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals($this->getCode($secret, $currentTimeSlice + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    public function getCode(string $secret, ?int $timeSlice = null): string
    {
        if ($timeSlice === null) {
            $timeSlice = (int) floor(time() / self::TIME_STEP);
        }

        $secretKey = base64_decode(strtr($secret, '-_', '+/'), true);
        if ($secretKey === false) {
            throw new RuntimeException('Invalid two-factor secret.');
        }

        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $segment = substr($hash, $offset, 4);
        $value = unpack('N', $segment);
        if (!is_array($value)) {
            throw new RuntimeException('Failed to generate TOTP code.');
        }

        $truncatedHash = $value[1] & 0x7FFFFFFF;
        $code = $truncatedHash % (10 ** self::DIGITS);

        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }
}

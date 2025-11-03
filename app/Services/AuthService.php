<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Core\Support\RateLimiter;
use PDO;
use PDOException;

class AuthService
{
    private const SESSION_USER = 'auth_user_id';

    private RateLimiter $rateLimiter;
    private TwoFactorService $twoFactor;
    private Mailer $mailer;

    public function __construct(
        private readonly PDO $connection,
        ?RateLimiter $rateLimiter = null,
        ?TwoFactorService $twoFactor = null,
        ?Mailer $mailer = null
    ) {
        $this->rateLimiter = $rateLimiter ?? new RateLimiter();
        $this->twoFactor = $twoFactor ?? new TwoFactorService();
        $this->mailer = $mailer ?? new Mailer();
    }

    public function attempt(string $email, string $password, ?string $twoFactorCode, string $ip, string $userAgent = ''): bool
    {
        $rateKey = $this->rateKey($email, $ip);
        if ($this->rateLimiter->tooManyAttempts($rateKey)) {
            return false;
        }

        $statement = $this->connection->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, (string) $user['password'])) {
            $this->rateLimiter->hit($rateKey);
            return false;
        }

        if (!empty($user['two_factor_secret'])) {
            if ($twoFactorCode === null || !$this->twoFactor->verify((string) $user['two_factor_secret'], $twoFactorCode)) {
                $this->rateLimiter->hit($rateKey);
                return false;
            }
        }

        $_SESSION[self::SESSION_USER] = (int) $user['id'];
        $this->rateLimiter->clear($rateKey);

        $this->storeSession((int) $user['id'], $ip, $userAgent);

        return true;
    }

    public function logout(): void
    {
        $token = session_id();
        if ($token !== '') {
            $statement = $this->connection->prepare('DELETE FROM user_sessions WHERE session_token = :token');
            $statement->execute(['token' => $token]);
        }

        unset($_SESSION[self::SESSION_USER]);
    }

    public function check(): bool
    {
        return isset($_SESSION[self::SESSION_USER]);
    }

    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        $statement = $this->connection->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $_SESSION[self::SESSION_USER]]);

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function hasRole(string $role): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        return in_array($role, array_map('trim', explode(',', (string) $user['roles'])), true);
    }

    public function register(string $name, string $email, string $password): int
    {
        $statement = $this->connection->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        if ($statement->fetchColumn()) {
            throw new PDOException('Email already registered.');
        }

        $insert = $this->connection->prepare('INSERT INTO users (name, email, password, roles, created_at, updated_at) VALUES (:name, :email, :password, :roles, NOW(), NOW())');
        $insert->execute([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'roles' => 'customer',
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function createPasswordResetToken(string $email): ?string
    {
        $user = $this->findByEmail($email);
        if (!$user) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $hashed = hash('sha256', $token);

        $this->connection->prepare('DELETE FROM password_resets WHERE email = :email')->execute(['email' => $email]);
        $statement = $this->connection->prepare('INSERT INTO password_resets (email, token, created_at) VALUES (:email, :token, NOW())');
        $statement->execute([
            'email' => $email,
            'token' => $hashed,
        ]);

        return $token;
    }

    public function sendPasswordReset(string $email, string $token): void
    {
        $resetLink = rtrim((string) Env::get('APP_URL', ''), '/') . '/password/reset?token=' . urlencode($token) . '&email=' . urlencode($email);
        $subject = 'Password Reset Request';
        $body = '<p>We received a password reset request for your account.</p>' .
            '<p><a href="' . escape($resetLink) . '">Click here to reset your password</a></p>' .
            '<p>If you did not request a reset, you can safely ignore this message.</p>';

        $this->mailer->send($email, $subject, $body);
    }

    public function resetPassword(string $email, string $token, string $password): bool
    {
        $hashed = hash('sha256', $token);
        $statement = $this->connection->prepare('SELECT created_at FROM password_resets WHERE email = :email AND token = :token LIMIT 1');
        $statement->execute(['email' => $email, 'token' => $hashed]);
        $reset = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$reset) {
            return false;
        }

        $expireMinutes = (int) Env::get('PASSWORD_RESET_EXPIRES', 60);
        if (isset($reset['created_at']) && strtotime((string) $reset['created_at']) < strtotime("-{$expireMinutes} minutes")) {
            $this->connection->prepare('DELETE FROM password_resets WHERE email = :email')->execute(['email' => $email]);
            return false;
        }

        $update = $this->connection->prepare('UPDATE users SET password = :password, updated_at = NOW() WHERE email = :email');
        $update->execute([
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'email' => $email,
        ]);

        $this->connection->prepare('DELETE FROM password_resets WHERE email = :email')->execute(['email' => $email]);

        return true;
    }

    public function generateTwoFactorSecret(): string
    {
        return $this->twoFactor->generateSecret();
    }

    public function enableTwoFactor(int $userId, string $secret): void
    {
        $statement = $this->connection->prepare('UPDATE users SET two_factor_secret = :secret, updated_at = NOW() WHERE id = :id');
        $statement->execute([
            'secret' => $secret,
            'id' => $userId,
        ]);
    }

    public function disableTwoFactor(int $userId): void
    {
        $statement = $this->connection->prepare('UPDATE users SET two_factor_secret = NULL, updated_at = NOW() WHERE id = :id');
        $statement->execute(['id' => $userId]);
    }

    public function provisioningUri(string $email, string $secret): string
    {
        return $this->twoFactor->getProvisioningUri($email, $secret, (string) Env::get('APP_NAME', 'Epinx'));
    }

    public function twoFactorQrCode(string $uri): string
    {
        return $this->twoFactor->getQrCodeUrl($uri);
    }

    public function verifyTwoFactor(string $secret, string $code): bool
    {
        return $this->twoFactor->verify($secret, $code);
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    private function rateKey(string $email, string $ip): string
    {
        return hash('sha256', strtolower($email) . '|' . $ip);
    }

    private function storeSession(int $userId, string $ip, string $userAgent): void
    {
        $token = session_id() ?: bin2hex(random_bytes(20));
        $statement = $this->connection->prepare('INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, last_used_at) VALUES (:user_id, :token, :ip, :agent, NOW()) ON DUPLICATE KEY UPDATE ip_address = VALUES(ip_address), user_agent = VALUES(user_agent), last_used_at = NOW()');
        $statement->execute([
            'user_id' => $userId,
            'token' => $token,
            'ip' => $ip,
            'agent' => substr($userAgent, 0, 255),
        ]);
    }
}

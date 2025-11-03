<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

class AuditService
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function log(?int $userId, string $action, array $metadata = [], ?string $ip = null): void
    {
        $statement = $this->connection->prepare('INSERT INTO audit_logs (user_id, action, ip_address, metadata, created_at) VALUES (:user_id, :action, :ip, :metadata, NOW())');
        $statement->execute([
            'user_id' => $userId,
            'action' => $action,
            'ip' => $ip,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
        ]);
    }
}

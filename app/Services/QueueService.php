<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

class QueueService
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function enqueue(string $type, array $payload, int $delaySeconds = 0): void
    {
        $statement = $this->connection->prepare('INSERT INTO job_queue (type, payload, available_at) VALUES (:type, :payload, DATE_ADD(NOW(), INTERVAL :delay SECOND))');
        $statement->execute([
            'type' => $type,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'delay' => $delaySeconds,
        ]);
    }

    public function reserve(): ?array
    {
        $statement = $this->connection->query('SELECT * FROM job_queue WHERE available_at <= NOW() ORDER BY id ASC LIMIT 1');
        $job = $statement ? $statement->fetch(PDO::FETCH_ASSOC) : false;

        if (!$job) {
            return null;
        }

        return $job;
    }

    public function delete(int $jobId): void
    {
        $statement = $this->connection->prepare('DELETE FROM job_queue WHERE id = :id');
        $statement->execute(['id' => $jobId]);
    }

    public function markAttempted(int $jobId, ?string $error = null): void
    {
        $statement = $this->connection->prepare('UPDATE job_queue SET attempts = attempts + 1, last_error = :error, available_at = CASE WHEN :error IS NULL THEN DATE_ADD(NOW(), INTERVAL 1 MINUTE) ELSE DATE_ADD(NOW(), INTERVAL POW(2, LEAST(attempts + 1, 10)) MINUTE) END WHERE id = :id');
        $statement->execute([
            'error' => $error,
            'id' => $jobId,
        ]);
    }
}

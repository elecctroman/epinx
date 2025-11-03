<?php
declare(strict_types=1);

namespace App\Core\Database;

use PDO;
use RuntimeException;

class Migrator
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function run(): void
    {
        $sql = file_get_contents(__DIR__ . '/../../database/schema.sql');
        if ($sql === false) {
            throw new RuntimeException('Migration file not found.');
        }

        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $statement) {
            if ($statement !== '') {
                $this->connection->exec($statement);
            }
        }
    }
}

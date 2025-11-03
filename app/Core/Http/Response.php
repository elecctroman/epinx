<?php
declare(strict_types=1);

namespace App\Core\Http;

class Response
{
    public function __construct(
        private readonly string $content,
        private readonly int $status = 200,
        private readonly array $headers = []
    ) {
    }

    public static function json(array $data, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';

        return new self((string) json_encode($data, JSON_THROW_ON_ERROR), $status, $headers);
    }

    public static function html(string $content, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'text/html; charset=utf-8';

        return new self($content, $status, $headers);
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value), true);
        }

        echo $this->content;
    }
}

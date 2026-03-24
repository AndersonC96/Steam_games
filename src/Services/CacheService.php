<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Services;

final class CacheService
{
    public function __construct(private readonly string $cacheDirectory)
    {
        if (!is_dir($this->cacheDirectory)) {
            mkdir($this->cacheDirectory, 0775, true);
        }
    }

    public function read(string $namespace, string $key, int $ttlSeconds): mixed
    {
        $filePath = $this->filePath($namespace, $key);
        if (!is_file($filePath)) {
            return null;
        }

        $raw = file_get_contents($filePath);
        if ($raw === false) {
            return null;
        }

        $cached = json_decode($raw, true);
        if (!is_array($cached) || !isset($cached['saved_at']) || !array_key_exists('payload', $cached)) {
            return null;
        }

        if ((time() - (int) $cached['saved_at']) > $ttlSeconds) {
            return null;
        }

        return $cached['payload'];
    }

    public function write(string $namespace, string $key, mixed $payload): void
    {
        $filePath = $this->filePath($namespace, $key);
        $data = [
            'saved_at' => time(),
            'payload' => $payload,
        ];

        file_put_contents($filePath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function filePath(string $namespace, string $key): string
    {
        $safeNamespace = preg_replace('/[^a-z0-9_\-]/i', '_', $namespace);
        $hash = sha1($key);

        return $this->cacheDirectory . '/' . $safeNamespace . '_' . $hash . '.json';
    }
}
